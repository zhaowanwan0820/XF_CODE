<?php

namespace core\service\third;

use core\service\BaseService;
use core\dao\third\ThirdDealLoadModel;

class ThirdDealLoadService extends BaseService
{
    public function getDealLoadIds($startId, $size){
        $result = ThirdDealLoadModel::Instance()->getDealLoadIds($startId, $size);
        $dealLoadIds = array();
        if (!empty($result)) {
            foreach ($result as $value) {
                $dealLoadIds[] = $value['id'];
            }
        }
        return $dealLoadIds;
    }

    public static function getInfoById($id){
        if(empty($id)){
            return false;
        }
        return ThirdDealLoadModel::Instance()->getDealLoadById(intval($id));
    }

}
