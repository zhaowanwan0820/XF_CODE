<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 创建基金赎回订单(只在本地生效)
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ<qicheng@ucfgroup.com>
 */
class ResponseCreateRedeemOrder extends ResponseBase
{
    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 商户号Id
     *
     * @var string
     * @required
     */
    private $merchantId;

    /**
     * 先锋支付商户号Id
     *
     * @var string
     * @required
     */
    private $ncfpayMerchantId;

    /**
     * app端与先锋支付服务端交互时用到的签名，应用场景：用于新版赎回业务中的支付密码验密场景
     *
     * @var string
     * @optional
     */
    private $signPwd = '';

    /**
     * app端与先锋支付服务端交互时用到的签名，应用场景：用于旧版赎回业务中的支付密码验密场景
     *
     * @var string
     * @optional
     */
    private $sign = '';

    /**
     * 先锋支付验密结果通知接口
     *
     * @var string
     * @optional
     */
    private $notifyUrl = '';

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return ResponseCreateRedeemOrder
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     * @return ResponseCreateRedeemOrder
     */
    public function setMerchantId($merchantId)
    {
        \Assert\Assertion::string($merchantId);

        $this->merchantId = $merchantId;

        return $this;
    }
    /**
     * @return string
     */
    public function getNcfpayMerchantId()
    {
        return $this->ncfpayMerchantId;
    }

    /**
     * @param string $ncfpayMerchantId
     * @return ResponseCreateRedeemOrder
     */
    public function setNcfpayMerchantId($ncfpayMerchantId)
    {
        \Assert\Assertion::string($ncfpayMerchantId);

        $this->ncfpayMerchantId = $ncfpayMerchantId;

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
     * @return ResponseCreateRedeemOrder
     */
    public function setSignPwd($signPwd = '')
    {
        $this->signPwd = $signPwd;

        return $this;
    }
    /**
     * @return string
     */
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * @param string $sign
     * @return ResponseCreateRedeemOrder
     */
    public function setSign($sign = '')
    {
        $this->sign = $sign;

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
     * @return ResponseCreateRedeemOrder
     */
    public function setNotifyUrl($notifyUrl = '')
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

}