<?php

namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;

class IntentionDetail extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "code" => array("filter" => "required", "message" => "code is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $code = trim($this->form->data['code']);
        $checkRet = $this->rpc->local('LoanIntentionService\checkQualification', array($loginUser, $code));

        $has_qualification = 1;
        if( $checkRet['errno'] !== 0 ){
            $has_qualification = 0;
        } else {
            $mobile = $loginUser['mobile'] ? moblieFormat($loginUser['mobile']) : '无';
            $idNo = $loginUser['idno'] ? idnoFormat($loginUser['idno']) : '无';

            $this->tpl->assign("token", $this->form->data['token']);
            $this->tpl->assign("code", $code);
            $this->tpl->assign("realName", $loginUser['real_name']);
            $this->tpl->assign("idNo", $idNo);
            $this->tpl->assign("mobile", $mobile);
            $this->tpl->assign("agreementUrl", urlencode(get_http() . get_host() . '/help/intention_agreement'));
            $this->tpl->assign("allAmount", $checkRet['ext']['principal']);
            $this->tpl->assign("type", $checkRet['ext']['type']);
            $this->tpl->assign("miniBorrowMoney",$checkRet['ext']['mini_borrow_money']/10000);
            $this->tpl->assign("miniBorrowMoneyNum",$checkRet['ext']['mini_borrow_money']);
            $max_money = $this->rpc->local('LoanIntentionService\getXFDMaxMoney',array());
            $this->tpl->assign("max_money",$max_money);
        }

        $this->tpl->assign("has_qualification", $has_qualification);

        if($checkRet['ext']['type'] == 2){
            $this->template = $this->getTemplate('intention_detail_job');
        }
    }
}
