<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据放款审批单号获取前置合同记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author duxuefeng
 */
class RequestGetContractByApproveNumber extends ProtoBufferBase
{
    /**
     * 放款审批单号
     *
     * @var string
     * @required
     */
    private $approveNumber;

    /**
     * @return string
     */
    public function getApproveNumber()
    {
        return $this->approveNumber;
    }

    /**
     * @param string $approveNumber
     * @return RequestGetContractByApproveNumber
     */
    public function setApproveNumber($approveNumber)
    {
        \Assert\Assertion::string($approveNumber);

        $this->approveNumber = $approveNumber;

        return $this;
    }

}