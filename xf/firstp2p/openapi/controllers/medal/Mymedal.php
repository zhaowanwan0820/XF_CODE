<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/25
 * Time: 9:43
 */

namespace openapi\controllers\medal;

use openapi\controllers\BaseAction;
use NCFGroup\Protos\Medal\RequestMedalUser;
use libs\web\Form;

class Mymedal extends BaseAction{

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
        $request = new RequestMedalUser();
        $userId = $user->getUserId();
        $request->setUserId($userId);
        $data = $this->rpc->local('MedalService\getUserMedalList', array($request));
        $this->json_data = $data;
        return true;
    }
}
