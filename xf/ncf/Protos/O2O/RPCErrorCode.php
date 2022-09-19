<?php

namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;
use NCFGroup\Common\Extensions\Enum\EnumExceptionLevel;

class RPCErrorCode extends AbstractErrorCodeBase {
    // rpc重试，对于需要进行重试的错误码，这个错误码不要随便改变
    const RPC_RETRY_AGAIN_LATER = 16;

    //维护期间异常
    const MAINTENANCE_EXCEPTION = 800;
    //model保存异常
    const MODEL_SAVE_EXCEPTION = 900;
    const DUPLICATE_SUBMIT = 901;
    // 业务工程请从1000开始，[0, 1000) 保留给框架层。
    //product error code begin with 1200
    const PRODUCT_NOT_EXIST = 1200;
    const PRODUCT_LIST_ERROR = 1201;
    //coupon_group error code begin with 1300
    const COUPON_GROUP_NOT_EXIST = 1300;
    const COUPON_GROUP_LIST_ERROR = 1301;
    const COUPON_GROUP_SETTING_ERROR = 1302;
    const COUPON_GROUP_NO_STOCK = 1303;
    const DISCOUNT_GROUP_NOT_EXIST = 1330;
    const DISCOUNT_GROUP_LIST_ERROR = 1331;
    const DISCOUNT_GROUP_SETTING_ERROR = 1332;
    const DISCOUNT_RULE_NOT_EXIST = 1334;
    const TRIGGER_RULE_NOT_EXIST = 1340;
    //coupon error code, begin with 1400
    const COUPON_ERROR = 1401;
    const COUPON_NO_EXISIT = 1402;
    const COUPON_USED = 1403;
    const COUPON_EXPIRED = 1404;
    const COUPON_WAIT_CONFIRM = 1405;
    const COUPON_NOT_START = 1406;
    const COUPON_NOT_SUIT = 1407;
    const COUPON_NOT_ONLINE_EXCHANGE = 1408;
    const COUPON_TOKEN = 1409;
    //store error code, begin with 1500
    const STORE_ERROR = 1500;
    const STORE_FORM_IS_NULL = 1501;
    // RPC
    const RPC_P2P_LOCK_MONEY_FAILED = 1601;
    const RPC_P2P_MONEY_CALL_FAILED = 1602;
    const RPC_UNION_FUND_CALL_FAILED = 1603;
    // game
    const GAME_NO_STOCK = 1701;     // 游戏奖品库存不足
    const GAME_NO_TIMES = 1702;     // 游戏次数不足
    const GAME_NOT_START = 1703;    // 游戏还未开始
    const GAME_IS_END = 1704;       // 游戏已结束
    const GAME_NOT_EXIST = 1705;    // 游戏不存在

    public static function initErrorInfo() {
        self::addToMapping(static::MAINTENANCE_EXCEPTION, array(EnumExceptionLevel::NONE, "系统正在维护"));

        self::addToMapping(static::MODEL_SAVE_EXCEPTION, array(EnumExceptionLevel::ERROR, "model save error！"));
        self::addToMapping(static::DUPLICATE_SUBMIT, array(EnumExceptionLevel::WARNING, "请求已经受理，请勿重复提交"));

        self::addToMapping(static::PRODUCT_NOT_EXIST, array(EnumExceptionLevel::ERROR, "该商品不存在！"));
        self::addToMapping(static::PRODUCT_LIST_ERROR, array(EnumExceptionLevel::ERROR, "获取商品列表失败！"));

        self::addToMapping(static::COUPON_TOKEN, array(EnumExceptionLevel::ERROR, "处理中,请耐心等待"));
        self::addToMapping(static::COUPON_GROUP_NOT_EXIST, array(EnumExceptionLevel::ERROR, "该券组不存在！"));
        self::addToMapping(static::COUPON_GROUP_LIST_ERROR, array(EnumExceptionLevel::ERROR, "获取券组列表失败！"));
        self::addToMapping(static::COUPON_GROUP_SETTING_ERROR, array(EnumExceptionLevel::ERROR, "券组配置错误！"));
        self::addToMapping(static::COUPON_GROUP_NO_STOCK, array(EnumExceptionLevel::ERROR, "抢光了！下次要尽早哦！"));

        self::addToMapping(static::DISCOUNT_GROUP_NOT_EXIST, array(EnumExceptionLevel::ERROR, "该投资券组不存在！"));
        self::addToMapping(static::DISCOUNT_GROUP_LIST_ERROR, array(EnumExceptionLevel::ERROR, "获取投资券组列表失败！"));
        self::addToMapping(static::DISCOUNT_GROUP_SETTING_ERROR, array(EnumExceptionLevel::ERROR, "投资券组配置不正确！"));

        self::addToMapping(static::DISCOUNT_RULE_NOT_EXIST, array(EnumExceptionLevel::ERROR, "该投资规则不存在！"));

        self::addToMapping(static::TRIGGER_RULE_NOT_EXIST, array(EnumExceptionLevel::ERROR, "触发规则不存在！"));

        self::addToMapping(static::STORE_FORM_IS_NULL, array(EnumExceptionLevel::ERROR, "该供应商没有匹配动态表单选项！"));
        self::addToMapping(static::COUPON_ERROR, array(EnumExceptionLevel::ERROR, "券码未知错误"));
        self::addToMapping(static::COUPON_NO_EXISIT, array(EnumExceptionLevel::ERROR, "该券码不存在！"));
        self::addToMapping(static::COUPON_USED, array(EnumExceptionLevel::ERROR, "该券码已使用！"));
        self::addToMapping(static::COUPON_EXPIRED, array(EnumExceptionLevel::ERROR, "该券码已过期！！"));
        self::addToMapping(static::COUPON_WAIT_CONFIRM, array(EnumExceptionLevel::ERROR, "券码待兑换确认中！"));
        self::addToMapping(static::COUPON_NOT_START, array(EnumExceptionLevel::ERROR, "券码未到兑换日期！"));
        self::addToMapping(static::COUPON_NOT_SUIT, array(EnumExceptionLevel::ERROR, "券码不适合本门店！"));
        self::addToMapping(static::COUPON_NOT_ONLINE_EXCHANGE, array(EnumExceptionLevel::ERROR, "券码不是线上兑换类型！"));
    }

}
