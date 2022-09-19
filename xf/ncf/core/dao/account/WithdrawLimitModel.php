<?php
namespace core\dao\account;
use core\dao\BaseModel;

/**
 * WithdrawLimitModel class
 *
 * @author  王群强<wangqunqiang@ucfgroup.com>
 **/
class WithdrawLimitModel extends BaseModel {

    public function minusRemainMoney($amount)
    {
        // 表名
        $tableName = $this->tableName();
        $sql = "UPDATE `{$tableName}` SET remain_money = remain_money - {$amount} WHERE id = '{$this->id}' AND remain_money >= {$amount}";
        $this->db->query($sql);
        return $this->db->affected_rows() == 1  ? true : false;
    }

    public function addRemainMoney($amount)
    {
        // 表名
        $tableName = $this->tableName();
        $sql = "UPDATE `{$tableName}` SET remain_money = remain_money + {$amount} WHERE id = '{$this->id}'";
        $this->db->query($sql);
        return $this->db->affected_rows() == 1  ? true : false;
    }
}