<?php

 /**
 * DealCompound class file.
 * 利滚利业务逻辑
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace core\service\deal;

use core\service\BaseService;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealCompoundModel;

/**
 * DealCompound service
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/
class DealCompoundService extends BaseService
{

    /**
     * 将年利率转为日利率
     * @param float $rate_year
     * @param int $redemption_period 赎回周期
     * @param bool only_display
     * @return float
     */
    public static function convertRateYearToDay($rate_year, $redemption_period, $only_display = false)
    {
        $day_rate = DealModel::instance()->convertRateYearToDay($rate_year, $redemption_period);
        if ($only_display === true ) {
            return bcmul($day_rate, '100', 5);
        } else {
            return $day_rate;
        }
    }

    /**
     * 根据日利率计算期间本息和
     * @param  float $principal
     * @param  float $rate_day
     * @param  int   $day
     * @return float
     */
    public static function getTotalMoneyByDayRate($principal, $rate_day, $day=360)
    {
        $money = $principal * pow(1+$rate_day, $day);

        return DealModel::instance()->floorfix($money);
    }

    /**
     * 根据年利率计算期间本息和
     * @param  float $principal
     * @param  float $rate_year
     * @param  int   $redemption_period 赎回周期
     * @param  int   $day
     * @return float
     */
    public static function getTotalMoneyByYearRate($principal, $rate_year, $redemption_period, $day=360)
    {
        $rate_day = self::convertRateYearToDay($rate_year, $redemption_period);
        $money = self::getTotalMoneyByDayRate($principal, $rate_day, $day);

        return $money;
    }

    /**
     * 获取用户个人中心 利滚利相关内容
     * pengchanglu@ucfgroup.com
     * @param $uid
     * @param $time
     * @return mixed
     */
    public function getUserCompoundMoney($uid ,$time = false)
    {
        $list = DealLoadModel::instance()->getUserCompoundList($uid);
        $compound_money = 0;//利滚利金额
        $repay_money = 0;//可赎回金额
        $interest = 0;//利息
        if (!$list) {
            return array('compound_money' =>0, 'repay_money'=>0, 'interest'=>0);
        }
        $deal_model = new DealModel();
        $compound_model = new DealCompoundModel();
        foreach ($list as $k => $v) {
            $deal = $deal_model->findViaSlave($v['deal_id']);
            $deal_compound = $compound_model->getDealCompoundByDealId($v['deal_id']);
            if ($deal['deal_status'] == 4) {//已经开始回款
                if ($time) {
                    $day = ceil(($time-$deal->repay_start_time)/86400);
                } else {
                    $day = ceil((get_gmtime()-$deal->repay_start_time)/86400);
                }
                $temp_money = self::getTotalMoneyByYearRate($v['money'], $deal->income_fee_rate, $deal_compound['redemption_period'], $day);
                $repay_money = bcadd($repay_money, $temp_money, 2);
                $interest = bcadd($interest, bcsub($temp_money, $v['money'], 2), 2);
            }
            if($deal['deal_status'] !=3){//流标
                $compound_money = $compound_money + $v['money'];
            }
        }
        return array('compound_money' =>$compound_money, 'repay_money'=>$repay_money, 'interest'=>$interest);
    }

    /**
     * 获取扩展数据
     * @param unknown $deal_id
     */
    public function getDealCompound($deal_id)
    {
        return DealCompoundModel::instance()->findBy('`deal_id`=":deal_id"', '*', array(':deal_id' => intval($deal_id)), true);
    }

}
