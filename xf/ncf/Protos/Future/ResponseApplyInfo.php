<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 申请确认信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseApplyInfo extends ResponseBase
{
    /**
     * 总操盘资金
     *
     * @var int
     * @required
     */
    private $totalAmount;

    /**
     *  警告线
     *
     * @var int
     * @required
     */
    private $warningAmount;

    /**
     * 平仓线
     *
     * @var int
     * @required
     */
    private $closeAmount;

    /**
     * 保证金
     *
     * @var int
     * @required
     */
    private $depositAmount;

    /**
     * 单日或单月利息
     *
     * @var int
     * @required
     */
    private $interestAmount;

    /**
     * 订单类型 按天按月
     *
     * @var int
     * @required
     */
    private $orderType;

    /**
     * 用户资金
     *
     * @var string
     * @required
     */
    private $userMoney;

    /**
     * @return int
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param int $totalAmount
     * @return ResponseApplyInfo
     */
    public function setTotalAmount($totalAmount)
    {
        \Assert\Assertion::integer($totalAmount);

        $this->totalAmount = $totalAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getWarningAmount()
    {
        return $this->warningAmount;
    }

    /**
     * @param int $warningAmount
     * @return ResponseApplyInfo
     */
    public function setWarningAmount($warningAmount)
    {
        \Assert\Assertion::integer($warningAmount);

        $this->warningAmount = $warningAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getCloseAmount()
    {
        return $this->closeAmount;
    }

    /**
     * @param int $closeAmount
     * @return ResponseApplyInfo
     */
    public function setCloseAmount($closeAmount)
    {
        \Assert\Assertion::integer($closeAmount);

        $this->closeAmount = $closeAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    /**
     * @param int $depositAmount
     * @return ResponseApplyInfo
     */
    public function setDepositAmount($depositAmount)
    {
        \Assert\Assertion::integer($depositAmount);

        $this->depositAmount = $depositAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getInterestAmount()
    {
        return $this->interestAmount;
    }

    /**
     * @param int $interestAmount
     * @return ResponseApplyInfo
     */
    public function setInterestAmount($interestAmount)
    {
        \Assert\Assertion::integer($interestAmount);

        $this->interestAmount = $interestAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @param int $orderType
     * @return ResponseApplyInfo
     */
    public function setOrderType($orderType)
    {
        \Assert\Assertion::integer($orderType);

        $this->orderType = $orderType;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserMoney()
    {
        return $this->userMoney;
    }

    /**
     * @param string $userMoney
     * @return ResponseApplyInfo
     */
    public function setUserMoney($userMoney)
    {
        \Assert\Assertion::string($userMoney);

        $this->userMoney = $userMoney;

        return $this;
    }

}