<?php

namespace core\dao\vip;

use core\dao\vip\VipBaseModel;

/**
 * VIP特权表
 *
 * @author liguizhi@ucfgroup.com
 */
class VipPrivilegeModel extends VipBaseModel
{
    public static $statusDesc = array(
        0 => '无效',
        1 => '有效'
    );

    public function getTask($id) {
        $condition = "id = '$id'";
        $task = $this->findBy($condition);
        return $task;
    }

    public function addTask($data) {

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

    public function updateTask($data) {
        $condition = "id = {$data['id']}";
        unset($data['id']);
        $res = $this->updateAll($data, $condition);
        return $this->db->affected_rows();
    }

    public function getFormatPrivilegeDetail($id) {
        $data = $this->getTask($id);
        if (empty($data)) {
            return false;
        }
        $result = array(
            'privilegeId' => $data['id'],
            'name' => $data['privilege_name'],
            'describe' => $data['privilege_desc'],
            'detail' => $data['privilege_detail'],
            'weight' => $data['weight'],
            'imgConf' => json_decode($data['img_conf'], true),
            'extraInfo' => json_decode($data['extra_info'], true),
        );
        return $result;
    }

    public function getAllEffectPrivilegeList() {
        $sql = 'SELECT * FROM firstp2p_vip_privilege WHERE status=1 AND effect_time<='. time(). ' AND is_delete=0 ORDER BY weight DESC';
        return $this->db->getAll($sql);
    }
}
