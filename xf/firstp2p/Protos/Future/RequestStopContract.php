<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 终止合约
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestStopContract extends AbstractRequestBase
{
    /**
     * 合约订单号
     *
     * @var string
     * @required
     */
    private $opOrderNo;

    /**
     * 审核通过与否
     *
     * @var int
     * @required
     */
    private $auditResult;

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
    public function getOpOrderNo()
    {
        return $this->opOrderNo;
    }

    /**
     * @param string $opOrderNo
     * @return RequestStopContract
     */
    public function setOpOrderNo($opOrderNo)
    {
        \Assert\Assertion::string($opOrderNo);

        $this->opOrderNo = $opOrderNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getAuditResult()
    {
        return $this->auditResult;
    }

    /**
     * @param int $auditResult
     * @return RequestStopContract
     */
    public function setAuditResult($auditResult)
    {
        \Assert\Assertion::integer($auditResult);

        $this->auditResult = $auditResult;

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
     * @return RequestStopContract
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
     * @return RequestStopContract
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
     * @return RequestStopContract
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
     * @return RequestStopContract
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
     * @return RequestStopContract
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
     * @return RequestStopContract
     */
    public function setInterest($interest)
    {
        \Assert\Assertion::integer($interest);

        $this->interest = $interest;

        return $this;
    }

}