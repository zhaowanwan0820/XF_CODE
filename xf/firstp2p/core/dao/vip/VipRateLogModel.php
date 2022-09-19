<?php
/**
 * VipRateLogModel
 **/

namespace core\dao\vip;
use core\dao\vip\VipBaseModel;


/**
 * VipRateLogModel vip返利记录表
 *
 * @uses BaseModel
 * @author liguizhi <liguizhi@ucfgroup.com>
 * @date 2017-06-22
 */
class VipRateLogModel extends VipBaseModel {

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

    public function getVipRateLogByToken ($token) {
        $token = trim($token);
        $condition = "token = '$token'";
        $logInfo = $this->findBy($condition);
        return $logInfo;
    }
}
