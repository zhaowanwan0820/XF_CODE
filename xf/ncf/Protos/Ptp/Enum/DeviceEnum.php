<?php

/**
 * DeviceEnum.php
 * 
 * Filename: DeviceEnum.php
 * Descrition: 端标识统一枚举
 * Author: yutao@ucfgroup.com
 * Date: 16-5-12 下午3:22
 */

namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Ptp\Enum\PaymentEnum;

class DeviceEnum extends AbstractEnum {

    const DEVICE_WEB = 0;
    const DEVICE_ANDROID = 4;
    const DEVICE_IOS = 3;
    const DEVICE_WAP = 8;
    const DEVICE_UNKNOWN = 5;

    public static $deviceName = [
        self::DEVICE_WEB => 'web',
        self::DEVICE_ANDROID => 'android',
        self::DEVICE_IOS => 'ios',
        self::DEVICE_WAP => 'wap',
        self::DEVICE_UNKNOWN => 'unknown',
    ];

    //payment device 映射表
    public static $paymentDeviceMap = [
        PaymentEnum::PLATFORM_WEB => self::DEVICE_WEB,
        PaymentEnum::PLATFORM_ANDROID => self::DEVICE_ANDROID,
        PaymentEnum::PLATFORM_IOS => self::DEVICE_IOS,
        PaymentEnum::PLATFORM_H5 => self::DEVICE_WAP,
    ];

}
