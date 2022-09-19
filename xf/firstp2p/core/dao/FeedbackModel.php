<?php
/**
 *用户反馈
 */
namespace core\dao;

class FeedbackModel extends BaseModel {
    /**
     * 列表页
     */
    public function getListByUserId($userId,$type){
        $condition=sprintf("SELECT * FROM %s WHERE user_id=%d AND type=%d ",$this->tableName(),$this->escape($userId),intval($type));
        $res=$this->findAllBySql($condition,true);
        return $res;
    }

    /**
     *  统计当日用户提问次数
     */
    public function getAskAmountByUserIdAndTime($userId){
        $condition = sprintf("`user_id`='%d' AND create_time>='%s' ", $this->escape($userId),strtotime(date('Y-m-d')));
        return $this->count($condition);
    }

    /**
     * 更新状态为已读
     */
    public function updateIsRead($data){
        $condition.=sprintf(" `user_id`='%d' AND `type`='%d' AND status='%d' AND is_read=0",intval($data['userId']),intval($data['type']),intval($data['status']));
        return $this->updateBy(array('is_read'=>intval($data['is_read'])), $condition);
    }

    /**
     *获取信息(通过状态和类型判断用户是否有新回复或者是否已读)
     */
    public function getInfo($data){
        $condition=sprintf("`user_id`='%d' AND is_read='%d' AND status='%d' AND `type`='%d'",$this->escape($data['userId']),intval($data['is_read']),intval($data['status']),intval($data['type']));
        $result=$this->findByViaSlave($condition);
        return $result;
    }

    /**
     * 统计用户历史提问次数
     */
    public  function getAskAmountByUserId($userId,$type){
        $condition = sprintf("`user_id`='%d' AND `type`='%d' ", $this->escape($userId),$type);
        return $this->count($condition);
    }
}
