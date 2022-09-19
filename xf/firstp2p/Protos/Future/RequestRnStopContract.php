<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 融牛终止合约
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestRnStopContract extends AbstractRequestBase
{
    /**
     * 合约订单号
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 利润
     *
     * @var int
     * @required
     */
    private $profit;

    /**
     * 退款金额
     *
     * @var int
     * @required
     */
    private $refundMoney;

    /**
     * 备注
     *
     * @var string
     * @optional
     */
    private $remarks = '';

    /**
     * 融牛订单状态
     *
     * @var int
     * @required
     */
    private $rnOrderStatus;

    /**
     * 结算扣减
     *
     * @var int
     * @required
     */
    private $deduct;

    /**
     * 总管理费
     *
     * @var int
     * @required
     */
    private $interest;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestRnStopContract
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
    public function getProfit()
    {
        return $this->profit;
    }

    /**
     * @param int $profit
     * @return RequestRnStopContract
     */
    public function setProfit($profit)
    {
        \Assert\Assertion::integer($profit);

        $this->profit = $profit;

        return $this;
    }
    /**
     * @return int
     */
    public function getRefundMoney()
    {
        return $this->refundMoney;
    }

    /**
     * @param int $refundMoney
     * @return RequestRnStopContract
     */
    public function setRefundMoney($refundMoney)
    {
        \Assert\Assertion::integer($refundMoney);

        $this->refundMoney = $refundMoney;

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
     * @return RequestRnStopContract
     */
    public function setRemarks($remarks = '')
    {
        $this->remarks = $remarks;

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
     * @return RequestRnStopContract
     */
    public function setRnOrderStatus($rnOrderStatus)
    {
        \Assert\Assertion::integer($rnOrderStatus);

        $this->rnOrderStatus = $rnOrderStatus;

        return $this;
    }
    /**
     * @return int
     */
    public function getDeduct()
    {
        return $this->deduct;
    }

    /**
     * @param int $deduct
     * @return RequestRnStopContract
     */
    public function setDeduct($deduct)
    {
        \Assert\Assertion::integer($deduct);

        $this->deduct = $deduct;

        return $this;
    }
    /**
     * @return int
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * @param int $interest
     * @return RequestRnStopContract
     */
    public function setInterest($interest)
    {
        \Assert\Assertion::integer($interest);

        $this->interest = $interest;

        return $this;
    }

}