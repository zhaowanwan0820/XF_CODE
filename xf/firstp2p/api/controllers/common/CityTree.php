<?php
/**
 * 基础通用接口 
 */
namespace api\controllers\common;

use api\controllers\AppBaseAction;

class CityTree extends AppBaseAction {
    public function invoke() {
        $regions = $this->rpc->local('RegionService\getRegionTree');
        $this->json_data = $regions;
    }
}
