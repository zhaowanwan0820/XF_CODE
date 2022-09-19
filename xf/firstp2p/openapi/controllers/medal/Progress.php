<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/5/26
 * Time: 14:08
 */

namespace openapi\controllers\medal;

use openapi\controllers\BaseAction;
use libs\web\Form;

class Progress extends BaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $user = $this->getUserByAccessToken();
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