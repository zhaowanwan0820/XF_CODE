<?php
namespace core\dao;

class ZxIdempotentModel extends BaseModel {

    public function getSumMoneyByBizType($batchOrderId){
        $sql = "select sum(money) as money,biz_type from ".$this->tableName() . " where batch_order_id=".$batchOrderId." group by biz_type";

        $res =  $this->findAllBySql($sql,true);
        $data = array();
        foreach ($res as $row){
            $data[$row['biz_type']] = $row['money'];
        }
        return $data;
    }
}
