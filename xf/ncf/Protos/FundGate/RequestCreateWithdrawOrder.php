<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 创建撤单订单
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestCreateWithdrawOrder extends AbstractRequestBase
{
    /**
     * 目标订单号（即：用户发起申购或赎回时产生的订单号）
     *
     * @var string
     * @required
     */
    private $targetOrderId;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 分站Id（默认为主站，值为1）
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

    /**
     * @return string
     */
    public function getTargetOrderId()
    {
        return $this->targetOrderId;
    }

    /**
     * @param string $targetOrderId
     * @return RequestCreateWithdrawOrder
     */
    public function setTargetOrderId($targetOrderId)
    {
        \Assert\Assertion::string($targetOrderId);

        $this->targetOrderId = $targetOrderId;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestCreateWithdrawOrder
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestCreateWithdrawOrder
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

        return $this;
    }

}