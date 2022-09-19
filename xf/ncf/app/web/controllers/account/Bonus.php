<?php
/**
 * 我的网信红包
 **/
namespace web\controllers\account;
use web\controllers\BaseAction;
use core\service\bonus\BonusService;

class Bonus extends BaseAction {

    public function init() {
        $this->check_login();
    }

    public function invoke() {

        $this->tpl->assign ( "page_title", '我的' . app_conf('NEW_BONUS_TITLE') );
        // $bonus_user = new BonusUser();
        $this->tpl->assign("bonus_user", BonusService::getUserBonusInfo($GLOBALS['user_info']['id']));
        $this->tpl->assign ( "inc_file", 'web/views/account/bonus.html' );

        $this->template = "web/views/account/frame.html";
    }
}

