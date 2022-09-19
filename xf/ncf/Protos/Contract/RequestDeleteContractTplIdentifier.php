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
class RequestDeleteContractTplIdentifier extends ProtoBufferBase
{
    /**
     * 模板标识 id
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
     * @return RequestDeleteContractTplIdentifier
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}