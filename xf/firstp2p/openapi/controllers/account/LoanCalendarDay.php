<?php
/**
 * 回款计划日历查看某天详情
 * @author zhaohui3@ucfgroup.com
 * @date 2016-7-22
 **/

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

class LoanCalendarDay extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            'date'=>array("filter"=>'string', 'option' => array('optional' => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userResponse = $this->getUserByAccessToken();
        if (empty($userResponse)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        $date = $data['date'];
        $uid = $userResponse->getUserId();
        $time = to_timespan($date,'Y-m-d');
        $result = $this->rpc->local('DealLoanRepayService\getRepayDealSumaryByTime',array($uid,$time));
        $this->json_data = $this->data_format($result);
    }

    /**
     * 排序规则
     * （同状态根据回款时间先后排序 新->旧）：
        1，待回款
        2，已回款
        3，提前回款
     * @param $data
     * @return mixed
     */
    protected function data_format($data){
        $return = array();
        foreach($data as $k=>$v) {
            $key = 0;
            if($v['data']['is_prepay']) {
                $key = '1' . $v['data']['real_time']; // 提前
            }elseif($v['data']['status'] == 1){
                $key = '2' .$v['data']['real_time']; // 已回
            }else{
                $key = '3' . $v['data']['time'];//待回
            }
            $key= $key + $v['deal_id'];
            $v['data']['principal'] = number_format($v['data']['principal'],2);
            $v['data']['interest'] = number_format($v['data']['interest'],2);
            $v['data']['damage'] = number_format($v['data']['damage'],2);
            $v['data']['overdue'] = number_format($v['data']['overdue'],2);
            $return[$key] = $v;
        }
        krsort($return,SORT_NUMERIC);
        return array_values($return);
    }
}
