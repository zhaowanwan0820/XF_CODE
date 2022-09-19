<?php

/**
 * @abstract openapi  资金记录接口
 * 
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

//use api\conf\Error;

/**
 * 资金记录列表接口
 *
 * Class FinancialRecord
 * @package openapi\controllers\account
 */
class FinancialRecord extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
            "offset" => array("filter" => "int", "message" => "offset is error", 'option' => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", 'option' => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $params['offset'] = intval($params['offset']);
        $params['count'] = empty($params['count']) ? 20 : intval($params['count']);
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $request = new \NCFGroup\Protos\Ptp\RequestGetUserFinancialRecord();

        try {
            $request->setUserId($userInfo->userId);
            $request->setCount($params['count']);
            $request->setOffset($params['offset']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getUserFinancialRecord',
            'args' => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user financial record failed";
            return false;
        }

        $result = array();
        foreach ($response as $k => $v) {
            $result[$k]['id'] = $v['id'];
            $result[$k]['time'] = to_date($v['log_time']);
            $result[$k]['type'] = $v['log_info'];
            $result[$k]['money'] = format_price($v['money'], false);
            $result[$k]['remark'] = $v['note'];
        }
        $this->json_data = $result;
    }

}
