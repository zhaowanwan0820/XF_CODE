<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 第三方交互-投资接口请求定义
 *
 * 由代码生成器生成, 不可人为修改
 * @author guofeng3
 */
class RequestThirdPartyInvest extends ProtoBufferBase
{
    /**
     * 商户ID
     *
     * @var int
     * @required
     */
    private $merchantId;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 商户编号
     *
     * @var string
     * @required
     */
    private $merchantNo;

    /**
     * 第三方付款单号
     *
     * @var string
     * @required
     */
    private $outOrderId;

    /**
     * 用户认筹金额，单位：分
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 交易事由,交易简单描述
     *
     * @var string
     * @required
     */
    private $case;

    /**
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param int $merchantId
     * @return RequestThirdPartyInvest
     */
    public function setMerchantId($merchantId)
    {
        \Assert\Assertion::integer($merchantId);

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
     * @return RequestThirdPartyInvest
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
    public function getMerchantNo()
    {
        return $this->merchantNo;
    }

    /**
     * @param string $merchantNo
     * @return RequestThirdPartyInvest
     */
    public function setMerchantNo($merchantNo)
    {
        \Assert\Assertion::string($merchantNo);

        $this->merchantNo = $merchantNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getOutOrderId()
    {
        return $this->outOrderId;
    }

    /**
     * @param string $outOrderId
     * @return RequestThirdPartyInvest
     */
    public function setOutOrderId($outOrderId)
    {
        \Assert\Assertion::string($outOrderId);

        $this->outOrderId = $outOrderId;

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
     * @return RequestThirdPartyInvest
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
    public function getCase()
    {
        return $this->case;
    }

    /**
     * @param string $case
     * @return RequestThirdPartyInvest
     */
    public function setCase($case)
    {
        \Assert\Assertion::string($case);

        $this->case = $case;

        return $this;
    }

}