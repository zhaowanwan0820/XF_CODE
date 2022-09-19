<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class BannerType extends AbstractEnum
{
    const NOURL = 0;
    const H5URL = 1;
    const NATIVEURL = 2;

    private static $_details = [
        self::NOURL => '无链接',
        self::H5URL => 'h5链接',
        self::NATIVEURL => 'native链接',
    ];

    public static function getMap()
    {
        return self::$_details;
    }
}
