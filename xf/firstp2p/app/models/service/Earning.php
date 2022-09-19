<?php
/**
 * Deal class file.
 * @author wangyiming@ucfgroup.com
 **/

namespace app\models\service;

use app\models\dao\Deal;
use app\models\dao\DealLoanRepay;
use app\models\dao\DealLoad;

/**
 * 订单收益计算类
 * @author wangyiming@ucfgroup.com
 **/
class Earning {
    /**
     * 计算预期收益
     * @param $deal_id int
     * @param $principal float
     * @return $earning float|false
     */
    public function getEarningMoney($deal_id, $principal) {
        if (!$deal_id) {
            return false;
        }
        $deal = Deal::instance()->find($deal_id);
        if (!$deal) {
            return false;
        }

        return $deal->getEarningMoney($principal);
    }
    
    /**
     * 计算预期还款总额
     * @param $deal_id int
     * @param $principal float
     * @return $earning float|false
     */
    public function getRepayMoney($deal_id) {
    	if (!$deal_id) {
    		return false;
    	}
    	$deal = Deal::instance()->find($deal_id);
    	if (!$deal) {
    		return false;
    	}
    
    	return $deal->getAllRepayMoney();
    }

    /**
     * 计算预期收益率
     * @param $deal_id int
     * @return $rate float|false
     */
    public function getEarningRate($deal_id) {
        $principal_fake = 10000;
        $earning_fake   = $this->getEarningMoney($deal_id, $principal_fake);
        if (!$earning_fake) {
            return false;
        }
        return $earning_fake / $principal_fake * 100;
    }
    
    /**
     * 计算全站即将带来收益总额(元)
     * @return float
     */
    public function getFutureEarnMoney(){
        
        //所有还款中的即将受益 在deal_loan_repay表中可直接查到
        $deal_loan_repay_model = new DealLoanRepay();
        $repay_future_eran_money = $deal_loan_repay_model->getRepayEarnMoney();
        
        //所有满标数据计入即将带来收益
        $full_future_eran_money = $this->getFullDealEarnMoney();
        
        return $repay_future_eran_money + $full_future_eran_money;
    }
    
    /**
     * 满标借款即将带来收益总额(元)
     * @return $full_future_eran_money float
     */
    public function getFullDealEarnMoney(){
        $full_future_eran_money = 0;
        $full_deals = Deal::instance()->findAll('`deal_status` = 2 AND `is_delete` = 0');
        
        if($full_deals){
            $deal_load_model = new DealLoad();
            foreach($full_deals as $deal){
                $load_list = $deal_load_model->getDealLoanList($deal->id);
                if($load_list){
                    foreach($load_list as $load){
                        $full_future_eran_money += $this->getEarningMoney($deal->id, $load->money);
                    }
                }
            }
        }
        
        return $full_future_eran_money;
    }
}
