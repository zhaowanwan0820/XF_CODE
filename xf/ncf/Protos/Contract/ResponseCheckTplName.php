<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据模板标识判断模板是否存在
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseCheckTplName extends ProtoBufferBase
{
    /**
     * 错误代码(0:调用成功,1:调用失败)
     *
     * @var int
     * @optional
     */
    private $errorCode = NULL;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $errorMsg = NULL;

    /**
     * 状态(0:不存在,1:存在)
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
     * @return ResponseCheckTplName
     */
    public function setErrorCode($errorCode = NULL)
    {
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
     * @return ResponseCheckTplName
     */
    public function setErrorMsg($errorMsg = NULL)
    {
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
     * @return ResponseCheckTplName
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }

}