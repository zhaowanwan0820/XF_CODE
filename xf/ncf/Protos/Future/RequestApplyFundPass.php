<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 配资审核通过，请求接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestApplyFundPass extends AbstractRequestBase
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
     * homs账户
     *
     * @var string
     * @required
     */
    private $homsAccount;

    /**
     * homs密码
     *
     * @var string
     * @required
     */
    private $homsPwd;

    /**
     * 开始交易时间
     *
     * @var string
     * @required
     */
    private $tradeStartDate;

    /**
     * 结束交易时间
     *
     * @var string
     * @required
     */
    private $tradeEndDate;

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
     * @return RequestApplyFundPass
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
     * @return RequestApplyFundPass
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
    public function getHomsAccount()
    {
        return $this->homsAccount;
    }

    /**
     * @param string $homsAccount
     * @return RequestApplyFundPass
     */
    public function setHomsAccount($homsAccount)
    {
        \Assert\Assertion::string($homsAccount);

        $this->homsAccount = $homsAccount;

        return $this;
    }
    /**
     * @return string
     */
    public function getHomsPwd()
    {
        return $this->homsPwd;
    }

    /**
     * @param string $homsPwd
     * @return RequestApplyFundPass
     */
    public function setHomsPwd($homsPwd)
    {
        \Assert\Assertion::string($homsPwd);

        $this->homsPwd = $homsPwd;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeStartDate()
    {
        return $this->tradeStartDate;
    }

    /**
     * @param string $tradeStartDate
     * @return RequestApplyFundPass
     */
    public function setTradeStartDate($tradeStartDate)
    {
        \Assert\Assertion::string($tradeStartDate);

        $this->tradeStartDate = $tradeStartDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeEndDate()
    {
        return $this->tradeEndDate;
    }

    /**
     * @param string $tradeEndDate
     * @return RequestApplyFundPass
     */
    public function setTradeEndDate($tradeEndDate)
    {
        \Assert\Assertion::string($tradeEndDate);

        $this->tradeEndDate = $tradeEndDate;

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
     * @return RequestApplyFundPass
     */
    public function setRemarks($remarks = '')
    {
        $this->remarks = $remarks;

        return $this;
    }

}