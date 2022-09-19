<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/27
 * Time: 17:16
 */

namespace openapi\controllers\medal;


use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Medal\RequestGetUserMedalAwards;

class Award extends BaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "medal_id" => array("filter" => "required","message" => "medalId is required"),
            "prize_id" => array("filter" => "required","message" => "prizeId is required"),
        );
        $this->form->rules = array_merge($this->form->rules, $this->sys_param_rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $user = $this->getUserByAccessToken();
        if(empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $user->getUserId();
        $data = $this->form->data;
        $requestUserMedalAward = new RequestGetUserMedalAwards();
        $requestUserMedalAward->setUserId($userId);
        $requestUserMedalAward->setMedalId(intval($data['medal_id']));
        $requestUserMedalAward->setAwards(explode(",", $data['prize_id']));
        try{
            $response = $this->rpc->local('MedalService\getAwards', array($requestUserMedalAward));
            $this->json_data = $response ? "success" : "fail";
            return true;
        } catch(\Exception $e) {
            $this->setErr("ERR_PARAMS_ERROR", $e->getMessage());
            return false;
        }
    }
}