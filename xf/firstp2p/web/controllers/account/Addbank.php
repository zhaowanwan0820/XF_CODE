<?php

/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\dao\BankModel;
use core\service\ConfService;
use libs\web\Url;

class Addbank extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        //身份认证维护页
        if (intval(app_conf("ID5_VALID")) === 3) {
            $this->tpl->assign("page_title", "系统维护中");
            $this->tpl->assign("content", app_conf("ID5_MAINTEN_MSG"));
            $this->template = "web/views/v2/account/maintain.html";
            $this->tpl->display("web/views/v2/account/maintain.html");
            return;
        }

        $user_info = $GLOBALS ['user_info'];
        // 如果企业用户访问添加银行界面，显示
        if ($user_info['user_type'] == '1') {
           return app_redirect(Url::gene('deal','promptCompany'));
        }

        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown()) {
            return $this->show_tips(\libs\utils\PaymentApi::maintainMessage(), '温馨提示', 0, '', '/');
        }

        // 在审核中的用户
        if ($user_info['idcardpassed'] == 3) {
            return $this->show_error('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', "", 0, '', '/account');
        }

        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());
        //如果未绑定手机或者没有实名认证
        if (intval($GLOBALS['user_info']['mobilepassed']) == 0 || intval($GLOBALS['user_info']['idcardpassed']) != 1 || !$GLOBALS['user_info']['real_name']) {
            $this->tpl->assign("page_title", "成为投资用户");
            $idTypes = getIdTypes();
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("agrant_id", 200);
            $this->tpl->assign("user", $GLOBALS['user_info']);
            $this->tpl->assign("bank_list", $bank_list);
            if (isset($_GET['from']))
            {
                $this->tpl->assign("from", trim($_GET['from']));
            }
            $this->tpl->assign("idCheckPassed", $GLOBALS['user_info']['idcardpassed'] == 1);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }
        // hotfix普惠pc站跳走
        // TODO SupervisionMock
        if ($this->is_firstp2p) {
            app_redirect(Url::gene('account', 'registerStandard'));
            return ;
        }
        //$this->show_success('用户已经开户', '', 0, 0, '/account/editBank', null, 0);
        //return;
        // 主站大陆用户跳转到支付绑卡界面
        // 跳转到先锋支付
        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_info['id']));
        if ((\libs\utils\Site::getId() == 1 || \libs\web\Open::checkOpenSwitch()) && empty($hasPassport))
        {
            app_redirect(Url::gene('account','registerSuccess'));
            return;
        }

        make_delivery_region_js();
        //$bank_list = $this->rpc->local('BankService\getBankUserByStatus', array('0'));
        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());
        //地区列表
        $region_lv1 = $this->rpc->local('BankService\getRegion', array(1));
        //获取用户银行卡信息
        $bankcard_info = $this->rpc->local('BankService\userBank', array($GLOBALS['user_info']['id'], true));
        if ($bankcard_info) {
            $bankcard_info['card_name'] = $GLOBALS['user_info']['real_name'];
            foreach ($bank_list as $k => $v) {
                if ($v['id'] == $bankcard_info['user_bank_id']) {
                    $bankcard_info['is_rec'] = $v['is_rec'];
                    break;
                }
            }
        }

        $this->tpl->assign("bank_list", $bank_list);
        $this->tpl->assign("region_lv1", $region_lv1);
        $this->tpl->assign("id", $bankcard_info['id']);
        $this->tpl->assign("realName", nameFormat($GLOBALS['user_info']['real_name']));
        $this->tpl->assign("user_bank_id", $bankcard_info['user_bank_id']);
        $this->tpl->assign('idno', idnoFormat($GLOBALS['user_info']['idno']));
        $this->tpl->assign("page_title", $GLOBALS['lang']['UC_BANK']);
        $this->tpl->assign('bankcard_info', $bankcard_info);
//	    $this->tpl->display("inc/uc/new/uc_editor_bank.html");
    }

}
