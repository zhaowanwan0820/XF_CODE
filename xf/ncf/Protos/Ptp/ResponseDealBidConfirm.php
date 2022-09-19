<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 投资确认
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class ResponseDealBidConfirm extends ResponseBase
{
    /**
     * 产品ID
     *
     * @var int
     * @required
     */
    private $productID;

    /**
     * 类型
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 标题
     *
     * @var string
     * @required
     */
    private $title;

    /**
     * 收益率
     *
     * @var string
     * @required
     */
    private $rate;

    /**
     * 期限
     *
     * @var string
     * @required
     */
    private $timelimit;

    /**
     * 总额
     *
     * @var string
     * @required
     */
    private $total;

    /**
     * 可投金额
     *
     * @var string
     * @required
     */
    private $avaliable;

    /**
     * 起投金额
     *
     * @var string
     * @required
     */
    private $mini;

    /**
     * 还款方式
     *
     * @var string
     * @required
     */
    private $repayment;

    /**
     * 状态
     *
     * @var int
     * @required
     */
    private $stats;

    /**
     * 账户余额
     *
     * @var string
     * @required
     */
    private $remain;

    /**
     * 默认的投资金额
     *
     * @var string
     * @required
     */
    private $money_loan;

    /**
     * 预期收益率
     *
     * @var string
     * @required
     */
    private $expire_rate;

    /**
     * 到期收益
     *
     * @var string
     * @required
     */
    private $expire_earning;

    /**
     * 红包金额
     *
     * @var string
     * @required
     */
    private $bonus;

    /**
     * 合同
     *
     * @var array
     * @required
     */
    private $contract;

    /**
     * 返填的优惠码
     *
     * @var string
     * @required
     */
    private $couponStr;

    /**
     * 优惠码描述
     *
     * @var string
     * @required
     */
    private $couponRemark;

    /**
     * 是否绑定,1是绑定
     *
     * @var int
     * @required
     */
    private $couponIsFixed;

    /**
     * 期间收益率
     *
     * @var float
     * @optional
     */
    private $periodRate = 0;

    /**
     * 标的类型,0普通  1利滚利标
     *
     * @var int
     * @optional
     */
    private $dealType = 0;

    /**
     * 是否专享标
     *
     * @var int
     * @optional
     */
    private $isBxt = 0;

    /**
     * 最大收益率
     *
     * @var string
     * @optional
     */
    private $maxRate = 0;

    /**
     * 投资人群
     *
     * @var int
     * @optional
     */
    private $dealCrowd = 0;

    /**
     * 还款方式
     *
     * @var int
     * @optional
     */
    private $loanType = 0;

    /**
     * 投资劵开关
     *
     * @var int
     * @optional
     */
    private $discountSwitch = 0;

    /**
     * type_id
     *
     * @var int
     * @optional
     */
    private $typeId = 0;

    /**
     * 产品加密ID
     *
     * @var string
     * @optional
     */
    private $productECID = '';

    /**
     * 返点比例 
     *
     * @var float
     * @optional
     */
    private $rebateRatioShow = '0.00';

    /**
     * 其他参数
     *
     * @var array
     * @optional
     */
    private $otherParams = NULL;

    /**
     * 项目风险承受能力
     *
     * @var array
     * @optional
     */
    private $dealProjectRisk = NULL;

    /**
     * 存管相关参数
     *
     * @var array
     * @optional
     */
    private $svInfo = NULL;

    /**
     * @return int
     */
    public function getProductID()
    {
        return $this->productID;
    }

    /**
     * @param int $productID
     * @return ResponseDealBidConfirm
     */
    public function setProductID($productID)
    {
        \Assert\Assertion::integer($productID);

        $this->productID = $productID;

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
     * @return ResponseDealBidConfirm
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return ResponseDealBidConfirm
     */
    public function setTitle($title)
    {
        \Assert\Assertion::string($title);

        $this->title = $title;

        return $this;
    }
    /**
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     * @return ResponseDealBidConfirm
     */
    public function setRate($rate)
    {
        \Assert\Assertion::string($rate);

        $this->rate = $rate;

        return $this;
    }
    /**
     * @return string
     */
    public function getTimelimit()
    {
        return $this->timelimit;
    }

    /**
     * @param string $timelimit
     * @return ResponseDealBidConfirm
     */
    public function setTimelimit($timelimit)
    {
        \Assert\Assertion::string($timelimit);

        $this->timelimit = $timelimit;

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
     * @return ResponseDealBidConfirm
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
    public function getAvaliable()
    {
        return $this->avaliable;
    }

    /**
     * @param string $avaliable
     * @return ResponseDealBidConfirm
     */
    public function setAvaliable($avaliable)
    {
        \Assert\Assertion::string($avaliable);

        $this->avaliable = $avaliable;

        return $this;
    }
    /**
     * @return string
     */
    public function getMini()
    {
        return $this->mini;
    }

    /**
     * @param string $mini
     * @return ResponseDealBidConfirm
     */
    public function setMini($mini)
    {
        \Assert\Assertion::string($mini);

        $this->mini = $mini;

        return $this;
    }
    /**
     * @return string
     */
    public function getRepayment()
    {
        return $this->repayment;
    }

    /**
     * @param string $repayment
     * @return ResponseDealBidConfirm
     */
    public function setRepayment($repayment)
    {
        \Assert\Assertion::string($repayment);

        $this->repayment = $repayment;

        return $this;
    }
    /**
     * @return int
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @param int $stats
     * @return ResponseDealBidConfirm
     */
    public function setStats($stats)
    {
        \Assert\Assertion::integer($stats);

        $this->stats = $stats;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemain()
    {
        return $this->remain;
    }

    /**
     * @param string $remain
     * @return ResponseDealBidConfirm
     */
    public function setRemain($remain)
    {
        \Assert\Assertion::string($remain);

        $this->remain = $remain;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney_loan()
    {
        return $this->money_loan;
    }

    /**
     * @param string $money_loan
     * @return ResponseDealBidConfirm
     */
    public function setMoney_loan($money_loan)
    {
        \Assert\Assertion::string($money_loan);

        $this->money_loan = $money_loan;

        return $this;
    }
    /**
     * @return string
     */
    public function getExpire_rate()
    {
        return $this->expire_rate;
    }

    /**
     * @param string $expire_rate
     * @return ResponseDealBidConfirm
     */
    public function setExpire_rate($expire_rate)
    {
        \Assert\Assertion::string($expire_rate);

        $this->expire_rate = $expire_rate;

        return $this;
    }
    /**
     * @return string
     */
    public function getExpire_earning()
    {
        return $this->expire_earning;
    }

    /**
     * @param string $expire_earning
     * @return ResponseDealBidConfirm
     */
    public function setExpire_earning($expire_earning)
    {
        \Assert\Assertion::string($expire_earning);

        $this->expire_earning = $expire_earning;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * @param string $bonus
     * @return ResponseDealBidConfirm
     */
    public function setBonus($bonus)
    {
        \Assert\Assertion::string($bonus);

        $this->bonus = $bonus;

        return $this;
    }
    /**
     * @return array
     */
    public function getContract()
    {
        return $this->contract;
    }

    /**
     * @param array $contract
     * @return ResponseDealBidConfirm
     */
    public function setContract(array $contract)
    {
        $this->contract = $contract;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponStr()
    {
        return $this->couponStr;
    }

    /**
     * @param string $couponStr
     * @return ResponseDealBidConfirm
     */
    public function setCouponStr($couponStr)
    {
        \Assert\Assertion::string($couponStr);

        $this->couponStr = $couponStr;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponRemark()
    {
        return $this->couponRemark;
    }

    /**
     * @param string $couponRemark
     * @return ResponseDealBidConfirm
     */
    public function setCouponRemark($couponRemark)
    {
        \Assert\Assertion::string($couponRemark);

        $this->couponRemark = $couponRemark;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponIsFixed()
    {
        return $this->couponIsFixed;
    }

    /**
     * @param int $couponIsFixed
     * @return ResponseDealBidConfirm
     */
    public function setCouponIsFixed($couponIsFixed)
    {
        \Assert\Assertion::integer($couponIsFixed);

        $this->couponIsFixed = $couponIsFixed;

        return $this;
    }
    /**
     * @return float
     */
    public function getPeriodRate()
    {
        return $this->periodRate;
    }

    /**
     * @param float $periodRate
     * @return ResponseDealBidConfirm
     */
    public function setPeriodRate($periodRate = 0)
    {
        $this->periodRate = $periodRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return ResponseDealBidConfirm
     */
    public function setDealType($dealType = 0)
    {
        $this->dealType = $dealType;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsBxt()
    {
        return $this->isBxt;
    }

    /**
     * @param int $isBxt
     * @return ResponseDealBidConfirm
     */
    public function setIsBxt($isBxt = 0)
    {
        $this->isBxt = $isBxt;

        return $this;
    }
    /**
     * @return string
     */
    public function getMaxRate()
    {
        return $this->maxRate;
    }

    /**
     * @param string $maxRate
     * @return ResponseDealBidConfirm
     */
    public function setMaxRate($maxRate = 0)
    {
        $this->maxRate = $maxRate;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealCrowd()
    {
        return $this->dealCrowd;
    }

    /**
     * @param int $dealCrowd
     * @return ResponseDealBidConfirm
     */
    public function setDealCrowd($dealCrowd = 0)
    {
        $this->dealCrowd = $dealCrowd;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoanType()
    {
        return $this->loanType;
    }

    /**
     * @param int $loanType
     * @return ResponseDealBidConfirm
     */
    public function setLoanType($loanType = 0)
    {
        $this->loanType = $loanType;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountSwitch()
    {
        return $this->discountSwitch;
    }

    /**
     * @param int $discountSwitch
     * @return ResponseDealBidConfirm
     */
    public function setDiscountSwitch($discountSwitch = 0)
    {
        $this->discountSwitch = $discountSwitch;

        return $this;
    }
    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     * @return ResponseDealBidConfirm
     */
    public function setTypeId($typeId = 0)
    {
        $this->typeId = $typeId;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductECID()
    {
        return $this->productECID;
    }

    /**
     * @param string $productECID
     * @return ResponseDealBidConfirm
     */
    public function setProductECID($productECID = '')
    {
        $this->productECID = $productECID;

        return $this;
    }
    /**
     * @return float
     */
    public function getRebateRatioShow()
    {
        return $this->rebateRatioShow;
    }

    /**
     * @param float $rebateRatioShow
     * @return ResponseDealBidConfirm
     */
    public function setRebateRatioShow($rebateRatioShow = '0.00')
    {
        $this->rebateRatioShow = $rebateRatioShow;

        return $this;
    }
    /**
     * @return array
     */
    public function getOtherParams()
    {
        return $this->otherParams;
    }

    /**
     * @param array $otherParams
     * @return ResponseDealBidConfirm
     */
    public function setOtherParams(array $otherParams = NULL)
    {
        $this->otherParams = $otherParams;

        return $this;
    }
    /**
     * @return array
     */
    public function getDealProjectRisk()
    {
        return $this->dealProjectRisk;
    }

    /**
     * @param array $dealProjectRisk
     * @return ResponseDealBidConfirm
     */
    public function setDealProjectRisk(array $dealProjectRisk = NULL)
    {
        $this->dealProjectRisk = $dealProjectRisk;

        return $this;
    }
    /**
     * @return array
     */
    public function getSvInfo()
    {
        return $this->svInfo;
    }

    /**
     * @param array $svInfo
     * @return ResponseDealBidConfirm
     */
    public function setSvInfo(array $svInfo = NULL)
    {
        $this->svInfo = $svInfo;

        return $this;
    }

}