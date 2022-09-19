<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 多投转账service
 *
 * 由代码生成器生成, 不可人为修改
 * @author jinhaidong
 */
class RequestDtTransfer extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = 0;

    /**
     * token
     *
     * @var string
     * @required
     */
    private $token;

    /**
     * 转账类型
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 转账金额
     *
     * @var float
     * @optional
     */
    private $money = 0;

    /**
     * 管理费用、需要和转账金额在一个事务中的
     *
     * @var float
     * @optional
     */
    private $fee = 0;

    /**
     * 管理方ID
     *
     * @var int
     * @optional
     */
    private $manageId = 0;

    /**
     * 多投标的ID
     *
     * @var string
     * @optional
     */
    private $dealId = 0;

    /**
     * 多投标的名称
     *
     * @var string
     * @optional
     */
    private $dealName = 0;

    /**
     * 起投金额
     *
     * @var string
     * @optional
     */
    private $minLoanMoney = 0;

    /**
     * 持有天数
     *
     * @var string
     * @optional
     */
    private $holdDays = 0;

    /**
     * 多投宝投资记录ID
     *
     * @var string
     * @optional
     */
    private $dealLoanId = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestDtTransfer
     */
    public function setUserId($userId = 0)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return RequestDtTransfer
     */
    public function setToken($token)
    {
        \Assert\Assertion::string($token);

        $this->token = $token;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestDtTransfer
     */
    public function setType($type = 0)
    {
        $this->type = $type;

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
     * @return RequestDtTransfer
     */
    public function setMoney($money = 0)
    {
        $this->money = $money;

        return $this;
    }
    /**
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param float $fee
     * @return RequestDtTransfer
     */
    public function setFee($fee = 0)
    {
        $this->fee = $fee;

        return $this;
    }
    /**
     * @return int
     */
    public function getManageId()
    {
        return $this->manageId;
    }

    /**
     * @param int $manageId
     * @return RequestDtTransfer
     */
    public function setManageId($manageId = 0)
    {
        $this->manageId = $manageId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param string $dealId
     * @return RequestDtTransfer
     */
    public function setDealId($dealId = 0)
    {
        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealName()
    {
        return $this->dealName;
    }

    /**
     * @param string $dealName
     * @return RequestDtTransfer
     */
    public function setDealName($dealName = 0)
    {
        $this->dealName = $dealName;

        return $this;
    }
    /**
     * @return string
     */
    public function getMinLoanMoney()
    {
        return $this->minLoanMoney;
    }

    /**
     * @param string $minLoanMoney
     * @return RequestDtTransfer
     */
    public function setMinLoanMoney($minLoanMoney = 0)
    {
        $this->minLoanMoney = $minLoanMoney;

        return $this;
    }
    /**
     * @return string
     */
    public function getHoldDays()
    {
        return $this->holdDays;
    }

    /**
     * @param string $holdDays
     * @return RequestDtTransfer
     */
    public function setHoldDays($holdDays = 0)
    {
        $this->holdDays = $holdDays;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealLoanId()
    {
        return $this->dealLoanId;
    }

    /**
     * @param string $dealLoanId
     * @return RequestDtTransfer
     */
    public function setDealLoanId($dealLoanId = 0)
    {
        $this->dealLoanId = $dealLoanId;

        return $this;
    }

}