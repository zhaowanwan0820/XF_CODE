<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 返回保存合同模板标识结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseSaveContractTplIdentifier extends ProtoBufferBase
{
    /**
     * 标识保存是否成功
     *
     * @var boolean
     * @required
     */
    private $result;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $errMsg = '';

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param boolean $result
     * @return ResponseSaveContractTplIdentifier
     */
    public function setResult($result)
    {
        \Assert\Assertion::boolean($result);

        $this->result = $result;

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
     * @return ResponseSaveContractTplIdentifier
     */
    public function setErrMsg($errMsg = '')
    {
        $this->errMsg = $errMsg;

        return $this;
    }

}