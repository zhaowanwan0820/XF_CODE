<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 申请追加保障金
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestAddBand extends AbstractRequestBase
{
    /**
     * 合约订单 ID
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 追加保障金金额（分）
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 增加备注
     *
     * @var string
     * @required
     */
    private $remarks;

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestAddBand
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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return RequestAddBand
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

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
     * @return RequestAddBand
     */
    public function setRemarks($remarks)
    {
        \Assert\Assertion::string($remarks);

        $this->remarks = $remarks;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestAddBand
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}