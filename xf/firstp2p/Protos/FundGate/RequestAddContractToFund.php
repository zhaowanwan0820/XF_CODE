<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 把协议添加到fund
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestAddContractToFund extends AbstractRequestBase
{
    /**
     * 基金Id
     *
     * @var int
     * @required
     */
    private $fundId;

    /**
     * 协议id
     *
     * @var int
     * @required
     */
    private $contractId;

    /**
     * @return int
     */
    public function getFundId()
    {
        return $this->fundId;
    }

    /**
     * @param int $fundId
     * @return RequestAddContractToFund
     */
    public function setFundId($fundId)
    {
        \Assert\Assertion::integer($fundId);

        $this->fundId = $fundId;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * @param int $contractId
     * @return RequestAddContractToFund
     */
    public function setContractId($contractId)
    {
        \Assert\Assertion::integer($contractId);

        $this->contractId = $contractId;

        return $this;
    }

}