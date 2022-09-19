<?php

/**
 * Passporttype.php
 *
 * @date 2014年5月22日
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;

class Passporttype extends BaseAction {

    public function init() {
        $this->check_login();
    }

    public function invoke() {

        $uid = $GLOBALS['user_info']['id'];
        if (!$uid) {
            return app_redirect(url("index"));
        }
        $text = array(
            '1' => array('1）港澳居民來往內地通行證', '2）香港永久性居民身份證', '/static/default/images/hk.jpg'),
            '2' => array('1）港澳居民來往內地通行證', '2）澳门永久性居民身份證', '/static/default/images/ma.jpg'),
            '3' => array('1）台灣居民來往大陸通行證（臺胞證）', '2）臺灣地區身份證', '/static/default/images/tw.jpg'),
        );

        $this->tpl->assign("text", json_encode($text));
    }

}
