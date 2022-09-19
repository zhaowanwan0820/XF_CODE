<?php
/**
 * ActivityModel
 **/

namespace core\dao\vip;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use libs\utils\Logger;
use core\dao\vip\VipBaseModel;

class ActivityModel extends VipBaseModel {
    /**
     * 增加在线活动
     */
    public function addActivity($data) {
        $data['create_time'] = $data['update_time'] = time();
        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        if ($this->insert()) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * 编辑在线活动
     */
    public function updateActivity($id,$data) {
        $data['update_time'] = time();
        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        $condition = "id = $id";

        $res = $this->updateAll($data, $condition);
        return $this->db->affected_rows();
    }

    /**
     * 删除在线活动
     */
    public function delActivity($id) {
        $sql = 'delete from '.$this->tableName().' where id = '.$id;
        return $this->db->query($sql);
    }

    /**
     * 根据ID获取活动信息
     */
    public function getActivityById($id) {
        $condition = 'id = '.intval($id);
        $ActivityInfo = $this->findBy($condition);
        return $ActivityInfo;
    }

    /**
     * @param $title
     */
    public function getActivityIdByTitle($title) {
        $condition = "title = '".$title."'";
        $ActivityInfo = $this->findBy($condition);
        return $ActivityInfo['id'];
    }
}
