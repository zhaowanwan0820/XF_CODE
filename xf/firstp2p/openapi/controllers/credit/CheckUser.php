<?php

/**
 * @abstract openapi  检查用户信息
 * @author gengkuan <gengkuan@ucfgroup.com>
 * @date 2018-11-07
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestUserMobile;

/**
 * 检查用户信息
 *
 * Class CheckUserBindCard
 * @package openapi\controllers\asm
 */
class CheckUser extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "real_name" => array("filter" => "string", "message" => "real_name is required"),
            "idno" => array("filter" => "string", "message" => "idno is required"),
            "user_name" => array("filter" => "string"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        $request = new ProtoUser();
        if(isset($params['user_name'])){
            $request->setUserName( htmlspecialchars(trim($params['user_name'])));
        }
        $return = false;
        $request->setUserTypes(1);//默认企业用户
        $request->setRealName(htmlspecialchars(trim($params['real_name'])));
        $request->setIdno(htmlspecialchars(trim($params['idno'])));
        $userResponse = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getCompanyUserInfoByINM', 'args' => $request));
        if($userResponse->resCode)  $return = false;
        $user_id = $userResponse->getUserId();
        if(!empty($user_id)){
            $return = true;
        }
        //判断用户是否存在
          $this->json_data = $return;
    }

}
