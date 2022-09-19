<?php

namespace core\service;

use libs\utils\Logger;
use core\dao\OtoAllowanceLogModel;
use core\service\O2OService;
use core\service\oto\O2ODiscountService;
use core\service\oto\O2ORpcService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\CouponEnum;

/**
 * 新年感恩投资券大派送
 */
class HappyNewYearService extends O2ORpcService {
    /**
     * 获取感恩活动大礼包
     */
    private function getNewYearDiscountGroupId($registerDays) {
        $regMonths = ceil($registerDays / 30);
        // 投资券奖励
        $discountGroupId = '';
        if ($regMonths <= 10) {
            $discountGroupId = app_conf('HAPPY_NEW_YEAR_2016_DISCOUNT_0_10');
        } else if ($regMonths > 10 && $regMonths <= 20) {
            $discountGroupId = app_conf('HAPPY_NEW_YEAR_2016_DISCOUNT_10_20');
        } else if ($regMonths > 20 && $regMonths <= 30) {
            $discountGroupId = app_conf('HAPPY_NEW_YEAR_2016_DISCOUNT_20_30');
        } else if ($regMonths > 30) {
            $discountGroupId = app_conf('HAPPY_NEW_YEAR_2016_DISCOUNT_30');
        }

        if (empty($discountGroupId)) {
            throw new \Exception('投资券配置错误');
        }
        return $discountGroupId;
    }
    private function getNewYearCouponGroupId($totalInvestMoney) {
        $couponGroupId = '';
        // 红包奖励，累计投资额大于10万，10元投资红包
        if ($totalInvestMoney > 100000) {
            $couponGroupId = app_conf('HAPPY_NEW_YEAR_2016_COUPON_100000');
            if (empty($couponGroupId)) {
                throw new \Exception('礼券配置错误');
            }
        }
        return $couponGroupId;
    }
    /**
     * @param $userId int 用户id
     * @param $registerDays int 用户已注册天数
     * @param $totalInvestMoney float 累计总投资额
     * @return array | false
     */
    public function getNewYearPackage($userId, $registerDays, $totalInvestMoney) {
        $res = array();
        try {
            if (empty($userId)) {
                throw new \Exception('用户不能为空');
            }

            $token = 'happy_new_year_2016_'.$userId;
            $log = OtoAllowanceLogModel::instance()->findBy("token='{$token}'");
            if ($log) {
                // 已经领取过了，获取当时的注册天数和累计投资额
                $registerDays = $log['allowance_coupon'];
                $totalInvestMoney = $log['allowance_money'];
            }

            $discountGroupId = $this->getNewYearDiscountGroupId($registerDays);
            $o2oDiscountService = new O2ODiscountService();
            $discountGroup = $o2oDiscountService->getDiscountGroup($discountGroupId);
            if ($discountGroup === false) {
                throw new \Exception($o2oDiscountService->getErrorMsg(), $o2oDiscountService->getErrorCode());
            }
            // 价格转化成整数
            $discountGroup['useDayLimit'] = intval($discountGroup['useDayLimit'] / 86400);
            $res['discount'] = $discountGroup;

            $couponGroupId =  $this->getNewYearCouponGroupId($totalInvestMoney);
            if (empty($couponGroupId)) {
                $res['coupon'] = array();
            } else {
                $o2oService = new O2OService();
                $couponGroup = $o2oService->getCouponGroupInfoById($couponGroupId);
                if ($couponGroup === false) {
                    throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
                }
                $couponGroup['useDayLimit'] = intval($couponGroup['useDayLimit'] / 86400);
                $res['coupon'] = $couponGroup;
            }

            // 是否已经领取
            $res['isAcquired'] = $log ? 1 : 0;
            // 是否已经领完或活动已结束
            // 检查活动是否结束
            $endTime = app_conf('HAPPY_NEW_YEAR_2016_END_TIME');
            if (empty($endTime)) {
                throw new \Exception('活动结束时间没有配置');
            }
            $endTimeStamp = strtotime($endTime);
            if (empty($endTimeStamp)) {
                throw new \Exception('活动结束时间配置错误');
            }

            $res['isOver'] = time() > $endTimeStamp ? 1 : 0;
            return $res;
        } catch (\Exception $e) {
            $params = array('userId'=>$userId, 'registerDays'=>$registerDays, 'totalInvestMoney'=>$totalInvestMoney);
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 感恩投资券大派送活动，领取大礼包
     * @param $userId int 用户id
     * @param $registerDays int 用户已注册天数
     * @param $totalInvestMoney float 累计总投资额
     * @return array | false
     */
    public function acquireNewYearPackage($userId, $registerDays, $totalInvestMoney) {
        if (empty($userId)) {
            $this->setErrorMsg('用户id不能为空');
            return false;
        }

        $currentTime = time();
        // 检查活动是否结束
        $endTime = app_conf('HAPPY_NEW_YEAR_2016_END_TIME');
        if (empty($endTime)) {
            $this->setErrorMsg('活动结束时间没有配置');
            return false;
        }

        $endTimeStamp = strtotime($endTime);
        if (empty($endTimeStamp)) {
            $this->setErrorMsg('活动结束时间配置错误');
            return false;
        }

        if ($currentTime > $endTimeStamp) {
            $this->setErrorMsg('活动已结束');
            return false;
        }

        $token = 'happy_new_year_2016_'.$userId;
        // 幂等判断，先查询是否已经领取了
        $log = OtoAllowanceLogModel::instance()->findBy("token='{$token}'");
        if ($log) {
            // 已经领取了
            return true;
        }

        // 下面需要事务保证
        $GLOBALS['db']->startTrans();
        try {
            // 添加触发返利记录
            $data = array();
            $data['from_user_id'] = 0;
            $data['to_user_id'] = $userId;
            $data['acquire_log_id'] = 0;
            $data['gift_id'] = 0;
            $data['gift_group_id'] = 0;
            $data['deal_load_id'] = 0;
            $data['action_type'] = OtoAllowanceLogModel::ACTION_TYPE_HAPPY_NEW_YEAR;
            $data['create_time'] = $currentTime;
            $data['update_time'] = $currentTime;
            $data['allowance_type'] = CouponGroupEnum::ALLOWANCE_TYPE_NEY_YEAR_PACKAGE;
            $data['allowance_money'] = $totalInvestMoney;
            $data['allowance_coupon'] = $registerDays;
            $data['token'] = $token;
            $data['status'] = OtoAllowanceLogModel::STATUS_DONE;
            $allowanceLogId = OtoAllowanceLogModel::instance()->addRecord($data);
            if (!$allowanceLogId) {
                throw new \Exception('领取失败');
            }

            $o2oService = new O2OService();
            $discountGroupId = $this->getNewYearDiscountGroupId($registerDays);
            if (empty($discountGroupId)) {
                throw new \Exception('投资券配置错误');
            }

            // 领取投资券
            $resDiscount = $o2oService->acquireDiscounts($userId, $discountGroupId, $token);
            if ($resDiscount === false) {
                throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
            }

            $couponGroupId = $this->getNewYearCouponGroupId($totalInvestMoney);
            if (!empty($couponGroupId)) {
                // 领取礼券
                $resCoupon = $o2oService->acquireCoupons($userId, $couponGroupId, $token);
                if ($resCoupon === false) {
                    throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
                }
            }

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $params = array('userId'=>$userId, 'registerDays'=>$registerDays, 'totalInvestMoney'=>$totalInvestMoney);
            return $this->_handleException($e, __FUNCTION__, $params);
        }
    }

    /**
     * 获取用户的累计投资额
     * @param $userId int 用户id
     * @return float 累计投资额
     */
    public function getUserTotalInvestMoney($userId) {
        $data = array('load_count'=>0, 'load_money'=>0);
        if (empty($userId) || !is_numeric($userId)) {
            return $data;
        }

        //总借出笔数    edit by wangyiming 流标的投资不记录在总数内，d.deal_status!=3
        $sql = "SELECT COUNT(*) AS load_count,SUM(d_l.money) AS load_money FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status in (4,5) AND d.loantype != 7 AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = {$userId}";
        $u_load = $GLOBALS['db']->get_slave()->getRow($sql);
        $data['load_count'] = $u_load['load_count'];
        //总借出金额  总投资额
        $data['load_money'] = $u_load['load_money'];
        if (is_duotou_inner_user()) {
            $dts =  new \core\service\DtAssetService();
            $duotouAsset = $dts->getDtAsset($userId);
            // 总投资额 + 多投宝
            $data['load_money'] = bcadd($data['load_money'], $duotouAsset['totalLoanMoney'], 2);
        }
        return $data;
    }
}
