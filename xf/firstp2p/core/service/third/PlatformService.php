<?php

namespace core\service\third;

use core\dao\third\ThirdDealModel;
use core\service\BaseService;

class PlatformService extends BaseService
{
    /**
     *获取平台信息.
     */
    public static function getPlatformInfo()
    {
        $platformInfo = array();
        $result = ThirdDealModel::Instance()->getPlatformInfo();
        if (!empty($result)) {
            foreach ($result as $value) {
                $platformInfo[$value['client_id']] = $value['client_name'];
            }
        }
        return $platformInfo;
    }
}
