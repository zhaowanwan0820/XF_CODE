<?php

namespace NCFGroup\Ptp\daos;

use NCFGroup\Common\Extensions\Base\Pageable;
use \Assert\Assertion as Assert;
use NCFGroup\Ptp\models\Firstp2pDeliveryRegion;

/**
 * RegionDAO
 * Region地区相关数据库操作
 *
 * @package default
 */
class RegionDAO {

    public static function getRegionList($region_level) {
        $ret = array();
        $builder = Firstp2pDeliveryRegion::query()
                ->columns("id,pid,name,regionLevel")
                ->where('regionLevel <= :region_level:', array('region_level' => $region_level));
        $ret = $builder->execute()->toArray();
        return $ret;
    }

}
