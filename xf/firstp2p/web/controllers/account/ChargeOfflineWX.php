<?php
/**
 * 网信PC大额充值入口
 */
namespace web\controllers\account;

use NCFGroup\Common\Library\Idworker;
use web\controllers\BaseAction;
use core\service\PaymentService;
use core\service\UserService;
use libs\web\Url;

class ChargeOfflineWX extends BaseAction {
    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS['user_info'];
        $user_id = (int)$user_info['id'];

        // 网信PC大额充值的开关、用户绑定的银行是否在大额充值银行的白名单里
        $wxOfflineChargeSwitch = PaymentService::isOfflineBankList($user_id, 1);
        if (!$wxOfflineChargeSwitch) {
            return $this->show_error('您绑定的银行卡暂不支持大额充值', '操作失败', 0, 0, '/account', 3);
        }

        // 用户是否在大额充值的用户黑名单里
        $wxOfflineUserBlack = PaymentService::inBlackList($user_id);
        if ($wxOfflineUserBlack) {
            return $this->show_error('系统维护中，暂不能充值', '操作失败', 0, 0, '/account', 3);
        }

        $userService = new UserService($user_id);
        // 所有用户均不验证银行卡是否已验证状态
        $opts = ['check_validate' => false];
        // 用户总资产检查，资产总额大于零的未验卡用户，可以继续使用充值功能，总资产为零的用户需要进行验卡操作
        $userSummary = $this->rpc->local('AccountService\getUserSummary', array($user_id));
        $siteId = \libs\utils\Site::getId();
        $total = bcadd(bcadd($GLOBALS['user_info']['money'], $GLOBALS['user_info']['lock_money'], 2), $userSummary['corpus'], 2);
        // 资产不为零或者不是分站，不验卡
        if (bccomp($total, '0.00', 2) <= 0 && ($siteId == 1 || \libs\web\Open::checkOpenSwitch())) // 主站或者与主站一致的分站 在资产为零的情况下验卡 wangge 20160414
        {
            $opts['check_validate'] = true;
        }
        $userCheck = $userService->isBindBankCard($opts);
        // 检查用户是否验卡成功
        if ($userCheck['ret'] !== true) {
            // 企业用户给提示
            if ($userService->isEnterprise() && ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND || $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID))
            {
                return app_redirect(Url::gene('deal', 'promptCompany'));
            }

            $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_id));
            // 白名单中的分站 大陆用户和已绑卡未验证的港澳台用户跳转到先锋支付绑卡/验卡
            if (($siteId == 1 || \libs\web\Open::checkOpenSwitch()) && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
            {
                return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }
            return $this->show_error($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
        }

        if (intval($GLOBALS['user_info']['idcardpassed']) == 3) {
            showErr('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', 0, '/account', 0);
            return;
        }

        // 非企业用户增加支付平台开户check
        if (empty($GLOBALS['user_info']['payment_user_id'])) {
            return $this->show_error('无法充值', "", 0, 0, url("shop",'account'), 3);
        }
        $this->tpl->assign("user_info", $user_info);

        // 用户绑卡信息
        $bankcardInfo = $this->rpc->local('UserBankcardService\getUserBankInfo', array($user_id));
        $this->tpl->assign('bankcardInfo', $bankcardInfo);

        // 跳转支付时需要的参数
        $orderId = Idworker::instance()->getId();
        $this->tpl->assign('orderId', $orderId);
        $this->tpl->assign('reqSource', 'PC');
        $this->tpl->assign('returnUrl', get_domain());
        $this->template = "web/views/account/charge_offline_wx.html";
    }
}