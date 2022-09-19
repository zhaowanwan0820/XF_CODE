<?php
/**
 * Created by PhpStorm.
 * User: yinli
 * Date: 2018/5/2
 * Time: 15:45
 */

namespace web\controllers\account;

use web\controllers\BaseAction;

class Specialusertype extends BaseAction {

    public function init() {
        $this->check_login();
    }

    public function invoke() {

        $uid = $GLOBALS['user_info']['id'];
        if (!$uid) {
            return app_redirect(url("index"));
        }

        $text = array(
            '1' => array('1)居民身份证','2)军官证'),
            '2' => array('1)签证','2)护照个人信息页'),
        );

        $this->tpl->assign("text", json_encode($text));
    }

}
