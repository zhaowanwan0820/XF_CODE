<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照项目id,合同id获取合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetContractByProject extends ProtoBufferBase
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
     * 模板列表
     *
     * @var array
     * @optional
     */
    private $data = NULL;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseGetContractByProject
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
     * @return ResponseGetContractByProject
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
     * @return ResponseGetContractByProject
     */
    public function setData(array $data = NULL)
    {
        $this->data = $data;

        return $this;
    }

}