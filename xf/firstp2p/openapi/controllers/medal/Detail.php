<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/26
 * Time: 14:29
 */

namespace openapi\controllers\medal;

use openapi\controllers\BaseAction;
use libs\web\Form;

class Detail extends BaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'medal_id' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->form->rules, $this->sys_param_rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $medalId = intval($this->form->data['medal_id']);
        $user = $this->getUserByAccessToken();
        if(empty($user)) {
            $this->json_data = $this->rpc->local('MedalService\getMedal', array($medalId, true));
            return true;
        }
        $userId = $user->getUserId();
        $request = $this->rpc->local("MedalService\\createUserMedalRequestParameter", array($userId, $medalId));
        try{
            $result = $this->rpc->local('MedalService\getOneMedalDetail', array($request));
            $this->json_data = $result;
            return true;
        } catch(\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMsg = $e->getMessage();
            return false;
        }
    }
}
