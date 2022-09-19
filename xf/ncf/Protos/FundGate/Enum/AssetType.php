<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AssetType extends AbstractEnum
{
    //资产种类
    const TYPE_STOCK = 10020; //股票
    const TYPE_BOND = 10010; //债券
    const TYPE_CASH = 1000202; //现金
    const TYPE_OTHER = 10090;//其它
    const TYPE_NV = 11001;//资产净值
    const TYPE_TT = 10099;//总资产

    private static $_details = array(
        self::TYPE_STOCK => '股票',
        self::TYPE_BOND => '债券',
        self::TYPE_CASH => '银行存款',
        self::TYPE_OTHER => '其它',
        self::TYPE_NV => '资产净值',
        self::TYPE_TT => '总资产',
    );

    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }
}
