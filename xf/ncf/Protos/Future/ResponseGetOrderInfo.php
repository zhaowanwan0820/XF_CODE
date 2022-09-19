<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 合同订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetOrderInfo extends ResponseBase
{
    /**
     * 支付订单ID
     *
     * @var string
     * @required
     */
    private $payId;

    /**
     * 订单状态
     *
     * @var int
     * @required
     */
    private $status;

    /**
     * p2p userId
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->payId;
    }

    /**
     * @param string $payId
     * @return ResponseGetOrderInfo
     */
    public function setPayId($payId)
    {
        \Assert\Assertion::string($payId);

        $this->payId = $payId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ResponseGetOrderInfo
     */
    public function setStatus($status)
    {
        \Assert\Assertion::integer($status);

        $this->status = $status;

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
     * @return ResponseGetOrderInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}
