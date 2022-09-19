<?php
/**
 * FestivalActivities class file.
 *
 * @author zhaohui3@ucfgroup.com
 **/

namespace core\dao;

/**
 * 后台api配置信息
 *
 * @author zhaohui3@ucfgroup.com
 **/
class FestivalActivitiesModel extends BaseModel
{
    /**
     * getActivityInfoByCondition
     * 根据给定条件返回相应的活动配置信息
     * @access public
     * @param string $condition 查询条件
     * @param boolean $is_array 是否返回数组
     *
     */
    public function getActivityInfoByCondition($condition,$is_array=true,$fields="*", $params = array()) {
        return $this->findAllViaSlave($condition, $is_array, $fields,$params);
    }
    /**
     * getActivityInfoById
     * 根据活动id查询相应的活动配置信息
     * @access public
     * @param string $id 活动id
     * @param $is_slave 查询主库还是从库
     * @param $fields 返回的查询字段
     * @param $is_effect 是否要查询正在进行的活动"`id` = '{$id}' and `start_time` < '{$time}' and `end_time` > '{$time}' and `is_effect` = '1'"
     */
    public function getActivityInfoById($id,$is_effect = false,$fields="*",$is_slave = false) {
        if ($is_effect) {
            $time = time();
            return $this->findBy("`id`=:id AND `start_time`<:time AND `end_time` >:end_time AND `is_effect` =:is_effect",$fields, array(':id' => $id,':time' => $time,':end_time' => $time,':is_effect' => '1'),$is_slave);
        }
        return $this->findBy('id=:id',$fields, array(':id' => $id),$is_slave);
    }
    /**
     * updateActivityInfo
     * 更新的用户信息
     * @param string $data
     * @param boolean
     */
    public function updateActivityInfo($data) {
        if(empty($data)){
            return false;
        }
        $this->setRow($data);
        $this->update($data);
        return true;
    }
}
