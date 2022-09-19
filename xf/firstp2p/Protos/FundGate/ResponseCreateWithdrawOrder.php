<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 创建撤单订单
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseCreateWithdrawOrder extends ResponseBase
{
    /**
     * 网信理财在先锋支付的商户ID
     *
     * @var string
     * @required
     */
    private $merchantId;

    /**
     * 用户id
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
     * app端与先锋支付服务端交互时用到的签名，应用场景：用于新版撤单业务中的支付密码验密场景
     *
     * @var string
     * @required
     */
    private $signPwd;

    /**
     * 先锋支付验密结果通知接口
     *
     * @var string
     * @required
     */
    private $notifyUrl;

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     * @return ResponseCreateWithdrawOrder
     */
    public function setMerchantId($merchantId)
    {
        \Assert\Assertion::string($merchantId);

        $this->merchantId = $merchantId;

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
     * @return ResponseCreateWithdrawOrder
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
     * @return ResponseCreateWithdrawOrder
     */
    public function setOrderId($orderId)
    {
        \Assert\Assertion::string($orderId);

        $this->orderId = $orderId;

        return $this;
    }
    /**
     * @return string
     */
    public function getSignPwd()
    {
        return $this->signPwd;
    }

    /**
     * @param string $signPwd
     * @return ResponseCreateWithdrawOrder
     */
    public function setSignPwd($signPwd)
    {
        \Assert\Assertion::string($signPwd);

        $this->signPwd = $signPwd;

        return $this;
    }
    /**
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * @param string $notifyUrl
     * @return ResponseCreateWithdrawOrder
     */
    public function setNotifyUrl($notifyUrl)
    {
        \Assert\Assertion::string($notifyUrl);

        $this->notifyUrl = $notifyUrl;

        return $this;
    }

}