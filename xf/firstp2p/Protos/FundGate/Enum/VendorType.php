<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class VendorType extends AbstractEnum
{
    // 基金提供商
    const TYPE_LH = 1; //联合基金
    const TYPE_YX = 2; //盈信基金
    const TYPE_YXSM = 3; //盈信私募
    const TYPE_YTSM = 4; //宜投私募

    private static $_details = [
        self::TYPE_LH => '联合基金',
        self::TYPE_YX => '盈信基金',
        self::TYPE_YXSM => '盈信私募',
        self::TYPE_YTSM => '宜投私募',
   ];

    public static function getMap()
    {
        return self::$_details;
    }

}
