<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use NCFGroup\Common\Extensions\RPC\RpcClientAdapter;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;

class Cpsorder extends AppBaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "user_id" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            "site_id" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            'page_no' => array('filter'=>'int'),
            'page_size' => array('filter'=>'int'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke (){
        $data = $this->form->data;
        $userId = intval($data['user_id']);
        $siteId = intval($data['site_id']);
        $pageNo = intval($data['page_no']);
        $pageSize = intval($data['page_size']);

        $pageNo = max($pageNo, 1);
        $pageSize = max($pageSize, 1);
        if(empty($userId) || empty($siteId)){
            $this->json_data = array();
            return true;
        }
        $openbackRpc = $GLOBALS["openbackRpc"];
        //$openbackRpcConfig = $GLOBALS['components_config']['components']['rpc']['openback'];
        //$openbackRpc = new RpcClientAdapter($openbackRpcConfig['rpcServerUri'], $openbackRpcConfig['rpcClientId'], $openbackRpcConfig['rpcSecretKey']);

        $parRequest = new SimpleRequestBase();
        $parRequest->setParamArray(array("user_id"=>$userId, "site_id"=>$siteId, "page_no"=>$pageNo, "page_size"=>$pageSize));

        try {
            $rpcRet = $openbackRpc->callByObject(array('service' => 'NCFGroup\Open\Services\OpenRebate', 'method' => 'getListByUserIdSiteId', 'args' => $parRequest));
        } catch (\Exception $e) {
            $responseBase = new ResponseBase();
            $responseBase->data = false;
            return false;
        }

        $this->json_data = $rpcRet->data;
        return true;
    }

}