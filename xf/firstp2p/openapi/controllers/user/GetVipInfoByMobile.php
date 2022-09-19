<?php

/**
 * 
 * @abstract 通过手机号获取用户相关vip信息
 * @author liguizhi<liguizhi@ucfgroup.com>
 * @date   2017-10-16
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestUserMobile;
use core\service\vip\VipService;
use core\service\UserService;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class GetVipInfoByMobile extends BaseAction {

    private $user_item = array("id"=>"", "userId" => "", "userName" => "", "realName" => "", "sex" => "","siteName"=>"", "inviteSiteName"=>"", "status" => "未注册");

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "mobile" => array("filter" => "required", "message" => "mobile is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $clientInfo = $this->getClientIdByAccessToken();
        if (!$clientInfo || $clientInfo['client_id'] !== $data['client_id']) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $mobile = (string) $data['mobile'];
        if(!is_mobile($mobile)) {
            $this->errorCode = -99;
            $this->errorMsg = "param set error";
            return false;
        }

        $mobileRequest = new RequestUserMobile();
        $mobileRequest->setMobile($mobile);
        $userResponse = $this->userServiceRequest(array('method'=> 'getUserInfoByMobile', 'args' => $mobileRequest));
        if($userResponse->resCode){
            $this->errorCode = 1;
            $this->errorMsg = "未注册";
            $this->json_data_err = $this->user_item;
            return false;
        }

        $userId = (int) $userResponse->getUserId();
        $result = array();
        $result['id']       = numTo32($userId);
        $result['userId']   = $userId;

        $vipRequest = new SimpleRequestBase();
        $vipRequest->setParamArray(array('userId' => $userId));
        $vipInfo = $this->vipServiceRequest(array('method'=> 'getVipInfoByUserId', 'args' => $vipRequest));

        if ($vipInfo) {
            $result = array_merge($result, $vipInfo);
        }

        //服务人信息
        $userService = new UserService();
        $referInfo = $userService->getReferUserGroupName($userId);
        $result = array_merge($result, $referInfo);

        $this->json_data = $result;
        return true;
    }

    /**
     * @请求UserService函数
     * @param  array  $params_array
     * @return object
     */
    private function userServiceRequest($params_array)
    {
        $req_res = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => $params_array['method'], 'args' => $params_array['args']));

        return $req_res;
    }

    private function vipServiceRequest($params_array) {
        $req_res = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpVip', 'method' => $params_array['method'], 'args' => $params_array['args']));
        return $req_res;
    }
}
