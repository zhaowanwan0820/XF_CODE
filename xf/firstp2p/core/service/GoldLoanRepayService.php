<?php
/**
 * 黄金标用户回款相关操作
 * @data 2017.05.22
 * @author 晓安
 */


namespace core\service;

use core\dao\DealModel;
use core\dao\UserModel;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use app\models\service\GoldFinance;
use core\service\GoldService;
use core\service\GoldDealService;

class GoldLoanRepayService extends GoldService{


    /**
     * 生成用户的回款计划
     * @param int $deal_id
     *
     */
    public function makeLoanRepay($deal_id){

        $request = new RequestCommon();
        $request_data = array(
            'deal_id' => $deal_id
        );
        $request->setVars($request_data);

        try {
            // 获取标信息
            $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getUnderlineDealById', $request);
            if (empty($response['data'])){
                throw new \Exception("获取gold rpc 标id为'.$deal_id .'信息为空");
            }
        }catch (\Exception $e) {
            throw new \Exception("获取gold rpc 标id为'.$deal_id .'信息失败");
            Logger::error('标id为'.$deal_id .$e->getMessage());
            return false;
        }


        $dealInfo = $response['data'];
        $GLOBALS['db']->startTrans();
        try {
            $ret = $this->createDealRepayListSub($dealInfo['loantype'], $dealInfo['repay_start_time'],$dealInfo['repay_time'], $dealInfo);
            if ($ret == false) {
                throw new \Exception("生成用户回款失败 ".$dealInfo['id']);
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            Logger::error('标id为'.$deal_id .$e->getMessage());
            return false;
        }

        return true;

    }

    public function createDealRepayListSub($loantype,$repay_start_time,$repay_time,$dealInfo){
        //$repay_time = $repay_start_time;
        if (!$repay_time || empty($repay_start_time)) {
            throw new \Exception("repay start time error");
        }


        $goldDealService = new GoldDealService();
        // 放款时已算好起息日并更新了
       // $repay_start_time = $goldDealService->getInterestDate($repay_start_time);

        $repay_cycle = $this->getRepayCycle($loantype,$repay_time);
        $repay_times = $this->getRepayTimes($loantype,$repay_time);

       // $first_repay_day = $deal_ext['first_repay_interest_day'];
        /*
        $loan_fee_arr = $deal_ext['loan_fee_ext'] ? json_decode($deal_ext['loan_fee_ext'], true) : array();
        $consult_fee_arr = $deal_ext['consult_fee_ext'] ? json_decode($deal_ext['consult_fee_ext'], true) : array();
        $guarantee_fee_arr = $deal_ext['guarantee_fee_ext'] ? json_decode($deal_ext['guarantee_fee_ext'], true) : array();
        $pay_fee_arr = $deal_ext['pay_fee_ext'] ? json_decode($deal_ext['pay_fee_ext'], true) : array();
        */
        // 按照期限
        if ($repay_times) {
            for($i = 0; $i < $repay_times; $i++) {

                $repayDay = $this->getRepayDay($repay_start_time,$repay_cycle,$loantype,$i+1);
                try {
                    $is_last = ($i+1 == $repay_times) ? true:false;
                    $this->createUserLoan($repayDay, $dealInfo,$is_last,$i+1);
                }catch (\Exception $e){
                    throw new \Exception("生成用户回款计划失败");
                    return false;
                }
            }
        }

        // 更新放款状态为已放款
        $function = "\core\service\GoldLoanRepayService::updateCompleteStatus";
        $param = array('deal_id' => $dealInfo['id'],'status' => 1);
        $job_model = new \core\dao\JobsModel();
        $job_model->priority = \core\dao\JobsModel::PRIORITY_GOLD_MAKE_LOAN_REPAY_COMPLETE_STATUS;
        $add_job = $job_model->addJob($function, $param, get_gmtime(),9999);
        if (!$add_job) {
            throw new \Exception("添加更新标放款完成状态失败");
            return false;
        }

        return true;

    }

    /**
     * 创建用户回款
     */
    public function createUserLoan($repayDay,$dealInfo,$is_last,$periods_index){
        $request = new RequestCommon();
        $request->setVars(array('id' => $dealInfo['id'],'id_desc' => 1));
        $response = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadByDealId', $request);
        if (empty($response['data'])){
            throw new \Exception("获取标所有投资记录失败");
        }
        $loan_list = $response['data'];

        foreach($loan_list as $v){
            $function = "\core\service\GoldLoanRepayService::create";
            $param = array('deal_load_info' => $v,'deal_info' => $dealInfo,'repay_day' => $repayDay,'is_last' =>$is_last, $periods_index);
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = \core\dao\JobsModel::PRIORITY_GOLD_MAKE_LOAN_REPAY;
            $add_job = $job_model->addJob($function, $param, get_gmtime(),9999);
            if (!$add_job) {
                throw new \Exception("添加用户回款计划任务失败");
            }
        }

        return true;
    }

    /**
     * 往gold写入回款计划
     */
    public function create($deal_load_info,$deal_info,$repay_day,$is_last,$periods_index){

        $request = new RequestCommon();
        $time = time();
        $inset_info = array(
        );
        // 补偿克重
        $result = $this->getRepayMoney($deal_load_info['buyAmount'],$is_last,true,false,$periods_index,$deal_info);
        $inset_info[] = array(
            'money' => $result['principal'],// 本克重
            'type' => 1,
            'dealRepayId' => $deal_info['user_id'],
            'dealLoanId' => $deal_load_info['id'],
            'loanUserId' => $deal_load_info['userId'],
            'dealId' => $deal_info['id'],
            'borrowUserId' => $deal_info['user_id'],
            'time' => $repay_day,
            'status' => 0,// 未还
            'realTime' => 0,
            'dealType' => 0,
            'createTime' => $time,
            'updateTime' => $time
        );
        $inset_info[] = array(
                'money' => $result['interest'],// 本克重
                'type' => 2,
                'dealRepayId' => $deal_info['user_id'],
                'dealLoanId' => $deal_load_info['id'],
                'loanUserId' => $deal_load_info['userId'],
                'dealId' => $deal_info['id'],
                'borrowUserId' => $deal_info['user_id'],
                'time' => $repay_day,
                'status' => 0,// 未还
                'realTime' => 0,
                'dealType' => 0,
                'createTime' => $time,
                'updateTime' => $time

        );
        $request->setVars(array('data' => $inset_info));
        try {
            $response = $this->requestGold('NCFGroup\Gold\Services\DealRepay', 'createLoanRepay', $request);

        }catch (\Exception $e){
            throw new \Exception("调用gold写入用户回款计划方法失败");
            return false;
        }
        if (empty($response['data'])){
            throw new \Exception("调用gold写入回款计划返回失败");
            return false;
        }
        return true;
    }

    public function updateCompleteStatus($deal_id,$status){

        if (empty($deal_id) || empty($status)){
            return false;
        }

        $update_dealInfo['dealId'] = $deal_id;
        $update_dealInfo['has_loan_status'] = $status;
        $request = new RequestCommon();
        $request->setVars($update_dealInfo);

        $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'updateCompleteStatus', $request);

        if (empty($response['data'])){
            throw new \Exception("更新标放款完成失败");
            return false;
        }
        // 监控
        \libs\utils\Monitor::add('GOLD_DEAL_MAKE_LOANS_SUCCESS');
        return true;

    }
    /**
     * 计算两次还款的间隔周期, 根据不同的还款方式，结果可能是月份或者天数
     *
     * @return integer
     **/
    public function getRepayCycle($loantype,$repay_start_time)
    {
        $repay_cycle= 0;
        if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_cycle = 3;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_cycle = 1;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_cycle = $repay_start_time;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_cycle = 1;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_cycle = $repay_start_time;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_cycle = 3;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $repay_cycle = 1;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_cycle = 1;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $repay_cycle = 3;
        }
        return $repay_cycle;
    }

    /**
     * 根据放款时间和期数计算还款时间
     * @param int $time 放款时间
     * @param int $repay_cycle 还款周期，月数/天数
     * @param int $loantype 还款方式
     * @param int $i 期数
     * @return int 还款时间
     */
    public function getRepayDay($repay_start_time, $repay_cycle, $loantype, $i=1) {
        $y = date("Y",$repay_start_time);
        $m = date("m",$repay_start_time);
        $d = date("d",$repay_start_time);

        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return strtotime($y."-".$m."-".$d) + $repay_cycle*24*60*60;
        }elseif($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $add_month_num = $i*$repay_cycle;
            return strtotime(date("Y-m-d H:i:s", strtotime("+ {$add_month_num} months", strtotime(date($repay_start_time,"Y-m-d H:i:s")))));
        }else{
            $target_m = $m + $repay_cycle * $i;

            $year = floor($target_m / 12);
            $y += $year;

            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
                $y--; // 当target_m=24时，$year=2，但是实际上并没发生跨年，于是在这里$y--;
            }

            $target = strtotime($y."-".$m."-".$d);
            if ($d != date('d',$target)) {
                $target = strtotime(date( 'Y',$target) . '-' . date('m',$target) . '-1', 'Y-m-d');
            }
            return $target;
        }
    }

    /**
     * 计算每期还款本金和利息以及总额
     *
     * @param boolen $is_last 是否最后一期
     * @param flaot $total_principal 本金总额
     * @param int $is_loan 0还款 1回款
     * @param int $interest_day 计息天数，仅按月付息、按季付息有效
     * @param int $periods_index 期数
     *
     * @return array 示例:array('total'=>111,'interest'=>222, 'principal'=>333)
     * total: 本期总还款额 interest: 本期利息  principal: 本期本金
     **/
    public function getRepayMoney($total_principal, $is_last = false, $is_loan = true, $interest_day = false, $periods_index=0,$dealInfo) {
        $rate = $is_loan ? $dealInfo['rate'] : 0;
        $result = array();
        $repay_times = $this->getRepayTimes($dealInfo['loantype'],$dealInfo['repay_time']);
        $result['principal'] = $repay_times == 1 ? $total_principal : $total_principal / $repay_times;  // 计算每期本金
        $loantype = $dealInfo['loantype'];
        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            if ($interest_day !== false) {
                $result['interest'] = $this->goldFloorfix($total_principal * $interest_day * ($rate / 100 / GoldFinance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->goldFloorfix($result['principal'] * ($rate / 100 /12 * $repay_times)); //每期应还利息
            }
            if($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['interest'] + $result['principal'];
            }else{
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            if ($interest_day !== false) {
                $result['interest'] = $this->goldFloorfix($total_principal * $interest_day * ($rate / 100 / GoldFinance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->goldFloorfix($result['principal'] * ($rate / 100 / 4 * $repay_times));
            }
            if ($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['principal'] + $result['interest'];
            } else {
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款
            $left_need_repay_principal = $total_principal;
            $month_rate = $rate / 12 /100;
            for ($i = 1; $i <= $repay_times; $i++) {
                $repay_principal = installmentPMT($i,$repay_times,$month_rate,$total_principal);
                if ($periods_index == $i) {
                    if ($i == 1) {
                        $interest = $total_principal * $rate /100 * $interest_day / 360;
                    } else {
                        $interest = $left_need_repay_principal * $month_rate;
                    }
                    $repay_money['principal'] = $this->floorfix($repay_principal, 2);
                    $repay_money['interest'] = $this->floorfix($interest, 2);
                    $repay_money['total'] = bcadd($repay_money['principal'],$repay_money['interest'],2);
                    return $repay_money;
                }
                $left_need_repay_principal -=  $this->floorfix($repay_principal, 2);
            }
        }elseif($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']){

            $avgPrincipal = $this->floorfix($total_principal/$repay_times,2);
            if($is_last) {
                $result['principal']  = $total_principal - $avgPrincipal * ($repay_times - 1);
            }else{
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷12）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal/$repay_times * ($periods_index - 1)) * $rate / 100 /12);
            $result['total'] = bcadd($result['principal'],$result['interest'],2);
        }elseif($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']){
            $avgPrincipal = $this->floorfix($total_principal/$repay_times,2);
            if($is_last) {
                $result['principal']  = $total_principal - $avgPrincipal * ($repay_times - 1);
            }else{
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷4）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal/$repay_times * ($periods_index-1)) * $rate / 100 /4);
            $result['total'] = bcadd($result['principal'],$result['interest'],2);
        } else { //按月付息之外的其他新借款
            $finance = new GoldFinance();
            $pmt = $finance->getPmtByDeal($dealInfo, $periods_index, $total_principal);
            if($pmt !== false){
                if (!$periods_index) {
                    $interest = $is_loan ? $pmt['income_fee'] : $pmt['interest'];
                    $result['interest'] = $this->goldFloorfix($interest * $total_principal / $dealInfo['borrow_amount'] / $repay_times);
                    $result['total'] = $result['interest'] + $result['principal'];
                } else {
                    $result['principal'] = $pmt['pmt_principal'];
                    $result['interest'] = $this->goldFloorfix($pmt['pmt'] - $pmt['pmt_principal']);
                    $result['total'] = $pmt['pmt'];
                }
            }
        }

        $result['principal'] = $this->goldFloorfix($result['principal']); //计算每期正常情况下应还本金
        $result['total'] = $this->floorfix($result['total']);
        return $result;
    }

    /**
     * 计算需要拆分为多少期进行还款
     *
     * @return integer
     **/
    public function getRepayTimes($loantype,$repay_time)
    {
        $repay_times = 0;
        if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_times = $repay_time / 3;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_times = $repay_time;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_times = 1;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_times = $repay_time;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_times = 1;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_times = $repay_time / 3;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $repay_times = $repay_time;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_times = $repay_time;
        }else if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $repay_times = $repay_time / 3;
        }
        return $repay_times;
    }
    /**
     * JIRA#1062 金额改为舍余处理
     * @param float $value
     * @param int $precision 小树位数
     * @return float
     */
    public function floorfix($value, $precision = 2) {
        $t = pow(10, $precision);
        if (!$t) {
            return 0;
        }
        // 为解决0.5被处理成0.499999的情况，首先在第5位小数进行四舍五入
        $value = round($value*$t, 5);
        return (float)floor($value) / $t;
    }

    /**
     * 补偿克重时使用
     * @param $value
     * @param int $precision
     * @param int $roundPlaces
     * @return float|int
     */
    public function goldFloorfix($value,$precision =3,$roundPlaces=6){
        $t = pow(10, $precision);
        if (!$t) {
            return 0;
        }
        // 为解决0.5被处理成0.499999的情况，首先在第5位小数进行四舍五入
        $value = round($value*$t, $roundPlaces);
        return (float)floor($value) / $t;
    }
}
