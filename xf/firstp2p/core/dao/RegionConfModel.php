<?php
/**
 * RegionConfModel class file.
 *
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * 地域信息
 *
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/
class RegionConfModel extends BaseModel {
    /**
     * 获取地区名称
     * @param int $id
     * @return string
     */
    public function getRegionName($id) {
        $row = $this->findViaSlave($id);
        return $row->name;
    }

    /**
     * getInfoByIds
     * 根据给定ID返回其名字信息
     *
     * @param mixed $ids
     * @access public
     * @return void
     */
    public function getInfoByIds($ids) {
        $condition = " id IN(:ids)";
        return $this->findAllViaSlave($condition, true, 'id, name', array(":ids" => $ids));
    }

} // END class RegionConfModel extends BaseModel

