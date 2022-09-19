<?php

namespace core\dao\vip;

use core\dao\vip\VipBaseModel;

/**
 * VIP经验值补发任务
 *
 * @author liguizhi@ucfgroup.com
 */
class VipPointResendModel extends VipBaseModel
{
    //审核状态
    const STATUS_INIT = 1;     //待审核
    const STATUS_SUCCESS = 2;  //已通过
    const STATUS_FAILED = 3;   //未通过

    //发送状态
    const SEND_STATUS_INIT = 1;     //待审核
    const SEND_STATUS_WORKING = 2;  //执行中
    const SEND_STATUS_DONE = 3;   //未通过

    //发送方式
    const TYPE_USERID    = 1;     //用户id
    const TYPE_USERGROUP = 2;     //用户组ID

    public static $status = array(
        1 => '待审核',
        2 => '审核通过',
        3 => '未通过'
    );

    public static $send_status = array(
        1 => '待发送',
        2 => '发送中',
        3 => '已完成'
    );

    public static $sendType = array(
        self::TYPE_USERID => '会员ID',
        self::TYPE_USERGROUP => '会员组ID'
    );


    public function getTask($id) {
        $condition = "id = '$id'";
        $task = $this->findBy($condition);
        $task['type_desc'] = self::$sendType[$task['type']];
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
}
