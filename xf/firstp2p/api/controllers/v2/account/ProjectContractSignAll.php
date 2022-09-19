<?php

namespace api\controllers\account;

use libs\rpc\Rpc;
use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;

use core\dao\DealProjectModel;

/**
 * 项目合同签署
 */
class ProjectContractSignAll extends AppBaseAction {

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
        $projectId = $data['id'];
        $dealProjectModel = new DealProjectModel();
        $project = $dealProjectModel->find(intval($projectId));
        $firstDeal = $dealProjectModel->getFirstDealByProjectId($projectId);

        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $dealInfo = $this->rpc->local('DealService\getDeal',array($firstDeal['id'], true, false));
        if(empty($dealInfo)){
            throw new \Exception('合同签署失败');
        }

        $userId = $userInfo['id'];
        $userName = $userInfo['user_name'];

        //判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
        $params = array(array('id' => $userId, 'user_name' => $userName));
        $user_role = $this->rpc->local('UserService\getUserAgencyInfoNew', $params);
        $advisory_role = $this->rpc->local('UserService\getUserAdvisoryInfo', $params);
        $entrust_role = $this->rpc->local('UserService\getUserEntrustInfo', $params);
        $is_agency = intval($user_role['is_agency']);
        $is_advisory = intval($advisory_role['is_advisory']);
        $is_entrust = intval($entrust_role['is_entrust']);
        $is_borrower = ($userId == $dealInfo['user_id']) ? 1 : 0;

        if($is_agency){
            $agency_id = $user_role['agency_info']['agency_id'];
        }elseif($is_advisory){
            $agency_id = $advisory_role['advisory_info']['agency_id'];
        }elseif($is_entrust){
            $agency_id = $entrust_role['entrust_info']['agency_id'];
        }

        //判断是否已经签署
        if($is_agency || $is_borrower || $is_advisory || $is_entrust){
            // 有资格签
            if($is_borrower){
                $sign_info = $this->rpc->local('ContractNewService\getProjectContSignNum',array($projectId, $userId, 0, 0));
            }else{
                $sign_info = $this->rpc->local('ContractNewService\getProjectContSignNum',array($projectId, $userId, 1, $agency_id));
            }
            if (!$sign_info || $sign_info['status'] != 0) {
                throw new \Exception('合同已经签署');
            }
        }else{
            // 木有资格签署
            throw new \Exception('合同不存在');
        }

        if($is_borrower){
            $role = 1;
        }elseif($is_agency){
            $role = 2;
        }elseif($is_advisory){
            $role = 3;
        }elseif($is_entrust){
            $role = 5;
        }

        $sign_info = $this->rpc->local('ContractNewService\signProjectCont',array($projectId, $role, $userId));

        if(!empty($sign_info)){
            $ret = 'success';
        }else{
            $ret = 'failed';
            throw new \Exception('合同签署失败!');
        }
        $this->json_data = $ret;
    }
}
