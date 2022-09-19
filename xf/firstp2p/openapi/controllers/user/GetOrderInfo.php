<?php

/**
 * 获取第三方订单的信息
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\lib\Tools;

class GetOrderInfo extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "order_id" => array("filter" => "required", "message" => "order_id is required"),
            "site_id" => array("filter" => "string", "message" => "site_id is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        /*$user_info = $this->getUserByAccessToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }*/
        $order_id = $data['order_id'];

        if ($order_id) {
            $order_res = $this->rpc->local(
                    'ThirdpartyOrderService\getOrderByOrderId',
                    array($order_id)
                    );
            if ($order_res['errno'] == 0) {
                $res_data = $order_res['data'];
                $res_data['openId'] = Tools::getOpenID($res_data['user_id']);
                unset($res_data['user_id']);
                $this->json_data = $res_data;
                return true;
            } else {
                $this->errorCode = -1;
                $this->errorMsg = "get orderInfo failed";
                return false;
            }
        }

        return true;
    }

}
