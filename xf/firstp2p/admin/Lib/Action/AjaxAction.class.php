<?php
/**
 * Ajax常用请求
 *
 * @ClassName: AjaxAction
 * @Description: todo(这里用一句话描述这个类的作用)
 * @author Liwei
 * @date Jun 28, 2013 9:10:55 AM
 *
 */

use app\models\service\Finance;
use core\dao\DealModel;
use core\dao\DealExtModel;

class AjaxAction extends CommonAction
{
    public function index()
    {
        return FALSE;
    }

    /**
     * 获取年利率
     *
     * @copyright  2012-2013    FirstP2P
     * @since      File available since Release 1.0 -- 2013-06-27 下午21:47:00
     * @author     Liwei
     *
     */
    public function getRate($repay_mode, $repay_period, $is_int = "", $is_return = false)
    {
        $repay_mode = empty($repay_mode) ? $_GET['repay_mode'] : $repay_mode;
        $repay_period = empty($repay_period) ? $_GET['repay_period'] : $repay_period;
        $loan_demand = floatval($_GET['loan_demand']);
        $is_int = empty($is_int) ? $_GET['is_int'] : $is_int;

        if ($repay_mode == 5) {
            $repay = array(
                'annualized_rate' => $GLOBALS['dict']['DAY_ONCE_RATE'],
            );

            $data = array('status' => 1,
                'data' => $repay
            );

            if (empty($is_return)) {
                echo json_encode($data);
                exit();
            } else {
                return json_encode($data);
            }
        }

        $repay_list = $this->getRepay($repay_mode, $repay_period);

        if (!empty($is_int)) {
            $repay_mode = $GLOBALS['dict']['REPAY_MODE'][$repay_mode];
            $repay_period = $GLOBALS['dict']['REPAY_PERIOD'][$repay_period];
        }
        if (empty($repay_mode) || empty($repay_period)) {
            $data = array('status' => 0,
                'data' => array(),
            );
        } else {
            $deployList = $this->getDeploy();

            $repay = array(
                'annualized_rate' => $deployList[$repay_mode][$repay_period],
                'period_rate' => $repay_list['period_rate'],
                'back_period' => $repay_list['back_period']
            );

            $data = array('status' => 1,
                'data' => $repay
            );
        }

        if (!$is_return) echo json_encode($data);
        return json_encode($data);
    }

    /**
     * 获取基础数据配置
     *
     * @copyright  2012-2013    FirstP2P
     * @since      File available since Release 1.0 -- 2013-06-27 下午21:47:00
     * @author     Liwei
     *
     */
    public function getRepay($repay_mode, $repay_period)
    {
        $repay_period_raw = $repay_period;
        $repay_period = $GLOBALS['dict']['REPAY_PERIOD'][$repay_period];
        $deployList = $this->getDeploy();
        switch ($repay_mode) {
            case 3:    //一次性到期
                $repaylist['back_period'] = $deployList['ONCE_BACK_ANNUALIZED_RATE'][$repay_period] . "%";    //年化收益率
                $repaylist['period_rate'] = $deployList['ONCE_BACK_PERIOD_RATE'][$repay_period] . "%";    //期间收益率
                break;
            case 2:    //按月等额
                $repaylist['back_period'] = $deployList['MONTH_EQUAL_BACK_ANNUALIZED_RATE'][$repay_period] . "%";    //年化收益率
                $repaylist['period_rate'] = "--";    //期间收益率
                break;
            case 1:        //按季等额回款
                $repaylist['back_period'] = $deployList['SEASON_EQUAL_BACK_ANNUALIZED_RATE']['twelveperiod'] . "%";    //年化收益率
                $repaylist['period_rate'] = "--";    //期间收益率
                break;
        }
        return $repaylist;
    }

    public function getDeploy()
    {
        $deployResult = $GLOBALS['db']->getAll("SELECT * FROM " . DB_PREFIX . "deploy");
        foreach ($deployResult as $val) {
            $deployList[$val['process']] = $val;
        }
        return $deployList;
    }

    /**
     * 借款年利率、平台管理费、出借人收益率 互查
     * @author wenyanlei  2013-8-15
     * @param $rate 借款年利率
     * @param $manage_rate 平台管理费
     * @param $income_rate 出借人收益率
     * @return float
     */
    public function get_fee_rate()
    {
        $rate = $_GET['rate'];
        $manage_rate = $_GET['manage_rate'];
        $income_rate = $_GET['income_rate'];
        $tag = $_GET['tag'];
        $repay_time = $_GET['repay_time'];
        $loantype = $_GET['loantype'];

        $fee_rate = 0;
        if ($tag == 'income_fee_rate') {
            #$fee_rate = ((1+$rate/100)*(1-$manage_rate/100)-1)*100;

            $rate = $rate / 100;
            $manage_rate = $manage_rate / 100;

            // 按天一次性
            if ($loantype == 5) {
                $period_income_rate = (1 + $rate / Finance::DAY_OF_YEAR * $repay_time) * (1 - $manage_rate / Finance::DAY_OF_YEAR * $repay_time) - 1;
                $fee_rate = $period_income_rate * Finance::DAY_OF_YEAR / $repay_time;
            } else {
                $period_income_rate = (1 + $rate / 12 * $repay_time) * (1 - $manage_rate / 12 * $repay_time) - 1;
                $fee_rate = $period_income_rate * 12 / $repay_time;
            }

            $fee_rate = $fee_rate * 100;

        } elseif ($tag == 'annualized_rate') {
            #$fee_rate = ((1+$income_rate/100)/(1-$manage_rate/100)-1)*100;

            $manage_rate = $manage_rate / 100;
            $income_rate = $income_rate / 100;

            if ($loantype == 5)
                $period_income_rate = $income_rate / Finance::DAY_OF_YEAR * $repay_time;
            else
                $period_income_rate = $income_rate / 12 * $repay_time;

            if ($loantype == 5)
                $x = (($period_income_rate + 1) / (1 - $manage_rate / Finance::DAY_OF_YEAR * $repay_time) - 1) / $repay_time * Finance::DAY_OF_YEAR;
            else
                $x = (($period_income_rate + 1) / (1 - $manage_rate / 12 * $repay_time) - 1) / $repay_time * 12;

            $fee_rate = $x * 100;
        }
        //echo number_format($fee_rate, 2);
        echo number_format($fee_rate, 5); // 把后台各项费率小数位数位数放开到5位，前端显示放2位，四舍五入 --20140102
    }

    /**
     * 根据还款方式将年化利率转换为期间利率
     * /m.php?m=Ajax&a=convertToPeriodRate&repay_mode=1&rate=9&period=2
     *
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function convertToPeriodRate()
    {
        $repay_mode = intval($_GET['repay_mode']);
        $rate = floatval($_GET['rate']);
        $period = intval($_GET['period']);

        echo Finance::convertToPeriodRate($repay_mode, $rate, $period);
    }

    /**
     * 获取利滚利 日利率
     */
    public function getDailyRate()
    {
        $rate = floatval($_GET['rate']);
        $redemption_period = floatval($_GET['redemption_period']);
        $deal_compound = new \core\service\DealCompoundService();
        echo $deal_compound->convertRateYearToDay($rate, $redemption_period);
    }

    /**
     * 根据还款方式，手续利率，借款期限，获取期间利率
     * @params $_POST
     */
    public function getPeriodInfo()
    {
        // 接受的参数
        $loantype = intval($_POST['loantype']);
        $repay_time = floatval($_POST['repay_time']);
        $loan_money = floatval($_POST['loan_money']);
        $loan_fee_rate = ceilfix($_POST['loan_fee_rate'], 5);
        $loan_first_rate = ceilfix($_POST['loan_first_rate'], 5);

        $period_rate = Finance::convertToPeriodRate($loantype, $loan_fee_rate, $repay_time);
        $total_money = DealModel::instance()->floorfix($loan_money * $period_rate / 100.0);

        // 需要返回的值
        $data['loan_first_fee'] = DealModel::instance()->floorfix($total_money * $loan_first_rate / $loan_fee_rate);
        $data['loan_last_rate'] = $loan_fee_rate - $loan_first_rate;
        $data['loan_last_fee'] = $total_money - $data['loan_first_fee'];
        $data['loan_rate_sum'] = $loan_fee_rate;
        $data['loan_fee_sum'] = $total_money;
        echo json_encode($data);
    }

    /**
     * 根据手续费收取方式，获取手续费金额
     * @params $_POST
     */
    public function getLoanFee()
    {
        // 接受的参数
        $deal_id = intval($_POST['deal_id']);
        $loan_fee_rate_type = intval($_POST['loan_fee_rate_type']);

        $deal = DealModel::instance()->findViaSlave($deal_id);
        // 计算服务费
        if (in_array($loan_fee_rate_type, array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
            $loan_fee_rate = $deal['loan_fee_rate'];
        } else {
            $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
        }

        $data['fee'] = DealModel::instance()->floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);
        echo json_encode($data);
    }

    /**
     * 获取企业名称
     * 如果是企业用户则获取企业名称，否则获取关联公司名称;如果没有关联公司，则返回真实名字
     * @params $_POST
     */
    public function getUserName()
    {
        $user_id = intval($_REQUEST['id']);
        echo json_encode(get_user_realname($user_id));
    }
}
