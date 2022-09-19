<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use core\enum\PaymentEnum;

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
