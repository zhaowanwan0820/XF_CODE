<?php
/**
 * ActivityUserModel
 **/

namespace core\dao\vip;
use libs\utils\Logger;
use core\dao\vip\VipBaseModel;

class ActivityUserModel extends VipBaseModel {
    /**
     * 增加活动用户
     * @param $data
     */
    public function addActivityUser($data) {
        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        $this->apply_time = time();
        if ($this->insert()) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * 判断用户是否报名
     * @param $userId
     * @param $activityId
     */
    public function isRegistration($userId,$activityId) {
        $cond = " user_id = ".intval($userId)." AND activity_id = ".intval($activityId);
        $activityUserInfo = $this->findBy($cond);
        return $activityUserInfo;
    }
}
