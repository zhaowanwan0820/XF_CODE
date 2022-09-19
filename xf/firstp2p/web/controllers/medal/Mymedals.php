<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/22
 * Time: 15:31
 */

namespace web\controllers\medal;

use web\controllers\BaseAction;
use NCFGroup\Protos\Medal\RequestMedalUser;
use core\service\MedalService;

class Mymedals extends BaseAction{

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $request = new RequestMedalUser();
        $request->setUserId(intval($GLOBALS['user_info']['id']));
        $medalService = new MedalService();
        $medalList = $medalService->getUserMedalList($request);
        $this->tpl->assign("list", json_encode($medalList));
        $this->tpl->assign("inc_file", "web/views/medal/mymedals.html");
        $this->template = "web/views/medal/frame.html";
    }
}