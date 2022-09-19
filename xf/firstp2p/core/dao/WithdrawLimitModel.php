<?php
namespace core\dao;

/**
 * WithdrawLimitModel class
 *
 * @author  王群强<wangqunqiang@ucfgroup.com>
 **/
class WithdrawLimitModel extends BaseModel {


    public function minusRemainMoney($amount)
    {
        $sql = "UPDATE firstp2p_withdraw_limit SET remain_money = remain_money - {$amount} WHERE id = '{$this->id}' AND remain_money >= {$amount}";
        $this->db->query($sql);
        return $this->db->affected_rows() == 1  ? true : false;
    }


    public function addRemainMoney($amount)
    {
        $sql = "UPDATE firstp2p_withdraw_limit SET remain_money = remain_money + {$amount} WHERE id = '{$this->id}'";
        $this->db->query($sql);
        return $this->db->affected_rows() == 1  ? true : false;
    }
}
