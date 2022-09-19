<?php

namespace openapi\controllers\creditchina;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestCreditCount;

/**
 * DealLoad
 * 中国信贷投资记录
 * @uses BaseAction
 * @package default
 * @author yangqing <yangqing@ucfgroup.com> 
 */
class DealLoad extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "time" => array("filter" => "int" ),
            //"oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $request = new RequestCreditCount();
        if(empty($data['time'])){
            $request->setTime(0);
        }else{
            $request->setTime(intval($data['time']));
        }
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getCreditDealLoad',
            'args' => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "unkown error";
            return false;
        }
        setLog(array('errno' => $response->resCode, 'errmsg' => 'CREDIT_DEALLOAD','output'=> print_r($response,true)));

        $this->json_data = $response->toArray();
    }

}
