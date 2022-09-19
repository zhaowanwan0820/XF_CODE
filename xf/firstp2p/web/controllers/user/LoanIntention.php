<?php

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LoanIntentionService;


class LoanIntention extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'code' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
        }
    }

    public function invoke(){
        // 检查用户是否有权进入
        $code = trim($this->form->data['code']);
        $checkRet = $this->rpc->local('LoanIntentionService\checkQualification',array($GLOBALS['user_info'],$code));
        if( $checkRet['errno'] !== 0 ){
            // 跳转回到原来页面
            if ( $_SERVER['REQUEST_METHOD']=="POST" ){
                $this->tpl->assign("error",$checkRet['errmsg']);
            }
            $this->template = "web/views/v2/account/frame.html";
            $this->tpl->assign("inc_file","web/views/v2/account/loanIntentionEnter.html");
            return true;
        }
        $this->tpl->assign("inc_file","web/views/v2/account/borrow.html");
        $this->template = "web/views/v2/account/frame.html";
        $this->tpl->assign("code",$code);
        $this->tpl->assign("realName",$GLOBALS['user_info']['real_name']);
        $this->tpl->assign("idNo",idnoFormat($GLOBALS['user_info']['idno']));
        $this->tpl->assign("mobile",moblieFormat($GLOBALS['user_info']['mobile']));
        $this->tpl->assign("allAmount",format_price($checkRet['ext']['principal']));
        $this->tpl->assign("miniBorrowMoney",$checkRet['ext']['mini_borrow_money']/10000);
        $this->tpl->assign("miniBorrowMoneyNum",$checkRet['ext']['mini_borrow_money']);
        $this->tpl->assign("type",$checkRet['ext']['type']);
        $max_money = $this->rpc->local('LoanIntentionService\getXFDMaxMoney',array());
        $this->tpl->assign("max_money",$max_money);
        $this->tpl->assign("max_money_show",($max_money/10000)."万");
        return true;
    }
}
