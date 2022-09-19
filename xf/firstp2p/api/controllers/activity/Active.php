<?php

namespace api\controllers\activity;

use libs\web\Form;
use api\controllers\BaseAction;

class Active extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'order_id'    => array('filter' => 'string'),
            'ncf_mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array(
                    "regexp" => "/^1[3456789]\d{9}$/",
                    "optional"=>true
                ),
            ),
            'rec_mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码格式不正确",
                "option" => array(
                    "regexp" => "/^1[3456789]\d{9}$/",
                    "optional"=>true
                ),
            ),
            'ncf_idno'    => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL', "请先核对您输入的信息无误再激活");
        }
    }

    public function invoke() {
        $data = $this->form->data;
        if (empty($data['order_id'])) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL', "请先核对您输入的信息无误再激活");
        }

        $retPack = $this->rpc->local('OpenService\activeActivity', array($data));
        if ($retPack['errno']) {
            return $this->setErr($retPack['errno'], $retPack['error']);
        }

        $this->json_data = $retPack['data'];
    }

}
