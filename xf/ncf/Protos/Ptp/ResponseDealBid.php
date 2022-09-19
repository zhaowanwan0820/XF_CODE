<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 投资接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class ResponseDealBid extends ResponseBase
{
    /**
     * 投资记录id
     *
     * @var int
     * @required
     */
    private $load_id;

    /**
     * 借款名称
     *
     * @var string
     * @required
     */
    private $deal_name;

    /**
     * 借款用途
     *
     * @var string
     * @required
     */
    private $type_info;

    /**
     * 收益率
     *
     * @var string
     * @required
     */
    private $income_rate;

    /**
     * 借款期限
     *
     * @var string
     * @required
     */
    private $repay_time;

    /**
     * 还款方式
     *
     * @var string
     * @required
     */
    private $loantype_name;

    /**
     * 借款金额
     *
     * @var string
     * @required
     */
    private $borrow_amount;

    /**
     * 推荐语
     *
     * @var string
     * @required
     */
    private $recommendation;

    /**
     * 红包数量
     *
     * @var string
     * @required
     */
    private $bonus_ttl;

    /**
     * 跳转url
     *
     * @var string
     * @required
     */
    private $bonus_url;

    /**
     * 分享标题
     *
     * @var string
     * @required
     */
    private $bonus_title;

    /**
     * 分享内容
     *
     * @var string
     * @required
     */
    private $bonus_content;

    /**
     * 分享时候的封面图片
     *
     * @var string
     * @required
     */
    private $bonus_face;

    /**
     * 投标完成时候弹框中内容
     *
     * @var string
     * @required
     */
    private $bonus_bid_finished;

    /**
     * 是否为o2o包
     *
     * @var int
     * @optional
     */
    private $isO2OBonus = '0';

    /**
     * 项目状态
     *
     * @var int
     * @optional
     */
    private $deal_status = 0;

    /**
     * 借款类别
     *
     * @var int
     * @optional
     */
    private $typeId = 0;

    /**
     * 投资劵面值
     *
     * @var string
     * @optional
     */
    private $discountPrice = '';

    /**
     * 还款类型
     *
     * @var int
     * @required
     */
    private $loanType;

    /**
     * 投资劵类型
     *
     * @var int
     * @optional
     */
    private $discountType = '1';

    /**
     * 可领取礼券数量
     *
     * @var int
     * @optional
     */
    private $o2oCouponCount = '0';

    /**
     * 单张礼券时的标题
     *
     * @var string
     * @optional
     */
    private $o2oCouponTitle = '';

    /**
     * 项目风险承受能力和个人评估不一致时弹窗内容
     *
     * @var array
     * @optional
     */
    private $dealProjectRisk = NULL;

    /**
     * 报备状态
     *
     * @var int
     * @optional
     */
    private $reportStatus = '';

    /**
     * @return int
     */
    public function getLoad_id()
    {
        return $this->load_id;
    }

    /**
     * @param int $load_id
     * @return ResponseDealBid
     */
    public function setLoad_id($load_id)
    {
        \Assert\Assertion::integer($load_id);

        $this->load_id = $load_id;

        return $this;
    }
    /**
     * @return string
     */
    public function getDeal_name()
    {
        return $this->deal_name;
    }

    /**
     * @param string $deal_name
     * @return ResponseDealBid
     */
    public function setDeal_name($deal_name)
    {
        \Assert\Assertion::string($deal_name);

        $this->deal_name = $deal_name;

        return $this;
    }
    /**
     * @return string
     */
    public function getType_info()
    {
        return $this->type_info;
    }

    /**
     * @param string $type_info
     * @return ResponseDealBid
     */
    public function setType_info($type_info)
    {
        \Assert\Assertion::string($type_info);

        $this->type_info = $type_info;

        return $this;
    }
    /**
     * @return string
     */
    public function getIncome_rate()
    {
        return $this->income_rate;
    }

    /**
     * @param string $income_rate
     * @return ResponseDealBid
     */
    public function setIncome_rate($income_rate)
    {
        \Assert\Assertion::string($income_rate);

        $this->income_rate = $income_rate;

        return $this;
    }
    /**
     * @return string
     */
    public function getRepay_time()
    {
        return $this->repay_time;
    }

    /**
     * @param string $repay_time
     * @return ResponseDealBid
     */
    public function setRepay_time($repay_time)
    {
        \Assert\Assertion::string($repay_time);

        $this->repay_time = $repay_time;

        return $this;
    }
    /**
     * @return string
     */
    public function getLoantype_name()
    {
        return $this->loantype_name;
    }

    /**
     * @param string $loantype_name
     * @return ResponseDealBid
     */
    public function setLoantype_name($loantype_name)
    {
        \Assert\Assertion::string($loantype_name);

        $this->loantype_name = $loantype_name;

        return $this;
    }
    /**
     * @return string
     */
    public function getBorrow_amount()
    {
        return $this->borrow_amount;
    }

    /**
     * @param string $borrow_amount
     * @return ResponseDealBid
     */
    public function setBorrow_amount($borrow_amount)
    {
        \Assert\Assertion::string($borrow_amount);

        $this->borrow_amount = $borrow_amount;

        return $this;
    }
    /**
     * @return string
     */
    public function getRecommendation()
    {
        return $this->recommendation;
    }

    /**
     * @param string $recommendation
     * @return ResponseDealBid
     */
    public function setRecommendation($recommendation)
    {
        \Assert\Assertion::string($recommendation);

        $this->recommendation = $recommendation;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus_ttl()
    {
        return $this->bonus_ttl;
    }

    /**
     * @param string $bonus_ttl
     * @return ResponseDealBid
     */
    public function setBonus_ttl($bonus_ttl)
    {
        \Assert\Assertion::string($bonus_ttl);

        $this->bonus_ttl = $bonus_ttl;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus_url()
    {
        return $this->bonus_url;
    }

    /**
     * @param string $bonus_url
     * @return ResponseDealBid
     */
    public function setBonus_url($bonus_url)
    {
        \Assert\Assertion::string($bonus_url);

        $this->bonus_url = $bonus_url;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus_title()
    {
        return $this->bonus_title;
    }

    /**
     * @param string $bonus_title
     * @return ResponseDealBid
     */
    public function setBonus_title($bonus_title)
    {
        \Assert\Assertion::string($bonus_title);

        $this->bonus_title = $bonus_title;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus_content()
    {
        return $this->bonus_content;
    }

    /**
     * @param string $bonus_content
     * @return ResponseDealBid
     */
    public function setBonus_content($bonus_content)
    {
        \Assert\Assertion::string($bonus_content);

        $this->bonus_content = $bonus_content;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus_face()
    {
        return $this->bonus_face;
    }

    /**
     * @param string $bonus_face
     * @return ResponseDealBid
     */
    public function setBonus_face($bonus_face)
    {
        \Assert\Assertion::string($bonus_face);

        $this->bonus_face = $bonus_face;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonus_bid_finished()
    {
        return $this->bonus_bid_finished;
    }

    /**
     * @param string $bonus_bid_finished
     * @return ResponseDealBid
     */
    public function setBonus_bid_finished($bonus_bid_finished)
    {
        \Assert\Assertion::string($bonus_bid_finished);

        $this->bonus_bid_finished = $bonus_bid_finished;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsO2OBonus()
    {
        return $this->isO2OBonus;
    }

    /**
     * @param int $isO2OBonus
     * @return ResponseDealBid
     */
    public function setIsO2OBonus($isO2OBonus = '0')
    {
        $this->isO2OBonus = $isO2OBonus;

        return $this;
    }
    /**
     * @return int
     */
    public function getDeal_status()
    {
        return $this->deal_status;
    }

    /**
     * @param int $deal_status
     * @return ResponseDealBid
     */
    public function setDeal_status($deal_status = 0)
    {
        $this->deal_status = $deal_status;

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
     * @return ResponseDealBid
     */
    public function setTypeId($typeId = 0)
    {
        $this->typeId = $typeId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDiscountPrice()
    {
        return $this->discountPrice;
    }

    /**
     * @param string $discountPrice
     * @return ResponseDealBid
     */
    public function setDiscountPrice($discountPrice = '')
    {
        $this->discountPrice = $discountPrice;

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
     * @return ResponseDealBid
     */
    public function setLoanType($loanType)
    {
        \Assert\Assertion::integer($loanType);

        $this->loanType = $loanType;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * @param int $discountType
     * @return ResponseDealBid
     */
    public function setDiscountType($discountType = '1')
    {
        $this->discountType = $discountType;

        return $this;
    }
    /**
     * @return int
     */
    public function getO2oCouponCount()
    {
        return $this->o2oCouponCount;
    }

    /**
     * @param int $o2oCouponCount
     * @return ResponseDealBid
     */
    public function setO2oCouponCount($o2oCouponCount = '0')
    {
        $this->o2oCouponCount = $o2oCouponCount;

        return $this;
    }
    /**
     * @return string
     */
    public function getO2oCouponTitle()
    {
        return $this->o2oCouponTitle;
    }

    /**
     * @param string $o2oCouponTitle
     * @return ResponseDealBid
     */
    public function setO2oCouponTitle($o2oCouponTitle = '')
    {
        $this->o2oCouponTitle = $o2oCouponTitle;

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
     * @return ResponseDealBid
     */
    public function setDealProjectRisk(array $dealProjectRisk = NULL)
    {
        $this->dealProjectRisk = $dealProjectRisk;

        return $this;
    }
    /**
     * @return int
     */
    public function getReportStatus()
    {
        return $this->reportStatus;
    }

    /**
     * @param int $reportStatus
     * @return ResponseDealBid
     */
    public function setReportStatus($reportStatus = '')
    {
        $this->reportStatus = $reportStatus;

        return $this;
    }

}