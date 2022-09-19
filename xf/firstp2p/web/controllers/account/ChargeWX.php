<?php
/**
 * 网信账户充值页
 * @author caolong<caolong@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\PaymentService;
use core\service\UserService;
use core\dao\ApiConfModel;
use libs\utils\PaymentApi;
use libs\web\Url;

class ChargeWX extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        if(app_conf('PAYMENT_ENABLE') == '0'){
            return $this->oldInvoke();
        }

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

        //非企业用户增加支付平台开户check
        if(empty($GLOBALS['user_info']['payment_user_id'])){
            //showErr('无法进行投保');
            return $this->show_error('无法充值', "", 0,0, url("shop",'account'), 3);
        }
        $paymentmethods = PaymentApi::getPaymentChannel();
        foreach ($paymentmethods as $paymentmethod => $paymentMethodDesc)
        {
            $paymentMethodOptionsHtml .= "<li data-name='{$paymentmethod}'>{$paymentMethodDesc}</li>";
        }
        $this->tpl->assign('paymentMethodOptionsHtml', $paymentMethodOptionsHtml);

        //是否开户
        $user_info['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user_info['id']));
        $this->tpl->assign("user_info",$user_info);

        $bankcardInfo = $this->rpc->local('UserBankcardService\getUserBankInfo', array($user_id));
        $this->tpl->assign('bankcardInfo',$bankcardInfo);

        //平台公告
        $noticeConf = $this->rpc->local("ApiConfService\getNoticeConf", array($siteId, ApiConfModel::NOTICE_PAGE_CHARGEWX));
        $this->tpl->assign("notice_conf", $noticeConf);

        // 网信PC大额充值的开关
        $this->tpl->assign('offlineChargeOpen', 1);
        // 用户绑定的银行是否在大额充值银行的白名单里
        $wxOfflineChargeSwitch = PaymentService::isOfflineBankList($user_id, 1);
        $this->tpl->assign('wxOfflineChargeSwitch', $wxOfflineChargeSwitch);
        // 用户是否在大额充值的用户黑名单里
        $wxOfflineUserBlack = PaymentService::inBlackList($user_id);
        $this->tpl->assign('wxOfflineUserBlack', $wxOfflineUserBlack);

        if(\libs\utils\ABControl::getInstance()->hit("newCharge")) {
            $this->tpl->assign("inc_file", "web/views/account/new_charge.html");
        } else {
            $this->tpl->assign("inc_file","web/views/account/charge.html");
        }

        $this->template = "web/views/account/charge.html";
    }

    /**
     * 旧的支付逻辑
     * @return boolean
     */
    public function oldInvoke()
    {
        $user_id = $GLOBALS['user_info']['id'];
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array($user_id));
        if (!$user_id || !$bankcard || $bankcard['status'] != 1) { // 没有银行卡信息或没有确认过则引导用户填写银行卡信息
            $msg = (intval($GLOBALS['user_info']['mobilepassed']) == 0 || intval($GLOBALS['user_info']['idcardpassed']) == 0) ? "请先填写身份证信息" : "请先填写银行卡信息";
            return $this->show_error($msg, "", 0, 0, url("shop", "account/addbank"), 3);
        }

        // 银行列表
        $bank_list = $this->rpc->local("BankService\getBankList");

        // 最近一次充值记录
        $user_id = $GLOBALS['user_info']['id'];
        $latest_order = $this->rpc->local("BankService\getUserOrder", array($user_id, 1));

        // 计算最后一次充值使用的银行
        $latest_bank = false;
        if (!empty($latest_order) && !empty($latest_order['list']) && !empty($latest_order['list'][0]['bank_id'])) {
            $latest_order_bank_id = $latest_order['list'][0]['bank_id'];
            foreach ($bank_list as $key => $val) {
                if ($val['short_name'] == $latest_order_bank_id) {
                    $latest_bank = $bank_list[$key];
                    unset($bank_list[$key]);
                }
            }
        }
        $this->tpl->assign("bank_list", $bank_list);
        $this->tpl->assign("latest_bank", $latest_bank);

        $bank_list_new = $bank_list;
        $first_bank = array_shift($bank_list_new);
        $first_bank_info = $this->rpc->local("BankService\getAuxiliary", array($first_bank['id']));
        $latest_bank_info = $this->rpc->local("BankService\getAuxiliary", array($latest_bank['id']));
        $this->tpl->assign("first_bank_info", $first_bank_info);
        $this->tpl->assign("latest_list", $latest_bank_info);

        $this->tpl->assign("inc_file", "web/views/account/old_charge.html");
        $this->template = "web/views/account/frame.html";
        return true;
    }
}
