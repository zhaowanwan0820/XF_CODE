<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/6/2
 * Time: 16:53
 */

namespace web\controllers\medal;

use web\controllers\BaseAction;

class Circle extends BaseAction{

    //隐藏新手进度圆圈的接口，Get请求。
    public function invoke() {
        if(!isset($GLOBALS ['user_info'])) {
            return ajax_return(array("status" => 0, "msg" => "请登陆后访问", "data" => ""));
        }

        \es_session::set(\core\service\MedalService::MEDAL_HIDDEN_CIRCLE, 1);
        return ajax_return(array("status" => 1, "msg" => "", "data" => ""));
    }

}