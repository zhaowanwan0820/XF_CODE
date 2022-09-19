<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 资金记录服务化
 *
 * 由代码生成器生成, 不可人为修改
 * @author guofeng3
 */
class RequestUserMoney extends ProtoBufferBase
{
    /**
     * 用户UID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 金额
     *
     * @var string
     * @required
     */
    private $money;

    /**
     * 资金类型
     *
     * @var string
     * @required
     */
    private $message;

    /**
     * 资金备注
     *
     * @var string
     * @required
     */
    private $note;

    /**
     * 金额类型(0:增加余额1:冻结金额，增加冻结资金同时减少余额2:减少冻结金额)
     *
     * @var int
     * @optional
     */
    private $moneyType = 0;

    /**
     * 是否管理员
     *
     * @var int
     * @optional
     */
    private $adminId = 0;

    /**
     * 是否是管理费
     *
     * @var int
     * @optional
     */
    private $isManage = 0;

    /**
     * 是否允许负数的用户余额
     *
     * @var int
     * @optional
     */
    private $negative = 0;

    /**
     * 是否异步更新用户余额
     *
     * @var boolean
     * @optional
     */
    private $isMoneyAsync = false;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUserMoney
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return RequestUserMoney
     */
    public function setMoney($money)
    {
        \Assert\Assertion::string($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return RequestUserMoney
     */
    public function setMessage($message)
    {
        \Assert\Assertion::string($message);

        $this->message = $message;

        return $this;
    }
    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     * @return RequestUserMoney
     */
    public function setNote($note)
    {
        \Assert\Assertion::string($note);

        $this->note = $note;

        return $this;
    }
    /**
     * @return int
     */
    public function getMoneyType()
    {
        return $this->moneyType;
    }

    /**
     * @param int $moneyType
     * @return RequestUserMoney
     */
    public function setMoneyType($moneyType = 0)
    {
        $this->moneyType = $moneyType;

        return $this;
    }
    /**
     * @return int
     */
    public function getAdminId()
    {
        return $this->adminId;
    }

    /**
     * @param int $adminId
     * @return RequestUserMoney
     */
    public function setAdminId($adminId = 0)
    {
        $this->adminId = $adminId;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsManage()
    {
        return $this->isManage;
    }

    /**
     * @param int $isManage
     * @return RequestUserMoney
     */
    public function setIsManage($isManage = 0)
    {
        $this->isManage = $isManage;

        return $this;
    }
    /**
     * @return int
     */
    public function getNegative()
    {
        return $this->negative;
    }

    /**
     * @param int $negative
     * @return RequestUserMoney
     */
    public function setNegative($negative = 0)
    {
        $this->negative = $negative;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getIsMoneyAsync()
    {
        return $this->isMoneyAsync;
    }

    /**
     * @param boolean $isMoneyAsync
     * @return RequestUserMoney
     */
    public function setIsMoneyAsync($isMoneyAsync = false)
    {
        $this->isMoneyAsync = $isMoneyAsync;

        return $this;
    }

}