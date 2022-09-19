<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 操作订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetOpOrderInfo extends ResponseBase
{
    /**
     * 操作订单状态
     *
     * @var int
     * @required
     */
    private $opOrderStatus;

    /**
     * 支付单号
     *
     * @var string
     * @optional
     */
    private $payId = '0';

    /**
     * p2p userid
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return int
     */
    public function getOpOrderStatus()
    {
        return $this->opOrderStatus;
    }

    /**
     * @param int $opOrderStatus
     * @return ResponseGetOpOrderInfo
     */
    public function setOpOrderStatus($opOrderStatus)
    {
        \Assert\Assertion::integer($opOrderStatus);

        $this->opOrderStatus = $opOrderStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->payId;
    }

    /**
     * @param string $payId
     * @return ResponseGetOpOrderInfo
     */
    public function setPayId($payId = '0')
    {
        $this->payId = $payId;

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
     * @return ResponseGetOpOrderInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}