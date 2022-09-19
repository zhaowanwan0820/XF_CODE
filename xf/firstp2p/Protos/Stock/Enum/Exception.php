<?php
namespace NCFGroup\Protos\Stock\Enum;
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
    const PARAM_ERROR = 10001;
    const MATCH_USER_INFO = 10002;
    const USER_NOT_EXISTS = 10003;
    const USER_VEDIO_PASS = 10004;
    const THRIFT_EXCEPTION = 10005;//thrift异常
    const USER_NOT_VALID = 10006;//
    const BANK_NOT_EXISTS = 10007;
    const SIGN_FAIL = 11001;
    const USER_INFO_ERROR = 10008;
    const REFUSE_REGISTER = 10009;
    const STAGE_ERROR = 10010;
    const CHECK_USER = 10011;
    const CONTRACT_NOT_EXISTS = 10012; //合同不存在
    const OPERATOR_ERROR = 10013; //视频见证人员异常
    const WEAK_PASSWORD = 10014; //弱密码

    const USER_NOT_LOGIN = 20001;
    const VCODE_WRONG = 20002;
    const USER_LOGIN_FAIL = 20003;
    const USER_PWD_WRONG = 20004;
    const USER_REFUSE_LOGIN = 20005;

    const SYSTEM_MAINTAIN = 30000;
    const SYSTEM_MAINTAIN_IS_NOT_DEAD = 30001;

    const BONUS_DATA_ERROR = 40000;

    const USER_NO_BANK_CARD = 50000;
    const TRADE_GDH_EMPTY = 50001;

    private static $details = [
        self::WEAK_PASSWORD => array(
            'message' => '密码强度较弱，请修改相应密码：%s',
            'level' => LoggerLevel::INFO,
        ),
        self::OPERATOR_ERROR => array(
            'message' => '视频见证人员异常，请重新进行视频见证.',
            'level' => LoggerLevel::INFO,
        ),
        self::USER_REFUSE_LOGIN => array(
            'message' => '你的请求已经超过了请求次数，不可以再请求！',
            'level' => LoggerLevel::INFO,
        ),
        self::SYSTEM_MAINTAIN_IS_NOT_DEAD  => array(
            'message' => '系统正在维护，请稍后再使用',
            'level' => LoggerLevel::INFO,
        ),
        self::SYSTEM_MAINTAIN => array(
            'message' => '系统维护',
            'level' => LoggerLevel::INFO,
        ),
        self::CHECK_USER => array(
            'message' => '绑定身份认证',
            'level' => LoggerLevel::INFO,
        ),
        self::STAGE_ERROR => array(
            'message' => '操作步骤异常',
            'level' => LoggerLevel::INFO,
        ),
        self::REFUSE_REGISTER => array(
            'message' => '%s',
            'level' => LoggerLevel::INFO,
        ),
        self::USER_PWD_WRONG => array(
            'message' => '用户名或交易密码错误',
            'level' => LoggerLevel::INFO,
        ),
        self::USER_LOGIN_FAIL => array(
            'message' => '系统错误, 请稍后再试',
            'level' => LoggerLevel::INFO,
        ),
        self::USER_NOT_LOGIN => array(
            'message' => '登录失效，请重新登录',
            'level' => LoggerLevel::INFO,
        ),
        self::VCODE_WRONG => array(
            'message' => '验证码错误',
            'level' => LoggerLevel::INFO,
        ),
        self::USER_INFO_ERROR => array(
            'message' => '用户信息不完整，请检查是否完成流程',
            'level' => LoggerLevel::INFO,
        ),
        self::SIGN_FAIL => array(
            'message' => '验签失败',
            'level' => LoggerLevel::INFO,
        ),
        self::BANK_NOT_EXISTS => [
            'message' => '银行信息不存在',
            'level' => LoggerLevel::INFO,
        ],
        self::USER_NOT_VALID => [
            'message' => '身份认证信息不正确',
            'level' => LoggerLevel::INFO,
        ],
        self::UNKNOWN => [
            "message" => "系统繁忙，请稍后再试或联系客服.",
            "level" => LoggerLevel::FATAL,
        ],
        self::PARAM_ERROR => [
            'message' => "参数(%s)不正确",
            'level' => LoggerLevel::INFO,
        ],
        self::MATCH_USER_INFO => [
            'message' => "用户信息与P2P注册信息不符",
            'level' => LoggerLevel::INFO,
        ],
        self::USER_NOT_EXISTS => [
            'message' => "用户不存在",
            'level' => LoggerLevel::INFO,
        ],
        self::USER_VEDIO_PASS => [
            'message' => '用户视频认证已经通过,不能修改信息',
            'level' => LoggerLevel::INFO,
        ],
        self::THRIFT_EXCEPTION => [
            'message' => '系统繁忙, 请重试',
            'level' => LoggerLevel::INFO,
        ],
        self::CONTRACT_NOT_EXISTS => [
            'message' => '合同不存在, 请联系客服',
            'level' => LoggerLevel::INFO,
        ],
        self::BONUS_DATA_ERROR => [
            'message' => '红包数据有问题',
            'level' => LoggerLevel::INFO,
        ],
        self::USER_NO_BANK_CARD => [
            'message' => '暂未绑定银行卡',
            'level' => LoggerLevel::INFO,
        ],
        self::TRADE_GDH_EMPTY => array(
            'message' => '您还没有开通%sA股交易权限，请联系客服开通，谢谢。',
            'level' => LoggerLevel::INFO,
        ),
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
        return "NCFGroup\\Protos\\Stock\\Exception\\";
    }
}
