<?php

namespace openapi\controllers\deal;

use core\enum\ThirdpartyDkEnum;
use core\service\deal\P2pIdempotentService;
use libs\web\Form;
use libs\utils\Logger;
use core\service\thirdparty\ThirdpartyDkService;
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
        $thirdpartyDkService = new ThirdpartyDkService();
        $thirdpartyDkInfo =  $thirdpartyDkService->getThirdPartyByOutOrderId($outerOrderId, $this->_client_id);
        if(!empty($thirdpartyDkInfo['order_id'])){
            $orderId = $thirdpartyDkInfo['order_id'];
        }else{
            $this->setErr("ERR_OUTER_ID");
            return false;
        }

        Logger::info(implode(" | ", [__CLASS__, __FUNCTION__, __LINE__,"订单查询","out_order_id:{$outerOrderId}"]));

        $orderInfo = P2pIdempotentService::getInfoByOrderId($thirdpartyDkInfo['order_id']);
        $params = json_decode(stripslashes($orderInfo['params']),true);
        $return = [
            'order_id' => $orderId,
            'status' => $thirdpartyDkInfo['status'],
            'finish_time' => $thirdpartyDkInfo['update_time'],
            'err_msg' => isset($params['errMsg']) ? $params['errMsg'] : '',
        ];
        if ($params['repayType'] == \core\enum\DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH) {
            $accounts = [];
            if (isset($params['repayUserId']) && $params['repayUserId'] > 0) {
                $accounts[] = $params['repayUserId'];
            }
            if (isset($params['rechargeUserId']) && $params['rechargeUserId'] > 0) {
                $accounts[] = $params['rechargeUserId'];
            }
            $return['accounts'] = implode(',', $accounts);
        }
        $this->json_data = $return;
        return true;
    }

}
