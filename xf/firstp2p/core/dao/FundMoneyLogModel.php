<?php
/**
 * FundMoneyLogModel class file.
 *
 * @author caolong@ucfgroup.com
 **/

namespace core\dao;

/**
 * 冻结与解冻
 *
 * @author luzhengshuai@ucfgroup.com
 **/
class FundMoneyLogModel extends BaseModel
{
    // 冻结
    const EVENT_LOCK = 1;

    // 解冻
    const EVENT_UNLOCK = 2;

    // 赎回,回款
    const EVENT_REFUND = 3;

    // 解冻,投标成功
    const INFO_DEAL_SUCCESS = 1;

    // 解冻,投标失败
    const INFO_DEAL_FAILED = 2;

    // 基金赎回
    const INFO_FUND_REFUND = 3;

    // 受理失败
    const INFO_FUND_FAILED = 4;

    // 私募分红
    const INFO_FUND_BONUS = 5;

    // 私募还本及分红
    const INFO_FUND_REPAYMENT_BONUS = 6;

    // 未处理
    const STATUS_UNTREATED = 0;

    // 处理成功
    const STATUS_SUCCESS = 1;

    // 处理失败
    const STATUS_FAILED = 2;

    public function getLogByConditions($outOrderId, $conditions = array(), $fields = "*") {

        $condition = "out_order_id = '" . $this->escape($outOrderId) . "'";
        foreach ($conditions as $field => $value) {
            $condition .= " AND $field  = '" . $this->escape($value) . "'";
        }

        return $this->findBy($condition, $fields);
    }

    public function updateLog($outOrderId, $conditions = array(), $data) {

        $condition = "out_order_id = '" . $this->escape($outOrderId) . "'";
        foreach ($conditions as $field => $value) {
            $condition .= " AND $field  = '" . $this->escape($value) . "'";
        }
        $result = $this->db->autoExecute($this->tableName(), $data, "UPDATE", $condition);
        $affectedRows = $this->db->affected_rows();
        if (!$result || $affectedRows <= 0) {
            return false;
        }
        return true;
    }

    /**
     * 插入一条数据
     * @param $data array 数据数组
     * @return float
     */
    public function insertData($data) {

        foreach ($data as $field => $value) {
            $this->$field = $this->escape($value);
        }

        $this->create_time = get_gmtime();

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

} // END class FundMoneyLogModel extends BaseModel
