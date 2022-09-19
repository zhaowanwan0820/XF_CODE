<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取合同签署数量
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetProjectSignNum extends ProtoBufferBase
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
     * 结果
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseGetProjectSignNum
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
     * @return ResponseGetProjectSignNum
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
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetProjectSignNum
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}