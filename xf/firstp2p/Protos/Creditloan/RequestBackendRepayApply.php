<?php
namespace NCFGroup\Protos\Creditloan;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 回款时还款申请接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */
class RequestBackendRepayApply extends AbstractRequestBase
{
    /**
     * 用户的ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 标的Id
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 回款Id
     *
     * @var int
     * @required
     */
    private $dealRepayId;

    /**
     * 标的回款类型:正常还款，提前还款
     *
     * @var int
     * @required
     */
    private $dealRepayType;
    /**
     * 代发批次号
     *
     * @var int
     */
    private $merchantBatchNo;
    /**
     * 代发商户号
     *
     * @var int
     */
    private $merchantId;
    /**
     * 标的类型
     *
     * @var int
     */
    private $dealType;

    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return RequestBackendRepayApply
     */
    public function setDealType($dealType)
    {
        \Assert\Assertion::integer($dealType);

        $this->dealType= $dealType;

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
     * @return RequestBackendRepayApply
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestBackendRepayApply
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealRepayId()
    {
        return $this->dealRepayId;
    }

    /**
     * @param int $dealRepayId
     * @return RequestBackendRepayApply
     */
    public function setDealRepayId($dealRepayId)
    {
        \Assert\Assertion::integer($dealRepayId);

        $this->dealRepayId = $dealRepayId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealRepayType()
    {
        return $this->dealRepayType;
    }

    /**
     * @param int $dealRepayType
     * @return RequestBackendRepayApply
     */
    public function setDealRepayType($dealRepayType)
    {
        \Assert\Assertion::integer($dealRepayType);

        $this->dealRepayType = $dealRepayType;

        return $this;
    }
    /**
     * @return int
     */
    public function getMerchantBatchNo()
    {
        return $this->merchantBatchNo;
    }

    /**
     * @param int $merchantBatchNo
     * @return RequestTriggerRepay
     */
    public function setMerchantBatchNo($merchantBatchNo)
    {
        \Assert\Assertion::integer($merchantBatchNo);

        $this->merchantBatchNo = $merchantBatchNo;

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
     * @return RequestTriggerRepay
     */
    public function setMerchantId($merchantId)
    {
        \Assert\Assertion::string($merchantId);

        $this->merchantId = $merchantId;

        return $this;
    }

}
