<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据合同id获取前置合同模板
 *
 * 由代码生成器生成, 不可人为修改
 * @author duxuefeng
 */
class RequestGetTplByContractId extends ProtoBufferBase
{
    /**
     * contractId
     *
     * @var int
     * @required
     */
    private $contractId;

    /**
     * @return int
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * @param int $contractId
     * @return RequestGetTplByContractId
     */
    public function setContractId($contractId)
    {
        \Assert\Assertion::integer($contractId);

        $this->contractId = $contractId;

        return $this;
    }

}