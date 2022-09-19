<?php
/**
 * DealCompoundModel class file.
 * @author 彭长路 <pengchanglu@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\DealLoanRepayModel;

/**
 * DealCompoundModel class
 * @author 彭长路 <pengchanglu@ucfgroup.com>
 **/
class DealCompoundModel extends BaseModel {
    /**
     * 根据deal_id获取利滚利标的信息
     * @param int $deal_id
     * @return object
     */
    public function getDealCompoundByDealId($deal_id) {
        $condition = sprintf("`deal_id`='%d'", intval($deal_id));
        return $this->findByViaSlave($condition);
    }

    /**
     * 根据deal_id获取利滚利未还投资记录id
     * @param int $deal_id
     * @return array
     */
    public function getDealCompoundLoadByDealId($deal_id, $time = 0) {
        $time = $time > 0 ? $time : get_gmtime();
        $sql = sprintf("SELECT DISTINCT(`deal_loan_id`) FROM %s WHERE `deal_id`='%d' AND `type` IN (8, 9) AND `status`='0' AND `time`<='%d' AND `time`!='0'", DealLoanRepayModel::instance()->tableName(), intval($deal_id), $time);
        return $this->findAllBySql($sql, true, array(), true);
    }

    /**
     * 获取截止到某个时间 金额不足还款的借款人
     * @param unknown $time
     * @return object
     */
    public function getMoneyLessBorrower($time){
        $sql = "SELECT `id`,`user_name`,`real_name`,`mobile`,`money`,`money_all` FROM `firstp2p_user` u LEFT JOIN
                (SELECT `borrow_user_id`,SUM(`money`) as money_all FROM `firstp2p_deal_loan_repay`
                WHERE `type` IN (8,9) AND `status` = 0 AND `time` = '".intval($time)."' GROUP BY `borrow_user_id`) r
                ON u.`id` = r.`borrow_user_id` WHERE u.`money` < r.`money_all`";
        return $this->findAllBySql($sql, true, array(), true);
    }


    /**
     * 获取单个用户的通知贷未赎回投资记录
     * @param int $user_id
     * @return float
     */
    public function getLoadMoneyNotRedeem($user_id) {
        $sql = "SELECT SUM(`money`) AS `m` FROM " . DealLoadModel::instance()->tableName() . " WHERE `user_id`='{$user_id}' AND `deal_id` IN (SELECT `id` FROM " . DealModel::instance()->tableName() . " WHERE `deal_status`='4') AND `id` NOT IN (SELECT `deal_load_id` FROM " . CompoundRedemptionApplyModel::instance()->tableName() . " WHERE `user_id`='{$user_id}') AND `deal_type`='1'";
        $res = $this->findBySql($sql, array(), true);
        return floatval($res['m']);
    }

}
