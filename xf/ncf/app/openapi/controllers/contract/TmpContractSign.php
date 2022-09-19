<?php
/**
 * User: duxuefeng
 * Date: 2018/6/2
 * Time: 18:15
 */

namespace openapi\controllers\contract;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\contract\ContractBeforeBorrowService;
use core\dao\deal\OrderNotifyModel;
use libs\utils\Logger;

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
        $contractResponse = ContractBeforeBorrowService::getContractById(intval($params['contractId']));
        if(empty($contractResponse)){
            $this->setErr("ERR_CONTRACT_EMPTY", ContractBeforeBorrowService::getErrorMsg());
            return false;
        }
        if($contractResponse['borrowerSignTime'] > 0){
            $this->setErr("ERR_CONTRACT_SIGNED");
            return false;
        }
        // 3 签署合同
        $signResponse = ContractBeforeBorrowService::signBeforeBorrowContract(intval($params['contractId']),time());
        if($signResponse != true){
            $this->setErr("ERR_CONTRACT_SIGN_FAILED", $contractResponse['errMsg']);
            return false;
        }

        $this->processOrderNotify($contractResponse,$params['client_id']);

        $this->json_data = "签署成功" ;
    }

    /**
     *  处理异步通知
     * @param  array $contractResponse
     * @param $clientId
     * @return bool
     */
    private function processOrderNotify($contractResponse,$clientId){

        if (empty($contractResponse['params'])){
            return true;
        }
        try {
            $params = json_decode($contractResponse['params'], true);
            if (empty($params['orderNotifyUrl'])) {
                return true;
            }
            // 签署成功异步通知第三方
            $orderNotifyModel = new OrderNotifyModel();
            $order_data = array(
                'client_id' => $clientId,
                'order_id' => $contractResponse['approveNumber'],
                'notify_url' => $params['orderNotifyUrl'],
                'notify_params' => array('approveNumber' => $contractResponse['approveNumber']),
            );
            $ret = $orderNotifyModel->insertData($order_data);
            if (empty($ret)){
                throw new \Exception(json_encode($order_data).' insert false');
            }
            return true;
        }catch (\Exception $e){
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.$e->getMessage());
            return false;
        }

        return true;
    }

}
