<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/5/26
 * Time: 9:10
 */

namespace web\controllers\medal;

use core\service\MedalService;
use web\controllers\BaseAction;

class Message extends BaseAction {

    public function invoke() {
        if(empty($GLOBALS ['user_info'])) {
            return ajax_return(array("status" => 0, "msg" => "请登陆后访问", "data" => ""));
        }

        $medalService = new MedalService();
        $request = $medalService->createUserMedalRequestParameter($GLOBALS['user_info']['id']);
        $messages = $medalService->fetchMedalMessage($request);
        ajax_return(array("status" => 1, "msg" => "", "data" => $messages));
        return true;
    }
}
