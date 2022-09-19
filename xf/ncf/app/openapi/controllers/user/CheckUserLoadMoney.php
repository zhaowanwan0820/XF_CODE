<?php
/**
 * 获取用户在途资产.
 * User: steven
 * Date: 2017/12/26
 * Time: 下午8:39
 */

namespace openapi\controllers\user;

use libs\web\Form;
use core\dao\deal\DealModel;
use core\service\user\UserService;
use openapi\controllers\BaseAction;

class CheckUserLoadMoney extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "user_name" => array("filter" => "required", "message" => "user_name is required"), //用户姓名
            "mobile" => array("filter" => "required", "message" => "mobile is required"), //手机号
            "id_no" => array("filter" => "required", "message" => "id_no is required"), //证件号码

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
        $mobile = intval($params['mobile']);
        $idNo = intval($params['id_no']);
        $realName = $params['user_name'];
        $userMobile = UserService::getUserByMobile($mobile);
        $userIdNo = UserService::getUserByIdno($idNo);
        if(empty($userMobile) && empty($userIdNo)){
            $this->setErr('user does not exists !', "未找到用户!");
            return false;
        }

        $result = array();
        $userIdArr = array();

        if(!empty($userMobile)) {
            if($userMobile['real_name'] == $realName){
                $userIdArr[] = $userMobile['id'];
            }
        }
        if(!empty($userIdNo)) {
            if($userIdNo['real_name'] == $realName){
                $userIdArr[] = $userIdNo['id'];
            }
        }

        if(empty($userIdArr)){
            $result['money'] = 0;
        }else{
            $dealModel = new DealModel();
            $money = $dealModel->getUnrepayP2pMoneyByUids($userIdArr);
            $result['money'] = $money;
        }
        $this->json_data = $result;
    }

}

