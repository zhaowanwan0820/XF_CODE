<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 追加保证金信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseAddBandInfo extends ResponseBase
{
    /**
     * 总资金
     *
     * @var int
     * @required
     */
    private $totalAmount;

    /**
     * 总操盘资金
     *
     * @var int
     * @required
     */
    private $operateAmount;

    /**
     * 警告线
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
     * 结束日期
     *
     * @var string
     * @required
     */
    private $endDate;

    /**
     * 用户可用额度
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
     * @return ResponseAddBandInfo
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
    public function getOperateAmount()
    {
        return $this->operateAmount;
    }

    /**
     * @param int $operateAmount
     * @return ResponseAddBandInfo
     */
    public function setOperateAmount($operateAmount)
    {
        \Assert\Assertion::integer($operateAmount);

        $this->operateAmount = $operateAmount;

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
     * @return ResponseAddBandInfo
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
     * @return ResponseAddBandInfo
     */
    public function setCloseAmount($closeAmount)
    {
        \Assert\Assertion::integer($closeAmount);

        $this->closeAmount = $closeAmount;

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
     * @return ResponseAddBandInfo
     */
    public function setEndDate($endDate)
    {
        \Assert\Assertion::string($endDate);

        $this->endDate = $endDate;

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
     * @return ResponseAddBandInfo
     */
    public function setUserMoney($userMoney)
    {
        \Assert\Assertion::string($userMoney);

        $this->userMoney = $userMoney;

        return $this;
    }

}