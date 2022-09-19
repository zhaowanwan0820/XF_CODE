<?php
/**
 * 月账单
 */

namespace task\apis\account;

use task\lib\ApiAction;

class UserMonthSummary extends ApiAction
{
    public function invoke()
    {
    	$param = $this->getParam();
    	$userId = $param['userId'];

        $year = intval($param['year']) ? intval($param['year']) : date('Y');
        $month = intval($param['month']) ? intval($param['month']) : date('m');

        $startTime = to_timespan($year."-".$month."-01");
        $endTime = to_timespan(date($year."-".$month."-01") . ' +1 month') - 1;
        $startNoTime = strtotime($year."-".$month."-01");
        $endNo8Time = strtotime(date($year."-".$month."-01") . ' +1 month') - 1; // 无8小时差别的时间

        // 当月结余 当月最后一笔资金记录的 remaining_total_money
        $result = $this->rpc->local('UserLogService\getUserSummaryByTime', array($uid, $startTime, $endTime));
        $result['remaining_total_money'] = $this->rpc->local('UserLogService\getUserReaminMoney', array($uid,$endTime));
        $result['month_list'] = $this->user_month_list($user);

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
        );

        $nodeTime = strtotime('2017-06-01');

        $p2pPrincipalRepay = array('提前还款本金','还本','转让本金'); // p2p 到账本金
        $p2pPrincipalBid = array('投资放款','投资扣款');// p2p 本金支出 多投宝叫投资扣款
        $p2pInterestBid = array('提前还款利息','提前还款补偿金','付息','支付收益','超额收益'); // p2p 投资收益 支付收益 是多投宝结息

        // p2p 投资收益 支付收益 是多投宝结息
        $p2pInterestRebate = $selectTime < $nodeTime ? array('返现券返利','加息券返利','邀请返利','投资返利','平台贴息','贴息')
            : array('使用红包充值');

        $balancePay = array('充值');//充值
        $balanceWithdraw = array('提现成功'); // 提现
        $dtManageFee = array('支付管理费'); // 多投管理费


        foreach($data as $row) {
            $logInfo = $row['log_info'];
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
        }



        if($data['crowdfunding'] > 0) {
            $result['others']['gy'] = -$data['crowdfunding'];
            $result['p2p']['principal']['bid'] +=$data['crowdfunding']; // 产品要求网贷支持不能包含公益标
        }
        return $result;
    }

}

