<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取用户交易记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class RequestGetTradingRecords extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 订单类型
     *
     * @var string
     * @optional
     */
    private $orderType = '';

    /**
     * 订单状态
     *
     * @var string
     * @optional
     */
    private $orderStatus = '';

    /**
     * 基金代码
     *
     * @var string
     * @optional
     */
    private $fundCode = '';

    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetTradingRecords
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @param string $orderType
     * @return RequestGetTradingRecords
     */
    public function setOrderType($orderType = '')
    {
        $this->orderType = $orderType;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    /**
     * @param string $orderStatus
     * @return RequestGetTradingRecords
     */
    public function setOrderStatus($orderStatus = '')
    {
        $this->orderStatus = $orderStatus;

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
     * @return RequestGetTradingRecords
     */
    public function setFundCode($fundCode = '')
    {
        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetTradingRecords
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }

}