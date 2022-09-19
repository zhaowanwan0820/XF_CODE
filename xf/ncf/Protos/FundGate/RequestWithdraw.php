<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 向基金公司发起撤单请求
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestWithdraw extends AbstractRequestBase
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
     * 分站Id（默认为主站，值为1）
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

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
     * @return RequestWithdraw
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
     * @return RequestWithdraw
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
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestWithdraw
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

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
     * @return RequestWithdraw
     */
    public function setAppVersion($appVersion = '')
    {
        $this->appVersion = $appVersion;

        return $this;
    }

}