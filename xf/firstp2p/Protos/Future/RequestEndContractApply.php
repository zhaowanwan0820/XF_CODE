<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 终止合约申请
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestEndContractApply extends AbstractRequestBase
{
    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 结束时间
     *
     * @var string
     * @required
     */
    private $endDate;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestEndContractApply
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param string $endDate
     * @return RequestEndContractApply
     */
    public function setEndDate($endDate)
    {
        \Assert\Assertion::string($endDate);

        $this->endDate = $endDate;

        return $this;
    }

}