<?php

/**
 * 
 * @abstract 通过user_id获取用户信息
 * @author yutao<yutao@ucfgroup.com>
 * @date   2015-02-26
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use core\service\UserService;

class GetInfoById extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "user_id" => array("filter" => "required", "message" => "user_id is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userId = intval($data['user_id']);
        $userIdResponse = new ProtoUser();
        $userIdResponse->setUserId($userId);
        $userResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getUserInfoById',
            'args' => $userIdResponse
        ));
        if ($userResponse->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userResponse->sex = ($userResponse->sex == 0) ? '女' : '男';
        $userResponse->registerTime = to_date($userResponse->registerTime);
        $result = array_intersect_key($userResponse->toArray(), array('user_id' => '', 'userName' => '', 'realName' => '', 'sex' => '', 'mobile' => '', 'groupId' => '', 'registerTime' => ''));

        //服务人信息
        $userService = new UserService();
        $referInfo = $userService->getReferUserGroupName($userId);
        $result = array_merge($result, $referInfo);

        $this->json_data = $result;
        return true;
    }

}
