<?php
/**
 * earning class file.
 * @author wangyiming@ucfgroup.com
 **/

namespace core\service;

use core\dao\DealModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealLoadModel;
use core\data\DealData;
use core\dao\DealPrepayModel;
use core\dao\DealRepayModel;
/**
 * 订单收益计算类
 * @author wangyiming@ucfgroup.com
 **/
class EarningService {
    /**
     * 计算预期收益
     * @param $deal_id int
     * @param $principal float
     * @param $is_preview bool true-需要计算补贴收益
     * @return $earning float|false
     */
    public function getEarningMoney($deal, $principal, $is_preview=false) {
        if (is_numeric($deal)) {
            $deal = DealModel::instance()->find($deal, '*', true);
        }
        if (!is_object($deal) || empty($deal)) {
            return false;
        }

        if ($is_preview === false) {
            return $deal->getEarningMoney($principal);
        } else {
            return $deal->getEarningMoney($principal) + $deal->getSubsidyMoney($principal);
        }
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
        $deal = DealModel::instance()->findViaSlave($deal_id);
        if (!$deal) {
            return false;
        }

        return $deal->getAllRepayMoney();
    }

    /**
     * 计算预期收益率
     * @param int $deal_id
     * @param bool $is_preview
     * @return float|bool
     */
    public function getEarningRate($deal_id, $is_preview=false) {
        $principal_fake = 10000;
        $earning_fake   = $this->getEarningMoney($deal_id, $principal_fake, $is_preview);
        if (!$earning_fake) {
            return false;
        }
        return $earning_fake / $principal_fake * 100;
    }

    /**
     * 根据cate获取贷款收益概述
     */
    public function getIncomeViewByCate($showAll=true,$cate = 0, $resetCache = false){
        $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
        if($showAll !== true){
            $site_id = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):app_conf('DEAL_SITE_ALLOW');
        }else{
            $site_id = 0;
        }
        $deal_data = new DealData();
        $data = $deal_data->getIncomeView($site_id.'_'.$cate);
        if (!$data || $resetCache === true) {
            $site = app_conf("TEMPLATE_ID");
            //年化收益率
            $data['income_rate_min'] = number_format(get_config_db('MIN_INCOME_RATE', $site), 2);
            $data['income_rate_max'] = number_format(get_config_db('MAX_INCOME_RATE', $site), 2);

            //投资人已累计投资
            $load = DealLoadModel::instance()->getTotalLoanMoneyByCate($site_id,$cate);
            $data['load'] = format_price($load/10000,false);

            //已为投资人带来收益
            $income_sum = DealLoanRepayModel::instance()->getPayedEarnMoneyByCate($site_id,$cate);
            $data['income_sum'] = format_price($income_sum/10000,false);

            //即将带来收益
            $income_plan_sum = $this->getFutureEarnMoneyByCate($site_id,$cate);
            $data['income_plan_sum'] = format_price($income_plan_sum/10000,false);

            $deal_data->setIncomeView($site_id.'_'.$cate, $data);
        }
        return $data;
    }

    /**
     * 根据cate获取贷款收益并永久缓存
     */
    public function getIncomeViewByCatePerp($showAll=true,$cate = 0)
    {//$cate=0;
        $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
        if($showAll !== true) {
            $site_id = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):app_conf('DEAL_SITE_ALLOW');
        } else {
            $site_id = 0;
        }
        $deal_data = new DealData();
        $site = app_conf("TEMPLATE_ID");
        //年化收益率
        $data['income_rate_min'] = number_format(get_config_db('MIN_INCOME_RATE', $site), 2);
        $data['income_rate_max'] = number_format(get_config_db('MAX_INCOME_RATE', $site), 2);

        //投资人已累计投资
        $load = DealLoadModel::instance()->getTotalLoanMoneyByCate($site_id,$cate);
        $data['load'] = format_price($load/10000,false);

        //已为投资人带来收益
        $income_sum = DealLoanRepayModel::instance()->getPayedEarnMoneyByCate($site_id,$cate);
        $data['income_sum'] = format_price($income_sum/10000,false);

        //即将带来收益
        $income_plan_sum = $this->getFutureEarnMoneyByCate($site_id,$cate);
        $data['income_plan_sum'] = format_price($income_plan_sum/10000,false);

        $deal_data->setIncomeViewPerp($site_id.'_'.$cate, $data,$expire = 0);

        return $data;
    }

    /**
     * 根据cate读取贷款收益：优先从缓存里读取
     */
    public function getIncomeViewByCate_cache($showAll=true,$cate = 0)
    {
        $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
        if($showAll !== true) {
            $site_id = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):app_conf('DEAL_SITE_ALLOW');
        } else {
            $site_id = 0;
        }
        $deal_data = new DealData();
        $data = $deal_data->getIncomeView($site_id.'_'.$cate);

        if (!$data) {
        $site = app_conf("TEMPLATE_ID");
        //年化收益率
        $data['income_rate_min'] = number_format(get_config_db('MIN_INCOME_RATE', $site), 2);
        $data['income_rate_max'] = number_format(get_config_db('MAX_INCOME_RATE', $site), 2);

        //投资人已累计投资
        //$load = DealLoadModel::instance()->getTotalLoanMoneyByCate($site_id,$cate);
        //$data['load'] = format_price($load/10000,false);
        $data['load'] = '--';

        //已为投资人带来收益
        //$income_sum = DealLoanRepayModel::instance()->getPayedEarnMoneyByCate($site_id,$cate);
        //$data['income_sum'] = format_price($income_sum/10000,false);
        $data['income_sum'] = '--';

        //即将带来收益
        //$income_plan_sum = $this->getFutureEarnMoneyByCate($site_id,$cate);
        //$data['income_plan_sum'] = format_price($income_plan_sum/10000,false);
        $data['income_plan_sum'] = '--';

        //$deal_data->setIncomeViewPerp($site_id.'_'.$cate, $data,$expire = 0);
        }

        return $data;
        }

    /**
     * 获取贷款收益概述
     */
    public function getIncomeView($showAll=true, $resetCache = false){
        $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
        if($showAll !== true){
            $site_id = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):app_conf('DEAL_SITE_ALLOW');
        }else{
            $site_id = 0;
        }
        $deal_data = new DealData();
        $data = $deal_data->getIncomeView($site_id);
        if (!$data || $resetCache === true) {
            $site = app_conf("TEMPLATE_ID");
            //年化收益率
            $data['income_rate_min'] = number_format(get_config_db('MIN_INCOME_RATE', $site), 2);
            $data['income_rate_max'] = number_format(get_config_db('MAX_INCOME_RATE', $site), 2);

            //投资人已累计投资
            $load = DealLoadModel::instance()->getTotalLoanMoney($site_id);
            $data['load'] = format_price($load/10000,false);

            //已为投资人带来收益
            $income_sum = DealLoanRepayModel::instance()->getPayedEarnMoney($site_id);
            $data['income_sum'] = format_price($income_sum,false);

            //即将带来收益
            $income_plan_sum = $this->getFutureEarnMoney($site_id);

            $data['income_plan_sum'] = format_price($income_plan_sum,false);

            $deal_data->setIncomeView($site_id, $data);
        }
        return $data;
    }


    /**
     * 获取贷款收益概述(new)
     */
    public function getIncomeViewNew($resetCache = false ,$deal_type = ''){
        $deal_data = new DealData();
        $key = 'all_site'.trim($deal_type);
        $data = $deal_data->getIncomeView($key);
        if (!$data || $resetCache === true) {
            //投资人已累计投资
            $status = '1,2,4,5';//1进行中，2满标，4还款中，5已还清

            $load = DealModel::instance()->getTotalLoanMoneyByDealStatus($status,$deal_type);
            $data['load'] = format_price($load/10000,false);

            //已为投资人带来收益
            $status = '1,2,3';//'0待还,1准时 2逾期 3严重逾期 4提前'
            $income_sum_repay = DealRepayModel::instance()->getRepayDealInterestByStatus($status,$deal_type);
            $income_sum_prepay = DealPrepayModel::instance()->getPrepayDealInterestByStatus(1,$deal_type);
            $income_sum = $income_sum_prepay + $income_sum_repay;
            $data['income_sum'] = format_price($income_sum,false);

            //即将带来收益(status=0)未还
            $income_plan_sum = DealRepayModel::instance()->getRepayDealInterestByStatus(0,$deal_type);
            $data['income_plan_sum'] = format_price($income_plan_sum,false);

            // 预计收益 包含即将带来的收益
            $data['income_expected_sum'] = format_price(bcadd($income_sum, $income_plan_sum, 2), false);

            //以平台交易数据表为披露依据，交易额取放款交易额，交易次数取放款投资次数，投资人数取放款的投资人数去重；剔除交易表中的公益标业务

            //是否显示披露信息 为1显示，其他不显示
            if(intval(app_conf('IS_PUBLISH_EFFECT')) == 1) {
                //交易总额： 放款总额（不计算流标和未满标）
                $data['borrow_amount_total'] = number_format(DealModel::instance()->getPublishBorrowAmountTotal($deal_type),2);
                //交易总笔数：已放款的投资次数
                $data['buy_count_total'] = number_format(DealModel::instance()->getPublishBuyCountTotal($deal_type));
                //投资人总数：已放款标的投资总人数（去重）
                $data['distinct_user_total'] = number_format(DealLoadModel::instance()->getPublishDistinctUserTotal($deal_type));
            }
            $deal_data->setIncomeView($key, $data);//写入redis
        }
        return $data;
    }

    /**
     * 获取贷款收益并永久的缓存起来
     */
    public function getDealsIncomeViewPerp($showAll=true,$resetCache = false){
        $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
        if($showAll !== true){
            $site_id = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):app_conf('DEAL_SITE_ALLOW');
        }else{
            $site_id = 0;
        }
        $deal_data = new DealData();
        $site = app_conf("TEMPLATE_ID");
        if ($resetCache === true) {
            $site = app_conf("TEMPLATE_ID");
            //年化收益率
            $data['income_rate_min'] = number_format(get_config_db('MIN_INCOME_RATE', $site), 2);
            $data['income_rate_max'] = number_format(get_config_db('MAX_INCOME_RATE', $site), 2);

            //投资人已累计投资
            $load = DealLoadModel::instance()->getTotalLoanMoney($site_id);
            $data['load'] = format_price($load/10000,false);

            //已为投资人带来收益
            $income_sum = DealLoanRepayModel::instance()->getPayedEarnMoney($site_id);
            $data['income_sum'] = format_price($income_sum,false);

            //即将带来收益
            $income_plan_sum = $this->getFutureEarnMoney($site_id);

            $data['income_plan_sum'] = format_price($income_plan_sum,false);

            $deal_data->setIncomeViewPerp($site_id, $data,$expire = 0);
        }
        return $data;
    }

    /**
     * 读取贷款收益
     */
    public function getDealsIncomeView($showAll=true, $deal_type = '') {
        $deal_data = new DealData();
        $key = 'all_site'.trim($deal_type);
        $data = $deal_data->getIncomeView($key);//读取redis
        if (empty($data)) {
            //投资人已累计投资
            //$load = DealLoadModel::instance()->getTotalLoanMoney($site_id);
            //$data['load'] = format_price($load/10000,false);
            $data['load'] = '--';

            //已为投资人带来收益
            //$income_sum = DealLoanRepayModel::instance()->getPayedEarnMoney($site_id);
            //$data['income_sum'] = format_price($income_sum,false);
            $data['income_sum'] = '--';
            //即将带来收益
            //$income_plan_sum = $this->getFutureEarnMoney($site_id);

            $data['income_plan_sum'] = '--';
            //$deal_data->setIncomeViewPerp($site_id, $data);//永久存在缓存中

            //是否显示披露信息 为1显示，其他不显示
            if(intval(app_conf('IS_PUBLISH_EFFECT')) == 1) {
                //交易总额： 放款总额（不计算流标和未满标）
                $data['borrow_amount_total'] = '--';
                //交易总笔数：已放款的投资次数
                $data['buy_count_total'] = '--';
                //投资人总数：已放款标的投资总人数（去重）
                $data['distinct_user_total'] = '--';
            }
        }
        //年化收益率
        $site = app_conf("TEMPLATE_ID");
        $data['income_rate_min'] = number_format(get_config_db('MIN_INCOME_RATE', $site), 2);
        $data['income_rate_max'] = number_format(get_config_db('MAX_INCOME_RATE', $site), 2);
        return $data;
    }

    /**
     * 根据cate计算全站即将带来收益总额(元)
     * @param int $site_id
     * @return float
     */
    public function getFutureEarnMoneyByCate($site_id = false,$cate = 0){
        //所有还款中的即将受益 在deal_loan_repay表中可直接查到
        $deal_loan_repay_model = new DealLoanRepayModel();
        $repay_future_eran_money = $deal_loan_repay_model->getRepayEarnMoneyByCate($site_id,$cate);
        //所有满标数据计入即将带来收益
        $full_future_eran_money = $this->getFullDealEarnMoneyByCate($site_id,$cate);

        return $repay_future_eran_money + $full_future_eran_money;
    }
    /**
     * 计算全站即将带来收益总额(元)
     * @param int $site_id
     * @return float
     */
    public function getFutureEarnMoney($site_id = false){
        //所有还款中的即将受益 在deal_loan_repay表中可直接查到
        $deal_loan_repay_model = new DealLoanRepayModel();
        $repay_future_eran_money = $deal_loan_repay_model->getRepayEarnMoney($site_id);
        //所有满标数据计入即将带来收益
        $full_future_eran_money = $this->getFullDealEarnMoney($site_id);

        return $repay_future_eran_money + $full_future_eran_money;
    }

    /**
     * 根据cate查满标借款即将带来收益总额(元)
     * @return $full_future_eran_money float
     */
    public function getFullDealEarnMoneyByCate($site_id,$cate){
        $full_future_eran_money = 0;
        $full_deals = DealModel::instance()->getFullDealsByCate($site_id,$cate);
        if($full_deals){
            foreach($full_deals as $deal){
                $full_future_eran_money += $this->getEarningMoney($deal, $deal->borrow_amount);
            }
        }
        return $full_future_eran_money;
    }

    /**
     * 满标借款即将带来收益总额(元)
     * @return $full_future_eran_money float
     */
    public function getFullDealEarnMoney($site_id){
        $full_future_eran_money = 0;
        $full_deals = DealModel::instance()->getFullDeals($site_id);
        if($full_deals){
            foreach($full_deals as $deal){
                $full_future_eran_money += $this->getEarningMoney($deal, $deal->borrow_amount);
            }
        }

        return $full_future_eran_money;
    }
}
