<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/22
 * Time: 15:29
 */

namespace web\controllers\medal;

use core\service\MedalService;
use web\controllers\BaseAction;

class Wall extends BaseAction{

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $medalService = new MedalService();
        $request = $medalService->createUserMedalRequestParameter($GLOBALS['user_info']['id']);
        $medalList = $medalService->getMedalList($request);
        //清除Medal气泡的标识。
        $key = sprintf(\core\service\MedalService::MEDAL_BUBBLE_HINT_FORMAT, $GLOBALS['user_info']['id']);
        \es_session::delete($key);
        $this->tpl->assign("list", json_encode($medalList));
        $this->tpl->assign("inc_file", "web/views/medal/wall.html");
        $this->template = "web/views/medal/frame.html";
    }
}