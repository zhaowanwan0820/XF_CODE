<?php

namespace openapi\controllers\creditchina;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestCreditCount;

/**
 * RegLog
 * 中国信贷注册用户记录
 * @uses BaseAction
 * @package default
 * @author yangqing <yangqing@ucfgroup.com> 
 */
class RegLog extends BaseAction {

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
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getCreditRegLog',
            'args' => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "unkown error";
            return false;
        }
        setLog(array('errno' => $response->resCode, 'errmsg' => 'CREDIT_REGLOG','output'=> print_r($response,true)));

        $this->json_data = $response->toArray();
    }

}
