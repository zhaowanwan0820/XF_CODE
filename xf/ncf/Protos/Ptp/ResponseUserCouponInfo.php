<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 用户邀请码接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ResponseUserCouponInfo extends ProtoBufferBase
{
    /**
     * 邀请码
     *
     * @var string
     * @required
     */
    private $coupon;

    /**
     * 邀请码有效
     *
     * @var int
     * @required
     */
    private $isNotCode;

    /**
     * 用户返利
     *
     * @var string
     * @required
     */
    private $rebateRatio;

    /**
     * 邀请人返利
     *
     * @var string
     * @required
     */
    private $refererRebateRatio;

    /**
     * 分享的内容
     *
     * @var string
     * @required
     */
    private $shareContent;

    /**
     * 是否为O2O
     *
     * @var int
     * @required
     */
    private $isO2O;

    /**
     * 邀请码投资列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * 邀请人数
     *
     * @var int
     * @required
     */
    private $consumeUserCount;

    /**
     * 已返
     *
     * @var float
     * @required
     */
    private $refererRebateAmount;

    /**
     * 未返
     *
     * @var float
     * @required
     */
    private $refererRebateAmountNo;

    /**
     * 是否可以分享红包
     *
     * @var int
     * @optional
     */
    private $canShare = 0;

    /**
     * 红包分享标题
     *
     * @var string
     * @optional
     */
    private $bonusTitle = '';

    /**
     * 红包分享图片
     *
     * @var string
     * @optional
     */
    private $bonusImg = '';

    /**
     * 邀请码模块列表
     *
     * @var array
     * @required
     */
    private $couponModelTypes;

    /**
     * 投资次数是否为多次
     *
     * @var int
     * @optional
     */
    private $bidMore = '0';

    /**
     * 被邀请人是否绑卡
     *
     * @var int
     * @optional
     */
    private $inviteeIsBank = '0';

    /**
     * 被邀请人是否投资
     *
     * @var int
     * @optional
     */
    private $inviteeIsInvest = '0';

    /**
     * 被邀请人的累计返利
     *
     * @var float
     * @optional
     */
    private $inviteeRebateAmount = '0';

    /**
     * 被邀请人的待返返利
     *
     * @var float
     * @optional
     */
    private $inviteeRebateAmountNo = '0';

    /**
     * 返利文案
     *
     * @var string
     * @optional
     */
    private $refererRebateMsg = '';

    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param string $coupon
     * @return ResponseUserCouponInfo
     */
    public function setCoupon($coupon)
    {
        \Assert\Assertion::string($coupon);

        $this->coupon = $coupon;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsNotCode()
    {
        return $this->isNotCode;
    }

    /**
     * @param int $isNotCode
     * @return ResponseUserCouponInfo
     */
    public function setIsNotCode($isNotCode)
    {
        \Assert\Assertion::integer($isNotCode);

        $this->isNotCode = $isNotCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getRebateRatio()
    {
        return $this->rebateRatio;
    }

    /**
     * @param string $rebateRatio
     * @return ResponseUserCouponInfo
     */
    public function setRebateRatio($rebateRatio)
    {
        \Assert\Assertion::string($rebateRatio);

        $this->rebateRatio = $rebateRatio;

        return $this;
    }
    /**
     * @return string
     */
    public function getRefererRebateRatio()
    {
        return $this->refererRebateRatio;
    }

    /**
     * @param string $refererRebateRatio
     * @return ResponseUserCouponInfo
     */
    public function setRefererRebateRatio($refererRebateRatio)
    {
        \Assert\Assertion::string($refererRebateRatio);

        $this->refererRebateRatio = $refererRebateRatio;

        return $this;
    }
    /**
     * @return string
     */
    public function getShareContent()
    {
        return $this->shareContent;
    }

    /**
     * @param string $shareContent
     * @return ResponseUserCouponInfo
     */
    public function setShareContent($shareContent)
    {
        \Assert\Assertion::string($shareContent);

        $this->shareContent = $shareContent;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsO2O()
    {
        return $this->isO2O;
    }

    /**
     * @param int $isO2O
     * @return ResponseUserCouponInfo
     */
    public function setIsO2O($isO2O)
    {
        \Assert\Assertion::integer($isO2O);

        $this->isO2O = $isO2O;

        return $this;
    }
    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseUserCouponInfo
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsumeUserCount()
    {
        return $this->consumeUserCount;
    }

    /**
     * @param int $consumeUserCount
     * @return ResponseUserCouponInfo
     */
    public function setConsumeUserCount($consumeUserCount)
    {
        \Assert\Assertion::integer($consumeUserCount);

        $this->consumeUserCount = $consumeUserCount;

        return $this;
    }
    /**
     * @return float
     */
    public function getRefererRebateAmount()
    {
        return $this->refererRebateAmount;
    }

    /**
     * @param float $refererRebateAmount
     * @return ResponseUserCouponInfo
     */
    public function setRefererRebateAmount($refererRebateAmount)
    {
        \Assert\Assertion::float($refererRebateAmount);

        $this->refererRebateAmount = $refererRebateAmount;

        return $this;
    }
    /**
     * @return float
     */
    public function getRefererRebateAmountNo()
    {
        return $this->refererRebateAmountNo;
    }

    /**
     * @param float $refererRebateAmountNo
     * @return ResponseUserCouponInfo
     */
    public function setRefererRebateAmountNo($refererRebateAmountNo)
    {
        \Assert\Assertion::float($refererRebateAmountNo);

        $this->refererRebateAmountNo = $refererRebateAmountNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getCanShare()
    {
        return $this->canShare;
    }

    /**
     * @param int $canShare
     * @return ResponseUserCouponInfo
     */
    public function setCanShare($canShare = 0)
    {
        $this->canShare = $canShare;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonusTitle()
    {
        return $this->bonusTitle;
    }

    /**
     * @param string $bonusTitle
     * @return ResponseUserCouponInfo
     */
    public function setBonusTitle($bonusTitle = '')
    {
        $this->bonusTitle = $bonusTitle;

        return $this;
    }
    /**
     * @return string
     */
    public function getBonusImg()
    {
        return $this->bonusImg;
    }

    /**
     * @param string $bonusImg
     * @return ResponseUserCouponInfo
     */
    public function setBonusImg($bonusImg = '')
    {
        $this->bonusImg = $bonusImg;

        return $this;
    }
    /**
     * @return array
     */
    public function getCouponModelTypes()
    {
        return $this->couponModelTypes;
    }

    /**
     * @param array $couponModelTypes
     * @return ResponseUserCouponInfo
     */
    public function setCouponModelTypes(array $couponModelTypes)
    {
        $this->couponModelTypes = $couponModelTypes;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidMore()
    {
        return $this->bidMore;
    }

    /**
     * @param int $bidMore
     * @return ResponseUserCouponInfo
     */
    public function setBidMore($bidMore = '0')
    {
        $this->bidMore = $bidMore;

        return $this;
    }
    /**
     * @return int
     */
    public function getInviteeIsBank()
    {
        return $this->inviteeIsBank;
    }

    /**
     * @param int $inviteeIsBank
     * @return ResponseUserCouponInfo
     */
    public function setInviteeIsBank($inviteeIsBank = '0')
    {
        $this->inviteeIsBank = $inviteeIsBank;

        return $this;
    }
    /**
     * @return int
     */
    public function getInviteeIsInvest()
    {
        return $this->inviteeIsInvest;
    }

    /**
     * @param int $inviteeIsInvest
     * @return ResponseUserCouponInfo
     */
    public function setInviteeIsInvest($inviteeIsInvest = '0')
    {
        $this->inviteeIsInvest = $inviteeIsInvest;

        return $this;
    }
    /**
     * @return float
     */
    public function getInviteeRebateAmount()
    {
        return $this->inviteeRebateAmount;
    }

    /**
     * @param float $inviteeRebateAmount
     * @return ResponseUserCouponInfo
     */
    public function setInviteeRebateAmount($inviteeRebateAmount = '0')
    {
        $this->inviteeRebateAmount = $inviteeRebateAmount;

        return $this;
    }
    /**
     * @return float
     */
    public function getInviteeRebateAmountNo()
    {
        return $this->inviteeRebateAmountNo;
    }

    /**
     * @param float $inviteeRebateAmountNo
     * @return ResponseUserCouponInfo
     */
    public function setInviteeRebateAmountNo($inviteeRebateAmountNo = '0')
    {
        $this->inviteeRebateAmountNo = $inviteeRebateAmountNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getRefererRebateMsg()
    {
        return $this->refererRebateMsg;
    }

    /**
     * @param string $refererRebateMsg
     * @return ResponseUserCouponInfo
     */
    public function setRefererRebateMsg($refererRebateMsg = '')
    {
        $this->refererRebateMsg = $refererRebateMsg;

        return $this;
    }

}