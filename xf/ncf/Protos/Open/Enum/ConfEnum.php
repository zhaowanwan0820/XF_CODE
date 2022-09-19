<?php

namespace NCFGroup\Protos\Open\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ConfEnum extends AbstractEnum {

    //是否有效
    const UNEFFECT = 0;
    const EFFECT = 1;
    //配置类型
    const OPERATE_CONFIG = 0;
    const COMMON_CONFIG = 1;
    const APP_CONFIG = 2;
    const WEB_CONFIG = 3;
    const WAP_CONFIG = 4;
    //允许开发者配置
    const ALLOWED = 1;
    const UNALLOWED = 0;
    //前端显示配置类型
    const DISPLAY_TEXT = 1;
    const DISPLAY_SWITCH = 2;
    //配置分类
    const CATE_NO_FRONT = 0;
    const CATE_FEATURE = 1;
    const CATE_BASE = 2;
    const CATE_BANNER = 3;
    const CATE_TEMPLATE = 4;

    public static $IS_EFFECT = array(
        self::UNEFFECT => '无效',
        self::EFFECT => '有效',
    );
    public static $CONF_TYPE = array(
        self::OPERATE_CONFIG => '运营配置',
        self::COMMON_CONFIG => 'common配置',
        self::APP_CONFIG => '生成APK配置',
        self::WEB_CONFIG => '生成WEB配置',
        self::WAP_CONFIG => '生成WAP配置',
    );
    public static $IS_ALLOW_DEV_CONF = array(
        self::UNALLOWED => '不允许',
        self::ALLOWED => '允许',
    );
    public static $DISPLAY_TYPE = array(
        self::DISPLAY_TEXT => 'text',
        self::DISPLAY_SWITCH => '开关',
    );
    public static $CONF_CATEGORY = array(
        self::CATE_NO_FRONT => '前端不显示',
        self::CATE_FEATURE => '功能列表',
        self::CATE_BASE => '基础信息列表',
        self::CATE_BANNER => '广告与banner列表',
        self::CATE_TEMPLATE => '模板设置',
    );

}
