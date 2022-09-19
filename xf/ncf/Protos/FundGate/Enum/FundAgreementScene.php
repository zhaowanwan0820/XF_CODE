<?php
namespace NCFGroup\Protos\FundGate\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class FundAgreementScene extends AbstractEnum
{
    const TYPE_PLATFORM_DISCLAIMER = 'platform_disclaimer';//网信理财平台免责声明
    const TYPE_WXLC_FUND_PLATFORM_SERVICES = 'wxlc_fund_platform_services';//网信理财基金平台服务协议
    const TYPE_INVESTORS_RIGHT = 'investors_right';//投资人权益须知
    const TYPE_YT_FUND_PLATFORM_SERVICES = 'yt_fund_platform_services';//深圳宜投基金销售有限公司销售服务协议
    const TYPE_WXLC_HINT = 'wxlc_hint';//网信理财温馨提示


    const SCENE_ACCOUNT = 'scene_account';//开户场景
    const SCENE_FUND_CHANNEL = 'scene_fundChannel';//基金频道

    const NOT_ACTIVE = '0';
    const IS_ACTIVE = '1';

    private static $_allTypes = array(
        self::TYPE_INVESTORS_RIGHT,
        self::TYPE_PLATFORM_DISCLAIMER,
        self::TYPE_WXLC_FUND_PLATFORM_SERVICES,
        self::TYPE_YT_FUND_PLATFORM_SERVICES,
        self::TYPE_WXLC_HINT,
     );//协议所有类型
    private static $_typeDetails = array(
        self::TYPE_INVESTORS_RIGHT => "投资人权益须知",
        self::TYPE_PLATFORM_DISCLAIMER => "网信理财平台免责声明",
        self::TYPE_WXLC_FUND_PLATFORM_SERVICES => "网信理财基金平台服务协议",
        self::TYPE_YT_FUND_PLATFORM_SERVICES => "深圳宜投基金销售有限公司销售服务协议",
        self::TYPE_WXLC_HINT => "网信理财温馨提示信息",
    );

    private static $_sceneTypes = array(
       self::SCENE_ACCOUNT => array(
            self::TYPE_INVESTORS_RIGHT,
            self::TYPE_YT_FUND_PLATFORM_SERVICES,
            ),
       self::SCENE_FUND_CHANNEL => array(
            self::TYPE_PLATFORM_DISCLAIMER,
            self::TYPE_WXLC_FUND_PLATFORM_SERVICES,
            ),
    );//场景对应的协议类型

    private static $_activeTypes = array(
        self::NOT_ACTIVE,
        self::IS_ACTIVE,
        );//是否激活

    public static function getAllTypes()
    {
        return self::$_allTypes;
    }

    public static function getAccountTypes()
    {
        return self::$_sceneTypes[self::SCENE_ACCOUNT];
    }

    public static function getFundChannelTypes()
    {
        return self::$_sceneTypes[self::SCENE_FUND_CHANNEL];
    }

    public static function getAllScence()
    {
        return array_keys(self::$_sceneTypes);
    }

    public static function getActiveMap()
    {
        return self::$_activeTypes;
    }

    public static function getTypesMap()
    {
        return self::$_typeDetails;
    }


}
