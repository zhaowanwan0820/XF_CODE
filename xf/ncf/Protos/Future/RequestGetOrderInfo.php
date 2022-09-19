<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取合同订单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestGetOrderInfo extends AbstractRequestBase
{
    /**
     * 合同订单ID
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestGetOrderInfo
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }

}