<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 客户信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ProtoInvestRecord extends ProtoBufferBase
{
    /**
     * 投资标ID
     *
     * @var string
     * @required
     */
    private $dealId;

    /**
     * 用户名
     *
     * @var string
     * @required
     */
    private $userName;

    /**
     * 用户真实姓名
     *
     * @var string
     * @required
     */
    private $realName;

    /**
     * 标名字
     *
     * @var string
     * @required
     */
    private $dealName;

    /**
     * 到期日期
     *
     * @var string
     * @required
     */
    private $dueDay;

    /**
     * 标总额
     *
     * @var string
     * @required
     */
    private $total;

    /**
     * 投资标利率
     *
     * @var string
     * @required
     */
    private $dealRate;

    /**
     * 投标金额
     *
     * @var string
     * @required
     */
    private $loanAmount;

    /**
     * 投标时间
     *
     * @var string
     * @optional
     */
    private $createTime = '';

    /**
     * 佣金分成
     *
     * @var string
     * @required
     */
    private $profitRate;

    /**
     * 佣金结算状态
     *
     * @var string
     * @required
     */
    private $profitStatus;

    /**
     * 标的期限
     *
     * @var string
     * @required
     */
    private $duration;

    /**
     * 标类型
     *
     * @var string
     * @required
     */
    private $dealTypeText;

    /**
     * 该客户给该理财师带来的佣金总额
     *
     * @var string
     * @required
     */
    private $commission;

    /**
     * 该客户的投资总额
     *
     * @var string
     * @required
     */
    private $investAmount;

    /**
     * @return string
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param string $dealId
     * @return ProtoInvestRecord
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::string($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return ProtoInvestRecord
     */
    public function setUserName($userName)
    {
        \Assert\Assertion::string($userName);

        $this->userName = $userName;

        return $this;
    }
    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * @param string $realName
     * @return ProtoInvestRecord
     */
    public function setRealName($realName)
    {
        \Assert\Assertion::string($realName);

        $this->realName = $realName;

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
     * @return ProtoInvestRecord
     */
    public function setDealName($dealName)
    {
        \Assert\Assertion::string($dealName);

        $this->dealName = $dealName;

        return $this;
    }
    /**
     * @return string
     */
    public function getDueDay()
    {
        return $this->dueDay;
    }

    /**
     * @param string $dueDay
     * @return ProtoInvestRecord
     */
    public function setDueDay($dueDay)
    {
        \Assert\Assertion::string($dueDay);

        $this->dueDay = $dueDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param string $total
     * @return ProtoInvestRecord
     */
    public function setTotal($total)
    {
        \Assert\Assertion::string($total);

        $this->total = $total;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealRate()
    {
        return $this->dealRate;
    }

    /**
     * @param string $dealRate
     * @return ProtoInvestRecord
     */
    public function setDealRate($dealRate)
    {
        \Assert\Assertion::string($dealRate);

        $this->dealRate = $dealRate;

        return $this;
    }
    /**
     * @return string
     */
    public function getLoanAmount()
    {
        return $this->loanAmount;
    }

    /**
     * @param string $loanAmount
     * @return ProtoInvestRecord
     */
    public function setLoanAmount($loanAmount)
    {
        \Assert\Assertion::string($loanAmount);

        $this->loanAmount = $loanAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param string $createTime
     * @return ProtoInvestRecord
     */
    public function setCreateTime($createTime = '')
    {
        $this->createTime = $createTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfitRate()
    {
        return $this->profitRate;
    }

    /**
     * @param string $profitRate
     * @return ProtoInvestRecord
     */
    public function setProfitRate($profitRate)
    {
        \Assert\Assertion::string($profitRate);

        $this->profitRate = $profitRate;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfitStatus()
    {
        return $this->profitStatus;
    }

    /**
     * @param string $profitStatus
     * @return ProtoInvestRecord
     */
    public function setProfitStatus($profitStatus)
    {
        \Assert\Assertion::string($profitStatus);

        $this->profitStatus = $profitStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param string $duration
     * @return ProtoInvestRecord
     */
    public function setDuration($duration)
    {
        \Assert\Assertion::string($duration);

        $this->duration = $duration;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealTypeText()
    {
        return $this->dealTypeText;
    }

    /**
     * @param string $dealTypeText
     * @return ProtoInvestRecord
     */
    public function setDealTypeText($dealTypeText)
    {
        \Assert\Assertion::string($dealTypeText);

        $this->dealTypeText = $dealTypeText;

        return $this;
    }
    /**
     * @return string
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * @param string $commission
     * @return ProtoInvestRecord
     */
    public function setCommission($commission)
    {
        \Assert\Assertion::string($commission);

        $this->commission = $commission;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestAmount()
    {
        return $this->investAmount;
    }

    /**
     * @param string $investAmount
     * @return ProtoInvestRecord
     */
    public function setInvestAmount($investAmount)
    {
        \Assert\Assertion::string($investAmount);

        $this->investAmount = $investAmount;

        return $this;
    }

}