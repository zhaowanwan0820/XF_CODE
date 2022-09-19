<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 向基金公司发起申购请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class RequestRemotePurchase extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * 是否确认风险
     *
     * @var int
     * @required
     */
    private $confirmRisk;

    /**
     * app版本号
     *
     * @var string
     * @optional
     */
    private $appVersion = '';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestRemotePurchase
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestRemotePurchase
     */
    public function setOrderId($orderId)
    {
        \Assert\Assertion::string($orderId);

        $this->orderId = $orderId;

        return $this;
    }
    /**
     * @return int
     */
    public function getConfirmRisk()
    {
        return $this->confirmRisk;
    }

    /**
     * @param int $confirmRisk
     * @return RequestRemotePurchase
     */
    public function setConfirmRisk($confirmRisk)
    {
        \Assert\Assertion::integer($confirmRisk);

        $this->confirmRisk = $confirmRisk;

        return $this;
    }
    /**
     * @return string
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param string $appVersion
     * @return RequestRemotePurchase
     */
    public function setAppVersion($appVersion = '')
    {
        $this->appVersion = $appVersion;

        return $this;
    }

}