<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Common\Library\LoggerLevel;

use Assert\Assertion as Assert;

class SmsType extends AbstractEnum
{
    const __default = NULL;

    // 开户成功
    const ACCOUNT_SUCCESS = 1;

    protected static $details = [
        self::APPLY_SUCCESS => [ // ???
            "name" => "开户成功",
            "key" => "TPL_SMS_STOCK_ACCOUNT_SUCCESS",
       ],
    ];

    public static function has(SmsType $code)
    {
        return isset(self::$details[$code->__toString()]);
    }

    public static function getByType(SmsType $code)
    {
        $detail = [];
        if(self::has($code)) {
            $detail = self::$details[$code->__toString()];
            $detail["type"] = $code->__toString();
        }
        return $detail;
    }

    public function getKey()
    {
        if(!self::has($this)) {
            throw new \OutOfRangeException("短信消息未配置");
        }
        return self::$details[$this->getValue()]["key"];
    }

    public function getName()
    {
        if(!self::has($this)) {
            throw new \OutOfRangeException("短信消息未配置");
        }
        return self::$details[$this->getValue()]["name"];
    }
}
