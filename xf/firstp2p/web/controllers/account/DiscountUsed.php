<?php
/**
 * 我的投资劵
 **/
namespace web\controllers\account;
use web\controllers\BaseAction;

class DiscountUsed extends BaseAction {

    public function init() {
        $this->check_login();
    }

    public function invoke()
    {
        if (!$this->isDiscountAvaliable) app_redirect(url("index"));

        $this->tpl->assign ( "page_title", '投资劵' );
        $this->tpl->assign ( "inc_file", 'web/views/v3/account/discountused.html' );

        $this->template = "web/views/account/frame.html";
    }
}

