<?php
/**
 * UserFestivalActivities class file.
 *
 * @author zhaohui3@ucfgroup.com
 **/

namespace core\dao;

/**
 * 后台api配置信息
 *
 * @author zhaohui3@ucfgroup.com
 **/
class UserFestivalActivitiesModel extends BaseModel
{
    /**
     * getUserActivityInfoByCondition
     * 根据给定条件返回相应的用户参与活动信息
     * @access public
     * @param string $condition 查询条件
     * @param boolean $is_array 是否返回数组
     *
     */
    public function getUserActivityInfoByCondition($condition,$is_array=true,$fields="*", $params = array()) {
        return $this->findAllViaSlave($condition, $is_array, $fields,$params);
    }
    /**
     * insertUserActivityInfoByCondition
     * 写入新的用户信息
     * @access public
     * @param string $condition 查询条件
     * @param boolean $is_array 是否返回数组
     *
     */
    public function insertUserActivityInfo($data) {
        if(empty($data)){
            return false;
        }
        $this->setRow($data);
        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }
    /**
     * updateUserActivityInfoByCondition
     * 更新的用户信息
     * @access public
     * @param string $condition 查询条件
     * @param boolean $is_array 是否返回数组
     */
    public function updateUserActivityInfo($data) {
        if(empty($data)){
            return false;
        }
        $this->setRow($data);
        $this->update($data);
        return true;
    }
}
