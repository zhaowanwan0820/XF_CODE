<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 发送生成合同记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseSendProjectContract extends ProtoBufferBase
{
    /**
     * 错误代码
     *
     * @var int
     * @required
     */
    private $errorCode;

    /**
     * 状态(1:成功,0:失败)
     *
     * @var string
     * @required
     */
    private $errorMsg;

    /**
     * 返回数据
     *
     * @var array
     * @required
     */
    private $data;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseSendProjectContract
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
     * @return ResponseSendProjectContract
     */
    public function setErrorMsg($errorMsg)
    {
        \Assert\Assertion::string($errorMsg);

        $this->errorMsg = $errorMsg;

        return $this;
    }
    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ResponseSendProjectContract
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}