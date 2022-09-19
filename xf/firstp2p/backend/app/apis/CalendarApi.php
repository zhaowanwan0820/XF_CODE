<?php

namespace NCFGroup\Ptp\Apis;

use core\service\DealLoanRepayCalendarService;
use core\service\ncfph\AccountService;

/**
 * 信仔机器人回款接口
 */
class CalendarApi{

    private $params;

    private function init(){
        $di = getDI();
        $this->params = $di->get('requestBody');
        if (empty($this->params['userId'])) {
            throw new \Exception("缺少参数 userId");
        }
    }

    public function bubble() {
        try{
            $this->init();
        }catch (\Exception $ex){
            return array('errorCode' => -1, 'errorMsg' => $ex->getMessage(), 'data' => array());
        }

        $userId = $this->params['userId'];
        $day = $this->params['day'];

        if (empty($day) || $day < 0) {
            $day = false;
        }

        $returnData = array('errorCode' => 0, 'errorMsg' => 'success', 'data' => array());

        $service = new DealLoanRepayCalendarService();

        $beginYear = date('Y');
        $beginMonth = date('n');
        $beginDay = date('j');
        $wxData = $service->getUserNoRepayCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
        $phAccountService = new AccountService();
        $phData = $phAccountService->getUserNoRepayCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
        $data['norepay_principal'] = bcadd($wxData['norepay_principal'], $phData['norepay_principal'], 2);
        $data['norepay_interest'] = bcadd($wxData['norepay_interest'], $phData['norepay_interest'], 2);
        $totalMoney = bcadd($data['norepay_principal'],$data['norepay_interest'],2);
        $returnData['data']['content'] = $totalMoney > 0 ? "您即将有".number_format($totalMoney,2)."元回款哦" : "";
        return $returnData;
    }

    public function msg(){
        try{
            $this->init();
        }catch (\Exception $ex){
            return array('errorCode' => -1, 'errorMsg' => $ex->getMessage(), 'data' => array());
        }
        $userId = $this->params['userId'];
        $day = $this->params['day'];

        if (empty($day) || $day < 0) {
            $day = false;
        }

        $s = new DealLoanRepayCalendarService();
        $phAccountService = new AccountService();

        $beginYear = date('Y');
        $beginMonth = date('n');
        $beginDay = date('j');

        $wxData = $s->getUserNoRepayCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
        $phData = $phAccountService->getUserNoRepayCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
        $noRepayData = array();
        $noRepayData['norepay_principal'] = bcadd($wxData['norepay_principal'], $phData['norepay_principal'], 2);
        $noRepayData['norepay_interest'] = bcadd($wxData['norepay_interest'], $phData['norepay_interest'], 2);

        $totalNum = 0;
        //如果有数据
        if( $noRepayData['norepay_principal'] > 0 || $noRepayData['norepay_interest'] > 0 ) {
            $wxLatestData = $s->getUserRecentCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
            $phLatestData = $phAccountService->getUserRecentCalendar($userId,$beginYear,$beginMonth,$beginDay,$day);
            $formatData = $this->_formatData($wxLatestData,$phLatestData);
            $totalNum = count($formatData);
            if($totalNum > 0) {
                $latestData = array_shift($formatData);
                if( $noRepayData['norepay_principal'] >  $latestData['norepay_principal'] || $noRepayData['norepay_interest'] >  $latestData['norepay_interest']) {
                    $totalNum = $totalNum>2 ? $totalNum : 2;
                }
            }
        }

        if($totalNum == 1){
            $date = $latestData['year']."年".$latestData['month']."月".$latestData['day']."日";
            $msg = "您在".$date."有回款，本金 <span>".number_format($noRepayData['norepay_principal'],2).
                "元</span>，利息 <span>".number_format($noRepayData['norepay_interest'],2)."元</span>。";
            $buttonTitle = "查看回款日历";
            $title = "为您找到的回款信息";
            $uri = '{"type":24}';
        }elseif($totalNum > 1){
            $date = $latestData['year']."年".$latestData['month']."月".$latestData['day']."日";
            $msg = "您的回款合计本金<span>".
                number_format($noRepayData['norepay_principal'],2).
                "元</span>，合计利息<span>".number_format($noRepayData['norepay_interest'],2).
                "元</span>。最近回款在".$date.
                "，本金<span>".number_format($latestData['norepay_principal'],2).
                "元</span>，利息<span>".number_format($latestData['norepay_interest'],2)."元</span>。";
            $buttonTitle = "查看回款日历";
            $title = "为您找到的回款信息";
            $uri = '{"type":24}';
        }else{
            $msg = "您当前没有要回款的款项，建议去投资一把赚取收益哦~";
            $buttonTitle = "去投资";
            $title = "为您找到的回款信息";
            $uri = '{"type":22}';
        }

        $returnData = array('errorCode' => 0, 'errorMsg' => 'success', 'data' => array());
        $returnData['data'] =  array(
            'content' => array(
                'actionList' => array(
                    array('imageUrl' => '', 'title' => $buttonTitle, 'type' => 1, 'uri' => $uri)
                ),
                'columnNum' => 1,
                'title' => $msg,
            ),
            'title' => $title,
            'type' => 1,
        );
        return $returnData;
    }

    private function _formatData($wxData, $phData)
    {
        $data = array();
        foreach ($wxData as $k => $v) {
            if(isset($phData[$k])) {
                $v['norepay_principal'] = bcadd($v['norepay_principal'], $phData[$k]['norepay_principal'], 2);
                $v['norepay_interest'] = bcadd($v['norepay_interest'], $phData[$k]['norepay_interest'], 2);
                unset($phData[$k]);
            }
            if( bccomp($v['norepay_principal'],0,2) ==1 || bccomp($v['norepay_interest'],0,2) ==1 ) {
                $data[$k] = $v;
            }
        }
        foreach ($phData as $k => $v) {
            if( bccomp($v['norepay_principal'],0,2) ==1 || bccomp($v['norepay_interest'],0,2) ==1 ) {
                $data[$k] = $v;
            }
        }
        ksort($data);
        return $data;
    }
}
