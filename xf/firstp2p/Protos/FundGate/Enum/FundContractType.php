<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundContractType extends AbstractEnum
{
    // 基金类型
    const RISK_DISCLOSURE  = 0; // 风险揭示书
    const ELECTRONIC_CONTRACT = 1; // 电子合同

    private static $_details = array(
        self::RISK_DISCLOSURE => '风险揭示书',
        self::ELECTRONIC_CONTRACT  => '电子合同',
    );
    private static $_url = array(
        self::RISK_DISCLOSURE  => 'riskDisclosure',
        self::ELECTRONIC_CONTRACT  => 'electronicContract',
    );


    public static function getMap()
    {
        return self::$_details;
    }

    public static function getName($type)
    {
        return isset(self::$_details[$type]) ? self::$_details[$type] : "";
    }

    public static function getUrl($type)
    {
        return isset(self::$_url[$type]) ? self::$_url[$type] : "";
    }
}
