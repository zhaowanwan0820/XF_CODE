<?php

namespace core\dao;

class BdActivityOrderModel extends BaseModel {

    public function insertOrder($data){
        if(empty($data)){
            return false;
        }

        $this->user_id = $data['user_id'];
        $this->relation_id = intval($data['relation_id']);
        $this->order_sn = $data['order_sn'];
        $this->count = $data['count'];
        $this->prize_id = $data['prize_id'];
        $this->coupon = $data['coupon'];

        $this->status = 0;
        $this->code = '';
        $this->result = '';
        $this->create_time = time();

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    public function updateOrder($order_sn, $params)
    {
        if(empty($params)){
            return false;
        }
        $data = array(
            'status' => $params['status'],
            'code' => $params['code'],
            'result' => $params['result'],
            );

        $condition = sprintf("`order_sn` = '%s'",$this->escape($order_sn));
        return $this->updateAll($data,$condition);
    }
}
