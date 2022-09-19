<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/5/26
 * Time: 11:49
 */

namespace web\controllers\medal;

use web\controllers\BaseAction;
use core\service\MedalService;

class Progress extends BaseAction{

    public function invoke() {
        if (empty($GLOBALS ['user_info'])) {
            return ajax_return(array("status" => 0, "msg" => "请登陆后访问", "data" => ""));
        }

        //获取隐藏的标记。
        $isHidden = \es_session::get(\core\service\MedalService::MEDAL_HIDDEN_CIRCLE);
        if($isHidden) {//用户隐藏了新手进度的圈圈
            return ajax_return(array("status" => 0, "msg" => "用户主动隐藏了该圆圈", "data" => ""));
        }
        $medalService = new MedalService();
        $request = $medalService->createUserMedalRequestParameter($GLOBALS['user_info']['id']);
        $result = $medalService->getMedalProgress($request);
        ajax_return(array("status" => 1, "msg" => "", "data" => $result));
        return true;
    }
}
