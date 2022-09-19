<?php
/**
 * VipLogModel
 **/

namespace core\dao\vip;
use core\dao\vip\VipBaseModel;


/**
 * VipLogModel vip账户升级记录表
 *
 * @uses BaseModel
 * @author liguizhi <liguizhi@ucfgroup.com>
 * @date 2017-06-22
 */
class VipLogModel extends VipBaseModel {
    public function addLog($data) {
        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        $this->create_time = time();

        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }

    public function getTopGradeByStartTime($userId, $startTime) {
        if (empty($userId) || empty($startTime)) {
            return false;
        }
        $condition = ' user_id='.intval($userId).' AND create_time>='.intval($startTime).' ORDER BY service_grade DESC LIMIT 1';
        $logInfo = $this->findBy($condition);
        if ($logInfo) {
            return $logInfo['service_grade'];
        }
        return false;
    }
}
