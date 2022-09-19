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
class User extends BaseAction {

    private $userId = '';
    private $output = '';
    public function init() {
        $oauth = new \PDOOAuth2();
        $result = $oauth->verifyAccessToken();
        if (empty($_POST['oauth_token'])) {
            return $this->output(array('paramter error!'));
        }
        if ($result) {
            return $this->userId = $oauth->getAccessToken($_POST['oauth_token']);
        } else {
            return $this->output(array('invalid token!'));
        }
    }

    public function invoke() {
        if ($this->userId) {
            $userInfo = $this->rpc->local("UserService\getUser", array($this->userId));
            $realname = ($userInfo['idcardpassed'] == 1)?$userInfo['real_name']:null;
            return $this->output(
                array(
                    'user_id' => base62encode($userInfo['id']),
                    'name' => $userInfo['user_name'],
                    'realname' => $realname,
                )
            );
        }
    }

    private function output($data) {
        header("Content-Type: application/json");
        header("Cache-Control: no-store");
        echo json_encode($data);
        return false;
    }

}

