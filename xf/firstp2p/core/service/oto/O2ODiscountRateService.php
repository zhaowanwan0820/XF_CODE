<?php

namespace core\service\oto;

use core\service\oto\O2ORpcService;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\DiscountRateModel;
use core\service\DiscountService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Finance;
use core\service\TransferService;
use core\dao\OtoBonusAccountModel;
use core\dao\BonusModel;
\FP::import("app.deal");

/**
 * O2O加息服务
 * Class O2ODiscountRateService
 * @package core\service\oto
 */
class O2ODiscountRateService extends O2ORpcService {
    /**
     * @param $token 唯一token，加息券随息返利幂等用
     * @param $dealLoanId 投资记录Id
     * @param $prepayInfo 提前还款信息
     * @return bool 使用成功返回ture 使用失败返回false
     */
    public function useDiscountRate($token, $dealLoanId, $prepayInfo = null) {
        PaymentApi::log("useDiscountRate: ".json_encode(
            array('token' => $token, 'dealLoanId'=>$dealLoanId, 'prepayInfo'=>$prepayInfo),
            JSON_UNESCAPED_UNICODE), Logger::INFO);

        $condition = "token = '{$token}'";
        $logInfo = DiscountRateModel::instance()->findBy($condition, 'id, allowance_id');
        // 已经返利过了
        if ($logInfo && $logInfo['allowance_id']) {
            PaymentApi::log("已经返利了", Logger::INFO);
            return true;
        }

        // 获取该交易的投资券信息
        $discountService = new DiscountService();
        $discount = $discountService->getConsumeDiscount($dealLoanId);
        // 数据的双重校验
        if ($discount === false) {
            PaymentApi::log("获取交易{$dealLoanId}所使用的投资券信息失败", Logger::ERR);
            return false;
        }

        // 非加息券，或随息发放
        if ($discount['type'] != CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES
            || $discount['goodsGiveType'] != CouponGroupEnum::DISCOUNT_GIVE_WITH_INTEREST) {
            PaymentApi::log("获取交易{$dealLoanId}所使用的投资券{$discount['id']},非加息券或随息发放", Logger::WARN);
            return true;
        }

        try {
            $goodPrice = $this->calcDiscountMoney($discount, $dealLoanId, $prepayInfo);

            // 记入相关日志备查
            PaymentApi::log('useDiscountRate'. ", 投资券{$discount['id']}, payout:" . $discount['wxUserId']
                . ' payin:'. $discount['ownerUserId']
                . ', type：' . CouponGroupEnum::$ALLOWANCE_TYPE[$discount['goodsType']]
                . ', money:' . $goodPrice . ', limit: '.$discount['goodsLimit'], Logger::INFO);

            $allowanceId = 0;
            // 添加返利记录
            if (empty($logInfo)) {
                $data = array();
                $data['user_id'] = $discount['ownerUserId'];
                $data['discount_id'] = $discount['id'];
                $data['discount_type'] = CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES;
                $data['consume_id'] = $dealLoanId;
                $data['allowance_type'] = $discount['goodsType'];
                $data['allowance_money'] = $goodPrice;
                $data['allowance_id'] = $allowanceId;
                $data['token'] = $token;
                $data['create_time'] = date('Y-m-d H:i:s');
                $logId = DiscountRateModel::instance()->addRecord($data);
            } else {
                $logId = $logInfo['id'];
            }
            if (!$logId) {
                throw new \Exception('添加返利记录失败');
            }
            // 转账金额，必须大于0
            if ($goodPrice > 0) {
                // 转账逻辑
                if ($discount['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                    $allowanceService = new \core\service\oto\O2OAllowanceService();
                    $allowanceId = $allowanceService->rebateBonus($discount['ownerUserId'], $discount['wxUserId'], $goodPrice,
                        $discount['goodsLimit'], BonusModel::BONUS_DISCOUNT_RAISE_RATE,
                        OtoBonusAccountModel::MODE_DISCOUNT_RAISE_RATE, $logId, $discount['discountGroupId']);
                } else {
                    // 属于配置错误，这里按正常的逻辑进行处理，当成垃圾数据
                    PaymentApi::log('useDiscountRate非法的返利类型'.$discount['goodsType'].', discount:'
                        .json_encode($discount, JSON_UNESCAPED_UNICODE), Logger::ERR);
                }
            } else {
                PaymentApi::log("useDiscountRate goodsPrice为零 ", Logger::WARN);
            }
            if ($allowanceId) {
                DiscountRateModel::instance()->updateRecord(array('id' => $logId, 'allowance_id' => $allowanceId));
            }

            return true;
        } catch (\Exception $e) {
            PaymentApi::log("useDiscountRate failed, : ".$e->getMessage(), Logger::ERR);
            return false;
        }
    }

    /**
     * 是否需要加息
     * @param $dealLoanId 用户投资记录Id
     * @return bool
     */
    public function isNeedDiscountRate($dealLoanId) {
        $discountService = new DiscountService();
        return $discountService->isUseDiscountRate($dealLoanId);
    }

    /**
     * 计算加息券加息金额
     * @param $dealLoanId 投资记录Id
     * @param $prepayInfo 提前还款信息
     * @return int 加息金额
     */
    private function calcDiscountMoney(array $discount, $dealLoanId, $prepayInfo = null) {
        // 投资记录信息
        $dealLoan = DealLoadModel::instance()->findViaSlave($dealLoanId);
        $dealModel = DealModel::instance()->findViaSlave($dealLoan->deal_id);
        // 总还款期数
        $repayTimes = $dealModel->getRepayTimes();
        $finance = new Finance();
        // 计算年化额
        $moneyYear = $finance->getMoneyYearPeriod($dealLoan->money, $dealModel->loantype, $dealModel->repay_time);
        $rebateRate = $dealModel->getRebateRate($dealModel->loantype);
        $goodPrice = bcmul($moneyYear, $discount['goodsPrice'] * 0.01 * $rebateRate, 5);
        if ($discount['goodsMaxPrice'] > 0 && bccomp($goodPrice, $discount['goodsMaxPrice'], 5) == 1) {
            $goodPrice = $discount['goodsMaxPrice'];
        }

        // 计算每期加钱
        $interest = $dealModel->floorfix(bcdiv($goodPrice, $repayTimes, 5));
        PaymentApi::log("calcDiscountMoney, goodPrice: {$goodPrice}, repayTimes: {$repayTimes}, $interest: {$interest}", Logger::INFO);
        if (!empty($prepayInfo)) {// 提前还款
            // 提前还款利息
            $preInterest = prepay_money_intrest($prepayInfo['principal'], $prepayInfo['remain_days'], $discount['goodsPrice']);
            $preInterest = $dealModel->floorfix($preInterest);
            $params = array(
                'pricipal'=>$prepayInfo['principal'],
                'remainDays'=>$prepayInfo['remain_days'],
                'goodsPrice'=>$discount['goodsPrice'],
                'preInterest'=>$preInterest,
                'interest'=>$interest
            );

            PaymentApi::log("calcDiscountMoney: ".json_encode($params), Logger::INFO);
            if (bccomp($interest, $preInterest, 5) == 1) {
                $interest = $preInterest;
            }
        }
        return $interest;
    }
}
