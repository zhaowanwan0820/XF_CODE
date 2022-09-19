<?php
namespace NCFGroup\Protos\Future\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Common\Library\LoggerLevel;

use Assert\Assertion as Assert;

class SmsType extends AbstractEnum
{
    const __default = NULL;

    // 申请配资
    const APPLY_SUCCESS = 1;
    const APPLY_FAILED = 2;

    // 续期
    const RENEW_SUCCESS = 3;
    const RENEW_FAILED = 4;

    // 追加
    const APPEND_SUCCESS = 5;
    const APPEND_FAILED = 6;

    // 终结清算
    const END_SUCCESS = 7;

    // 提取利润
    const PROFIT_SUCCESS = 8;
    const PROFIT_FAILED = 9;

    // 触线
    const WARNING = 10;
    const CLOSE = 11;

    protected static $details = [
        self::APPLY_SUCCESS => [ // 1199
            "name" => "申请配资审核通过",
            "key" => "TPL_SMS_PEIZI_APPLY_SUCCESS",
        ],

        self::APPLY_FAILED => [ // 1200
            "name" => "申请配资审核失败",
            "key" => "TPL_SMS_PEIZI_APPLY_FAILED",
        ],

        self::RENEW_SUCCESS => [ // 1201
            "name" => "续期审核成功",
            "key" => "TPL_SMS_PEIZI_RENEW_SUCCESS",
        ],

        self::RENEW_FAILED => [ // 1202
            "name" => "续期审核失败",
            "key"  => "TPL_SMS_PEIZI_RENEW_FAILED",
        ],

        self::APPEND_SUCCESS => [ // 1203
            "name" => "追加审核成功",
            "key" => "TPL_SMS_PEIZI_APPEND_SUCCESS"
        ],

        self::APPEND_FAILED => [ // 1204
            "name" => "追加审核失败",
            "key" => "TPL_SMS_PEIZI_APPEND_FAILED",
        ],

        self::END_SUCCESS => [ // 1205
            "name" => "清算审核成功",
            "key" => "TPL_SMS_PEIZI_END_SUCCESS",
        ],

        self::PROFIT_SUCCESS => [ // 1206
            "name" => "提取收益审核成功",
            "key" => "TPL_SMS_PEIZI_PROFIT_SUCCESS",
        ],

        self::PROFIT_FAILED => [ // 1207
            "name" => "提取收益审核失败",
            "key" => "TPL_SMS_PEIZI_PROFIT_FAILED",
        ],

        self::WARNING => [ // 1208
            "name" => "触警告线",
            "key" => "TPL_SMS_PEIZI_WARNING",
        ],

        self::CLOSE => [ // 1209
            "name" => "触平仓线（合约未到期）",
            "key" => "TPL_SMS_PEIZI_CLOSE"
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

    public function allowedRongNiu()
    {
        if($this->getValue() == self::WARNING || $this->getValue() == self::CLOSE) {
            return true;
        }
        throw new \OutOfRangeException("融牛只能发送警告和平仓类短信");
    }
}
