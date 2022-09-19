<?php
/**
 * Fund class file.
 * @author yangqing <yangqing@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\BaseModel;

/**
 * FundSubscribeModel
 *
 * @uses BaseModel
 * @package default
 */
class FundSubscribeModel extends BaseModel {

    /**
     * getCountbyFund
     * 获取预约人员总数
     *
     * @param mixed $fundId
     * @access public
     * @return int
     */
    public function getCountbyFund($fundId) {
        $condition = "`fund_id` = :fund_id ";
        $param = array(':fund_id'=>$fundId);
        return intval($this->countViaSlave($condition,$param));
    }

    /**
     * add
     * 添加预约人员
     *
     * @param mixed $data
     * @access public
     * @return bool
     */
    public function add($data){
        $this->setRow($data);
        $ret = $this->insert();
        $this->id = $this->db->insert_id();
        return $ret;
    }

    /**
     * getList
     * 获取预约人员列表
     *
     * @param mixed $fund_id 基金ID
     * @param mixed $offset
     * @param mixed $limit
     * @param mixed $order
     * @param mixed $sort
     * @access public
     * @return list
     */
    public function getList($fund_id,$offset,$limit,$order,$sort) {
        if($limit > 0){
            $condition = "`fund_id`= :fund_id order by :order :sort limit :offset , :limit";
            $param = array(':fund_id'=>$fund_id,':order'=>$order,':sort'=>$sort,':offset'=>$offset,':limit'=>$limit);
        }else{
            $condition = "`fund_id`= :fund_id order by :order :sort ";
            $param = array(':fund_id'=>$fund_id,':order'=>$order,':sort'=>$sort);
        }
        $list = $this->findAllViaSlave($condition,true,'*',$param);
        if($list){
            return $list;
        }
        return false;
    }
    
    /**
     * getCount
     * 获取预约人员列表总数
     *
     * @param mixed $fund_id 基金ID
     * @access public
     * @return list
     */
    public function getCount($fund_id) {
        $condition = "`fund_id`= :fund_id";
        $param = array(':fund_id'=>$fund_id);
        $count = $this->count($condition,$param);
        return $count;
    }
}
