<?php
/**
 * 用户资金记录月账单
 * @author jinhaidong@ucfgroup.com
 * @date 2016-7-19 14:58:46
 **/

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;

class UserMonthSummary extends AppBaseAction {

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
        $year = intval($data['year']) ? intval($data['year']) : date('Y');
        $month = intval($data['month']) ? intval($data['month']) : date('m');

        $startTime = to_timespan($year."-".$month."-01");
        $endTime = to_timespan(date($year."-".$month."-01") . ' +1 month') - 1;
        $startNoTime = strtotime($year."-".$month."-01");
        $endNo8Time = strtotime(date($year."-".$month."-01") . ' +1 month') - 1; // 无8小时差别的时间
        $uid = $user['id'];
        // 当月结余 当月最后一笔资金记录的 remaining_total_money
        $result = $this->rpc->local('UserLogService\getUserSummaryByTime', array($uid, $startTime, $endTime));
        $result['remaining_total_money'] = $this->rpc->local('UserLogService\getUserReaminMoney', array($uid,$endTime));
        $result['month_list'] = $this->user_month_list($user);

        //用户黄金数据
        $result['gold_withdraw'] = $this->rpc->local('GoldService\getTotalLogInfoByTime', array($uid,$startNoTime,$endNo8Time,'提金'));
        $result['gold_ycj_earn'] = $this->rpc->local('GoldService\getTotalLogInfoByTime', array($uid,$startNoTime,$endNo8Time,'黄金收益克重'));

        // 用户投资的公益标
        $result['crowdfunding'] = $this->rpc->local('DealService\getUserCrowdfundingMoneyByTime', array($uid,$startTime,$endTime));
        $this->json_data = $this->data_format($result,$endNo8Time);
    }

    private function user_month_list($user) {
        $now = date('Y-m-d');
        $regTime = to_date($user['create_time'],'Y-m-d');

        $m =  $this->getMonthNum($now,$regTime);

        $n = $m >5 ? 5 : $m;

        // strtotime('-x month'); 在涉及到月份修改的时候，可能不会得到预料的结果 https://bugs.php.net/bug.php?id=27793
        $standardTime = strtotime(date('Y-m',time()) . '-01 00:00:01');
        $mlist[] = date('Y年n月');
        for($i=1;$i<=$n;$i++) {
            $mlist[] = date('Y年n月',strtotime("-{$i} month",$standardTime));
        }
        return $mlist;
    }

    private function getMonthNum( $date1, $date2, $tags='-'){
        $time1 = strtotime($date1);
        $time2 = strtotime($date2);
        $date1 = explode($tags,$date1);
        $date2 = explode($tags,$date2);
        $months =abs($date1[0]-$date2[0])*12;
        if($time1 > $time2){
            return $months+$date1[1]-$date2[1];
        }else{
            return -($months+$date2[1]-$date1[1]);
        }
    }

    /**
     * 格式化数据
     * @param Array $data
     */
    protected function data_format($data,$selectTime){
        $result = array(
            'remaining_total_money' => $data['remaining_total_money'],
            'month_list' => $data['month_list'],
            'current_year' => date('Y'),
            'current_month' => date('m'),
            'p2p' => array(// p2p
                'principal' => array(
                    'repay' => 0, // 回款本金
                    'bid' => 0,   // 投资
                ),
                'interest' => array(// 投资收益
                    'bid' => array(
                        'total' => 0,
                        'detail' => array(),
                    ),
                    'rebate' => array(// 返利收益
                        'total' => 0,
                        'detail' => array(),
                    )
                ),
            ),
            'fund' => array( // 基金
                'buy' => 0,  // 申购
                'redeem' =>0,// 赎回
            ),
            'balance' => array( // 充值提现情况
                'pay' => 0,      // 充值
                'withdraw' => 0, // 提现
            ),
            'others' => array( // 其它
                'gy' => 0, //公益标
                'dtFee' => 0, // 多投手续费
            ),
        );

        $nodeTime = strtotime('2017-06-01');

        $p2pPrincipalRepay = array('提前还款本金','还本','转让本金'); // p2p 到账本金
        $p2pPrincipalBid = array('投资放款','投资扣款');// p2p 本金支出 多投宝叫投资扣款
        $p2pInterestBid = array('提前还款利息','提前还款补偿金','付息','支付收益','超额收益'); // p2p 投资收益 支付收益 是多投宝结息

        // p2p 投资收益 支付收益 是多投宝结息
        $p2pInterestRebate = $selectTime < $nodeTime ? array('返现券返利','加息券返利','邀请返利','投资返利','平台贴息','贴息')
            : array('使用红包充值');

        $fundBuy = array('基金申购成功'); // 基金申购
        $fundRedeem = array('基金赎回','基金到账'); // 基金赎回
        $balancePay = array('充值');//充值
        $balanceWithdraw = array('提现成功'); // 提现
        $dtManageFee = array('支付管理费'); // 多投管理费



        // jira: PTPIOS-3333
        $gold = array(
            'buy' => 0, // 买金 --资金记录-买金货款划转
            'to_cash' => 0, // 变现  资金记录--黄金变现
            'withdraw' => $data['gold_withdraw'], // 提金 交易记录--提金
            'service_fee' => 0, // 手续费 资金记录--买金手续费+黄金变现手续费+提金手续费
            'yjb_earn' => 0, //优金宝收益 资金记录--黄金收益
            'ycj_earn' => $data['gold_ycj_earn'], //优长金收益 交易记录--黄金收益克重
        );

        $goldBuy = array('买金货款划转','买金');
        $goldToCash = array('黄金变现');
        $goldWithdraw = array('提金');
        $goldServiceFee = array('买金手续费','黄金变现手续费','提金手续费');
        $goldYjbEarn = array('黄金收益');
        $goldYcjEarn = array('黄金收益克重');

        foreach($data as $row) {
            $logInfo = !empty($row['log_info']) ? $row['log_info'] : '';
            if(in_array($logInfo,$p2pPrincipalRepay)) {
                $result['p2p']['principal']['repay'] = bcadd($result['p2p']['principal']['repay'],$row['m'],2);
            }
            if(in_array($logInfo,$p2pPrincipalBid)) {
                $result['p2p']['principal']['bid'] = bcadd($result['p2p']['principal']['bid'],$row['lm'],2);
            }
            // p2p收益
            if(in_array($logInfo,$p2pInterestBid)) {
                if($logInfo == '支付收益') {
                    $logInfo='支付收益(智多新)';
                }
               if($logInfo == '付息') {
                    $logInfo='支付收益';
                }
                if($logInfo == '提前还款利息') {
                    $logInfo='提前还款付息/收益';
                }
                $result['p2p']['interest']['bid']['total'] = bcadd($result['p2p']['interest']['bid']['total'],$row['m'],2);
                $result['p2p']['interest']['bid']['detail'][]=array(
                    'name'=>$logInfo,
                    'money'=>$row['m'],
                );
            }
            if(in_array($logInfo,$p2pInterestRebate)) {
                if($logInfo == '邀请返利'){
                    $logInfo = '邀请奖励';
                }
                $result['p2p']['interest']['rebate']['total'] = bcadd($result['p2p']['interest']['rebate']['total'],$row['m'],2);
                $result['p2p']['interest']['rebate']['detail'][]=array(
                    'name'=>($logInfo == '使用红包充值') ? '奖励收益' : $logInfo,
                    'money'=>$row['m'],
                );
            }

            // 基金赎回到账
            if(in_array($logInfo,$fundBuy)) {
                $result['fund']['buy'] = bcadd($result['fund']['buy'],$row['lm'],2);
            }
            if(in_array($logInfo,$fundRedeem)) {
                $result['fund']['redeem'] = bcadd($result['fund']['redeem'],$row['m'],2);
            }

            // 充值提现
            if(in_array($logInfo,$balancePay)) {
                $result['balance']['pay'] = bcadd($result['balance']['pay'],$row['m'],2);
            }
            if(in_array($logInfo,$balanceWithdraw)) {
                $result['balance']['withdraw'] = bcadd($result['balance']['withdraw'],$row['lm'],2);
            }

            if(in_array($logInfo,$dtManageFee)) {
                $result['others']['dtFee'] = bcadd($result['others']['dtFee'],$row['m'],2);
            }

            // 黄金相关
            if(in_array($logInfo,$goldBuy)){
                if($logInfo == '买金货款划转'){
                    $goldBuyMoney = $row['lm'];
                }else{
                    $goldBuyMoney = $row['m'];
                }
                $gold['buy'] = bcadd($gold['buy'],$goldBuyMoney,2);
            }
            if(in_array($logInfo,$goldToCash)){
                $gold['to_cash'] = bcadd($gold['to_cash'],$row['m'],2);
            }
            if(in_array($logInfo,$goldServiceFee)){
                $gold['service_fee'] = bcadd($gold['service_fee'],$row['m'],2);
            }
            if(in_array($logInfo,$goldYjbEarn)){
                $gold['yjb_earn'] = bcadd($gold['yjb_earn'],$row['m'],2);
            }
        }


        if($gold['buy'] !=0 ) {
            $result['gold'][] = array('type' => 1,'title' => '买金','detail'=>$gold['buy'],'unit' => '元');
        }
        if($gold['to_cash'] > 0){
            $result['gold'][] = array('type' => 2,'title' => '变现','detail'=>$gold['to_cash'],'unit' => '元');
        }
        if($gold['withdraw'] > 0){
            $result['gold'][] = array('type' => 3,'title' => '提金','detail'=>$gold['withdraw'],'unit'  => '克');
        }
        if($gold['service_fee'] != 0){
            $result['gold'][] = array('type' => 4,'title' => '手续费','detail'=>$gold['service_fee'],'unit' => '元');
        }
        if($gold['yjb_earn'] > 0){
            $result['gold'][] = array('type' => 5,'title' => '优金宝收益','detail'=>$gold['yjb_earn'],'unit' => '元');
        }
        if($gold['ycj_earn'] > 0){
            $result['gold'][] = array('type' => 6,'title' => '优长金收益','detail'=>$gold['ycj_earn'],'unit' => '克');
        }

        if($data['crowdfunding']>0) {
            $result['others']['gy'] = -$data['crowdfunding'];
            $result['p2p']['principal']['bid'] +=$data['crowdfunding']; // 产品要求网贷支持不能包含公益标
        }
        return $result;
    }
}
