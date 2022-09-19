<?php
namespace core\service\bonus;

use core\dao\DealModel;
use core\service\DealCompoundService;
/**
 * 投资产生正常红包的具体实现策略类
 * @date 2014-12-18
 * @author zhanglei5@ucfgroup.com
 */
class NormalBonusStrategy extends BonusStrategy
{

    public function makeBonus() {
        $rs = $this->check();
        if ($rs === false) {
            return false;
        }

        $deal_info = DealModel::instance()->find($this->deal_id, '`loantype`, `repay_time`, `deal_type`, `type_id`');
        if ($deal_info['type_id'] == 25) {//公益标不生成红包
            return false;
        }
        if ($deal_info['deal_type'] == 1) {
            // 通知贷标的还款时间依照赎回日计算计息日期
            $deal_compound_service = new DealCompoundService();
            $deal_info['repay_time'] = $deal_compound_service->getPeriodDay($this->loan_id);
        }
        $year_ratio = 0.25;
        if($deal_info) {
            if($deal_info['loantype'] == 5){
                $year_ratio = $deal_info['repay_time'] / 360;
            }else{
                $year_ratio = $deal_info['repay_time'] / 12;
            }
           $rs = $this->bonus_service->generation($this->user_id, $this->loan_id, $this->loan_money, $year_ratio, $this->deal_id);
           return $rs;
        }
        return false;
    }
}
?>
