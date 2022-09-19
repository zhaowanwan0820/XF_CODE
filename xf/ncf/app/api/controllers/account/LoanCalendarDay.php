<?php
/**
 * 回款计划日历查看某天详情
 * @author jinhaidong@ucfgroup.com
 * @date 2016-3-29 16:07:52
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\deal\DealLoanRepayService;

class LoanCalendarDay extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter"=>"required"),
            'date'=>array("filter"=>'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $user = $this->user;
        $data = $this->form->data;
        $time = to_timespan($data['date'], 'Y-m-d');
        $result = DealLoanRepayService::getRepayDealSumaryByTime($user['id'], $time);
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
