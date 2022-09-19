<?php

/**
 * @abstract openapi  获得地区列表接口
 * 
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 获得地区列表接口
 *
 * Class GetRegionList
 * @package openapi\controllers\account
 */
class GetRegionList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "region_level" => array("filter" => "int", "message" => "region_level must be int"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $param = $this->form->data;
        $region_level = intval($param['region_level']);

        $request = new \NCFGroup\Protos\Ptp\RequestRegionList();
        try {
            if (!empty($region_level)) {
                $request->setRegionLevel($region_level);
            }
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpBank',
            'method' => 'getRegionList',
            'args' => $request
        ));

        if (!$response) {
            $this->errorCode = -1;
            $this->errorMsg = "get regionList ERROR";
            return false;
        }
        $this->json_data = $response;
    }

}
