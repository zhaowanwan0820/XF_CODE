<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 用户持仓基金详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetPurchaseOrderDetail extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetPurchaseOrderDetail
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetPurchaseOrderDetail
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }

}