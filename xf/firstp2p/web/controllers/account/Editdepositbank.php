<?php
/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\BankModel;

class Editdepositbank extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'type'=>array("filter"=>'int'),
        );
        $this->form->validate();
    }

    public function invoke() {

        $user_info = $GLOBALS ['user_info'];
        // 用户类型-企业用户，需要联系客服进行处理 Add By guofeng 20160114 18:25
        if (isset($user_info['user_type']) && $user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
        {
            header('Location:' . get_domain() . '/account/baseinfoCompany');
            return;
        }
        //如果未绑定手机
        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])==0){
            $idTypes = getIdTypes();
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("page_title","成为投资者");
            $this->tpl->assign("agrant_id",200);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }
        //如果没有实名认证
        if(!$GLOBALS['user_info']['real_name'] || $GLOBALS['user_info']['idcardpassed'] !=1){
            $idTypes = getIdTypes();
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("page_title","成为投资者");
            $this->tpl->assign("agrant_id",200);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }
        make_delivery_region_js();

        //查询有无修改正在审核中
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($GLOBALS['user_info']['id']));
        if ($bankcard['is_audit'] == 1) {
            return $this->show_error("您已提交了一次修改申请，不能重复修改，请耐心等待审核结果!", "", 0, 0, "/account");
        }

        //新的修改逻辑页面
        $data = $this->form->data;
        $type = $data['type'];
        $is_new = true;
        if($type){
           // $is_new = true;
            $this->template = "web/views/account/ebank.html";
        }


        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());
        $hideExtra = true;
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                $hideExtraBanks[] = $bank['id'];
            }
        }
        //$bank_list = BankModel::instance()->getAllByStatusOrderByRecSortId('0');
        //地区列表
        $region_lv1 = $this->rpc->local('BankService\getRegion',array(1));
        //获取用户银行卡信息
        $bankcard_info = $this->rpc->local('BankService\userBank',array($GLOBALS['user_info']['id'],$is_new));
        if (!in_array($bankcard_info['bank_id'], $hideExtraBanks)){
            $bank_list = BankModel::instance()->getAllByStatusOrderByRecSortId('0');
            $hideExtra = false;
        }
        $this->tpl->assign('hideExtra', $hideExtra);
        $this->tpl->assign("bank_list", $bank_list);
        $this->tpl->assign("region_lv1", $region_lv1);
        $this->tpl->assign("id", $bankcard_info['id']);
        $this->tpl->assign("user_bank_id", $bankcard_info['user_bank_id']);
        $this->tpl->assign('idno', idnoFormat($user_info['idno']));
        $this->tpl->assign('realName', nameFormat($user_info['real_name']));
        $this->tpl->assign("page_title", $GLOBALS['lang']['UC_BANK']);
        $this->tpl->assign('bankcard_info', $bankcard_info);
    }
}
