<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 创建申购订单
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class ResponseCreatePurchaseOrder extends ResponseBase
{
    /**
     * 网信理财在先锋支付的商户ID
     *
     * @var string
     * @required
     */
    private $merchantId;

    /**
     * 网信理财在联合基金的商户ID
     *
     * @var string
     * @required
     */
    private $fundMerchantId;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * app端与先锋支付服务端交互时用到的签名，应用场景：（1）用于开先锋支付基金子账户；（2）用于旧版申购业务中的支付密码验密场景
     *
     * @var string
     * @required
     */
    private $sign;

    /**
     * 基金名称
     *
     * @var string
     * @required
     */
    private $fundName;

    /**
     * 购买金额（单位：分）
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 订单号
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * 是否开过户
     *
     * @var int
     * @required
     */
    private $flag;

    /**
     * 认证类型
     *
     * @var string
     * @optional
     */
    private $certType = '';

    /**
     * 认证类型
     *
     * @var string
     * @optional
     */
    private $cardNo = '';

    /**
     * 实名
     *
     * @var string
     * @optional
     */
    private $realName = '';

    /**
     * 手机号码
     *
     * @var string
     * @optional
     */
    private $mobileNo = '';

    /**
     * app端与先锋支付服务端交互时用到的签名，应用场景：用于新版申购业务中的支付密码验密场景
     *
     * @var string
     * @optional
     */
    private $signPwd = '';

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
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     * @return ResponseCreatePurchaseOrder
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
    public function getFundMerchantId()
    {
        return $this->fundMerchantId;
    }

    /**
     * @param string $fundMerchantId
     * @return ResponseCreatePurchaseOrder
     */
    public function setFundMerchantId($fundMerchantId)
    {
        \Assert\Assertion::string($fundMerchantId);

        $this->fundMerchantId = $fundMerchantId;

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
     * @return ResponseCreatePurchaseOrder
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
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * @param string $sign
     * @return ResponseCreatePurchaseOrder
     */
    public function setSign($sign)
    {
        \Assert\Assertion::string($sign);

        $this->sign = $sign;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundName()
    {
        return $this->fundName;
    }

    /**
     * @param string $fundName
     * @return ResponseCreatePurchaseOrder
     */
    public function setFundName($fundName)
    {
        \Assert\Assertion::string($fundName);

        $this->fundName = $fundName;

        return $this;
    }
    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return ResponseCreatePurchaseOrder
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

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
     * @return ResponseCreatePurchaseOrder
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
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @param int $flag
     * @return ResponseCreatePurchaseOrder
     */
    public function setFlag($flag)
    {
        \Assert\Assertion::integer($flag);

        $this->flag = $flag;

        return $this;
    }
    /**
     * @return string
     */
    public function getCertType()
    {
        return $this->certType;
    }

    /**
     * @param string $certType
     * @return ResponseCreatePurchaseOrder
     */
    public function setCertType($certType = '')
    {
        $this->certType = $certType;

        return $this;
    }
    /**
     * @return string
     */
    public function getCardNo()
    {
        return $this->cardNo;
    }

    /**
     * @param string $cardNo
     * @return ResponseCreatePurchaseOrder
     */
    public function setCardNo($cardNo = '')
    {
        $this->cardNo = $cardNo;

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
     * @return ResponseCreatePurchaseOrder
     */
    public function setRealName($realName = '')
    {
        $this->realName = $realName;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobileNo()
    {
        return $this->mobileNo;
    }

    /**
     * @param string $mobileNo
     * @return ResponseCreatePurchaseOrder
     */
    public function setMobileNo($mobileNo = '')
    {
        $this->mobileNo = $mobileNo;

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
     * @return ResponseCreatePurchaseOrder
     */
    public function setSignPwd($signPwd = '')
    {
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
     * @return ResponseCreatePurchaseOrder
     */
    public function setNotifyUrl($notifyUrl = '')
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

}