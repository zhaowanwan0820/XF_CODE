<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 确定申请信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestApplyInfo extends AbstractRequestBase
{
    /**
     * rule id
     *
     * @var int
     * @required
     */
    private $ruleId;

    /**
     * amount
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return int
     */
    public function getRuleId()
    {
        return $this->ruleId;
    }

    /**
     * @param int $ruleId
     * @return RequestApplyInfo
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
     * @return RequestApplyInfo
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestApplyInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}