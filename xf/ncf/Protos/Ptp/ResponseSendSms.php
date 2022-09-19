<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 发送手机短信验证码
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangyiming
 */
class ResponseSendSms extends ProtoBufferBase
{
    /**
     * 结果码
     *
     * @var int
     * @required
     */
    private $resCode;

    /**
     * 错误码，默认为0代表成功
     *
     * @var int
     * @optional
     */
    private $errCode = 0;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $errMsg = NULL;

    /**
     * @return int
     */
    public function getResCode()
    {
        return $this->resCode;
    }

    /**
     * @param int $resCode
     * @return ResponseSendSms
     */
    public function setResCode($resCode)
    {
        \Assert\Assertion::integer($resCode);

        $this->resCode = $resCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getErrCode()
    {
        return $this->errCode;
    }

    /**
     * @param int $errCode
     * @return ResponseSendSms
     */
    public function setErrCode($errCode = 0)
    {
        $this->errCode = $errCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }

    /**
     * @param string $errMsg
     * @return ResponseSendSms
     */
    public function setErrMsg($errMsg = NULL)
    {
        $this->errMsg = $errMsg;

        return $this;
    }

}