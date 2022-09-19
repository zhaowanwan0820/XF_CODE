<?php
/**
 * 预约投资券服务
 *
 * @date 2017-11-09
 * @author weiwei12@ucfgroup.com
 */

namespace core\service;

use libs\utils\Logger;
use core\dao\UserReservationModel;
use core\dao\JobsModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\service\ReservationConfService;
use core\service\O2OService;
use core\service\oto\O2ODiscountService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Task\Services\TaskService as GTaskService;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\event\O2OExchangeDiscountEvent;
use libs\sms\SmsServer;

class ReservationDiscountService extends BaseService
{
    /**
     * 异步冻结投资券
     */
    public function asyncFreezeDiscount($reserveId) {
        $jobs_model = new JobsModel();
        $function = '\core\service\ReservationDiscountService::freezeDiscount';
        $param = array(intval($reserveId));
        $jobs_model->priority = JobsModel::PRIORITY_RESERVE_DISCOUNT;
        $ret = $jobs_model->addJob($function, $param);
        if ($ret === false) {
            throw new \Exception('添加异步冻结投资券任务失败');
        }
    }

    /**
     * 冻结投资券
     */
    public function freezeDiscount($reserveId) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('冻结投资券'))));
        try {
            $userReservationModel = new UserReservationModel();
            $userReserveInfo = $userReservationModel->find($reserveId);

            if (empty($userReserveInfo)) {
                throw new \Exception('该预约记录不存在', -1);
            }

            if (empty($userReserveInfo['discount_id'])) {
                throw new \Exception('用户预约未使用投资券', -2);
            }
            $discountId = $userReserveInfo['discount_id'];

            //冻结券
            $o2oService = new O2OService();
            $discountInfo = $o2oService->getDiscount($discountId);
            if (empty($discountInfo)) {
                throw new \Exception('查询券信息失败,discountId:' . $discountId, -3);
            }
            $result = $o2oService->freezeDiscount($userReserveInfo['user_id'], $discountId, $userReserveInfo['create_time'], $reserveId, CouponGroupEnum::CONSUME_TYPE_RESERVE, $discountInfo['type']);
            if (empty($result)) {
                throw new \Exception('冻结投资券失败,discountId:' . $discountId, -3);
            }

            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('冻结投资券成功,discountId:%s', $discountId))));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('ExceptionCode:%d, ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            return false;
        }

    }

    /**
     * 异步兑换投资券
     */
    public function asyncExchangeDiscount($reserveId) {
        $jobs_model = new JobsModel();
        $function = '\core\service\ReservationDiscountService::exchangeDiscount';
        $param = array(intval($reserveId));
        $jobs_model->priority = JobsModel::PRIORITY_RESERVE_DISCOUNT;
        $ret = $jobs_model->addJob($function, $param);
        if ($ret === false) {
            throw new \Exception('添加异步兑换投资券任务失败');
        }
    }

    /**
     * 兑换投资券
     */
    public function exchangeDiscount($reserveId) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('兑换投资券'))));
        try {
            $userReservationModel = new UserReservationModel();
            $userReserveInfo = $userReservationModel->find($reserveId);

            if (empty($userReserveInfo) || $userReserveInfo['reserve_status'] != UserReservationModel::RESERVE_STATUS_END) {
                throw new \Exception('该预约记录不存在或预约尚未结束', -1);
            }

            if (empty($userReserveInfo['discount_id'])) {
                throw new \Exception('用户预约未使用投资券', -2);
            }
            $discountId = $userReserveInfo['discount_id'];

            //查询券
            $o2oService = new O2OService();
            $discountInfo = $o2oService->getDiscount($discountId);
            if (empty($discountInfo)) {
                throw new \Exception('查询券信息失败，discountId:'.$discountId, -3);
            }

            //检查投资券是否可用
            $o2oDiscountService = new O2ODiscountService();
            $reservationConfService = new ReservationConfService();
            $errorInfo = [];
            $deadlineDays = $reservationConfService->convertToDays($userReserveInfo['invest_deadline'], $userReserveInfo['invest_deadline_unit']);
            $investAmount = bcdiv($userReserveInfo['invest_amount'], 100, 2);
            $extraParam = ['dealId' => '', 'money' => $investAmount, 'bidDayLimit' => $deadlineDays];
            $isCanUseDiscount = $o2oDiscountService->canUseDiscount($userReserveInfo['user_id'], $discountId, $discountInfo['discountGroupId'], $errorInfo, CouponGroupEnum::CONSUME_TYPE_RESERVE, $extraParam);

            //不符合使用规则，需要解冻
            if (!$isCanUseDiscount) {
                $result = $o2oService->unfreezeDiscount($userReserveInfo['user_id'], $discountId, $userReserveInfo['create_time'], $reserveId, CouponGroupEnum::CONSUME_TYPE_RESERVE, $discountInfo['type']);
                if (!$result) {
                    throw new \Exception('不符合投资券使用规则，解冻投资券失败，discountId:' . $discountId, -4);
                }

                //更新预约投资券使用状态，未使用
                $userReservationModel->updateDiscountStatus($reserveId, UserReservationModel::DISCOUNT_STATUS_FAILED);

                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('不符合投资券使用规则，解冻投资券成功,discountId:%s', $discountId))));
                return true;
            }

            //添加兑换券任务
            //计算年化投资金额，加息券使用，计算公式: 年化投资金额 = 投资金额 * (预约天数 / 360天) * 折算系数
            $annualizedAmount = bcmul($investAmount * $deadlineDays / 360, $userReserveInfo['rate_factor'], 2);
            $obj = new GTaskService();
            $event = new O2OExchangeDiscountEvent($userReserveInfo['user_id'], $discountId, $reserveId, '随心约', '', 0, 0, CouponGroupEnum::CONSUME_TYPE_RESERVE, $annualizedAmount);
            $taskId = $obj->doBackground($event, 10, TASK::PRIORITY_NORMAL);

            //更新预约投资券使用状态，已使用
            $userReservationModel->updateDiscountStatus($reserveId, UserReservationModel::DISCOUNT_STATUS_SUCCESS);

            //发送兑换成功短信
            $tpl = 'TPL_SMS_RESERVE_DISCOUNT_EXCHANGE_SUCCESS';
            $user = UserModel::instance()->find($userReserveInfo['user_id']);
            if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
            {
                $mobile = 'enterprise';
                $accountTitle = get_company_shortname($user['id']); // by fanjingwen
            } else {
                $mobile = $user['mobile'];
                $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
            }
            $investName = $userReserveInfo['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '出借' : '投资';
            $smsContent = array(
                'account_title' => $accountTitle,
                'now_time' => date('Y-m-d'),
                'invest_name1' => $investName,
                'invest_amount' => $investAmount,
                'invest_count' => $userReserveInfo['invest_count'],
                'invest_name2' => $investName,
                'platform_name' => $userReserveInfo['site_id'] == 100 ? '网信普惠' : '网信',
            );
            SmsServer::instance()->send($mobile, $tpl, $smsContent, $user['id'], $userReserveInfo['site_id']);

            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('兑换投资券成功,discountId:%s', $discountId))));
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $reserveId, sprintf('ExceptionCode:%d, ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            return false;
        }
    }

}
