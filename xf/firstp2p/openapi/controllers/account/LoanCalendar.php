<?php
/**
 * 回款计划日历
 * @author zhaohui3@ucfgroup.com
 * @date 2016-7-21
 **/

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;

class LoanCalendar extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
                "oauth_token" => array("filter"=>"required","message" => "oauth_token is required"),
                'year'=>array("filter"=>'required'),
                'month'=>array("filter"=>'required'),
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
        $year = intval($data['year']);
        $month = intval($data['month']);

        $uid = $userResponse->getUserId();
        $result = $this->rpc->local('DealLoanRepayCalendarService\getDealLoanRepayCalendar', array($uid, $year, $month, 'openapi'));
        $this->json_data = $this->data_format($result);
    }

    /**
     * 格式化数据
     * @param Array $data
     */
    protected function data_format($data){
        if(!$data){
            return Null;
        }

        $arr = array('total'=>0.00,'repay'=>0.00,'data'=>array());
        $norepayData = array();
        $repayData = array();

        foreach($data as $k=>$v){
            $tmpTotal = 0;
            $tmpRepay = 0;
            if($v['norepay_interest'] > 0 || $v['norepay_principal'] > 0) {
                $money = bcadd($v['norepay_interest'],$v['norepay_principal'],2);
                $norepayData[$v['repay_day']] = array(
                    'day' => $v['repay_day'],
                    'status' => 0, // 未还
                    'money' => number_format($money,2),
                );
                // 存在未还款同时可能已经存在已还款，需要把这部分加上
                $tmpTotal= \libs\utils\Finance::addition(array($v['repay_principal'],$v['repay_interest'],$v['prepay_principal'],$v['prepay_interest']),2);
                $tmpTotal = bcadd($tmpTotal,$money,2);
                $tmpRepay= \libs\utils\Finance::addition(array($v['repay_principal'],$v['repay_interest'],$v['prepay_principal'],$v['prepay_interest']),2);
            }else{
                $money = \libs\utils\Finance::addition(array($v['repay_interest'],$v['repay_principal'],$v['prepay_principal'],$v['prepay_interest']),2);
                $repayData[$v['repay_day']] = array(
                    'day' => $v['repay_day'],
                    'status' => 1, // 已还
                    'money' => number_format($money,2),
                );
                $tmpRepay = $money;
                $tmpTotal = $money;
            }
            $arr['repay'] = bcadd($arr['repay'],$tmpRepay,2);
            $arr['total'] = bcadd($arr['total'],$tmpTotal,2);

        }
        sort($norepayData); // 待还正序
        rsort($repayData); // 已还倒序

        $arr['data'] = array_merge($norepayData,$repayData);
        $arr['total'] = number_format($arr['total'],2);
        $arr['repay'] = number_format($arr['repay'],2);
        return $arr;
    }
}