<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 合约续期
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestContractExtension extends AbstractRequestBase
{
    /**
     * 订单号
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
     * @return RequestContractExtension
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }

}