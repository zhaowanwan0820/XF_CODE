<?php
/**
 * DeliveryModel class file.
 *
 * @author zhaohui3@ucfgroup.com
 **/

namespace core\dao;

/**
 * 地区信息
 *
 * @author wenyanlei@ucfgroup.com
 **/
class DeliveryModel extends BaseModel
{
    /**
     * getInfoByCondition
     * 根据给定条件返回用户地址信息
     * @access public
     * @param string $condition 查询条件
     * @param boolean $is_array 是否返回数组
     *
     */
    public function getInfoByCondition($condition,$is_array=true,$fields="*", $params = array()) {
        return $this->findAll($condition, $is_array, $fields,$params);
    }
}