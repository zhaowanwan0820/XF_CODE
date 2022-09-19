<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 多券商状态同步
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestMulitNotify extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 券商用户唯一标识
     *
     * @var string
     * @required
     */
    private $customerCode;

    /**
     * 开户步骤
     *
     * @var string
     * @required
     */
    private $currentStep;

    /**
     * 用户姓名
     *
     * @var string
     * @required
     */
    private $customerName;

    /**
     * 用户电话
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * 渠道编号
     *
     * @var string
     * @required
     */
    private $sourceNo;

    /**
     * 券商ID
     *
     * @var string
     * @required
     */
    private $traderNo;

    /**
     * 有效用户状态
     *
     * @var int
     * @required
     */
    private $tradeStatus;

    /**
     * 有效时间
     *
     * @var string
     * @required
     */
    private $tradeDate;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestMulitNotify
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
    public function getCustomerCode()
    {
        return $this->customerCode;
    }

    /**
     * @param string $customerCode
     * @return RequestMulitNotify
     */
    public function setCustomerCode($customerCode)
    {
        \Assert\Assertion::string($customerCode);

        $this->customerCode = $customerCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * @param string $currentStep
     * @return RequestMulitNotify
     */
    public function setCurrentStep($currentStep)
    {
        \Assert\Assertion::string($currentStep);

        $this->currentStep = $currentStep;

        return $this;
    }
    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return RequestMulitNotify
     */
    public function setCustomerName($customerName)
    {
        \Assert\Assertion::string($customerName);

        $this->customerName = $customerName;

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
     * @return RequestMulitNotify
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
    public function getSourceNo()
    {
        return $this->sourceNo;
    }

    /**
     * @param string $sourceNo
     * @return RequestMulitNotify
     */
    public function setSourceNo($sourceNo)
    {
        \Assert\Assertion::string($sourceNo);

        $this->sourceNo = $sourceNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getTraderNo()
    {
        return $this->traderNo;
    }

    /**
     * @param string $traderNo
     * @return RequestMulitNotify
     */
    public function setTraderNo($traderNo)
    {
        \Assert\Assertion::string($traderNo);

        $this->traderNo = $traderNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getTradeStatus()
    {
        return $this->tradeStatus;
    }

    /**
     * @param int $tradeStatus
     * @return RequestMulitNotify
     */
    public function setTradeStatus($tradeStatus)
    {
        \Assert\Assertion::integer($tradeStatus);

        $this->tradeStatus = $tradeStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeDate()
    {
        return $this->tradeDate;
    }

    /**
     * @param string $tradeDate
     * @return RequestMulitNotify
     */
    public function setTradeDate($tradeDate)
    {
        \Assert\Assertion::string($tradeDate);

        $this->tradeDate = $tradeDate;

        return $this;
    }

}