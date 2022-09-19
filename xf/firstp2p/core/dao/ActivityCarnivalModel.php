<?php

/**
 * Description of class
 *
 * @author yutao <yutao@ucfgroup.com>
 */

namespace core\dao;

class ActivityCarnivalModel extends BaseModel {

    /**
     * 
     * @param type $userInfo
     * @return boolean
     */
    public function insertWinUsers($userInfo) {
        extract($userInfo);
        if (isset($user_id) && isset($user_name) && isset($create_time) && isset($last_changed_time) && isset($expire_time)) {
            $sql = "INSERT INTO " . $this->tableName() . "(`user_id`,`user_name`,`gift_practical`,`gift_virtual`,`create_time`,`last_changed_time`,`expire_time`) VALUES ('{$user_id}','{$user_name}','{$gift_practical}','{$gift_virtual}','{$create_time}','{$last_changed_time}','{$expire_time}')";
            return $this->execute($sql);
        }
        return FALSE;
    }

    /**
     * 根据user_id取得得奖用户信息
     * @param  $user_id
     * @return type
     */
    public function findUsersById($user_id) {
        $condition = sprintf("`user_id` = '%d' limit 1", $this->escape($user_id));
        return $this->findBy($condition);
    }

    /**
     * 
     * @param type $user_id  
     * @param type $is_commit
     * @param type $gift_choose
     * @param type $last_changed_time
     * @param type $recipient_name
     * @param type $mobile
     * @param type $province
     * @param type $city
     * @param type $country
     * @param type $address
     * @return type
     */
    public function updateUserWin($user_id, $is_commit, $gift_choose, $last_changed_time, $recipient_name = '', $mobile = '', $province = '', $city = '', $country = '', $address = '') {
        $sql = sprintf("UPDATE " . $this->tableName() . " SET `is_commit` = '%d',`gift_choose` = '%s',`last_changed_time` = '%d',`recipient_name` = '%s',`mobile`='%s',`province` ='%s',`city`= '%s',`country`='%s',`address`='%s' WHERE `user_id` = '%d'", $this->escape($is_commit), $this->escape($gift_choose), $this->escape($last_changed_time), $this->escape($recipient_name), $this->escape($mobile), $this->escape($province), $this->escape($city), $this->escape($country), $this->escape($address), $this->escape($user_id));
        return $this->updateRows($sql);
    }

}
