<?php

namespace openapi\controllers\deal;

use core\dao\ThirdpartyDkModel;
use core\service\P2pIdempotentService;
use core\service\SupervisionOrderService;
use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use openapi\controllers\BaseAction;

class OrderInfo extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = [
            'out_order_id' => ['filter' => 'required', 'message' => "out_order_id is error"],
        ];

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $outerOrderId = isset($data['out_order_id']) ? $data['out_order_id'] : '';
        $thirdpartyDkInfo =  $this->rpc->local('ThirdpartyDkService\getThirdPartyByOutOrderId', [$outerOrderId, $this->_client_id]);
        if(!empty($thirdpartyDkInfo['order_id'])){
            $orderId = $thirdpartyDkInfo['order_id'];
        }else{
            $this->setErr("ERR_OUTER_ID");
            return false;
        }

        Logger::info(implode(" | ", [__CLASS__, __FUNCTION__, __LINE__,"订单查询","out_order_id:{$outerOrderId}"]));

        if($thirdpartyDkInfo['status'] == ThirdpartyDkModel::REQUEST_STATUS_FAIL){
            //代扣失败返回失败原因
            $orderInfo = P2pIdempotentService::getInfoByOrderId($thirdpartyDkInfo['order_id']);
            $params = json_decode(stripslashes($orderInfo['params']),true);
        }

        $this->json_data = [
            'order_id' => $orderId,
            'status' => $thirdpartyDkInfo['status'],
            'finish_time' => $thirdpartyDkInfo['update_time'],
            'err_msg' => isset($params['errMsg']) ? $params['errMsg'] : '',
        ];
        return true;
    }

}
