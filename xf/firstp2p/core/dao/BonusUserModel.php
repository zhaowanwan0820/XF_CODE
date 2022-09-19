<?php
/**
 * BonusUserModel class file.
 * @author Wang Shi Jie <wangshijie@ucfgroup.com>
 */

namespace core\dao;

class BonusUserModel extends BaseModel
{

    /**
     * 插入账户信息
     */
    public function saveUser($data) {
        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }
        return $this->id;
    }

    /**
     * 更新红包用户信息
     */
    public function updateUser($user_id, $data) {

        $sql = 'UPDATE %s SET %s WHERE user_id = %s';
        $values = array();
        foreach ($data as $k => $v) {
            $values[] = "$k=$v";
        }
        $sql = sprintf($sql, 'firstp2p_bonus_user', implode(', ', $values), $user_id);
        return $this->updateRows($sql);
    }

    /**
     * 根据uid获取用户的红包账户信息
     */
    public function getUser($user_id) {
        $condition = "user_id=".intval($user_id);
        return $this->findBy($condition, '*', array(), true);
    }
}
