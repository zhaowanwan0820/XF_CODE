<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取可用的投资券个数
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestAvailableDiscountCount extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $ownerUserId;

    /**
     * 投资金额
     *
     * @var int
     * @optional
     */
    private $bidAmount = 0;

    /**
     * 投资期限
     *
     * @var int
     * @optional
     */
    private $bidDayLimit = 0;

    /**
     * 项目id
     *
     * @var int
     * @optional
     */
    private $projectId = 0;

    /**
     * 产品类别
     *
     * @var string
     * @optional
     */
    private $category = '';

    /**
     * 标tag
     *
     * @var string
     * @optional
     */
    private $dealTag = '';

    /**
     * 投资券类型，1为返现券，2为加息券，3为黄金券，0表示不区分
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 交易类型，1为p2p，2为duotou，3为gold
     *
     * @var int
     * @optional
     */
    private $consumeType = 1;

    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->ownerUserId;
    }

    /**
     * @param int $ownerUserId
     * @return RequestAvailableDiscountCount
     */
    public function setOwnerUserId($ownerUserId)
    {
        \Assert\Assertion::integer($ownerUserId);

        $this->ownerUserId = $ownerUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidAmount()
    {
        return $this->bidAmount;
    }

    /**
     * @param int $bidAmount
     * @return RequestAvailableDiscountCount
     */
    public function setBidAmount($bidAmount = 0)
    {
        $this->bidAmount = $bidAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getBidDayLimit()
    {
        return $this->bidDayLimit;
    }

    /**
     * @param int $bidDayLimit
     * @return RequestAvailableDiscountCount
     */
    public function setBidDayLimit($bidDayLimit = 0)
    {
        $this->bidDayLimit = $bidDayLimit;

        return $this;
    }
    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestAvailableDiscountCount
     */
    public function setProjectId($projectId = 0)
    {
        $this->projectId = $projectId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return RequestAvailableDiscountCount
     */
    public function setCategory($category = '')
    {
        $this->category = $category;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealTag()
    {
        return $this->dealTag;
    }

    /**
     * @param string $dealTag
     * @return RequestAvailableDiscountCount
     */
    public function setDealTag($dealTag = '')
    {
        $this->dealTag = $dealTag;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestAvailableDiscountCount
     */
    public function setType($type = 0)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsumeType()
    {
        return $this->consumeType;
    }

    /**
     * @param int $consumeType
     * @return RequestAvailableDiscountCount
     */
    public function setConsumeType($consumeType = 1)
    {
        $this->consumeType = $consumeType;

        return $this;
    }

}