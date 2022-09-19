<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 绑定邀请码请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestBindInvitationCode extends AbstractRequestBase
{
    /**
     * 用户Id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 邀请码
     *
     * @var string
     * @required
     */
    private $invitationCode;

    /**
     * 投资金额
     *
     * @var float
     * @required
     */
    private $money;

    /**
     * 标ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 投资记录ID
     *
     * @var int
     * @required
     */
    private $dealLoadId;

    /**
     * 项目名称
     *
     * @var string
     * @optional
     */
    private $type = 'jijin';

    /**
     * 投资起息日
     *
     * @var date
     * @required
     */
    private $repayStartTime;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestBindInvitationCode
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
    public function getInvitationCode()
    {
        return $this->invitationCode;
    }

    /**
     * @param string $invitationCode
     * @return RequestBindInvitationCode
     */
    public function setInvitationCode($invitationCode)
    {
        \Assert\Assertion::string($invitationCode);

        $this->invitationCode = $invitationCode;

        return $this;
    }
    /**
     * @return float
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param float $money
     * @return RequestBindInvitationCode
     */
    public function setMoney($money)
    {
        \Assert\Assertion::float($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestBindInvitationCode
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoadId()
    {
        return $this->dealLoadId;
    }

    /**
     * @param int $dealLoadId
     * @return RequestBindInvitationCode
     */
    public function setDealLoadId($dealLoadId)
    {
        \Assert\Assertion::integer($dealLoadId);

        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RequestBindInvitationCode
     */
    public function setType($type = 'jijin')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return date
     */
    public function getRepayStartTime()
    {
        return $this->repayStartTime;
    }

    /**
     * @param date $repayStartTime
     * @return RequestBindInvitationCode
     */
    public function setRepayStartTime(date $repayStartTime)
    {
        $this->repayStartTime = $repayStartTime;

        return $this;
    }

}