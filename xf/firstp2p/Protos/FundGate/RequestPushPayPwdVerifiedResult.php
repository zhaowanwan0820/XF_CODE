<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 推送支付密码的验证结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestPushPayPwdVerifiedResult extends AbstractRequestBase
{
    /**
     * 验密返回状态
     *
     * @var string
     * @required
     */
    private $status;

    /**
     * 验密返回消息
     *
     * @var string
     * @required
     */
    private $respMsg;

    /**
     * 验密响应码
     *
     * @var string
     * @required
     */
    private $respCode;

    /**
     * 商户用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 商户ID
     *
     * @var string
     * @required
     */
    private $merchantId;

    /**
     * 业务订单号
     *
     * @var string
     * @required
     */
    private $bizOrderId;

    /**
     * 签名
     *
     * @var string
     * @required
     */
    private $sign;

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return RequestPushPayPwdVerifiedResult
     */
    public function setStatus($status)
    {
        \Assert\Assertion::string($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getRespMsg()
    {
        return $this->respMsg;
    }

    /**
     * @param string $respMsg
     * @return RequestPushPayPwdVerifiedResult
     */
    public function setRespMsg($respMsg)
    {
        \Assert\Assertion::string($respMsg);

        $this->respMsg = $respMsg;

        return $this;
    }
    /**
     * @return string
     */
    public function getRespCode()
    {
        return $this->respCode;
    }

    /**
     * @param string $respCode
     * @return RequestPushPayPwdVerifiedResult
     */
    public function setRespCode($respCode)
    {
        \Assert\Assertion::string($respCode);

        $this->respCode = $respCode;

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
     * @return RequestPushPayPwdVerifiedResult
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
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     * @return RequestPushPayPwdVerifiedResult
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
    public function getBizOrderId()
    {
        return $this->bizOrderId;
    }

    /**
     * @param string $bizOrderId
     * @return RequestPushPayPwdVerifiedResult
     */
    public function setBizOrderId($bizOrderId)
    {
        \Assert\Assertion::string($bizOrderId);

        $this->bizOrderId = $bizOrderId;

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
     * @return RequestPushPayPwdVerifiedResult
     */
    public function setSign($sign)
    {
        \Assert\Assertion::string($sign);

        $this->sign = $sign;

        return $this;
    }

}