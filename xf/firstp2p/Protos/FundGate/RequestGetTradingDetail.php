<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取用户交易详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class RequestGetTradingDetail extends AbstractRequestBase
{
    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * 用户id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestGetTradingDetail
     */
    public function setOrderId($orderId)
    {
        \Assert\Assertion::string($orderId);

        $this->orderId = $orderId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetTradingDetail
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}