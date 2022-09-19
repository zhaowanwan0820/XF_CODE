<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 创建基金申购订单(只在本地生效)
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class RequestCreatePurchaseOrder extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 基金编码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 申购金额(单位：分)
     *
     * @var int
     * @required
     */
    private $amount;

    /**
     * 是否确认风险
     *
     * @var int
     * @required
     */
    private $confirmRisk;

    /**
     * 分站Id（默认为主站，值为1）
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

    /**
     * 是否是灰度
     *
     * @var bool
     * @optional
     */
    private $isPreProduct = false;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestCreatePurchaseOrder
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
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestCreatePurchaseOrder
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

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
     * @return RequestCreatePurchaseOrder
     */
    public function setAmount($amount)
    {
        \Assert\Assertion::integer($amount);

        $this->amount = $amount;

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
     * @return RequestCreatePurchaseOrder
     */
    public function setConfirmRisk($confirmRisk)
    {
        \Assert\Assertion::integer($confirmRisk);

        $this->confirmRisk = $confirmRisk;

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
     * @return RequestCreatePurchaseOrder
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsPreProduct()
    {
        return $this->isPreProduct;
    }

    /**
     * @param bool $isPreProduct
     * @return RequestCreatePurchaseOrder
     */
    public function setIsPreProduct($isPreProduct = false)
    {
        $this->isPreProduct = $isPreProduct;

        return $this;
    }

}