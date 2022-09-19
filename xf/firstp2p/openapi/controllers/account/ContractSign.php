<?php

namespace openapi\controllers\account;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;
/**
 * 合同列表
 */
class ContractSign extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'cid' => array('filter' => 'int'),//合同id
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $return = array();
        $data = $this->form->data;
        $contractId = $data['cid'];

        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        //合同为空或者已签署
        $cont_info = $this->rpc->local('ContractService\getContract', array($contractId));
        if(empty($cont_info) || $cont_info['sign_time'] > 0){
            $this->setErr('ERR_CONTRACT_EMPTY');
        }

        //单个签署合同
        $userId = $userInfo->userId;
        $userName = $userInfo->userName;
        $params = array($cont_info, array('id' => $userId, 'user_name' => $userName));
        $signResult = $this->rpc->local('ContractService\signOneContNew', $params);
        if( $signResult ){
            $ret = 'success';
        }else{
            $ret = 'failed';
            $this->setErr('ERR_CONTRACT_SIGN_FAILED');
            return false;
        }

        $this->json_data = $ret;
    }
}
