<?php

/**
 * 普惠实名认证页面
 *
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Url;
use libs\utils\PaymentApi;
use core\enum\UserEnum;
use core\enum\UserAccountEnum;
use core\service\risk\RiskService;

class Addbank extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS['user_info'];
        $riskRet = RiskService::check('REALNAME', array(
            'user_id'=>$user_info['id'],
            'mobile'=>$user_info['mobile'],
            'idno'=>$user_info['idno'],
            'user_type'=>$user_info['user_type'],
            'account_type'=>$user_info['user_purpose'],
            'invite_code'=>$user_info['invite_code']
        ));

        if ($riskRet !== true) {
            return $this->show_error('为了您的账户安全，请前往网信普惠APP进行实名认证', '请前往网信普惠APP', 0);
        }

        // 如果企业用户访问添加银行界面，显示
        if ($user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
        {
           return app_redirect(Url::gene('deal','promptCompany'));
        }

        //在审核中的用户
        if ($GLOBALS['user_info']['idcardpassed'] == 3) {
            return $this->show_error('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', "", 0, '', '/account');
        }

        // 如果未绑定手机或者没有实名认证
        if (intval($GLOBALS['user_info']['mobilepassed']) == 0 || intval($GLOBALS['user_info']['idcardpassed']) != 1 || empty($GLOBALS['user_info']['real_name'])) {
            $idTypes = getIdTypes();
            $this->tpl->assign("page_title", "成为出借人");
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("agrant_id", 200);
            $this->tpl->assign("user", $GLOBALS['user_info']);
            if (isset($_GET['from'])) {
                $this->tpl->assign("from", trim($_GET['from']));
            }
            $this->tpl->assign("idCheckPassed", $GLOBALS['user_info']['idcardpassed'] == 1);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }

        // 跳到实名认证成功页面
        app_redirect(Url::gene('account', 'registerStandard'));
        return ;
    }
}
