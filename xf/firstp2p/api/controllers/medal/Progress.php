<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/5/26
 * Time: 13:58
 */

namespace api\controllers\medal;

use api\controllers\AppBaseAction;
use libs\web\Form;

class Progress extends AppBaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if(empty($user)) {
            $this->setErr("ERR_GET_USER_FAIL");
            return false;
        }

        $request = $this->rpc->local("MedalService\\createUserMedalRequestParameter", array($GLOBALS['user_info']['id']));
        $progress = $this->rpc->local("MedalService\\getMedalProgress", array($request));
        $this->json_data = $progress;
        return true;
    }

}