<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;
use NCFGroup\Common\Extensions\Enum\EnumExceptionLevel;

class RPCErrorCode extends AbstractErrorCodeBase
{
    // protos一些常量
    const SUCCESS = 0;
    const FAILD = 1;

    //model保存异常
    const MODEL_SAVE_EXCEPTION = 900;
    const DUPLICATE_SUBMIT = 901;

    // 业务工程请从1000开始，[0, 1000) 保留给框架层。
    //params error code begin with 1200
    const REQUEST_PARAMS_ERROR = 1200;

    //warning error code begin with 1300
    const USERNAME_NOT_EXIST = 1300;
    const USERNAME_PASSWORD_NOT_MATCH= 1301;
    const REQUEST_FREQUENT = 1302;
    const REQUEST_FORBID = 1303;
    const AGE_ERROR = 1304;
    const IDNO_AREADY_EXIST = 1305;
    const USER_AREADY_AUTH = 1306;

    public static function initErrorInfo() {
        self::addToMapping(static::MODEL_SAVE_EXCEPTION, array(EnumExceptionLevel::ERROR, '模型操作失败'));
        self::addToMapping(static::DUPLICATE_SUBMIT, array(EnumExceptionLevel::WARNING, '请求已经受理，请勿重复提交'));
        self::addToMapping(static::REQUEST_PARAMS_ERROR, array(EnumExceptionLevel::ERROR, "请求参数不正确！"));

        self::addToMapping(static::USERNAME_NOT_EXIST, array(EnumExceptionLevel::WARNING, "用户名不存在！"));
        self::addToMapping(static::USERNAME_PASSWORD_NOT_MATCH, array(EnumExceptionLevel::WARNING, "用户名和密码不匹配！"));
        self::addToMapping(static::REQUEST_FREQUENT, array(EnumExceptionLevel::WARNING, "请求过于频繁，请发送验证码！"));
        self::addToMapping(static::REQUEST_FORBID, array(EnumExceptionLevel::WARNING, "请求过于频繁，禁止请求！"));
        self::addToMapping(static::AGE_ERROR, array(EnumExceptionLevel::WARNING, "身份认证失败，平台仅支持年龄为18-70周岁的用户进行投资！"));
        self::addToMapping(static::IDNO_AREADY_EXIST, array(EnumExceptionLevel::WARNING, "您输入的身份证号已存在！"));
        self::addToMapping(static::USER_AREADY_AUTH, array(EnumExceptionLevel::WARNING, "该用户已经认证过了，不需要再认证了！"));
    }
}
