<?php
/**
 * Deal class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace app\models\dao;

use app\models\service\Finance;
use core\service\DealService;
use core\dao\DealLoanTypeModel;
use core\dao\DealRepayModel;
use core\dao\DealExtModel;
use core\dao\UserCarryModel;

/**
 * Deal class
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class Deal extends BaseModel
{
    /**
     * 借款状态
     *
     * @var string
     **/
    public static $DEAL_STATUS = array(
        'waiting'     => 0, //等待材料
        'progressing' => 1, //进行中
        'full'        => 2, //满标
        'failed'      => 3, //流标
        'repaying'    => 4, //还款中
        'repaid'      => 5, //已还清
    );

    public static $PARENT_TYPE = array(
        'general' => -1, //普通标
        'main'    => 0,  //母标
        //'sub'     => 1,  //大于0是子标,请使用isSub方法
    );

    /**
     * 创建母标以及子标的还款回款计划表
     *
     * @return void
     **/
    public function createDealRepayList() {
        $this->db->startTrans();
        try {
            $this->createDealRepayListSub();
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * 创建子标以及正常标的还款回款计划表
     *
     * @return void
     **/
    private function createDealRepayListSub() {
        $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
        if (!$repay_time) {
            throw new \Exception("repay start time error");
        }

        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
        $dealService = new DealService();
        $deal = $dealService->getDeal($this->id);
        $isDtb = $dealService->isDealDT($this->id);

        $repay_cycle = $this->getRepayCycle();
        $repay_times = $this->getRepayTimes();
        $deal_ext = \core\dao\DealExtModel::instance()->getInfoByDeal($this->id, false);
        $first_repay_day = $deal_ext['first_repay_interest_day'];

        $loan_fee_arr = $deal_ext['loan_fee_ext'] ? json_decode($deal_ext['loan_fee_ext'], true) : array();
        $consult_fee_arr = $deal_ext['consult_fee_ext'] ? json_decode($deal_ext['consult_fee_ext'], true) : array();
        $guarantee_fee_arr = $deal_ext['guarantee_fee_ext'] ? json_decode($deal_ext['guarantee_fee_ext'], true) : array();
        $pay_fee_arr = $deal_ext['pay_fee_ext'] ? json_decode($deal_ext['pay_fee_ext'], true) : array();
        $canal_fee_arr = $deal_ext['canal_fee_ext'] ? json_decode($deal_ext['canal_fee_ext'], true) : array();
        $consult_fee_period = $this->floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);

        if($isDtb) {
            $management_fee_arr = $deal_ext['management_fee_ext'] ? json_decode($deal_ext['management_fee_ext'], true) : array();//管理服务费
        }

        if ($first_repay_day) {
            if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//消费分期固定日还款
                for ($i=0; $i<$repay_times; $i++) {
                    if ($i == 0) {
                        $interest_day = ($first_repay_day - $repay_time) / 86400;
                        $repay_time = $first_repay_day;
                    } else {
                        $interest_day = 0;
                        $repay_time = $this->getRepayDay($first_repay_day, $repay_cycle, $this->loantype, $i);
                    }
                    $is_last = (($i + 1) == $repay_times);
                    $repay_money = $this->getRepayMoney($this->borrow_amount,$is_last,false,$interest_day,$i+1);
                    $loan_fee = $consult_fee = $guarantee_fee = $pay_fee = $management_fee = $canal_fee = 0;
                    $k = $i+1;
                    if(isset($loan_fee_arr[$k])) {
                        $loan_fee = $loan_fee_arr[$k];
                    }
                    if(isset($consult_fee_arr[$k])) {
                        $consult_fee = $consult_fee_arr[$k];
                    }
                    if(isset($guarantee_fee_arr[$k])) {
                        $guarantee_fee = $guarantee_fee_arr[$k];
                    }
                    if(isset($pay_fee_arr[$k])) {
                        $pay_fee = $pay_fee_arr[$k];
                    }
                    if(isset($management_fee_arr) && (isset($management_fee_arr[$k]))) {
                        $management_fee = $management_fee_arr[$k];
                    }
                    if(isset($canal_fee_arr[$k])) {
                        $canal_fee = $canal_fee_arr[$k];
                    }
                    $this->insertDealRepayList($repay_time, $repay_money, $is_last,  $loan_fee, ($consult_fee + $consult_fee_period), $guarantee_fee, $pay_fee, $management_fee ,$canal_fee, $interest_day,$i+1);
                }
            } else {// 如果后收服务费且借款需要按日计息，则第一期不收手续费，所有手续费向后延续一期
                $repay_times += 1;
                for ($i=0; $i<$repay_times; $i++) {
                    if ($i == 0) {
                        $interest_day = ($first_repay_day - $repay_time) / 86400;
                        $repay_time = $first_repay_day;
                        $repay_money = $this->getRepayMoney($this->borrow_amount, 0, false, $interest_day);
                        $management_fee_val = 0;
                        $this->insertDealRepayList($repay_time, $repay_money, 0, 0, 0, 0, 0, $management_fee_val,0, $interest_day);
                    } elseif ( $i+1 == $repay_times) {
                        $last_repay_time = $this->getLastRepayDay();
                        $interest_day = ($last_repay_time - $repay_time) / 86400;
                        $repay_money = $this->getRepayMoney($this->borrow_amount, 1, false, $interest_day);
                        $management_fee_val = 0;
                        if($isDtb) {
                            $management_fee_val = $management_fee_arr[$i];//管理服务费
                        }
                        $this->insertDealRepayList($last_repay_time, $repay_money, 1, $loan_fee_arr[$i], $consult_fee_arr[$i], $guarantee_fee_arr[$i], $pay_fee_arr[$i], $management_fee_val,$canal_fee_arr[$i], $interest_day);
                    } else {
                        $repay_time = $this->getRepayDay($first_repay_day, $repay_cycle, $this->loantype, $i);
                        $repay_money = $this->getRepayMoney($this->borrow_amount, 0);
                        if($isDtb) {
                            $management_fee_val = $management_fee_arr[$i];//管理服务费
                        }
                        $this->insertDealRepayList($repay_time, $repay_money, 0, $loan_fee_arr[$i], ($consult_fee_arr[$i] + $consult_fee_period), $guarantee_fee_arr[$i], $pay_fee_arr[$i], $management_fee_val,$canal_fee_arr[$i]);
                    }
                }
            }

            // 修改下次还款时间为第一期付息日
            $this->next_repay_time = $first_repay_day;
            $this->save();
        } else {
            if ($repay_times) {
                for($i = 0; $i < $repay_times; $i++) {
                    $repay_time = $this->getRepayDay($this->repay_start_time, $repay_cycle, $this->loantype, $i+1);
                    $is_last = (($i + 1) == $repay_times);
                    $repay_money = $this->getRepayMoney($this->borrow_amount, $is_last, false, false, $i+1);
                    $management_fee_val = 0;
                    if($isDtb) {
                        $management_fee_val = $management_fee_arr[$i+1];//管理服务费
                    }
                    $this->insertDealRepayList($repay_time, $repay_money, $is_last, $loan_fee_arr[$i+1], ($consult_fee_arr[$i+1] + $consult_fee_period), $guarantee_fee_arr[$i+1], $pay_fee_arr[$i+1], $management_fee_val,$canal_fee_arr[$i+1], false, $i+1);
                }
            } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
                $management_fee_val = 0;
                if($isDtb) {
                    $management_fee_val = $management_fee_arr[1];//管理服务费
                }
                $this->insertDealRepayList($repay_time, $repay_money, true, $loan_fee_arr[1], ($consult_fee_arr[1] + $consult_fee_period), $guarantee_fee_arr[1], $pay_fee_arr[1],$management_fee_val, $canal_fee_arr[1]);
            }
        }
    }

    /**
     * 向还款计划表插入数据，并生成回款计划
     * @param int $repay_time 还款日期
     * @param array $repay_money
     * @param bool $is_last
     * @param int|bool $interest_day 计息天数
     */
    public function insertDealRepayList($repay_time, $repay_money, $is_last, $loan_fee, $consult_fee, $guarantee_fee, $pay_fee, $management_fee,$canal_fee, $interest_day=false, $periods_index=0) {
        if (UserCarryModel::LOAN_AFTER_CHARGE === DealExtModel::instance()->getDealExtLoanType($this->id)) { // 放款类型为：收费后放款，还款计划不生成手续费，因为已统一视为前收收取
            $loan_fee = $consult_fee = $guarantee_fee = $pay_fee = $management_fee = 0;
        }

        $deal_repay = new DealRepay();
        $deal_repay->deal_id = $this->id;
        $deal_repay->user_id = $this->user_id;
        $deal_repay->repay_money = $repay_money['total'];
        $deal_repay->principal = $repay_money['principal'];
        $deal_repay->interest = $repay_money['interest'];
        $deal_repay->repay_time = $repay_time;
        $deal_repay->loan_fee = $loan_fee;
        $deal_repay->consult_fee = $consult_fee;
        $deal_repay->guarantee_fee = $guarantee_fee;
        $deal_repay->pay_fee = $pay_fee;
        $deal_repay->management_fee = $management_fee;
        $deal_repay->canal_fee = $canal_fee;
        $deal_repay->create_time = get_gmtime();
        $deal_repay->update_time = get_gmtime();
        $deal_repay->deal_type = $this->deal_type;

        // 保存还款类型
        $dealRepayService = new \core\service\DealRepayService();

        $deal_repay->repay_type = $dealRepayService->getRepayTypeByDeal($this->id);

        // 通知贷和公益标不生成回款计划
        if ($this->deal_type != 1 && $this->loantype != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
            if ($deal_repay->save() === false) {
                throw new \Exception("insert deal repay list error");
            }
        }
        //生成本期回款计划表
        $result = DealLoanRepay::instance()->createLoanRepayPlan($deal_repay, $is_last, $interest_day, $periods_index);
        if ($result === false) {
            throw new \Exception("create loan repay plan error");
        } else {
            if ($this->deal_type != 1 && $this->loantype != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
                $deal_repay = $deal_repay->find($deal_repay->id);
                $deal_repay->repay_money = $result + $loan_fee + $consult_fee + $guarantee_fee + $pay_fee + $management_fee + $canal_fee;
                $deal_repay->interest = $result - $repay_money['principal'];
                $deal_repay->update_time = get_gmtime();

                if ($is_last == true && in_array($this->loantype, array($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'], $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']))) {
                    $principal_fix = \core\dao\DealRepayModel::instance()->getFixPrincipalByDeal($this->getRow(), $deal_repay->id);
                    if ($principal_fix === false) {
                        throw new \Exception("get fix principal fail");
                    } else {
                        $deal_repay->principal = $principal_fix;
                    }
                } else {
                    $deal_repay->principal = $repay_money['principal'];
                }

                if ($deal_repay->save() === false) {
                    throw new \Exception("update deal repay list error");
                }
            }
        }
    }

    /**
     * 获取最后一期还款日
     * @return int 时间戳
     */
    public function getLastRepayDay() {
        $time = $this->repay_start_time;
        $repay_cycle = $this->getRepayCycle();
        $repay_times = $this->getRepayTimes();

        $y = to_date($time,"Y");
        $m = to_date($time,"m");
        $d = to_date($time,"d");

        if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return to_timespan($y."-".$m."-".$d,"Y-m-d") + $repay_cycle*24*60*60;
        }else{
            $target_m = $m + $repay_cycle * $repay_times;

            $year = floor($target_m / 12);
            $y += $year;

            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
                $y--; // 当target_m=24时，$year=2，但是实际上并没发生跨年，于是在这里$y--;
            }
            return to_timespan($y."-".$m."-".$d,"Y-m-d");
        }
    }

    /**
     * 计算需要拆分为多少期进行还款
     *
     * @return integer
     **/
    public function getRepayTimes()
    {
        $repay_times = 0;
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_times = $this->repay_time / 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_times = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_times = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_times = $this->repay_time / 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $repay_times = $this->repay_time / 3;
        }
        return $repay_times;
    }

    /**
     * 计算两次还款的间隔周期, 根据不同的还款方式，结果可能是月份或者天数
     *
     * @return integer
     **/
    public function getRepayCycle()
    {
        $repay_cycle= 0;
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_cycle = 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_cycle = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_cycle = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_cycle = 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
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
        $y = to_date($repay_start_time,"Y");
        $m = to_date($repay_start_time,"m");
        $d = to_date($repay_start_time,"d");

        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return to_timespan($y."-".$m."-".$d,"Y-m-d") + $repay_cycle*24*60*60;
        }elseif($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $add_month_num = $i*$repay_cycle;
            return to_timespan(date("Y-m-d H:i:s", strtotime("+ {$add_month_num} months", strtotime(to_date($repay_start_time)))));
        }else{
            $target_m = $m + $repay_cycle * $i;

            $year = floor($target_m / 12);
            $y += $year;

            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
                $y--; // 当target_m=24时，$year=2，但是实际上并没发生跨年，于是在这里$y--;
            }

            $target = to_timespan($y."-".$m."-".$d,"Y-m-d");
            if ($d != to_date($target, 'd')) {
                $target = to_timespan(to_date($target, 'Y') . '-' . to_date($target, 'm') . '-1', 'Y-m-d');
            }
            return $target;
        }
    }

    /**
     * 根据给定的还款时间以及还款周期计算下次还款时间
     *
     * @param integer $time 本次还款时间或者开始还款时间
     * @param integer $repay_cycle 还款周期，可能是月数或者天数
     * @param integer $loantype 还款方式
     *
     * @return integer unix time
     **/
    public function nextRepayDay($time, $repay_cycle, $loantype)
    {
        $y = to_date($time,"Y");
        $m = to_date($time,"m");
        $d = to_date($time,"d");

        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return to_timespan($y."-".$m."-".$d,"Y-m-d") + $repay_cycle*24*60*60;
        }else{
            $target_m = $m + $repay_cycle;

            $year = floor($target_m / 12);
            $y += $year;

            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
                $y--; // 当target_m=24时，$year=2，但是实际上并没发生跨年，于是在这里$y--;
            }
            return to_timespan($y."-".$m."-".$d,"Y-m-d");
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
    public function getRepayMoney($total_principal, $is_last = false, $is_loan = false, $interest_day = false, $periods_index=0) {
        $rate = $is_loan ? $this->income_fee_rate : $this->rate;
        $result = array();
        $repay_times = $this->getRepayTimes();
        $result['principal'] = $repay_times == 1 ? $total_principal : $total_principal / $repay_times;  // 计算每期本金
        if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            if ($interest_day !== false) {
                $result['interest'] = $this->floorfix($total_principal * $interest_day * ($rate / 100 / Finance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->floorfix($result['principal'] * ($rate / 100 /12 * $repay_times)); //每期应还利息
            }
            if($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['interest'] + $result['principal'];
            }else{
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            if ($interest_day !== false) {
                $result['interest'] = $this->floorfix($total_principal * $interest_day * ($rate / 100 / Finance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->floorfix($result['principal'] * ($rate / 100 / 4 * $repay_times));
            }
            if ($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['principal'] + $result['interest'];
            } else {
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款
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
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']){

            $avgPrincipal = $this->floorfix($total_principal/$repay_times,2);
            if($is_last) {
                $result['principal']  = $total_principal - $avgPrincipal * ($repay_times - 1);
            }else{
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷12）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal/$repay_times * ($periods_index - 1)) * $rate / 100 /12);
            $result['total'] = bcadd($result['principal'],$result['interest'],2);
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']){
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
            $finance = new Finance();
            $pmt = $finance->getPmtByDealId($this->id, $periods_index, $total_principal);
            if($pmt !== false){
                if (!$periods_index) {
                    $interest = $is_loan ? $pmt['income_fee'] : $pmt['interest'];
                    $result['interest'] = $this->floorfix($interest * $total_principal / $this->borrow_amount / $repay_times);
                    $result['total'] = $result['interest'] + $result['principal'];
                } else {
                    $result['principal'] = $pmt['pmt_principal'];
                    $result['interest'] = $this->floorfix($pmt['pmt'] - $pmt['pmt_principal']);
                    $result['total'] = $pmt['pmt'];
                }
            }
        }

        $result['principal'] = $this->floorfix($result['principal']); //计算每期正常情况下应还本金
        $result['total'] = $this->floorfix($result['total']);
        return $result;
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
     * 根据投资金额计算预期收益
     * @param $principal float 本金
     * @return $earning float 收益
     */
    public function getEarningMoney($principal) {
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            $earning = $principal * $this->income_fee_rate / 100 / 12 * $this->getRepayTimes();
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            $earning = $principal * $this->income_fee_rate / 100 / 4 * $this->getRepayTimes();
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款
            $earning =  0;
            $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
            if(intval($repay_time) <=0) {
                $repay_time = to_timespan(date("Y-m-d"));
            }
            $deal_ext = \core\dao\DealExtModel::instance()->getInfoByDeal($this->id, false);
            $first_repay_day = $deal_ext['first_repay_interest_day'];
            $left_need_repay_principal = $principal;
            $rate = $this->income_fee_rate / 100;
            $month_rate =  $rate / 12;
            $repay_times = $this->getRepayTimes();
            for ($i = 1; $i <= $repay_times; $i++) {
                $repay_principal = installmentPMT($i,$repay_times,$month_rate,$principal);
                if ($i == 1) {
                    $interest_day = ($first_repay_day - $repay_time) / 86400;
                    $interest = $principal * $rate * $interest_day / 360;
                } else {
                    $interest = $left_need_repay_principal * $month_rate;
                }
                $earning += $this->floorfix($interest,2);
                $left_need_repay_principal -= $this->floorfix($repay_principal, 2);
            }
        } elseif ($this->id > $GLOBALS['dict']['OLD_DEAL_ID']) {
            $finance = new Finance();
            $pmt = $finance->getPmtByDealId($this->id);
            $earning = $principal / $this->borrow_amount * $pmt['income_fee'];
        } else {
            if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']){ //到期支付本金收益
                $earning = $principal * ($this->income_fee_rate / 100 / 12 * $this->repay_time);
            }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) { //按月等额还款
                $earning = $principal * ($this->income_fee_rate / 100 / 12 * $this->repay_time);
            }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) { //按季等额还款
                $earning = $principal * ($this->income_fee_rate / 100 / 4 * $this->repay_time);
            }
        }
        return $earning;
    }

    /**
     * 根据借款金额计算预期还款总额
     * @return $repay_money float 还款总额
     */
    public function getAllRepayMoney() {
        $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
        $repay_cycle = $this->getRepayCycle();
        $repay_times = $this->getRepayTimes();

        $repay_money = 0;
        for($i = 0; $i < $repay_times; $i++) {
            $repay_time = $this->getRepayDay($this->repay_start_time, $repay_cycle, $this->loantype, $i+1);
            $is_last = (($i + 1) == $repay_times);
            $repay_info = $this->getRepayMoney($this->borrow_amount, $is_last);;
            $repay_money += $repay_info['total'];
        }

        return $repay_money;
    }

    /**
     * 检查是否已经申请或已完成提前还款
     *
     * @return boolean
     **/
    public function isAppliedPrepay()
    {
        $deal_prepay = new DealPrepay();
        $count = $deal_prepay->count("deal_id= $this->id and (status =0 or status = 1)");
        return $count > 0;
    }

    /**
     *  检查是否已经逾期
     *
     * @return boolean
     **/
    public function isOverdue()
    {
        return $this->next_repay_time + 24*3600 < get_gmtime();
    }

    /**
     * 检查是否已经可以进行提前还款
     *
     * @return boolean
     **/
    public function canPrepay()
    {
        $gone_days = (get_gmtime() - $this->repay_start_time)/(24*60*60);
        return $gone_days >= $this->prepay_days_limit;
    }

    /**
     * 计算总计需还款金额
     *
     * @return float
     **/
    public function totalRepayMoney()
    {
        $deal_repay = new DealRepay();
        $sql = "SELECT sum(repay_money) FROM ".$deal_repay->tableName()." where deal_id=$this->id";
        return $this->db->getOne($sql);
    }

    /**
     * 待还金额
     *
     * @return float
     **/
    public function remainRepayMoney()
    {
        return $this->totalRepayMoney() - $this->repay_money;
    }

    /**
     * 获取借款类型名称
     *
     * @return string
     **/
    public function getLoantypeName()
    {
        return $GLOBALS['dict']['LOAN_TYPE'][$this->loantype];
    }

    /**
     * 是否子标
     *
     * @return boolean
     **/
    public function isSub()
    {
        return $this->parent_id > 0;
    }

    /**
     * 是否母标
     *
     * @return boolean
     **/
    public function isMain()
    {
        return $this->parent_id === 0;
    }

    /**
     * 还款完成时的相关处理
     *
     * @return void
     **/
    public function repayCompleted()
    {
        $this->update(array('deal_status'=>Deal::$DEAL_STATUS['repaid']));
        if($this->isSub()){
            $subs = $this->findAll("parent_id = $this->parent_id");
            $all_repaid = true;
            foreach ($subs as $sub) {
                if($sub->deal_status != Deal::$DEAL_STATUS['repaid']){
                    $all_repaid = false;
                    break;
                }
            }
            if($all_repaid){
                $main_deal = $this->find($this->parent_id);
                $main_deal->update(array('deal_status'=>Deal::$DEAL_STATUS['repaid']));
            }
        }
    }
} // END class Deal extends BaseModel
