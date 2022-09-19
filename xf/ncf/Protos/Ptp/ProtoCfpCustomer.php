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
class ProtoCfpCustomer extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

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
     * 手机号
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * 显示的手机号
     *
     * @var string
     * @required
     */
    private $mobileShow;

    /**
     * 客户备注
     *
     * @var string
     * @required
     */
    private $memo;

    /**
     * 总佣金
     *
     * @var string
     * @required
     */
    private $profitTotal;

    /**
     * 在投金额
     *
     * @var string
     * @required
     */
    private $investingTotal;

    /**
     * 最新到期本金日期
     *
     * @var string
     * @required
     */
    private $latestDay;

    /**
     * 最新到期本金
     *
     * @var string
     * @required
     */
    private $latestAmount;

    /**
     * 最新到期本金(原始值)
     *
     * @var string
     * @required
     */
    private $latestAmountOriginal;

    /**
     * 在投平均收益
     *
     * @var string
     * @required
     */
    private $profitRatioAvg;

    /**
     * 在投平均周期
     *
     * @var string
     * @required
     */
    private $periodAvg;

    /**
     * 在投标数量
     *
     * @var string
     * @required
     */
    private $investNum;

    /**
     * 最近投资时间(进行中，满标，还款中)
     *
     * @var string
     * @required
     */
    private $pastDay;

    /**
     * 最近到期标名字
     *
     * @var string
     * @required
     */
    private $dealName;

    /**
     * 最近到期标投标金额
     *
     * @var string
     * @required
     */
    private $loanAmount;

    /**
     * 最近到期标投标金额(原始值)
     *
     * @var string
     * @required
     */
    private $loanAmountOriginal;

    /**
     * 投资标利率
     *
     * @var string
     * @required
     */
    private $dealRate;

    /**
     * 投资标利率(原始值)
     *
     * @var string
     * @required
     */
    private $dealRateOriginal;

    /**
     * 最近到期标的投资期限
     *
     * @var string
     * @required
     */
    private $bidRepayLimitTime;

    /**
     * 投标类型
     *
     * @var string
     * @required
     */
    private $dealLoanType;

    /**
     * 标ID
     *
     * @var string
     * @required
     */
    private $dealId;

    /**
     * 标类型
     *
     * @var string
     * @required
     */
    private $dealTypeText;

    /**
     * 是否从未投资过，1是，0否
     *
     * @var string
     * @optional
     */
    private $neverInvest = '0';

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return ProtoCfpCustomer
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
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return ProtoCfpCustomer
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
     * @return ProtoCfpCustomer
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
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return ProtoCfpCustomer
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobileShow()
    {
        return $this->mobileShow;
    }

    /**
     * @param string $mobileShow
     * @return ProtoCfpCustomer
     */
    public function setMobileShow($mobileShow)
    {
        \Assert\Assertion::string($mobileShow);

        $this->mobileShow = $mobileShow;

        return $this;
    }
    /**
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @param string $memo
     * @return ProtoCfpCustomer
     */
    public function setMemo($memo)
    {
        \Assert\Assertion::string($memo);

        $this->memo = $memo;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfitTotal()
    {
        return $this->profitTotal;
    }

    /**
     * @param string $profitTotal
     * @return ProtoCfpCustomer
     */
    public function setProfitTotal($profitTotal)
    {
        \Assert\Assertion::string($profitTotal);

        $this->profitTotal = $profitTotal;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestingTotal()
    {
        return $this->investingTotal;
    }

    /**
     * @param string $investingTotal
     * @return ProtoCfpCustomer
     */
    public function setInvestingTotal($investingTotal)
    {
        \Assert\Assertion::string($investingTotal);

        $this->investingTotal = $investingTotal;

        return $this;
    }
    /**
     * @return string
     */
    public function getLatestDay()
    {
        return $this->latestDay;
    }

    /**
     * @param string $latestDay
     * @return ProtoCfpCustomer
     */
    public function setLatestDay($latestDay)
    {
        \Assert\Assertion::string($latestDay);

        $this->latestDay = $latestDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getLatestAmount()
    {
        return $this->latestAmount;
    }

    /**
     * @param string $latestAmount
     * @return ProtoCfpCustomer
     */
    public function setLatestAmount($latestAmount)
    {
        \Assert\Assertion::string($latestAmount);

        $this->latestAmount = $latestAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getLatestAmountOriginal()
    {
        return $this->latestAmountOriginal;
    }

    /**
     * @param string $latestAmountOriginal
     * @return ProtoCfpCustomer
     */
    public function setLatestAmountOriginal($latestAmountOriginal)
    {
        \Assert\Assertion::string($latestAmountOriginal);

        $this->latestAmountOriginal = $latestAmountOriginal;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfitRatioAvg()
    {
        return $this->profitRatioAvg;
    }

    /**
     * @param string $profitRatioAvg
     * @return ProtoCfpCustomer
     */
    public function setProfitRatioAvg($profitRatioAvg)
    {
        \Assert\Assertion::string($profitRatioAvg);

        $this->profitRatioAvg = $profitRatioAvg;

        return $this;
    }
    /**
     * @return string
     */
    public function getPeriodAvg()
    {
        return $this->periodAvg;
    }

    /**
     * @param string $periodAvg
     * @return ProtoCfpCustomer
     */
    public function setPeriodAvg($periodAvg)
    {
        \Assert\Assertion::string($periodAvg);

        $this->periodAvg = $periodAvg;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestNum()
    {
        return $this->investNum;
    }

    /**
     * @param string $investNum
     * @return ProtoCfpCustomer
     */
    public function setInvestNum($investNum)
    {
        \Assert\Assertion::string($investNum);

        $this->investNum = $investNum;

        return $this;
    }
    /**
     * @return string
     */
    public function getPastDay()
    {
        return $this->pastDay;
    }

    /**
     * @param string $pastDay
     * @return ProtoCfpCustomer
     */
    public function setPastDay($pastDay)
    {
        \Assert\Assertion::string($pastDay);

        $this->pastDay = $pastDay;

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
     * @return ProtoCfpCustomer
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
    public function getLoanAmount()
    {
        return $this->loanAmount;
    }

    /**
     * @param string $loanAmount
     * @return ProtoCfpCustomer
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
    public function getLoanAmountOriginal()
    {
        return $this->loanAmountOriginal;
    }

    /**
     * @param string $loanAmountOriginal
     * @return ProtoCfpCustomer
     */
    public function setLoanAmountOriginal($loanAmountOriginal)
    {
        \Assert\Assertion::string($loanAmountOriginal);

        $this->loanAmountOriginal = $loanAmountOriginal;

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
     * @return ProtoCfpCustomer
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
    public function getDealRateOriginal()
    {
        return $this->dealRateOriginal;
    }

    /**
     * @param string $dealRateOriginal
     * @return ProtoCfpCustomer
     */
    public function setDealRateOriginal($dealRateOriginal)
    {
        \Assert\Assertion::string($dealRateOriginal);

        $this->dealRateOriginal = $dealRateOriginal;

        return $this;
    }
    /**
     * @return string
     */
    public function getBidRepayLimitTime()
    {
        return $this->bidRepayLimitTime;
    }

    /**
     * @param string $bidRepayLimitTime
     * @return ProtoCfpCustomer
     */
    public function setBidRepayLimitTime($bidRepayLimitTime)
    {
        \Assert\Assertion::string($bidRepayLimitTime);

        $this->bidRepayLimitTime = $bidRepayLimitTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealLoanType()
    {
        return $this->dealLoanType;
    }

    /**
     * @param string $dealLoanType
     * @return ProtoCfpCustomer
     */
    public function setDealLoanType($dealLoanType)
    {
        \Assert\Assertion::string($dealLoanType);

        $this->dealLoanType = $dealLoanType;

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
     * @return ProtoCfpCustomer
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
    public function getDealTypeText()
    {
        return $this->dealTypeText;
    }

    /**
     * @param string $dealTypeText
     * @return ProtoCfpCustomer
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
    public function getNeverInvest()
    {
        return $this->neverInvest;
    }

    /**
     * @param string $neverInvest
     * @return ProtoCfpCustomer
     */
    public function setNeverInvest($neverInvest = '0')
    {
        $this->neverInvest = $neverInvest;

        return $this;
    }

}