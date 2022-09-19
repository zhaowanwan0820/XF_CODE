<?php

namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;
use NCFGroup\Common\Extensions\Enum\EnumExceptionLevel;

class RPCErrorCode extends AbstractErrorCodeBase {

    //[100,600) oauth2框架错误码
    //[600-800) 系统级别错误码

    const CLIENTID_NULL = 600;
    const CLIENTINFO_NULL = 601;
    const TIMESTAMP_NULL = 602;
    const TIMESTAMP_ERROR = 603;
    const SIGN_NULL = 604;
    const SIGN_ERROR = 605;
    const LACK_AUTHORITY = 606;
    const AUTH_FAILED = 607;
    const OPENID_NULL = 608;
    const OPENID_ERROR = 609;
    //维护期间异常
    const MAINTENANCE_EXCEPTION = 800;
    //model保存异常
    const MODEL_SAVE_EXCEPTION = 900;
    const DUPLICATE_SUBMIT = 901;
    const SQL_ERROR = 902;
    const SQL_NULL = 903;
    // 业务工程请从10000开始
    const ERROR_PARAM = 10000;
    const LOAD_BINDINFO_ERR = 10001;
    const LOAD_USERINFO_ERR = 10002;

    const USER_NOT_LOGIN = 20000; // 未登录用户
    const USER_NOT_DEV   = 20001; // 非开发者用户
    const GUIDE_ERROR    = 20002; // 引导错误

    public static function initErrorInfo() {
        self::addToMapping(static::SQL_ERROR, array(EnumExceptionLevel::ERROR, "sql执行错误"));
        self::addToMapping(static::SQL_NULL, array(EnumExceptionLevel::WARNING, "查询无记录"));

        self::addToMapping(static::MAINTENANCE_EXCEPTION, array(EnumExceptionLevel::NONE, "系统正在维护"));

        self::addToMapping(static::MODEL_SAVE_EXCEPTION, array(EnumExceptionLevel::ERROR, "model save error！"));
        self::addToMapping(static::DUPLICATE_SUBMIT, array(EnumExceptionLevel::WARNING, "请求已经受理，请勿重复提交"));

        self::addToMapping(static::CLIENTID_NULL, array(EnumExceptionLevel::ERROR, "clientId不能为空"));
        self::addToMapping(static::CLIENTINFO_NULL, array(EnumExceptionLevel::ERROR, "clientId无效"));
        self::addToMapping(static::TIMESTAMP_NULL, array(EnumExceptionLevel::ERROR, "时间戳为空"));
        self::addToMapping(static::TIMESTAMP_ERROR, array(EnumExceptionLevel::ERROR, "时间戳不正确"));
        self::addToMapping(static::SIGN_NULL, array(EnumExceptionLevel::ERROR, "签名为空"));
        self::addToMapping(static::SIGN_ERROR, array(EnumExceptionLevel::ERROR, "签名不正确"));

        self::addToMapping(static::OPENID_NULL, array(EnumExceptionLevel::ERROR, "open_id不能为空"));
        self::addToMapping(static::OPENID_ERROR, array(EnumExceptionLevel::ERROR, "open_id不正确"));
        self::addToMapping(static::ERROR_PARAM, array(EnumExceptionLevel::ERROR, "参数错误"));
        self::addToMapping(static::LOAD_BINDINFO_ERR, array(EnumExceptionLevel::ERROR, "获取绑定信息失败"));
        self::addToMapping(static::LOAD_USERINFO_ERR, array(EnumExceptionLevel::ERROR, "获取用户信息失败"));
        self::addToMapping(static::USER_NOT_LOGIN, array(EnumExceptionLevel::ERROR, "用户尚未登录"));
        self::addToMapping(static::USER_NOT_DEV, array(EnumExceptionLevel::ERROR, "非开发者用户"));
        self::addToMapping(static::GUIDE_ERROR, array(EnumExceptionLevel::ERROR, "非正常的引导步骤"));
    }

}
