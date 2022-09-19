<?php
/**
 * DeliveryRegionService class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace core\service;

use system\libs\oauth;
use core\dao\DeliveryRegionModel;

/**
 * Class DeliveryRegionService
 * @package core\service
 */
class DeliveryRegionService extends BaseService {
    /**
     * 获取地区信息
     *
     * @return DeliveryRegionModel
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function getRegion($id)
    {
        return DeliveryRegionModel::instance()->findViaSlave($id);
    }

    /**
     * 获取地区信息
     *
     * @return DeliveryRegionModel
     * @author 文岭<liwenling@ucfgroup.com>
     **/
    public function getRegions($ids)
    {
        $result = DeliveryRegionModel::instance()->getInfoByIds($ids);
        $data = array();
        if($result)foreach($result as $v){
            $data[$v['id']] = $v;
        }
        unset($result);
        return $data;
    }

    /**
     * checkRegions 
     * 验证地址关联正确
     * 
     * @param mixed $lv1 
     * @param mixed $lv2 
     * @param mixed $lv3 
     * @param mixed $lv4 
     * @access public
     * @return void
     */
    public function checkRegions($lv1, $lv2, $lv3 = null, $lv4 = null) {
        return DeliveryRegionModel::instance()->checkRegions($lv1, $lv2, $lv3, $lv4);
    }
}
