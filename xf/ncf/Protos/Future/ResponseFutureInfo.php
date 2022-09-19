<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 配资详情信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseFutureInfo extends ResponseBase
{
    /**
     * 总资产 单位分
     *
     * @var int
     * @required
     */
    private $totalAmount;

    /**
     * 操盘金额 单位分
     *
     * @var int
     * @required
     */
    private $opAmount;

    /**
     * 合约类型 按天 按月
     *
     * @var bool
     * @required
     */
    private $isByDay;

    /**
     * 管理费
     *
     * @var int
     * @required
     */
    private $managementFee;

    /**
     * 亏损警告线
     *
     * @var int
     * @required
     */
    private $warningAmount;

    /**
     * 亏损平仓线
     *
     * @var int
     * @required
     */
    private $closeOutAmount;

    /**
     * 仓位要求
     *
     * @var string
     * @required
     */
    private $requirement;

    /**
     * 开始时间
     *
     * @var string
     * @required
     */
    private $startTime;

    /**
     * 结束时间
     *
     * @var string
     * @required
     */
    private $endTime;

    /**
     * 投资方向
     *
     * @var string
     * @required
     */
    private $investmentDirection;

    /**
     * 配资金额
     *
     * @var int
     * @required
     */
    private $matchMoney;

    /**
     * 哪个证券
     *
     * @var string
     * @required
     */
    private $accountBrand;

    /**
     * 保证金
     *
     * @var int
     * @required
     */
    private $guaranteeMoney;

    /**
     * 是否有待审核的续期合约申请
     *
     * @var bool
     * @required
     */
    private $hasExtenstionOporder;

    /**
     * 触线
     *
     * @var int
     * @required
     */
    private $line;

    /**
     * 账户
     *
     * @var string
     * @required
     */
    private $account;

    /**
     * 密码
     *
     * @var string
     * @required
     */
    private $password;

    /**
     * 是否可以追回保证金
     *
     * @var bool
     * @required
     */
    private $canZhuiJia;

    /**
     * 是否可以提取利润
     *
     * @var bool
     * @required
     */
    private $canTiQu;

    /**
     * 是否可以续期
     *
     * @var bool
     * @required
     */
    private $canXuQi;

    /**
     * 是否可以终止
     *
     * @var bool
     * @required
     */
    private $canZhongZhi;

    /**
     * @return int
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param int $totalAmount
     * @return ResponseFutureInfo
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
    public function getOpAmount()
    {
        return $this->opAmount;
    }

    /**
     * @param int $opAmount
     * @return ResponseFutureInfo
     */
    public function setOpAmount($opAmount)
    {
        \Assert\Assertion::integer($opAmount);

        $this->opAmount = $opAmount;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsByDay()
    {
        return $this->isByDay;
    }

    /**
     * @param bool $isByDay
     * @return ResponseFutureInfo
     */
    public function setIsByDay($isByDay)
    {
        \Assert\Assertion::boolean($isByDay);

        $this->isByDay = $isByDay;

        return $this;
    }
    /**
     * @return int
     */
    public function getManagementFee()
    {
        return $this->managementFee;
    }

    /**
     * @param int $managementFee
     * @return ResponseFutureInfo
     */
    public function setManagementFee($managementFee)
    {
        \Assert\Assertion::integer($managementFee);

        $this->managementFee = $managementFee;

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
     * @return ResponseFutureInfo
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
    public function getCloseOutAmount()
    {
        return $this->closeOutAmount;
    }

    /**
     * @param int $closeOutAmount
     * @return ResponseFutureInfo
     */
    public function setCloseOutAmount($closeOutAmount)
    {
        \Assert\Assertion::integer($closeOutAmount);

        $this->closeOutAmount = $closeOutAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getRequirement()
    {
        return $this->requirement;
    }

    /**
     * @param string $requirement
     * @return ResponseFutureInfo
     */
    public function setRequirement($requirement)
    {
        \Assert\Assertion::string($requirement);

        $this->requirement = $requirement;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param string $startTime
     * @return ResponseFutureInfo
     */
    public function setStartTime($startTime)
    {
        \Assert\Assertion::string($startTime);

        $this->startTime = $startTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param string $endTime
     * @return ResponseFutureInfo
     */
    public function setEndTime($endTime)
    {
        \Assert\Assertion::string($endTime);

        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestmentDirection()
    {
        return $this->investmentDirection;
    }

    /**
     * @param string $investmentDirection
     * @return ResponseFutureInfo
     */
    public function setInvestmentDirection($investmentDirection)
    {
        \Assert\Assertion::string($investmentDirection);

        $this->investmentDirection = $investmentDirection;

        return $this;
    }
    /**
     * @return int
     */
    public function getMatchMoney()
    {
        return $this->matchMoney;
    }

    /**
     * @param int $matchMoney
     * @return ResponseFutureInfo
     */
    public function setMatchMoney($matchMoney)
    {
        \Assert\Assertion::integer($matchMoney);

        $this->matchMoney = $matchMoney;

        return $this;
    }
    /**
     * @return string
     */
    public function getAccountBrand()
    {
        return $this->accountBrand;
    }

    /**
     * @param string $accountBrand
     * @return ResponseFutureInfo
     */
    public function setAccountBrand($accountBrand)
    {
        \Assert\Assertion::string($accountBrand);

        $this->accountBrand = $accountBrand;

        return $this;
    }
    /**
     * @return int
     */
    public function getGuaranteeMoney()
    {
        return $this->guaranteeMoney;
    }

    /**
     * @param int $guaranteeMoney
     * @return ResponseFutureInfo
     */
    public function setGuaranteeMoney($guaranteeMoney)
    {
        \Assert\Assertion::integer($guaranteeMoney);

        $this->guaranteeMoney = $guaranteeMoney;

        return $this;
    }
    /**
     * @return bool
     */
    public function getHasExtenstionOporder()
    {
        return $this->hasExtenstionOporder;
    }

    /**
     * @param bool $hasExtenstionOporder
     * @return ResponseFutureInfo
     */
    public function setHasExtenstionOporder($hasExtenstionOporder)
    {
        \Assert\Assertion::boolean($hasExtenstionOporder);

        $this->hasExtenstionOporder = $hasExtenstionOporder;

        return $this;
    }
    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @param int $line
     * @return ResponseFutureInfo
     */
    public function setLine($line)
    {
        \Assert\Assertion::integer($line);

        $this->line = $line;

        return $this;
    }
    /**
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param string $account
     * @return ResponseFutureInfo
     */
    public function setAccount($account)
    {
        \Assert\Assertion::string($account);

        $this->account = $account;

        return $this;
    }
    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return ResponseFutureInfo
     */
    public function setPassword($password)
    {
        \Assert\Assertion::string($password);

        $this->password = $password;

        return $this;
    }
    /**
     * @return bool
     */
    public function getCanZhuiJia()
    {
        return $this->canZhuiJia;
    }

    /**
     * @param bool $canZhuiJia
     * @return ResponseFutureInfo
     */
    public function setCanZhuiJia($canZhuiJia)
    {
        \Assert\Assertion::boolean($canZhuiJia);

        $this->canZhuiJia = $canZhuiJia;

        return $this;
    }
    /**
     * @return bool
     */
    public function getCanTiQu()
    {
        return $this->canTiQu;
    }

    /**
     * @param bool $canTiQu
     * @return ResponseFutureInfo
     */
    public function setCanTiQu($canTiQu)
    {
        \Assert\Assertion::boolean($canTiQu);

        $this->canTiQu = $canTiQu;

        return $this;
    }
    /**
     * @return bool
     */
    public function getCanXuQi()
    {
        return $this->canXuQi;
    }

    /**
     * @param bool $canXuQi
     * @return ResponseFutureInfo
     */
    public function setCanXuQi($canXuQi)
    {
        \Assert\Assertion::boolean($canXuQi);

        $this->canXuQi = $canXuQi;

        return $this;
    }
    /**
     * @return bool
     */
    public function getCanZhongZhi()
    {
        return $this->canZhongZhi;
    }

    /**
     * @param bool $canZhongZhi
     * @return ResponseFutureInfo
     */
    public function setCanZhongZhi($canZhongZhi)
    {
        \Assert\Assertion::boolean($canZhongZhi);

        $this->canZhongZhi = $canZhongZhi;

        return $this;
    }

}