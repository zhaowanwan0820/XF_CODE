<?php
/**
 * 黄金提金回调
 * @author wangzhen3
 *
 */

namespace openapi\controllers\gold;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\GoldDeliverService;


class UpdateOrderStatus extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "order_id" => array("filter" => "int"),
            "outer_order_id" => array("filter" => "string"),
            "status" => array("filter" => "int"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $orderId = $this->form->data['order_id'];
        $outerOrderId = $this->form->data['outer_order_id'];
        $status = $this->form->data['status'];
        $goldDeliverService = new GoldDeliverService();
        $response = $goldDeliverService->updateOrderStatus($orderId, $outerOrderId,$status);
        $this->errorCode = intval($response['errCode']);
        $this->errorMsg = $response['errMsg'];
        $this->json_data = array();
    }

}
