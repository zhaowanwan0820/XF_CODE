<?php

namespace openapi\controllers\house;

use openapi\controllers\BaseAction;
use libs\web\Form;

/**
 * 网信房贷订单状态更新回调接口
 */
class StatusNotify extends BaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'order_id'  => array('filter' => 'required'),
            'status' => array('filter' => 'required'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $orderId = $this->form->data['order_id'];
        $status = $this->form->data['status'];
        $res = $this->rpc->local('HouseService\notify', array($orderId, $status), 'house');
        $status = $res ? 1 : 0;
        $result = array('status'=>$status);
        $this->json_data = $result;
        return true;
    }

}

