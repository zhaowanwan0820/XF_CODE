<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Common\Library\LoggerLevel;

use Assert\Assertion as Assert;

class Exception extends AbstractEnum
{
    const __default = self::UNKNOWN;

    /**
     * 请不要使用重复异常码
     */
    const UNKNOWN = 10000;
    const USER_NOT_EXISTS = 10001;
    const AUTH_FAILED = 10002;
    const NEED_LOGIN = 10003;
    const OP_RETRY = 10004;
    const RULE_VERSION_ERROR = 10005;
    const ORDER_NOT_EXISTS = 10007;
    const SIGN_ERROR = 10008;
    /**
     *  冻结失败
     */
    const FREEZE_FAIL = 10006;

    const MONEY_LESS_THAN_MIN_DEPOSIT = 10010;
    const DEPOSIT_RANGE = 10011;
    const USER_ORDER_NOT_EXISTS = 10012;
    const ORDER_STATUS_ERROR = 10013;

    /** 配资规则不存在 **/
    const RULE_NOT_EXISTS = 10014;
    /** 最低追加保证金金额错误 */
    const LEAST_ADD_DEPOSIT_ERROR = 10015;
    const USER_UNAUTH = 10016;

    private static $details = [
        self::USER_UNAUTH => [
            'message' => "用户未认证,请先认证用户.",
            'level' => LoggerLevel::INFO
        ],
        self::LEAST_ADD_DEPOSIT_ERROR => [
            "message" => "追加保证金最小金额(%s), 无法追加",
            "level" => LoggerLevel::INFO
        ],

        self::ORDER_STATUS_ERROR => [
            "message" => "用户订单状态失效",
            "level" => LoggerLevel::INFO
        ],

        self::USER_ORDER_NOT_EXISTS => [
            "message" => "用户订单不存在",
            "level" => LoggerLevel::INFO
        ],

        self::UNKNOWN => [
            "message" => "未知错误",
            "level" => LoggerLevel::FATAL,
        ],

        self::USER_NOT_EXISTS => [
            "message" => "用户不存在，请核实后再试",
            "level" =>  LoggerLevel::INFO,
        ],

        self::MONEY_LESS_THAN_MIN_DEPOSIT => [
            "message" => "保证金低于最小金额(%s)，无法进行配资",
            "level" => LoggerLevel::INFO,
        ],

        self::OP_RETRY => [
            "message" => "系统繁忙，请稍后再试",
            "level" => LoggerLevel::INFO,
        ],

        self::RULE_VERSION_ERROR => [
            "message" => "配资规则版本异常,刷新页面后再试",
            "level" => LoggerLevel::INFO,
        ],
        self::ORDER_NOT_EXISTS => [
            "message" => "订单不存在,请确认订单是否正确",
            "level" => LoggerLevel::INFO,
        ],
        self::FREEZE_FAIL => [
            "message" => "冻结失败",
            "level" => LoggerLevel::INFO,
        ],
        self::SIGN_ERROR => [
            "message" => "签名不正确",
            "level"  => LoggerLevel::INFO,
        ],
        self::DEPOSIT_RANGE => [
            "message" => "申请配资保证金范围: %s ~ %s",
            "level" => LoggerLevel::INFO,
        ],

        self::RULE_NOT_EXISTS => [
            "message" => "配资规则不存在",
            "level" => LoggerLevel::INFO,
        ],
    ];

    public static function has(Exception $code)
    {
        return isset(self::$details[$code->__toString()]);
    }

    public static function getByCode(Exception $code)
    {
        $detail = [];
        if(self::has($code)) {
            $detail = self::$details[$code->__toString()];
            $detail["code"] = $code->__toString();
        }
        return $detail;
    }

    public static function newException($eCode)
    {
        Assert::notEmpty($eCode);
        $code = new self($eCode);
        $codeMap2Name = array_flip(self::validValues(true));
        $eName = $codeMap2Name[$eCode];
        // USER_NOT_EXISTS -> \Demo\Protos\ExceptionUserNotExists
        $eClassName = __NAMESPACE__."\\Exception".\Phalcon\Text::camelize($eName);
        $exception = new $eClassName($code->getMessage());
        $exception->setCode($code->getCode());
        $exception->setLevel($code->getLevel());
        return $exception;
    }

    public function getMessage()
    {
        if(!self::has($this)) {
            return self::$details[self::__default]["message"];
        }
        return self::$details[$this->getValue()]["message"];
    }

    public function getLevel()
    {
        if(!self::has($this)) {
            return self::$details[self::__default]["level"];
        }
        return self::$details[$this->getValue()]["level"];
    }

    public function getCode()
    {
        return $this->getValue();
    }

    public static function exceptionClassPrefix()
    {
        return "NCFGroup\\Protos\\Future\\Exception\\";
    }
}
