<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 理财师信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseCfpInfo extends ResponseBase
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
     * 邮箱
     *
     * @var string
     * @required
     */
    private $email;

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
     * 总收益
     *
     * @var string
     * @required
     */
    private $profitTotal;

    /**
     * 已结算
     *
     * @var string
     * @required
     */
    private $beenSettled;

    /**
     * 未结算
     *
     * @var string
     * @required
     */
    private $tobeSettled;

    /**
     * 客户数
     *
     * @var string
     * @required
     */
    private $customerNum;

    /**
     * 在投客户数
     *
     * @var string
     * @required
     */
    private $investingNum;

    /**
     * 优惠码
     *
     * @var string
     * @required
     */
    private $couponStr;

    /**
     * 优惠码详情
     *
     * @var string
     * @optional
     */
    private $couponInfoStr = '';

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return ResponseCfpInfo
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
     * @return ResponseCfpInfo
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return ResponseCfpInfo
     */
    public function setEmail($email)
    {
        \Assert\Assertion::string($email);

        $this->email = $email;

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
     * @return ResponseCfpInfo
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
     * @return ResponseCfpInfo
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
     * @return ResponseCfpInfo
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
    public function getProfitTotal()
    {
        return $this->profitTotal;
    }

    /**
     * @param string $profitTotal
     * @return ResponseCfpInfo
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
    public function getBeenSettled()
    {
        return $this->beenSettled;
    }

    /**
     * @param string $beenSettled
     * @return ResponseCfpInfo
     */
    public function setBeenSettled($beenSettled)
    {
        \Assert\Assertion::string($beenSettled);

        $this->beenSettled = $beenSettled;

        return $this;
    }
    /**
     * @return string
     */
    public function getTobeSettled()
    {
        return $this->tobeSettled;
    }

    /**
     * @param string $tobeSettled
     * @return ResponseCfpInfo
     */
    public function setTobeSettled($tobeSettled)
    {
        \Assert\Assertion::string($tobeSettled);

        $this->tobeSettled = $tobeSettled;

        return $this;
    }
    /**
     * @return string
     */
    public function getCustomerNum()
    {
        return $this->customerNum;
    }

    /**
     * @param string $customerNum
     * @return ResponseCfpInfo
     */
    public function setCustomerNum($customerNum)
    {
        \Assert\Assertion::string($customerNum);

        $this->customerNum = $customerNum;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestingNum()
    {
        return $this->investingNum;
    }

    /**
     * @param string $investingNum
     * @return ResponseCfpInfo
     */
    public function setInvestingNum($investingNum)
    {
        \Assert\Assertion::string($investingNum);

        $this->investingNum = $investingNum;

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
     * @return ResponseCfpInfo
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
    public function getCouponInfoStr()
    {
        return $this->couponInfoStr;
    }

    /**
     * @param string $couponInfoStr
     * @return ResponseCfpInfo
     */
    public function setCouponInfoStr($couponInfoStr = '')
    {
        $this->couponInfoStr = $couponInfoStr;

        return $this;
    }

}