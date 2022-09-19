<?php
/**
 * 充值聚合页
 * @author weiwei12<weiwei12@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\PaymentService;
use core\service\UserService;
use libs\utils\PaymentApi;
use libs\web\Url;
class Charge extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS['user_info'];
        $user_id = $GLOBALS['user_info']['id'];
        $userService = new UserService($user_id);
        // 所有用户均不验证银行卡是否已验证状态
        $opts = ['check_validate' => false];
        // 用户总资产检查，资产总额大于零的未验卡用户，可以继续使用充值功能，总资产为零的用户需要进行验卡操作
        $userSummary = $this->rpc->local('AccountService\getUserSummary', array($user_id));
        $siteId = \libs\utils\Site::getId();
        $total = bcadd(bcadd($GLOBALS['user_info']['money'], $GLOBALS['user_info']['lock_money'], 2), $userSummary['corpus'], 2);
        // 资产不为零或者不是分站，不验卡
        if (bccomp($total, '0.00', 2) <= 0 && ($siteId == 1 || \libs\web\Open::checkOpenSwitch())) //主站或者与主站一致的分站 在资产为零的情况下验卡 wangge 20160414
        {
            $opts['check_validate'] = true;
        }
        $userCheck = $userService->isBindBankCard($opts);
        // 检查用户是否验卡成功
        if ($userCheck['ret'] !== true)
        {
            // 企业用户给提示
            if ($userService->isEnterprise() && ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND || $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID))
            {
                return app_redirect(Url::gene('deal','promptCompany'));
            }

            //$hasPassport = \libs\db\Db::getInstance('firstp2p')->getOne("SELECT COUNT(*) FROM firstp2p_user_passport WHERE uid = '{$GLOBALS['user_info']['id']}'");
            $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_id));
            // 白名单中的分站 大陆用户和已绑卡未验证的港澳台用户跳转到先锋支付绑卡/验卡
            if (($siteId == 1 || \libs\web\Open::checkOpenSwitch()) && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
            {
                return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }
            return $this->show_error($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
        }

        if(intval($GLOBALS['user_info']['idcardpassed'])==3){
            showErr('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', 0,'/account' , 0 );
            return;
        }

        //是否开户
        //$user_info['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user_info['id']));

        //资产中心余额
        //$balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($user_info['id']));
        //$user_info['svFreezeMoney'] = $balanceResult['supervisionLockMoney'];
        //$user_info['svTotalMoney'] = $balanceResult['supervisionMoney'];
        //$user_info['svCashMoney'] = $balanceResult['supervisionBalance'];

        $accountInfo = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user_info['id'], $user_info['user_purpose']);
        $user_info['isSvUser'] = $accountInfo['isSupervisionUser'];
        $user_info['svCashMoney'] = $accountInfo['money'];
        $user_info['svFreezeMoney'] = $accountInfo['lockMoney'];
        $user_info['svTotalMoney'] = $accountInfo['totalMoney'];

        $this->tpl->assign("user_info",$user_info);
        $this->template = "web/views/account/charge_multi.html";//聚合页模版
        return true;
    }
}
