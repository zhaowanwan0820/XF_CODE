<?php
/**
 * User: duxuefeng
 * Date: 2018/6/2
 * Time: 18:15
 */

namespace openapi\controllers\contract;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Contract\RequestGetContractById;
use NCFGroup\Protos\Contract\RequestSignBeforeBorrowContract;

/**
 * 签署前置合同
 *
 */
class TmpContractSign extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "contractId" => array("filter" => "required", "message" => "contractId is required"),
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
        // 1 contractId
        if(!is_numeric($params['contractId']) || ($params['contractId'] <= 0)){
            $this->setErr("ERR_PARAMS_ERROR", "contractId参数错误");
            return false;
        }
        // 2 是否已经签署
        $rpc = new \libs\utils\Rpc('contractRpc');
        if(!$rpc){
            return $this->setErr('ERR_SYSTEM');
        }
        $contractRequest = new RequestGetContractById();
        $contractRequest->setId(intval($params['contractId']));
        $contractResponse = $rpc->go('NCFGroup\Contract\Services\ContractBeforeBorrow','getContractById',$contractRequest);
        if($contractResponse['errCode'] != 0){
            $this->setErr("ERR_CONTRACT_EMPTY", $contractResponse['errMsg']);
            return false;
        }
        if($contractResponse['data']['borrowerSignTime'] > 0){
            $this->setErr("ERR_CONTRACT_SIGNED");
            return false;
        }
        // 3 签署合同
        $signRequest = new RequestSignBeforeBorrowContract();
        $signRequest->setId(intval($params['contractId']));
        $signRequest->setBorrowerSignTime(time());
        $signResponse = $rpc->go('NCFGroup\Contract\Services\ContractBeforeBorrow','signBeforeBorrowContract',$signRequest);
        if($signResponse['errCode'] != 0){
            $this->setErr("ERR_CONTRACT_SIGN_FAILED", $contractResponse['errMsg']);
            return false;
        }

        $this->json_data = "签署成功" ;
    }

}
