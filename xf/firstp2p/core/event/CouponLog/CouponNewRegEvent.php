<?php
/**
 *
 *
 * @date 2016-03-10
 * @author <zhaoxiaoan@ucfgroup.com>
 */

namespace core\event\CouponLog;

use core\event\BaseEvent;
use libs\utils\Logger;

class CouponNewRegEvent extends BaseEvent {

    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function execute() {
        if (empty($this->data)) {
            return false;
        }

        $log_info = array(__CLASS__, __FUNCTION__);

        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
        switch ($this->data['type']){
            case 'log' :
                $where = ' l.id >='.intval($this->data['start']).' AND l.id<'.$this->data['end'].' ';
                $sql = "update {$db_prefix}coupon_log l, {$db_prefix}deal_load d set l.site_id=d.site_id where l.deal_load_id>0 and l.deal_load_id=d.id  and  $where ";
                break;
            case 'pay' :
                $where = ' l.id >='.intval($this->data['start']).' AND l.id<'.$this->data['end'].' ';
                $sql = "update {$db_prefix}coupon_pay_log l, {$db_prefix}deal_load d set l.site_id=d.site_id where  l.deal_load_id=d.id  and  $where ";
                break;
            case 'del_reg':
                $where =  ' id >='.intval($this->data['start']).' AND id<'.$this->data['end'].' ';
                if (!empty($this->data['update_type_where'])){
                    $type = $this->data['update_type_where'];
                }else{
                    $type = 1;
                }
                $sql = "delete from {$db_prefix}coupon_log where $where AND type=$type";
                break;
            case 'update_type':
                if (!empty($this->data['update_type_value']) && !empty($this->data['update_type_where'])) {
                    $where = ' id >=' . intval($this->data['start']) . ' AND id<' . $this->data['end'] . ' ';
                    $sql = "UPDATE {$db_prefix}coupon_log SET type='{$this->data['update_type_value']}' WHERE $where AND type={$this->data['update_type_where']}";
                }else{
                    Logger::error(implode(" | ", array_merge($log_info, array('pamar update_type_value empty',json_encode($this->data)))));
                }
                break;
            case 'user':
                $this->shortAliasUpper();
                break;
            default:
                $where = 'id >='.intval($this->data['start']).' AND id<'.$this->data['end'].' AND deal_load_id=0 AND type=1';
                $sql = "REPLACE  into {$db_prefix}coupon_log_reg (`id`,`type` ,`deal_id`,`deal_load_id`,`deal_type`,`deal_repay_time`,`rebate_days`,`rebate_days_update_time`,`deal_repay_days` ,`consume_user_id`,`consume_user_name` ,`refer_user_id` ,`refer_user_name`,`agency_user_id`,`deal_load_money`,`short_alias`,`rebate_amount`,`rebate_ratio`,`rebate_ratio_amount`,`referer_rebate_amount`,`referer_rebate_ratio` ,`referer_rebate_ratio_amount`,`agency_rebate_amount`,`agency_rebate_ratio`,`agency_rebate_ratio_amount`,`referer_rebate_ratio_factor`,`deal_status`,`pay_status`,`create_time`,`pay_time`,`add_type`,`admin_id`,`is_delete`,`update_time`) select `id`,`type` ,`deal_id`,`deal_load_id`,`deal_type`,`deal_repay_time`,`rebate_days`,`rebate_days_update_time`,`deal_repay_days` ,`consume_user_id`,`consume_user_name` ,`refer_user_id`,`refer_user_name`,`agency_user_id`,`deal_load_money`,`short_alias`,`rebate_amount`,`rebate_ratio`,`rebate_ratio_amount`,`referer_rebate_amount`,`referer_rebate_ratio` ,`referer_rebate_ratio_amount`,`agency_rebate_amount`,`agency_rebate_ratio`,`agency_rebate_ratio_amount`,`referer_rebate_ratio_factor`,`deal_status`,`pay_status`,`create_time`,`pay_time`,`add_type`,`admin_id`,`is_delete`,`update_time`  from {$db_prefix}coupon_log  FORCE INDEX(PRI) WHERE $where";
                break;
        }
        try {
            if (!empty($sql)) {

                $result = $GLOBALS['db']->query($sql);
            }
        }catch (\Exception $e){
            Logger::error(implode(" | ", array_merge($log_info, array('mysql error',$e->getMessage(),$sql,json_encode($this->data)))));
        }
        return true;
    }

    /**
     * user表的推荐优惠码改为大写
     * @param array $this->data
     *
     */
     function shortAliasUpper(){
        $log_info = array(__CLASS__, __FUNCTION__);
        if (empty($this->data)) {
            return false;
        }
        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
        $where = ' id >='.intval($this->data['start']).' AND id<'.intval($this->data['end']).' ';
        $sqlList = "SELECT invite_code,id FROM {$db_prefix}user WHERE ".$where;
        $list = $GLOBALS['db']->get_slave()->getAll($sqlList);
         if (empty($list)){
             Logger::info(implode(" | ", array_merge($log_info, array('data empty',json_encode($this->data)))));
             return true;
         }
        foreach ($list as $v){
            if (empty($v['id']) ||empty($v['invite_code']) || ($v['invite_code'] == strtoupper($v['invite_code']) )){
                continue;
            }
            $short_alias_upper = strtoupper($v['invite_code']);
            $id = intval($v['id']);
            $update_sql = "update {$db_prefix}user SET invite_code='$short_alias_upper' WHERE id=$id";
            $result = $GLOBALS['db']->query($update_sql);
            if ($result === false){
                Logger::error(implode(" | ", array_merge($log_info, array('mysql error',$update_sql,json_encode($this->data)))));
            }
        }
    }

    public function alertMails() {
        return array('liangqiang@ucfgroup.com', 'zhaoxiaoan@ucfgroup.com', 'wangzhen3@ucfgroup.com', 'gengkuan@ucfgroup.com');
    }

}