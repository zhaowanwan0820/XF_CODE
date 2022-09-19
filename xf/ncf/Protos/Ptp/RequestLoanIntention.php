<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 用户提交的借款意向
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangge
 */
class RequestLoanIntention extends AbstractRequestBase
{
    /**
     * 邀请码
     *
     * @var string
     * @optional
     */
    private $inviteCode = '';

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 借款金额
     *
     * @var int
     * @optional
     */
    private $money = 0;

    /**
     * 借款期限
     *
     * @var int
     * @optional
     */
    private $time = 0;

    /**
     * 联系电话
     *
     * @var string
     * @optional
     */
    private $phone = '';

    /**
     * 地址
     *
     * @var string
     * @optional
     */
    private $addr = '';

    /**
     * 所在公司
     *
     * @var string
     * @optional
     */
    private $company = '';

    /**
     * 工作职级
     *
     * @var string
     * @optional
     */
    private $wl = '';

    /**
     * 借款码
     *
     * @var string
     * @optional
     */
    private $code = '';

    /**
     * @return string
     */
    public function getInviteCode()
    {
        return $this->inviteCode;
    }

    /**
     * @param string $inviteCode
     * @return RequestLoanIntention
     */
    public function setInviteCode($inviteCode = '')
    {
        $this->inviteCode = $inviteCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestLoanIntention
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param int $money
     * @return RequestLoanIntention
     */
    public function setMoney($money = 0)
    {
        $this->money = $money;

        return $this;
    }
    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     * @return RequestLoanIntention
     */
    public function setTime($time = 0)
    {
        $this->time = $time;

        return $this;
    }
    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return RequestLoanIntention
     */
    public function setPhone($phone = '')
    {
        $this->phone = $phone;

        return $this;
    }
    /**
     * @return string
     */
    public function getAddr()
    {
        return $this->addr;
    }

    /**
     * @param string $addr
     * @return RequestLoanIntention
     */
    public function setAddr($addr = '')
    {
        $this->addr = $addr;

        return $this;
    }
    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     * @return RequestLoanIntention
     */
    public function setCompany($company = '')
    {
        $this->company = $company;

        return $this;
    }
    /**
     * @return string
     */
    public function getWl()
    {
        return $this->wl;
    }

    /**
     * @param string $wl
     * @return RequestLoanIntention
     */
    public function setWl($wl = '')
    {
        $this->wl = $wl;

        return $this;
    }
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return RequestLoanIntention
     */
    public function setCode($code = '')
    {
        $this->code = $code;

        return $this;
    }

}