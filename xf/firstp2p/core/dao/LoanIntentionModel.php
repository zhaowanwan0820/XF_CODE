<?php

namespace core\dao;

/**
 * LoanIntentionModel class
 **/
class LoanIntentionModel extends BaseModel {

    const LOAN_INTENTION_STATUS_START = 1; //待审核
    const LOAN_INTENTION_STATUS_PASS = 2; // 通过
    const LOAN_INTENTION_STATUS_REJECT = 3; // 拒绝


    public function insert($data) {

        // 状态初始为待审核
        $data['status'] = self::LOAN_INTENTION_STATUS_START;
        $data['apply_time'] = get_gmtime(); // time();
        $this->setRow($data);
        if($this->save()){
            return $this->id;
        }else{
            return false;
        }
    }

    public function getBXTByUid($user_id,$type){
        // 制药存在通过或者待审核的时候就不让借钱了。一个人只能来一发   // 状态不是驳回
        $sql = "SELECT * FROM `firstp2p_loan_intention` WHERE `user_id`=:user_id AND `status`!=:status AND `type`=1 LIMIT 1";
        $params = array(
            ':user_id' => $user_id,
            ':status' => self::LOAN_INTENTION_STATUS_REJECT,
            );
        $ret = $this->findAllBySql($sql, true, $params, false);
        if(!empty($ret) && !empty($ret[0])){
            return $ret[0];
        }else{
            return array();
        }
    }

    public function getXFDByUid($user_id,$type){
        $sql = "SELECT * FROM `firstp2p_loan_intention` WHERE `user_id`=:user_id AND `status`=:status AND `type`=2 LIMIT 1";
        $params = array(
            ':user_id' => $user_id,
            ':status' => self::LOAN_INTENTION_STATUS_START,
            );
        $ret = $this->findAllBySql($sql, true, $params, false);
        if(!empty($ret) && !empty($ret[0])){
            return $ret[0];
        }else{
            return array();
        }
    }
}
