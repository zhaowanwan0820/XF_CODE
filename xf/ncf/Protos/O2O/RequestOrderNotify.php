<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:合作方推送订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestOrderNotify extends ProtoBufferBase
{
    /**
     * 订单编号
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * 通知信息
     *
     * @var string
     * @required
     */
    private $notifyInfo;

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestOrderNotify
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
    public function getNotifyInfo()
    {
        return $this->notifyInfo;
    }

    /**
     * @param string $notifyInfo
     * @return RequestOrderNotify
     */
    public function setNotifyInfo($notifyInfo)
    {
        \Assert\Assertion::string($notifyInfo);

        $this->notifyInfo = $notifyInfo;

        return $this;
    }

}