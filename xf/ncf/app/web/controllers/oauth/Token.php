<?php
/**
 * @file
 * Sample token endpoint.
 *
 * Obviously not production-ready code, just simple and to the point.
 *
 * In reality, you'd probably use a nifty framework to handle most of the crud for you.
 */


namespace web\controllers\oauth;
use web\controllers\BaseAction;
require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";
class Token extends BaseAction {

    public function init() {
        if ($_POST) {
            $oauth = new \PDOOAuth2();
            $oauth->grantAccessToken();
            return false;
        }
    }

    public function invoke() {}

}

