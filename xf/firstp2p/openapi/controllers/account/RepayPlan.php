<?php

/**
 * @abstract openapi回款计划
 * @author yutao@ucfgroup.com
 * @date  2014-01-27
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestRepayPlan;

class RepayPlan extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required"),
            //1 未还  2已还
            'type' => array("filter" => 'int', 'option' => array('optional' => true)),
            'count' => array("filter" => 'int', 'option' => array('optional' => true)),
            'offset' => array("filter" => 'int', 'option' => array('optional' => true)),
            'beginTime' => array("filter" => 'int', 'option' => array('optional' => true)),
            'endTime' => array("filter" => 'int', 'option' => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $request = new RequestRepayPlan();
        $count = intval($data['count']) ? intval($data['count']) : 10;
        $type = (intval($data['type']) == 1 ) ? 0 : 1;
        $beginTime = intval($data['beginTime'] - 8 * 3600);
        $endTime = intval($data['endTime'] - 8 * 3600);
        $beginTime = $beginTime > 0 ? $beginTime : 0;
        $endTime = $endTime > 0 ? $endTime : 0;
        try {
            $request->setUserId(intval($userInfo->userId));
            $request->setBeginTime($beginTime);
            $request->setEndTime($endTime);
            $request->setOffset(intval($data['offset']));
            $request->setCount($count);
            $request->setType($type);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDealLoanRepay',
            'method' => 'getRepayList',
            'args' => $request
        ));

        /**
          if (!is_array($response['list']) || count($response['list']) < 1) {
          $this->errorCode = -1;
          $this->errorMsg = "get user repay failed";
          return false;
          }
         * */
        $ret['list'] = $this->data_format($response['list'], $type);
        $ret['counts'] = $response['counts'];
        $this->json_data = $ret;
    }

    /**
     * 格式化数据
     * @param unknown $data
     * @param unknown $type
     * @return NULL|Ambigous <multitype:, unknown>
     */
    protected function data_format($data, $type) {
        if (!$data) {
            return Null;
        }
        //print_r($data);
        $arr = array();
        foreach ($data as $k => $v) {
            $arr[$k]['productID'] = $v['deal_id'];
            $arr[$k]['name'] = $v['deal_name'];
            $arr[$k]['time'] = strtotime($v['time']);
            $arr[$k]['real_time'] = strtotime($v['real_time']);
            $arr[$k]['type'] = $v['money_type'];
            $arr[$k]['status'] = $v['repay_status'];
            $arr[$k]['money'] = $v['money'];
            $arr[$k]['position'] = $type;
        }
//        if($type == 1){
////             rsort($arr);
//            $arr = $this->data_sort($arr);
//        }
        return $arr;
    }

    /**
     * data 排序
     * @param unknown $data
     * @return multitype:unknown
     */
    protected function data_sort($data) {
        $c = count($data);
        $arr = array();
        foreach ($data as $k => $v) {
            $arr[] = $data[$c - $k - 1];
        }
        return $arr;
    }

}
