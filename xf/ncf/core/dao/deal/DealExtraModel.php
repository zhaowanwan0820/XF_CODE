<?php

namespace core\dao\deal;

use core\dao\BaseModel;

class DealExtraModel extends BaseModel
{

    public function addDealExtra($dealId, $data)
    {
        $res = $this->saveDealExtra($dealId,$data);
        return $res ? $this->deal_id : 0;
    }

    public function saveDealExtra($dealId, $data)
    {
        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }
        $this->update_time = get_gmtime();
        $this->deal_id = $dealId;
        return $this->save();
    }

    /**
     * 根据deal_id获取额外信息
     * @param int $deal_id
     * @return object
     */
    public function getDealExtraByDealId($deal_id,$isSlave=true)
    {
        static $deal_extra;
        if (!isset($deal_extra[$deal_id])) {
            $deal_id = intval($deal_id);
            $condition = "`deal_id`='%d'";
            $condition = sprintf($condition, $this->escape($deal_id));
            $deal_extra[$deal_id] = $this->findBy($condition,'*',array(),$isSlave);
        }
        return $deal_extra[$deal_id];
    }

    /**
     * 插入一条deal_extra数据
     * @param $data array 数据数组
     */
    public function insertDealExtra($data){
        if(empty($data)){
            return false;
        }

        $this->deal_id = $data['deal_id'];
        $this->recourse_user = $data['recourse_user'];
        $this->recourse_time = $data['recourse_time'];
        $this->recourse_type = $data['recourse_type'];
        $this->lawsuit_address = $data['lawsuit_address'];
        $this->arbitrate_address = $data['arbitrate_address'];
        $this->create_time = get_gmtime();
        $this->update_time = get_gmtime();

        return $this->insert();
    }
}