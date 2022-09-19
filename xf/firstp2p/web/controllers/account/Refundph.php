<?php
/**
 * Refund.php 访问普惠还款标的的接口
 *
 * @date 2018年8月9日17:17:54
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\ncfph\AccountService;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Refundph extends BaseAction {

    public function init() {
        if(!$this->check_login()) {
            return false;
        }
        $this->form = new Form();
        $this->form->rules = array(
            'status'=>array("filter"=>'int'),
            'p'=>array("filter"=>'int'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $user_id = $GLOBALS['user_info']['id'];
        $data = $this->form->data;
        $status = intval($data['status']);
        $page = intval($data['p'])<=0 ? 1 : intval($data['p']);

        $accountServcie = new AccountService();
        $result = $accountServcie->getRefund($user_id,$status,$page,app_conf("PAGE_SIZE"));
        $this->tpl->assign("is_ph",1); // 用于区分网信和普惠
        $this->tpl->assign("status",$result['status']);
        $this->tpl->assign("deal_list",$result['deal_list']);
        $page = new \Page($result['count'],app_conf("PAGE_SIZE"));//初始化分页对象
        $p  =  $page->show();
        $this->tpl->assign('pages',$p);
        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_DEAL_REFUND']);

        $this->tpl->assign("inc_file","web/views/v3/account/refund.html");
        $this->template = "web/views/account/frame.html";
    }
}
