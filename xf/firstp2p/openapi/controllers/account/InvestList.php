<?php

/**
 * @abstract 用户投资列表
 * @author yutao@ucfgroup.com
 * @date  2015-06-08
 */

namespace openapi\controllers\account;

use openapi\controllers\BaseAction;
use libs\web\Form;

/**
 * 已投资列表接口
 *
 * status（可选）：状态；string；默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
 *
 * Class InvestList
 * @package openapi\controllers\account
 */
class InvestList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "status" => array("filter" => "string", "message" => "status is error", "option" => array('optional' => true)),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
            'compound' => array('filter' => 'string', "option" => array('optional' => true)),
            'beginTime' => array('filter' => 'int', "option" => array('optional' => true)),
            'endTime' => array('filter' => 'int', "option" => array('optional' => true)),
            'filterLoantype' => array('filter' => 'int', "option" => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        //处理status字段，默认为0；0-全部 1-投资中 2-满标 4-还款中 5-已还清；status>0时，支持多个状态合并查询，status值以英文逗号隔开，如status=1,2 查询投资中和满标的列表。
        if (empty($this->form->data['status'])) {
            $status = 0;
        } else {
            $status = $this->form->data['status'];
            $status_array = explode(',', $status);
            foreach ($status_array as $k => $item) {
                $item = intval($item);
                if ($item == 0) {
                    $status = 0;
                    break;
                } else if (!in_array($item, array(1, 2, 4, 5))) {
                    unset($status_array[$k]);
                }
            }
            $status = ($status == 0) ? 0 : (implode(',', $status_array));
        }
        $this->form->data['status'] = $status;
        $params = $this->form->data;
        $params['offset'] = empty($params['offset']) ? 0 : intval($params['offset']);
        $params['count'] = empty($params['count']) ? 10 : intval($params['count']);

        $beginTime = intval($params['beginTime'] - 8 * 3600);
        $endTime = intval($params['endTime'] - 8 * 3600);
        $beginTime = $beginTime > 0 ? date("Y-m-d", $beginTime) : false;
        $endTime = $endTime > 0 ? date("Y-m-d", $endTime) : false;

        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $request = new \NCFGroup\Protos\Ptp\RequestGetUserInvestList();
        try {
            $request->setUserId($userInfo->userId);
            $request->setCount($params['count']);
            $request->setOffset($params['offset']);
            $request->setStatus($params['status']);
            $request->setCompound($params['compound']);
            $request->setBeginTime($beginTime);
            $request->setEndTime($endTime);
            if (isset($params['filterLoantype'])) {
                $request->setFilterLoantype(intval($params['filterLoantype']));
            }
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDealLoad',
            'method' => 'getDealLoadList',
            'args' => $request
        ));
        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user invest list failed";
            return false;
        }

        $this->json_data = $response->userInvestList;
    }

}
