<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 删除单个模板标识
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseDeleteContractTplIdentifier extends ProtoBufferBase
{
    /**
     * 删除结果
     *
     * @var boolean
     * @required
     */
    private $result;

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param boolean $result
     * @return ResponseDeleteContractTplIdentifier
     */
    public function setResult($result)
    {
        \Assert\Assertion::boolean($result);

        $this->result = $result;

        return $this;
    }

}