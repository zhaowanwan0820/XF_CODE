<?php
/**
 * 担保
 *
 **/

namespace core\dao\deal;

use core\dao\BaseModel;
class DealGuarantorModel extends BaseModel {

    const STATUS_UNBOUND = 0; // 创建未绑定
    const STATUS_BOUND = 1; // 已绑定
    const STATUS_PASSED = 2; // 同意担保
    const STATUS_DENY = 3; // 不同意担保

    public function checkDealGuarantorStatus($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }

        $condition = "`deal_id`='%d'";
        $condition = sprintf($condition, $this->escape($deal_id));
        $guarantor_list = $this->findAllViaSlave($condition);
        if (!empty($guarantor_list)) {
            foreach ($guarantor_list as $val) {
                if ($val['status'] != self::STATUS_PASSED) {
                    return $val['status'];
                }
            }
        }
        return self::STATUS_PASSED;
    }

    public function getAllByDeal($deal_id) {
        $sql = "SELECT * FROM ".$this->tableName()." WHERE deal_id=:deal_id";
        return $this->findBySql($sql, array(":deal_id"=>$deal_id), true);
    }
}
