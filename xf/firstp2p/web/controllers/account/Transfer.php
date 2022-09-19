<?php

/**
 * Transfer.php
 * 网信、企业PC提供余额划转功能
 *
 * @date 2018-01-03
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\SupervisionAccountService;
use core\service\UserService;
use libs\web\Url;
use NCFGroup\Common\Library\Idworker;

class Transfer extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        try {
            $user_info = $GLOBALS['user_info'];
            $userId = $GLOBALS['user_info']['id'];
            $disableTransfer = app_conf('SV_UNTRANSFERABLE');
            if (empty($this->isSvOpen) || $disableTransfer) {
                return $this->show_error('非法操作', '非法操作', 0, 0, '/account', 3);
            }
            // 用户绑卡开户状态验证
            $userServ = new UserService($userId);
            $userCheck = $userServ->isBindBankCard();
            if ($userCheck['ret'] !== true)
            {
                // 企业用户给提示
                if($userServ->isEnterprise() && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND)
                {
                    return app_redirect(Url::gene('deal','promptCompany'));
                }
                $siteId = \libs\utils\Site::getId();
                $hasPassport = $this->rpc->local('AccountService\hasPassport', array($userId));
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
            $user_info['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user_info['id']));

            //资产中心余额
            $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($user_info['id']));
            $user_info['svFreezeMoney'] = $balanceResult['supervisionLockMoney'];
            $user_info['svTotalMoney'] = $balanceResult['supervisionMoney'];
            $user_info['svCashMoney'] = $balanceResult['supervisionBalance'];

            if (empty($user_info['isSvUser'])) {
                return $this->show_error('请先开通网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain() . '/account'), 3);
            }

            $orderId = Idworker::instance()->getId();
            $this->tpl->assign("orderId", $orderId);
            $this->tpl->assign("user_info",$user_info);
            $this->tpl->assign("doneBankOperate_url", sprintf('/payment/payCheck?id=%s&type=1', $orderId));

        } catch (\Exception $e) {
            return $this->show_error($e->getMessage(), '操作失败', 0, 0, '/', 3);
        }
        $this->template = "web/views/account/transfer.html";
    }

}
