<?php
namespace NCFGroup\Common\Extensions\Base;

use NCFGroup\Common\Extensions\Enum\EnumExceptionLevel;

abstract class AbstractErrorCodeBase extends EnumBase
{
    // 保留 [0-200) 之间为系统预留错误代码。
    const SUCCESS                   = 0;  //成功
    const FAILED                    = 1;  //失败
    const SERVER_ERROR              = 2;  //服务器错误
    const UNKNOWN_ERROR             = 3;  //未知错误
    const UPDATE_ERROR              = 4;  //保存错误
    const DELETE_ERROR              = 5;  //删除错误
    const INSERT_ERROR              = 6;  //新增错误
    const ID_NOT_EXIST              = 7;  //ID不存在
    const ENUM_OUT_OF_BOUNDS        = 8;  //枚举类型超出
    const OUT_OF_BOUNDS             = 9;  //枚举类型超出
    const RPC_IP_RESTRICTION        = 10;  //RPC调用处限制了IP，不匹配
    const RPC_PARAM_MISS            = 11;  //RPC调用处的请求参数缺失
    const RPC_SERVICE_NOT_FOUND     = 12;  //RPC的服务找不到
    const RPC_SIGNATURE_RESTRICTION = 13;  //RPC的验签参数不匹配

    // 用户自定义的错误代码，请从1000开始。
    protected static $mapping = array(
        self::SUCCESS                   => array(EnumExceptionLevel::NONE, "成功！"),
        self::FAILED                    => array(EnumExceptionLevel::ERROR, "失败!"),
        self::SERVER_ERROR              => array(EnumExceptionLevel::ERROR, "服务器错误！"),
        self::UNKNOWN_ERROR             => array(EnumExceptionLevel::ERROR, "服务器未知错误！"),
        self::UPDATE_ERROR              => array(EnumExceptionLevel::ERROR, "更新错误！"),
        self::DELETE_ERROR              => array(EnumExceptionLevel::ERROR, "删除错误！"),
        self::INSERT_ERROR              => array(EnumExceptionLevel::ERROR, "插入错误！"),
        self::ID_NOT_EXIST              => array(EnumExceptionLevel::ERROR, "%s ID（%s) 不存在！"),
        self::ENUM_OUT_OF_BOUNDS        => array(EnumExceptionLevel::ERROR, "枚举类型(%s)超出！"),
        self::OUT_OF_BOUNDS             => array(EnumExceptionLevel::ERROR, "超出边界！"),
        self::RPC_IP_RESTRICTION        => array(EnumExceptionLevel::ERROR, "客户端IP地址（%s）受限制！"),
        self::RPC_PARAM_MISS            => array(EnumExceptionLevel::ERROR, "RPC调用参数不存在！"),
        self::RPC_SERVICE_NOT_FOUND     => array(EnumExceptionLevel::ERROR, "RPC服务（%s）不存在"),
        self::RPC_SIGNATURE_RESTRICTION => array(EnumExceptionLevel::ERROR, "客户端验证签名（%s）错误！"),
    );

    protected static function addToMapping($key, $value)
    {
        if (!array_key_exists($key, self::$mapping)) {
            self::$mapping[$key] = $value;
        }
    }

    public static function initErrorInfo()
    {

    }

    public static function getErrorInfo($errorCode)
    {
        // 定义该Error Code的Exception Level以及Exception Message
        static::initErrorInfo();
        if (array_key_exists($errorCode, static::$mapping)) {
            return self::$mapping[$errorCode];
        } else {
            throw new \OutOfBoundsException("ErrorCode($errorCode) is not existed!", self::OUT_OF_BOUNDS);
        }
    }

}
