<?php
/**
 * 基础通用接口 
 */
namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Regions extends AppBaseAction {
    public function invoke() {
        $this->form = new Form();
        $regions = $this->rpc->local('RegionService\getValidRegions');
        $this->json_data = $regions;
    }
}
