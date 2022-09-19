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
class ResponseUserCoupon extends ProtoBufferBase
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
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param string $coupon
     * @return ResponseUserCoupon
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
     * @return ResponseUserCoupon
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
     * @return ResponseUserCoupon
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
     * @return ResponseUserCoupon
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
     * @return ResponseUserCoupon
     */
    public function setShareContent($shareContent)
    {
        \Assert\Assertion::string($shareContent);

        $this->shareContent = $shareContent;

        return $this;
    }

}