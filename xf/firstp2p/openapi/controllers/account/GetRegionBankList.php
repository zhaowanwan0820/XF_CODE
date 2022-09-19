<?php

/**
 * @abstract openapi  获得地区银行信息列表
 * 
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RequestRegionBankList;

/**
 * 获得地区银行信息列表
 *
 * Class GetRegionBankList
 * @package openapi\controllers\account
 */
class GetRegionBankList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'p' => array("filter" => 'string'), //省份信息
            'c' => array("filter" => "required", "message" => "city is required"), //city信息
            'b' => array("filter" => "required", "message" => "bank is required"), //bank
//            'n' => array("filter" => 'string'), //网点名字
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $request = new RequestRegionBankList();
        try {
            $request->setBank($data['b']);
            $request->setProvince($data['p']);
            $request->setCity($data['c']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpBank',
            'method' => 'getRegionBankList',
            'args' => $request
        ));

        if (!$response) {
            $this->errorCode = "-1";
            $this->errorMsg = "get regionBankList failed";
            return false;
        }
        $this->json_data = $response;
    }

}
