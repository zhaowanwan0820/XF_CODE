<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 请求发起赎回
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ <qicheng@ucfgroup.com>
 */
class RequestRedeem extends AbstractRequestBase
{
    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * app版本号
     *
     * @var string
     * @optional
     */
    private $appVersion = '';

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestRedeem
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestRedeem
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
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param string $appVersion
     * @return RequestRedeem
     */
    public function setAppVersion($appVersion = '')
    {
        $this->appVersion = $appVersion;

        return $this;
    }

}