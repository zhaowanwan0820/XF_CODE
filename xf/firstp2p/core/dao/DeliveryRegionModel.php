<?php
/**
 * DeliveryRegionModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

/**
 * 地区信息
 *
 * @author wenyanlei@ucfgroup.com
 **/
class DeliveryRegionModel extends BaseModel
{
    public function getRegionsByLevel($region_level) {
        $condition = "region_level=:region_level";
        return $this->findAll($condition, true, '*', array(':region_level' => $region_level));
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
        if (empty($ids)) return array();

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        $records = array();
        foreach ($ids as $id) {
            if (empty($id) || !is_numeric($id)) {
                continue;
            }
            $records[] = intval($id);
        }

        if (empty($records)) return array();

        $ids = implode(',', $records);
        $condition = " id IN(:ids)";
        return $this->findAll($condition, true, 'id, name', array(":ids" => $ids));
    }

    /**
     * getCityTree
     * 根据city一级别获取树形结构
     *
     * @access public
     * @return void
     */
    public function getCityTree() {
        $query = "SELECT dr2.id, dr2.name, dr1.id sub_id, dr1.name sub_name FROM firstp2p_delivery_region dr1 LEFT JOIN firstp2p_delivery_region dr2"
                ." ON dr1.pid=dr2.id WHERE dr1.region_level=3";
        return $this->findAllBySql($query);
    }

    /**
     * checkRegions
     * 验证地址关联正确，只支持系统中的四级匹配，如果不再是四级，则该接口需要检查确认无问题
     *
     * @param mixed $lv1
     * @param mixed $lv2
     * @param mixed $lv3
     * @param mixed $lv4
     * @access public
     * @return void
     */
    public function checkRegions($lv1, $lv2, $lv3, $lv4) {
        $query = "SELECT COUNT(*) FROM firstp2p_delivery_region a LEFT JOIN firstp2p_delivery_region b ON a.pid=b.id LEFT JOIN firstp2p_delivery_region c ON b.pid=c.id LEFT JOIN firstp2p_delivery_region d ON c.pid=d.id";
        //WHERE a.id=:lv4 AND b.id=:lv3 AND c.id=:lv2 AND d.id=:lv1";
        $conds = array();
        if (!empty($lv4)) {
            $conds[] = "a.id=:lv4";
        }
        if (!empty($lv3)) {
            $conds[] = "b.id=:lv3";
        }
        if (!empty($lv2)) {
            $conds[] = "c.id=:lv2";
        }
        if (!empty($lv1)) {
            $conds[] = "d.id=:lv1";
        }
        if (!empty($conds)) {
            $query .= " WHERE ".implode(' AND ', $conds);
        }
        $params = array(
                    ':lv1' => $lv1,
                    ':lv2' => $lv2,
                    ':lv3' => $lv3,
                    ':lv4' => $lv4,
                );
        $ttl = $this->countBySql($query, $params);
        return $ttl >= 1;
    }

    /**
     * getRegionsByCity
     * 根据城市名字获取其地区面包屑
     *
     * @param mixed $city
     * @access public
     * @return void
     */
    public function getRegionsByCity($city)
    {
        $query = "SELECT dr2.id, dr2.name, dr1.id sub_id, dr1.name sub_name FROM firstp2p_delivery_region dr1"
                ." LEFT JOIN firstp2p_delivery_region dr2"
                ." ON dr1.pid=dr2.id WHERE dr1.region_level=3 AND dr1.name='".$this->escape($city)."'";
        return $this->findBySql($query);
    }

    /**
     * getRegionsByLevel3
     * 根据城市ID获取上级信息
     *
     * @param mixed $regionId
     * @access public
     * @return void
     */
    public function getRegionsByLevel3($regionId)
    {
        $query = "SELECT dr2.id, dr2.name, dr1.id sub_id, dr1.name sub_name FROM firstp2p_delivery_region dr1"
                ." LEFT JOIN firstp2p_delivery_region dr2"
                ." ON dr1.pid=dr2.id WHERE dr1.region_level=3 AND dr1.id='".$this->escape($regionId)."'";
        return $this->findBySql($query);
    }

} // END class DeliveryRegionModel extends BaseModel
