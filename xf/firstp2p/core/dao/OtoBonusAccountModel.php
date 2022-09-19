<?php
/**
 * OtoBonusAccountModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

/**
 * 用户gift关系表
 *
 * @author luzhengshuai@ucfgroup.com
 */
class OtoBonusAccountModel extends BaseModel
{
    const MODE_ACQUIRE = 1;
    const MODE_CONFIRM = 2;
    const MODE_DISCOUNT = 3;//返现券
    const MODE_DISCOUNT_RAISE_RATE = 4; // 加息券
    const MODE_DISCOUNT_GOLD = 5;  // 黄金券

    public function saveData($data) {

        foreach ($data as $field => $value) {
            if ($value !== NULL && $value !== '') {
                $this->$field = $this->escape($value);
            }
        }

        $this->create_time = time();

        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }

    public function getAccount($bonus) {
        $condition = "bonus_id = {$bonus['id']}";
        if ($bonus['group_id'] != 0) {
            $condition .= " OR bonus_group_id = {$bonus['group_id']}";
        }
        $res = $this->findByViaSlave($condition, 'account_id');
        if (!empty($res) && $res['account_id']) {
            return $res['account_id'];
        }

        return false;
    }

    public function getCouponNumberByBonusId($bonusId) {
        $condition = "bonus_id = $bonusId";
        $res = $this->findByViaSlave($condition);
        if (empty($res) || !$res['log_id']) {
            return false;
        }

        if ($res['trigger_mode'] == self::MODE_ACQUIRE) {
            $logInfo = OtoAcquireLogModel::instance()->findViaSlave($res['log_id'], 'id,gift_id,gift_code');
        }
        if ($res['trigger_mode'] == self::MODE_CONFIRM) {
            $logInfo = OtoConfirmLogModel::instance()->findViaSlave($res['log_id'], 'id,gift_id,gift_code');
        }

        if (!empty($logInfo)) {
            return $logInfo->getRow();
        }

        return false;
    }
}
