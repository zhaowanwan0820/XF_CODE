<?php
/**
 * 回款计划日历
 * @author jinhaidong@ucfgroup.com
 * @date 2016-3-29 16:07:52
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;

class LoanCalendar extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
                "token" => array("filter"=>"required"),
                'year'=>array("filter"=>'int', 'option' => array('optional' => true)),
                'month'=>array("filter"=>'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        $year = intval($data['year']);
        $month = intval($data['month']);

        $uid = $user['id'];
        $result = $this->rpc->local('DealLoanRepayCalendarService\getDealLoanRepayCalendar', array($uid, $year, $month, 'api'));

        $ncfphData = (new \core\service\ncfph\AccountService())->getDealLoanRepayCalendar($uid, $year, $month, 'api');
        $result = $this->mergeP2PData($result, $ncfphData);

        $needFileds = ['norepay_interest', 'repay_interest', 'norepay_principal', 'repay_principal', 'prepay_principal', 'prepay_interest'];
        foreach($result as $k=>$v){
            $unset = true;
            foreach($needFileds as $kk=>$vv){
                if(bccomp($v[$vv],0,2) != 0){
                    $unset = false;
                    break;
                }
            }
            if($unset){
                unset($result[$k]);
            }
        }
        $this->json_data = $this->data_format($result);
    }

    private function mergeP2PData($wxData, $p2pData)
    {
        $tmpWxData = $tmpP2pData = [];
        foreach ($wxData as $key => $value) {
            $tmpWxData[$value['repay_day']] = $value;
        }
        foreach ($p2pData as $key => $value) {
            $tmpP2pData[$value['repay_day']] = $value;
        }

        $mergeData = [];
        $mergeFileds = ['norepay_interest', 'repay_interest', 'norepay_principal', 'repay_principal', 'prepay_principal', 'prepay_interest'];
        for ($day = 1; $day <= 31; $day++) {
            if (isset($tmpWxData[$day]) && isset($tmpP2pData[$day])) {
                foreach ($mergeFileds as $field) {
                    $tmpWxData[$day][$field] = bcadd($tmpWxData[$day][$field], $tmpP2pData[$day][$field], 2);
                }
                $mergeData[$day] = $tmpWxData[$day];
                $mergeData[$day]['repay_day'] = $day;
            } elseif (isset($tmpWxData[$day])) {
                $mergeData[$day] = $tmpWxData[$day];
                $mergeData[$day]['repay_day'] = $day;
            } elseif (isset($tmpP2pData[$day])) {
                $mergeData[$day] = $tmpP2pData[$day];
                $mergeData[$day]['repay_day'] = $day;
            }
        }

        return $mergeData;
    }

    /**
     * 格式化数据
     * @param Array $data
     */
    protected function data_format($data){
        if(!$data){
            return Null;
        }

        $arr = array(
            'total'=>0.00,
            'repay'=>0.00,
            'repayDetail'=>array(
                'principal'=>0.00,
                'interest'=>0.00,
            ),
            'norepayDetail'=>array(
                'principal'=>0.00,
                'interest'=>0.00,
            ),
            'data'=>array()
        );
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
                $arr['norepayDetail']['principal'] = bcadd($arr['norepayDetail']['principal'],$v['norepay_principal'],2);
                $arr['norepayDetail']['interest'] = bcadd($arr['norepayDetail']['interest'],$v['norepay_interest'],2);
            }else{
                $money = \libs\utils\Finance::addition(array($v['repay_interest'],$v['repay_principal'],$v['prepay_principal'],$v['prepay_interest']),2);
                $repayData[$v['repay_day']] = array(
                    'day' => $v['repay_day'],
                    'status' => 1, // 已还
                    'money' => number_format($money,2),
                );
                $tmpRepay = $money;
                $tmpTotal = $money;
                $arr['repayDetail']['principal'] = bcadd($arr['repayDetail']['principal'],$v['repay_principal'],2);
                $arr['repayDetail']['principal'] = bcadd($arr['repayDetail']['principal'],$v['prepay_principal'],2);
                $arr['repayDetail']['interest'] = bcadd($arr['repayDetail']['interest'],$v['repay_interest'],2);
                $arr['repayDetail']['interest'] = bcadd($arr['repayDetail']['interest'],$v['prepay_interest'],2);
            }
            $arr['repay'] = bcadd($arr['repay'],$tmpRepay,2);
            $arr['total'] = bcadd($arr['total'],$tmpTotal,2);
        }
        sort($norepayData); // 待还正序
        rsort($repayData); // 已还倒序

        $arr['data'] = array_merge($norepayData,$repayData);
        $arr['total'] = number_format($arr['total'],2);
        $arr['repay'] = number_format($arr['repay'],2);
        $arr['repayDetail']['principal'] = number_format($arr['repayDetail']['principal'],2);
        $arr['repayDetail']['interest'] = number_format($arr['repayDetail']['interest'],2);
        $arr['norepayDetail']['principal'] = number_format($arr['norepayDetail']['principal'],2);
        $arr['norepayDetail']['interest'] = number_format($arr['norepayDetail']['interest'],2);
        return $arr;
    }
}
