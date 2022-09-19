<?php 
namespace core\service\bonus;

/**
 * 投资产生 888 红包的具体实现策略类 
 * @date 2014-12-18
 * @author zhanglei5@ucfgroup.com
 */

class XqlBonusStrategy extends BonusStrategy
{
    
    public $xql_id = 0;
    public $money = 888;
    public $group_count = 1;
    public $bonus_count = 88;
    public $send_limit_day = 2;
    public $use_limit_day = 2;
    public function makeBonus() {
        $rs = $this->check();

        if ($rs === false) {
            return false;
        }

        /*$count = app_conf('BONUS_XQL_COUNT');
        $money = app_conf('BONUS_XQL_GROUP_MONEY');
        $send_limit_days = app_conf('BONUS_XQL_SEND_LIMIT_DAYS');
        $get_limit_days = app_conf('BONUS_XQL_GET_LIMIT_DAYS');*/
        $year_ratio = 0.25;

        $bonus_type_id = \core\service\BonusService::TYPE_XQL;
        $result = false;
        for($i = 0; $i < $this->group_count; $i++) {
            $result = $this->bonus_service->generation($this->user_id, $this->loan_id, $this->loan_money,
                $year_ratio, $this->deal_id , $bonus_type_id, $this->money, $this->bonus_count, $this->send_limit_day);
            if ($this->xql_id) {
                $group_id = $this->bonus_service->encrypt($result);
                \SiteApp::init()->cache->set('bonus_xql_use_limit_day_'.$group_id, $this->use_limit_day, intval($this->send_limit_day) * 86400);
                \SiteApp::init()->cache->set('bonus_xql_super_id_'.$group_id, $this->xql_id, 30 * 86400);
            }
        }
        return $result;
    }
}

?>
