<?php
namespace core\service\repay;

use libs\utils\Logger;
use core\service\BaseService;
use core\enum\DealLoanRepayEnum;
use core\service\deal\P2pIdempotentService;
use core\enum\DealRepayEnum;
use core\dao\repay\DealLoanRepayModel;

class DealPartRepayService extends BaseService
{
    const REPAY_TYPE_NORMAL = 0;
    const REPAY_TYPE_PART = 1;
    const REPAY_TYPE_NORMAL_PART = 2; // 有部分还款的最后一次正常还款

    const CACHE_PART_REPAY_LIST = 'pcfph_repay_service_part_repay_list_%s';

    public static function getPartRepayInfo($realMoney, $interest, $principal, $repayList, $orderId)
    {
        $redisKey = sprintf(self::CACHE_PART_REPAY_LIST, $orderId);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $result = json_decode($redis->get($redisKey), true);
        if (!empty($result)) {
            return $result;
        }

        $interestRatio = $interest ? 1 : 0;
        if ($interest > 0 && bccomp($realMoney, $interest, 2) != 1) {
            $interestRatio = bcdiv($realMoney, $interest, 5);
        }

        $realMoney = bcsub($realMoney, $interest, 2);
        $principalRatio = ($principal && $realMoney > 0) ? 1 : 0;
        if ($principal > 0 && $realMoney > 0 && bccomp($realMoney, $principal, 2) != 1) {
            $principalRatio = bcdiv($realMoney, $principal, 5);
        }

        $list = [];
        foreach ($repayList as $item) {
            $money = "0";
            if ($item['type'] == DealLoanRepayEnum::MONEY_PRINCIPAL) {
                if ($principal > 0) {
                    $money = bcmul($item['money'], $principalRatio, 2);
                }
            } elseif ($item['type'] == DealLoanRepayEnum::MONEY_INTREST) {
                if ($interest > 0) {
                    $money = bcmul($item['money'], $interestRatio, 2);
                }
            }

            if ($money > 0) {
                $list[$item['id']] = $money;
            }
        }

        $redis->setex($redisKey, 86400, json_encode($list));

        Logger::info(implode(' | ', [__METHOD__, $orderId, json_encode($list)]));

        return $list;
    }

    /**
     * 获取部分还款相关金额
     */
    public static function getPartRepayMoney($dealRepay, $partRepayMoney)
    {
        if (is_object($dealRepay)) $dealRepay = $dealRepay->_row;
        $totalFee = $dealRepay['loan_fee'] + $dealRepay['guarantee_fee'] + $dealRepay['consult_fee'] + $dealRepay['pay_fee'] + $dealRepay['canal_fee'];
        $needToRepayTotal = bcsub($dealRepay['repay_money'], $dealRepay['part_repay_money'], 2);
        $needToRepayInterest = $needToRepayPrincipal = 0;
        if ($dealRepay['status'] == DealRepayEnum::STATUS_WAITING) {
            if ($dealRepay['part_repay_money'] > 0) {
                $repayMoneyWithoutFee = bcsub($dealRepay['part_repay_money'], $totalFee, 2);
                if (bccomp($repayMoneyWithoutFee, $dealRepay['interest'], 2) < 0) {// 利息没还完
                    $needToRepayInterest = bcsub($dealRepay['interest'], $repayMoneyWithoutFee, 2);
                    $needToRepayPrincipal = $dealRepay['principal'];
                } else {
                    $repayMoneyWithoutFeeAndInterest = bcsub($repayMoneyWithoutFee, $dealRepay['interest'], 2);
                    $needToRepayPrincipal = bcsub($dealRepay['principal'], $repayMoneyWithoutFeeAndInterest, 2);
                }
            } else {
                $needToRepayInterest = $dealRepay['interest'];
                $needToRepayPrincipal = $dealRepay['principal'];
            }
        }

        $realRepayMoneyWithoutFee = 0;
        if ($partRepayMoney) {
            $realRepayMoneyWithoutFee = $dealRepay['part_repay_money'] > 0 ? $partRepayMoney : bcsub($partRepayMoney, $totalFee, 2);
        } else {
            $realRepayMoneyWithoutFee = bcsub($dealRepay['repay_money'], $dealRepay['part_repay_money'], 2);
        }

        $res = [
            'totalFee' => $totalFee, // 总费用
            'needToRepayTotal' => $needToRepayTotal, // 待还
            'needToRepayInterest' => $needToRepayInterest, // 待还利息
            'repayInterest' => bcsub($dealRepay['interest'], $needToRepayInterest, 2), // 已还利息
            'needToRepayPrincipal' => $needToRepayPrincipal, // 待还本金
            'repayPrincipal' => bcsub($dealRepay['principal'], $needToRepayPrincipal, 2), // 已还本金
            'repayMoneyWithoutFee' => $realRepayMoneyWithoutFee, // 排除费用的还款金额
            'isFeeRepayed' => $dealRepay['part_repay_money'] > 0 ? true : false,
        ];
        Logger::info(implode('|', [__METHOD__, json_encode($dealRepay), json_encode($res)]));
        return $res;
    }

    /**
     * 根据订单号获取部分还款金额
     */
    public static function getPartRepayMoneyByOrderId($orderId)
    {
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo) {
            throw new \Exception("order_id不存在");
        }
        $params = json_decode($orderInfo['params'], true);
        Logger::info(implode('|', [__METHOD__, $orderId, json_encode($params)]));
        return [
            'partRepayType' => isset($params['partRepayType']) ? $params['partRepayType'] : self::REPAY_TYPE_NORMAL,
            'totalRepayMoney' => isset($params['totalRepayMoney']) ? $params['totalRepayMoney'] : false,
            'partRepayMoneyOrg' => isset($params['partRepayMoneyOrg']) ? $params['partRepayMoneyOrg'] : 0,
            'isFeeRepayed' => isset($params['isFeeRepayed']) ? $params['isFeeRepayed'] : false,
        ];
    }

    /**
     * 拆分dealLoan记录
     */
    public static function partRepayDealLoanOne($dealLoanRepay, $partMoney)
    {
        $newMoney = bcsub($dealLoanRepay->money, $partMoney, 2);
        if (bccomp($newMoney, 0, 2) <= 0) return true;
        $obj = new DealLoanRepayModel;
        $obj->deal_id = $dealLoanRepay->deal_id;
        $obj->deal_repay_id = $dealLoanRepay->deal_repay_id;
        $obj->deal_loan_id = $dealLoanRepay->deal_loan_id;
        $obj->loan_user_id = $dealLoanRepay->loan_user_id;
        $obj->borrow_user_id = $dealLoanRepay->borrow_user_id;
        $obj->money = $newMoney;
        $obj->type = $dealLoanRepay->type;
        $obj->time = $dealLoanRepay->time;
        $obj->real_time = 0;
        $obj->status = $dealLoanRepay->status;
        $obj->deal_type = $dealLoanRepay->deal_type;
        $obj->create_time = $obj->update_time = time();
        Logger::info(implode('|', [__METHOD__, json_encode($dealLoanRepay->_row), json_encode($obj->_row), $partMoney]));
        if ($obj->save()) return true;
        else throw new \Exception("拆分dealLoan记录失败");

    }

}
