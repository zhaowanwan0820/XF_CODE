<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/6/3
 * Time: 11:22
 */

namespace openapi\controllers\medal;

use openapi\controllers\BaseAction;
use core\service\MedalService;
use libs\web\Form;

class Beginner extends BaseAction{

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
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $result = 0;
        if($redis) {
            $key = sprintf(MedalService::MEDAL_BEGINNER_HINT_FORMAT, $user->getUserId());
            $result = $redis->rPop($key);
        }
        $this->json_data = intval($result);
        return true;
    }
}