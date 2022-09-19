<?php

/**
 * GetLastLoadList.php
 *
 * @date 2017-06-23
 * @author yanjun <yanjun5@ucfgroup.com>
 */


namespace openapi\controllers\account;

use openapi\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Protos\Ptp\RequestGetUserInvestList;

/**
 * 获取最新的30条投资记录
 *
 * Class GetLastLoadList
 * @package openapi\controllers\account
 */
class GetLastLoadList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "count" => array("filter" => "int", "option" => array('optional' => true))
        );
            $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;

        $count = !empty($params['count']) ? intval($params['count']) : 30;
        $request = new RequestGetUserInvestList();
        $request->setCount($count);
        $response = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpDealLoad',
                'method' => 'getLastLoadList',
                'args' => $request
        ));
        if($response->resCode || empty($response->lastLoadList)){
            $this->errorCode = -1;
            $this->errorMsg = "get last load list failed";
            return false;
        }

        $this->json_data = $response;
    }

}
