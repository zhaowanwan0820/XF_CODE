<?php

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;
use core\service\deal\DealService;
use core\service\contract\ContractService;
use core\service\contract\ContractNewService;
use api\conf\Error;

/**
 * 签署合同
 */
class ContractSignAll extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "ERR_AUTH_FAIL"),
            "id" => array("filter" => "int", "message" => "id is error"),
        );

        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $dealId = $data['id'];
        $user = $this->user;

        $dealService = new DealService();
        $dealInfo = $dealService->getDeal($dealId, true, false,true);
        if(empty($dealInfo)){
            return $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
        }

        $userId = $user['id'];
        $userName = $user['user_name'];

        //判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
        $userInfo = array('id' => $userId, 'user_name' => $userName);
        $roleInfo = UserService::getUserRole($userInfo);
        $is_agency = intval($roleInfo['user_agency_info']['is_agency']);
        $is_advisory = intval($roleInfo['user_advisory_info']['is_advisory']);
        $is_entrust = intval($roleInfo['user_entrust_info']['is_entrust']);
        $is_borrower = ($userId == $dealInfo['user_id']) ? 1 : 0;

        if($is_agency){
            $agency_id = $roleInfo['user_agency_info']['agency_info']['agency_id'];
            $type = 2;
        }elseif($is_advisory){
            $agency_id = $roleInfo['user_advisory_info']['advisory_info']['agency_id'];
            $type = 3;
        }elseif($is_entrust){
            $agency_id = $roleInfo['user_entrust_info']['entrust_info']['agency_id'];
            $type = 5;
        }elseif($is_borrower){
            $agency_id = 0;
            $type = 1;
        }

        $contractNewService = new ContractNewService();
        //判断是否已经签署
        if($is_agency || $is_borrower || $is_advisory || $is_entrust){
            // 有资格签
            $sign_info = $contractNewService->getContSignNum($dealId, $type, $agency_id);
            if (!$sign_info || $sign_info['status'] != 0) {
                return $this->setErr('ERR_DARKMOON_SIGNED');
            }
        }else{
            // 木有资格签署
            return $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
        }

        if(is_numeric($dealInfo['contract_tpl_type'])){
            $sign_info = $contractNewService->signAll($dealId, $type, $userId);
        }else{
            return $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
        }

        if(!empty($sign_info)){
            $ret = 'success';
        }else{
            $ret = 'failed';
            return $this->setErr('ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL', '合同签署失败!');
        }
        $this->json_data = $ret;
    }
}
