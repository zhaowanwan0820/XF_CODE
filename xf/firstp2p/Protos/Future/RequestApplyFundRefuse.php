<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 申请配资未通过
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestApplyFundRefuse extends AbstractRequestBase
{
    /**
     * 网信订单号
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 融牛订单状态
     *
     * @var int
     * @required
     */
    private $rnOrderStatus;

    /**
     * 注释
     *
     * @var string
     * @optional
     */
    private $remarks = '';

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestApplyFundRefuse
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getRnOrderStatus()
    {
        return $this->rnOrderStatus;
    }

    /**
     * @param int $rnOrderStatus
     * @return RequestApplyFundRefuse
     */
    public function setRnOrderStatus($rnOrderStatus)
    {
        \Assert\Assertion::integer($rnOrderStatus);

        $this->rnOrderStatus = $rnOrderStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @param string $remarks
     * @return RequestApplyFundRefuse
     */
    public function setRemarks($remarks = '')
    {
        $this->remarks = $remarks;

        return $this;
    }

}