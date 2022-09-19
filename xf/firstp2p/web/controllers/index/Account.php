<?php
/**
 * Index.php
 *
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\index;

use web\controllers\BaseAction;
use libs\utils\Finance;


class Account extends BaseAction {
    public function invoke() {
        if (!$GLOBALS['user_info']) {
            $this->show_error('登录信息过期，请重新登录', '', 1);
            return false;
        }

        $user_info = $GLOBALS['user_info'];

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user_info['id']));
        $money_available = Finance::addition(array($user_info['money'], $bonus['money']), 2);
        //是否开户
        //$isSvUser = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user_info['id']));

        //资产中心余额
        //$balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($user_info['id']));

        $ncfph = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user_info['id'], 1);
        $isSvUser = $ncfph['isSupervisionUser'];
        $balanceResult = ['supervisionBalance' => $ncfph['money']];

        //加上存管金额
        $money_available = Finance::addition(array($money_available, $balanceResult['supervisionBalance']), 2);

        //$static = $this->rpc->local('AccountService\getUserStaicsInfo', array($user_info['id']));
        $totalRefererRebateAmount = $this->rpc->local('CouponLogService\getTotalRefererRebateAmount', array($user_info['id']));
        $pending = $this->rpc->local('AccountService\getUserPendingAmount', array($user_info['id']));
        // Fix warning by sunxuefeng at 2019-03-26 from wangshijie
        $pending_ncfph = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user_info['id'], 1);
        $pending = \core\service\ncfph\AccountService::mergeP2P($pending, $pending_ncfph);
        $bankcard = $this->rpc->local('UserBankcardService\getBankcard',array($user_info['id']));

        $data = array(
            'money' => number_format($money_available, 2),
            'bonus' => number_format($bonus['money'], 2),
            'principal' => number_format($pending['principal'], 2),
            'interest' => number_format($pending['interest'], 2),
            'coupon' => number_format($totalRefererRebateAmount['referer_rebate_amount_no'], 2),
            'isSvUser' => (int)$isSvUser,
            'svCashMoney' => number_format($balanceResult['supervisionBalance'], 2),
            'wxCashMoney' => number_format($user_info['money'], 2),
            'totalCashMoney' => number_format(Finance::addition(array($user_info['money'], $balanceResult['supervisionBalance'])), 2),
            'bankcardVerifyStatus' => intval($bankcard['verify_status']),
        );

        $result = array(
            "error" => "",
            "data" => $data,
        );

        echo json_encode($result);
        return true;
    }
}
