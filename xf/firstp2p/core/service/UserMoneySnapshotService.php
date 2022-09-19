<?php
/**
 * 用户余额快照服务
 * @date 2017-09-07
 * @author guofeng3 <guofeng3@ucfgroup.com>
 */
namespace core\service;

use libs\utils\Logger;
use core\dao\UserMoneySnapshotModel;
use core\dao\UserModel;
use core\service\UserThirdBalanceService;

class UserMoneySnapshotService extends BaseService {
    // 最大的提醒天数
    const MAX_REMIND_DAYS = 30;

    /**
     * 信仔-获取用户闲置资金
     * @param int $userId 用户ID
     * @param int $remindDay 用户设置的提醒天数，不能超过30天
     * @param int $remindMoney 用户设置的提醒金额,单位元
     * @return 闲置资金，单位元
     */
    public function getUserIdleMoney($userId, $remindDay, $remindMoney)
    {
        $remindDay = (int)$remindDay;
        // 超过指定天数，不处理
        if ($remindDay > self::MAX_REMIND_DAYS) {
            return 0;
        }

        // 查询用户闲置资金
        $result = UserMoneySnapshotModel::instance()->getUserIdleMoney($userId, $remindDay);
        if (empty($result)) {
            return 0;
        }

        // 提现金额，单位分
        $remindMoneyCent = bcmul($remindMoney, 100, 0);
        $isIdle = true;
        $sortArray = [];
        for ($i = 1; $i <= $remindDay; $i++) {
            $loopDate = date('Ymd', strtotime(sprintf('-%d day', $i)));
            if (!empty($result[$loopDate])) {
                // 计算用户总的可用余额(超级账户余额+资产中心余额)
                $userTotalIdleMoney = (int)$result[$loopDate]['money'] + (int)$result[$loopDate]['supervision_money'];
            }else{
                $userTotalIdleMoney = 0;
            }
            $sortArray[] = $userTotalIdleMoney;

            // 只要有一天不符合条件，则没有闲置资金
            if ($userTotalIdleMoney < $remindMoneyCent) {
                $isIdle = false;
                break;
            }
        }

        Logger::info(sprintf('UserMoneySnapshotService::getUserIdleMoney, XinChat-查询用户闲置资金, userId:%d, remindDay:%d, remindMoney:%s元, isIdle:%d', $userId, $remindDay, $remindMoney, (int)$isIdle));
        // 指定天数内，有闲置资金
        if ($isIdle) {
            sort($sortArray, SORT_NUMERIC);
            $idleMoney = array_shift($sortArray);
            return bcdiv($idleMoney, 100, 2);
        }
        return 0;
    }

    /**
     * 获取用户当前的总余额(网信余额+网贷余额)，单位元
     * @param int $userId 用户ID
     * @param boolean $isBonus 是否包含红包
     */
    public static function getUserMoneyToday($userId, $isBonus = false) {
        $result = ['money'=>'0.00', 'supervision_money'=>'0.00', 'total_money'=>'0.00'];
        if (empty($userId)) {
            return $result;
        }
        // 获取用户超级账户余额
        $userInfo = UserModel::instance()->find($userId, 'money', true);
        if (empty($userInfo)) {
            return $result;
        }
        $result['money'] = $userInfo['money'];

        // 获取用户网贷账户余额
        $thirdBalanceService = new UserThirdBalanceService();
        $supervisionUserInfo = $thirdBalanceService->getUserSupervisionMoney($userId, true);
        $result['supervision_money'] = !empty($supervisionUserInfo['supervisionBalance']) ? $supervisionUserInfo['supervisionBalance'] : '0.00';

        // 用户当前总余额(超级账户余额+网贷余额)
        $result['total_money'] = bcadd($result['money'], $result['supervision_money'], 2);

        // 包含红包
        if ($isBonus) {
            // 获取用户红包余额
            $bonusInfo = (new \core\service\BonusService())->getUsableBonus($userId);
            if (!empty($bonusInfo['money'])) {
                $result['total_money'] = bcadd($result['total_money'], $bonusInfo['money'], 2);
            }
        }
        return $result;
    }
}
