<?php
/**
 * earning class file.
 * @author wangyiming@ucfgroup.com
 **/

namespace core\service\deal;

use core\dao\deal\DealModel;
use core\data\DealData;

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

}
