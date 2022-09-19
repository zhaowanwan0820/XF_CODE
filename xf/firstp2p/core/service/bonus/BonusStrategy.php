<?php
namespace core\service\bonus;
/**
 * 产生红包的策略类 
 * @date 2014-12-18
 * @author zhanglei5@ucfgroup.com
 */

abstract class BonusStrategy
{
    public $deal_id;
    public $loan_id;
    public $user_id;
    public $loan_money;
    public $bonus_service;

    public abstract function makeBonus();

    public function check() {
        //判断使用红包单次投资金额下限
        if($this->loan_money < app_conf("BONUS_BID_MIN_MONEY")){
            return false;
        }

        //发红包活动时间判断
        $now = time();
        $bonus_start = strtotime(app_conf('BONUS_START_TIME'));
        $bonus_end = strtotime(app_conf('BONUS_END_TIME'));
        if($now < $bonus_start || $now > $bonus_end){
            return false;
        }
        return true;
    }

}
?>
