<?php

namespace core\service;

use core\dao\DeliveryRegionModel;
use core\dao\UserBankcardModel;
use core\dao\RegionConfModel;

class RegionService extends BaseService {

    /**
     * getValidRegions 
     * 获取系统中有使用用户的地区信息
     * 
     * @access public
     * @return void
     */
    public function getValidRegions() {
        $regionIds = UserBankcardModel::instance()->getValidRegionIds();
        $ids = array();
        foreach ($regionIds as $region) {
            $ids[] = $region['rid'];
        }
        $regionInfos = DeliveryRegionModel::instance()->getInfoByIds($ids);
        return $regionInfos;
    }

    /**
     * getRegionTree 
     * 拼出树形结构，给前端调用
     * 
     * @access public
     * @return void
     */
    public function getRegionTree() {
        $rs = DeliveryRegionModel::instance()->getCityTree();
        $tree = array();
        foreach ($rs as $r) {
            if (!isset($tree[$r->id])) {
                $tree[$r->id] = array(
                                    'id' => $r->id,
                                    'name' => $r->name,
                                );
            }
            $tree[$r->id]['subs'][] = array(
                                        'id' => $r->sub_id,
                                        'name' => $r->sub_name,
                                    );
        }
        return array_values($tree);
    }

    /**
     * getRegionName
     * 获得地区信息
     * @param integer $regionId
     * @return string
     */
    public function getRegionName($regionId) {
        return RegionConfModel::instance()->getRegionName($regionId);
    }

}
