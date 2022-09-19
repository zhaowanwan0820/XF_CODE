<?php

/**
 * Created by PhpStorm.
 * User: lvbaosong@ucfgroup.com
 * Date: 2016/6/23
 * Time: 15:42
 * 风控服务工厂
 */
namespace core\service\risk;

use libs\utils\Risk;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

class RiskServiceFactory{
    static $_instances = array();
    /**
     * @param string $serviceType 服务类型
     * @param string $platform 埋点平台
     * @param int $device 埋点调用端
     * @return  服务类实例
     */
    public static function instance($serviceType,$platform=Risk::PF_WEB,$device=DeviceEnum::DEVICE_WEB){

        if (isset(self::$_instances[$serviceType])) {
            return self::$_instances[$serviceType];
        }

        if(!$ServiceClass=Risk::getServiceClass($serviceType)){
            Logger::error("RiskServiceFactory:{$serviceType} is not exist!");
            throw new \Exception("{$serviceType} not exist!");
        }

        $serviceInstance = new $ServiceClass($serviceType,$platform,$device);
        self::$_instances[$serviceType] = $serviceInstance;

        return $serviceInstance;

    }
}
