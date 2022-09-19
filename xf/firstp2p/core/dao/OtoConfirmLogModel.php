<?php
/**
 * OtoConfirmLogModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

/**
 * 用户gift关系表
 *
 * @author luzhengshuai@ucfgroup.com
 */
class OtoConfirmLogModel extends BaseModel
{
    public function saveConfirmLog($data) {

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

    public function getConfirmLogByGiftId($giftId) {
        $condition = "gift_id = '$giftId'";
        return $this->findBy($condition);
    }
}
