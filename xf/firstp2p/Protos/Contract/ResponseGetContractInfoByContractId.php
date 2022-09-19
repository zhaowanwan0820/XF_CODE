<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 返回 根据合同id 标id 获取合同模板信息 结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseGetContractInfoByContractId extends ProtoBufferBase
{
    /**
     * 此合同模板信息
     *
     * @var array
     * @required
     */
    private $data;

    /**
     * 错误码
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
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ResponseGetContractInfoByContractId
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }
    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseGetContractInfoByContractId
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
     * @return ResponseGetContractInfoByContractId
     */
    public function setErrorMsg($errorMsg)
    {
        \Assert\Assertion::string($errorMsg);

        $this->errorMsg = $errorMsg;

        return $this;
    }

}