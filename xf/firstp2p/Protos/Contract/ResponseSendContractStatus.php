<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 发送状态回调
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseSendContractStatus extends ProtoBufferBase
{
    /**
     * 错误码(0为成功调用)
     *
     * @var int
     * @required
     */
    private $errorCode;

    /**
     * 错误信息
     *
     * @var string
     * @required
     */
    private $errorMsg;

    /**
     * 状态(0:失败,1:成功)
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseSendContractStatus
     */
    public function setErrorCode($errorCode)
    {
        \Assert\Assertion::integer($errorCode);

        $this->errorCode = $errorCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     * @return ResponseSendContractStatus
     */
    public function setErrorMsg($errorMsg)
    {
        \Assert\Assertion::string($errorMsg);

        $this->errorMsg = $errorMsg;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     * @return ResponseSendContractStatus
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }

}