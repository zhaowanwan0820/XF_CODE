<?php
/**
 * DealChannelLogModel.php
 *
 * @date 2014-04-03
 * @author wenyanlei@ucfgroup.com
 */

namespace core\dao;


/**
 * 渠道推广投标记录
 * @package core\dao
 */
class DealChannelLogModel extends BaseModel {

    /**
     * 取某个借款的返利总额
     *
     * @param $deal_id intval 借款id
     * @return float
     */
    public function getSumMoneyByDealId($deal_id) {
        
        $deal_id = intval($deal_id);
        $res = '0';
        
        if ($deal_id > 0) {
            $sql = "SELECT SUM(`pay_fee`) FROM " . DB_PREFIX . "deal_channel_log WHERE `deal_id` = '%d' AND `is_delete` = 0";
            $sql = sprintf($sql, $this->escape($deal_id));
            $res = $this->db->getOne($sql);
        }
        return $res;
    }

    public function getLogByDealLoanId($deal_load_id) {
        $condition = "deal_load_id=:deal_load_id";
        return $this->findAll($condition, true, '*', array(":deal_load_id" => $deal_load_id));
    }

    public function addRecord($channel_value, $channel_type, $name, $create_time, $update_time) {
        $sql = "INSERT INTO ".$this->tableName()."(channel_value, channel_type, name, create_time, update_time)"
                ." VALUES('".$this->escape($channel_value)."', '".$this->escape($channel_type)."', '"
                .$this->escape($name)."', '".$this->escape($create_time)."', '".$this->escape($update_time)."')";
        $rs = $this->db->query($sql);
        if (empty($rs)) {
            return 0;
        }
        return $this->db->insert_id();
    }

    public function updateStatusByDeal($deal_id, $deal_status) { 
        $sql = "UPDATE ".$this->tableName()." SET deal_status=".$this->escape($deal_status)." WHERE deal_id=".intval($deal_id);
        return $this->db->query($sql);
    }

    public function getListWithLog($deal_id) {
        $sql = "SELECT l.id FROM " . DB_PREFIX . "deal_channel_log l JOIN " . DB_PREFIX . "deal_channel d ON l.channel_id=d.id";
        $sql .= " WHERE l.deal_id=".intval($deal_id)." AND l.fee_status=0 AND l.is_delete=0 AND d.channel_type=0";
        return $this->db->getAll($sql);
    }

    public function getInfoByLogId($channel_log_id) {
        //只处理顾问类型，不处理网站类型
        $sql = "SELECT l.*, d.channel_value, d.channel_type FROM " . DB_PREFIX . "deal_channel_log l JOIN " 
            .DB_PREFIX . "deal_channel d ON l.channel_id=d.id"
            ." WHERE l.id=".intval($channel_log_id)." AND d.channel_type=0";
        return $this->db->getRow($sql);
    }

    public function updateStatusByLogId($channel_log_id) {
        //更新推广记录结算状态为已结清
        $sql = "UPDATE ".$this->tableName()." SET deal_status=1, fee_status=1 WHERE id=".intval($channel_log_id);
        return $this->db->query($sql);
    }
}
