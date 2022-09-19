<?php
/**
 * BonusUsedModel class file.
 *
 * @author wangshijie@ucfgroup.com
 */

namespace core\dao;

/**
 * 红包
 *
 * @author wangshijie@ucfgroup.com
 */
class BonusUsedModel extends BaseModel
{
    const TYPE_GET = 1; //获取到得红包类型
    const TYPE_SEND = 2; //发送的红包类型
    /**
     * 使用红包记录
     */
    public function insert_batch($bonus_ids, $deal_load_id, $deal_id, $time, $consume_id) {
        $values = array();
        foreach ($bonus_ids as $bonus_id) {
            $values[] = "($deal_id, $bonus_id, $deal_load_id, $time, $consume_id)";
        }
        $sql = 'INSERT INTO %s (`deal_id`, `bonus_id`, `deal_load_id`, `used_at`, `consume_id`) VALUES %s';
        $sql = sprintf($sql, 'firstp2p_bonus_used', implode(', ', $values));
        return $this->execute($sql);
    }

    /**
     * 获取红包的使用情况
     * @param unknown $bonus_id
     */
    public function getBonusUsedByid($bonus_id){
        return $this->findByViaSlave("bonus_id = ':bonus_id'", '*', array(':bonus_id' => $bonus_id));
    }

}
