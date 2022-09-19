<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取模板标识列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class RequestGetContractTplIdentifier extends ProtoBufferBase
{
    /**
     * 合同模板标识 id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestGetContractTplIdentifier
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}