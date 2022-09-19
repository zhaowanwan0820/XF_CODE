<?php
namespace NCFGroup\Protos\Creditloan;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 回款时校验是否触发还款接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */
class RequestTriggerRepay extends AbstractRequestBase
{
    /**
     * 用户的ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 标的类型
     *
     * @var int
     * @required
     */
    private $dealType;

    /**
     * 标的产品类别
     *
     * @var int
     * @required
     */
    private $dealProductType;

    /**
     * 标的还款方式
     *
     * @var int
     * @required
     */
    private $dealLoanType;

    /**
     * 标的Tag
     *
     * @var array
     * @required
     */
    private $dealTag;

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
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestTriggerRepay
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
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return RequestTriggerRepay
     */
    public function setDealType($dealType)
    {
        \Assert\Assertion::integer($dealType);

        $this->dealType = $dealType;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealProductType()
    {
        return $this->dealProductType;
    }

    /**
     * @param int $dealProductType
     * @return RequestTriggerRepay
     */
    public function setDealProductType($dealProductType)
    {
        \Assert\Assertion::integer($dealProductType);

        $this->dealProductType = $dealProductType;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoanType()
    {
        return $this->dealLoanType;
    }

    /**
     * @param int $dealLoanType
     * @return RequestTriggerRepay
     */
    public function setDealLoanType($dealLoanType)
    {
        \Assert\Assertion::integer($dealLoanType);

        $this->dealLoanType = $dealLoanType;

        return $this;
    }
    /**
     * @return array
     */
    public function getDealTag()
    {
        return $this->dealTag;
    }

    /**
     * @param array $dealTag
     * @return RequestTriggerRepay
     */
    public function setDealTag(array $dealTag)
    {
        $this->dealTag = $dealTag;

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
     * @return RequestTriggerRepay
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
     * @return RequestTriggerRepay
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
     * @return RequestTriggerRepay
     */
    public function setDealRepayType($dealRepayType)
    {
        \Assert\Assertion::integer($dealRepayType);

        $this->dealRepayType = $dealRepayType;

        return $this;
    }
}
