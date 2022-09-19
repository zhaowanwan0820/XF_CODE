<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 申请配资
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestApplyFund extends AbstractRequestBase
{
    /**
     * 网信用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 融牛用户ID
     *
     * @var string
     * @required
     */
    private $rnUserId;

    /**
     * 配资方案ID
     *
     * @var int
     * @required
     */
    private $ruleId;

    /**
     * 购买资金（分）
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 开始交易时间; 0: 当日；1：下一交易日
     *
     * @var int
     * @required
     */
    private $startTimeType;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestApplyFund
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getRnUserId()
    {
        return $this->rnUserId;
    }

    /**
     * @param string $rnUserId
     * @return RequestApplyFund
     */
    public function setRnUserId($rnUserId)
    {
        \Assert\Assertion::string($rnUserId);

        $this->rnUserId = $rnUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getRuleId()
    {
        return $this->ruleId;
    }

    /**
     * @param int $ruleId
     * @return RequestApplyFund
     */
    public function setRuleId($ruleId)
    {
        \Assert\Assertion::integer($ruleId);

        $this->ruleId = $ruleId;

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
     * @return RequestApplyFund
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

        return $this;
    }
    /**
     * @return int
     */
    public function getStartTimeType()
    {
        return $this->startTimeType;
    }

    /**
     * @param int $startTimeType
     * @return RequestApplyFund
     */
    public function setStartTimeType($startTimeType)
    {
        \Assert\Assertion::integer($startTimeType);

        $this->startTimeType = $startTimeType;

        return $this;
    }

}