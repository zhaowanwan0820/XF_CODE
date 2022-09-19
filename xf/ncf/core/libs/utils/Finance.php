<?php
/**
 * Finance class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace libs\utils;
use core\dao\deal\DealModel;
use core\dao\repay\DealLoanRepayModel;
use core\enum\DealLoanRepayEnum;
use core\enum\DealEnum;

/**
 * 金融计算工具类
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Finance
{
    const DAY_OF_YEAR = 360;    //金融计算通常将一年作为360天计算
    const MONTH_OF_YEAR = 12;   //一年中的月数
    const RATE_DIGIT = 5; //利率位数

    /**
     * 根据还款方式将年化利率转换为期间利率
     *
     * @param integer $repay_mode 还款方式
     * @param float $rate 年化借款利率
     * @param int $period 借款期限, 单位以还款方式为准
     * @param bool $is_round 结果是否四舍五入，默认为true
     *
     * @return array
     **/
    public static function convertToPeriodRate($repay_mode, $rate, $period, $is_round=true) {
        $period = $period >= 0 ? $period : 0;
        if($repay_mode == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']){
            $period_rate = $period / self::DAY_OF_YEAR * $rate;
        } else {
            $period_rate = $period / self::MONTH_OF_YEAR * $rate;
        }
        return $is_round ? round($period_rate, self::RATE_DIGIT) : $period_rate;
    }

    /**
     * 计算逾期罚息金额
     *
     * @param float $principal 本金
     * @param int $day 逾期天数
     * @param float $rate 年化利率
     * @param float $overdue_rate 逾期罚息系数
     *
     * @return float 逾期罚息金额
     **/
    public static function overdue($principal, $day, $rate, $overdue_rate)
    {
        return round($principal * ($day / self::DAY_OF_YEAR * $rate * $overdue_rate), 2);
    }

    /**
     * PMT年金计算方法
     * @param $i float 期间收益率
     * @param $n int 期数
     * @param $p float 本金
     * @return float 每期应还金额
     */
    public static function getPmtMoney($i, $n, $p) {
        return $p * $i * pow((1 + $i), $n) / ( pow((1 + $i), $n) -1);
    }

    /**
     * PMT每期应还本金计算方法
     * 等额本息还贷第n个月还贷本金：贷款本金*月利率(1+月利率)^(还款月数-1)/[(1+月利率)^还款总期数-1]
     * @param $i float 期间收益率
     * @param $n int 期数
     * @param $p float 本金
     * @param $periods_index 当前第几期
     * @return float 每期应还金额
     */
    public static function getPmtPrincipal($i, $n, $p,$periods_index) {
        return $p * $i * pow((1 + $i), $periods_index-1) / ( pow((1 + $i), $n) -1);
    }

    /**
     * 根据投资信息获取投资应获收益
     * @param $deal_loan_info array
     * return float
     */
    public static function getExpectEarningByDealLoan($deal_loan_info) {
        $deal_dao = new DealModel();
        $deal = $deal_dao->findViaSlave($deal_loan_info['deal_id']);
        if ($deal['deal_status'] == DealEnum::$DEAL_STATUS['repaying'] || $deal['deal_status'] == DealEnum::$DEAL_STATUS['repaid']) {
            $deal_loan_repay = new DealLoanRepayModel();
            return $deal_loan_repay->getTotalMoneyByTypeLoanId($deal_loan_info['id'], DealLoanRepayEnum::MONEY_INTREST, 0);
        } else {
            return $deal->getEarningMoney($deal_loan_info['money']);
        }
    }

    /**
     * 根据标的id获取PMT信息方法
     * @param $deal_id int
     * @param $periods_index int 期数
     * @return array|bool
     */
    public function getPmtByDealId($deal_id, $periods_index=0, $total_principal=0) {
        $deal_dao = new DealModel();
        $deal = $deal_dao->findViaSlave($deal_id);
        return $this->getPmtByDeal($deal, $periods_index, $total_principal);
    }

    /**
     * 根据标的信息获取PMT信息方法
     * @param $deal array
     * @return array|bool
     */
    public function getPmtByDeal($item, $periods_index=0, $total_principal=0) {
        if (!$item) {
            return false;
        }

        $data = array();

        $data['loantype'] = $item['loantype'];
        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['desc'] = $item['repay_time'] . '天' . $GLOBALS['dict']['LOAN_TYPE']["{$item['loantype']}"];
        } else {
            $data['desc'] = $item['repay_time'] . '月' . $GLOBALS['dict']['LOAN_TYPE']["{$item['loantype']}"];
        }

        $data['borrow_sum'] = $item['borrow_sum']; // 借款总额度
        if ($periods_index && $total_principal) {
            $data['borrow_amount'] = $total_principal;
        } else {
            $data['borrow_amount'] = $item['borrow_amount'];  // 借款分配额度
        }
        $data['repay_time'] = $item['repay_time']; // 借款期限
        \FP::import("app.deal");
        $data['repay_interval'] = get_delta_month_time($item['loantype'], $item['repay_time']); // 还款间隔月数

        $data['rate'] = $item['rate'] / 100;  // 年华借款利率

        // 如果是按天一次性
        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            if($item['id'] <= $GLOBALS['dict']['OLD_DEAL_DAY_ID']){
                $data['repay_fee_rate'] = $data['rate'] / 365 * $data['repay_interval']; // 借款期间利率
            }else{
                $data['repay_fee_rate'] = $data['rate'] / self::DAY_OF_YEAR * $data['repay_interval']; // 借款期间利率
            }
        } else {
            $data['repay_fee_rate'] = $data['rate'] / self::MONTH_OF_YEAR * $data['repay_interval']; // 借款期间利率
        }

        $data['repay_num'] = $data['repay_time'] / $data['repay_interval']; // 还款次数
        // 代码里没有引用，应该没用了，为保险起见，注释掉了
        // $data['borrow_rate'] = empty($data['borrow_sum']) ? 0 : $data['borrow_amount'] / $data['borrow_sum']; // 借款分配比例
        $data['fv'] = 0; // Fv为未来值（余值），或在最后一次付款后希望得到的现金余额，如果省略Fv，则假设其值为零，也就是一笔贷款的未来值为零。
        $data['type'] = 0; // Type数字0或1，用以指定各期的付款时间是在期初还是期末。1代表期初（先付：每期的第一天付），不输入或输入0代表期末（后付：每期的最后一天付）。
        $data['pmt'] = self::getPmtMoney($data['repay_fee_rate'], $data['repay_num'], $data['borrow_amount']); //借款人每期还款额

        if ($periods_index) {
            $data['pmt_principal'] = self::getPmtPrincipal($data['repay_fee_rate'], $data['repay_num'], $data['borrow_amount'],$periods_index);
        } else {
            $data['pmt_principal'] = null;
        }

        $data['manage_fee_rate'] = $item['manage_fee_rate'] / 100; // 账户管理费率年化
        $data['interest'] = $data['pmt'] * $data['repay_num'] - $data['borrow_amount']; // 总利息

        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['manage_fee'] = $data['pmt'] * $data['manage_fee_rate'] / self::DAY_OF_YEAR * $data['repay_time']; // 管理费
        } else {
            $data['manage_fee'] = $data['pmt'] * $data['manage_fee_rate'] / self::MONTH_OF_YEAR * $data['repay_time']; // 管理费
        }

        $data['manage_rate'] = $data['manage_fee'] / $data['pmt']; // 管理费收取比例
        $data['income_fee'] = $data['interest'] - $data['manage_fee'];  // 理财总收益
        $data['real_repay_fee_rate'] = $data['interest'] / $data['borrow_amount']; // 实际借款利率
        $data['income_fee_rate'] = $data['income_fee'] / $data['borrow_amount']; // 实际理财收益率

        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['period_income_rate'] = (1 + $data['rate'] /self::DAY_OF_YEAR * $data['repay_time']) * (1 - $data['manage_fee_rate'] /self::DAY_OF_YEAR * $data['repay_time']) -1;   // 理财期间收益率
            $data['simple_interest'] = $data['period_income_rate'] * self::DAY_OF_YEAR / $data['repay_time']; // 理财年化收益率（单利）
            $data['compound_interest'] = pow( (1 + $data['period_income_rate']), (self::DAY_OF_YEAR / $data['repay_time'])) -1;  // 理财年化收益率（复利）
        } else {
            $data['period_income_rate'] = (1 + $data['rate'] / self::MONTH_OF_YEAR * $data['repay_time']) * (1 - $data['manage_fee_rate'] /self::MONTH_OF_YEAR * $data['repay_time']) -1;   // 理财期间收益率
            $data['simple_interest'] = $data['period_income_rate'] * self::MONTH_OF_YEAR / $data['repay_time']; // 理财年化收益率（单利）
            $data['compound_interest'] = pow( (1 + $data['period_income_rate']), (self::MONTH_OF_YEAR / $data['repay_time'])) -1;  // 理财年化收益率（复利）
        }

        return $data;
    }

    /**
     * 金额加法运算
     * @param unknown $float_arr
     * @param number $decimal
     * @return float
     */
    public static function addition($float_arr = array(), $decimal = 2){
        $sum = 0;
        if($float_arr){
            foreach($float_arr as $float){
                $sum = bcadd($sum, floatval($float), 5);
            }
        }
        return number_format($sum, $decimal, '.', '');
    }

    /**
     * 根据投资额和标的信息获取年化投资额
     * @param float $money 投资金额
     * @param int $loantype 计息方式
     * @param int $repay_time 计息时间
     * @return float 年化投资额
     */
    public static function getMoneyYearPeriod($money, $loantype, $repay_time) {
        if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return $money * $repay_time / self::DAY_OF_YEAR;
        } else {
            return $money * $repay_time / self::MONTH_OF_YEAR;
        }
    }

    /**
     * 精度数舍余
     * @param $value
     * @param int $precision 保留小数点位数
     * @return float|int
     */
    public static function floorfix2($value, $precision = 2) {
        $format1 = '%.'.$precision++.'f';
        $format2 = '%.'.$precision.'f';
        return sprintf($format1,substr(sprintf($format2, $value), 0, -1));
    }

    /**
     * 提前还款利息部分计算
     *
     * @return void
     **/
    public static function prepay_money_intrest($remain_principal, $remain_days, $rate) {
        return $remain_principal * ((($remain_days) / 360) * ($rate / 100));
    }
}
