<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取可用的优惠券
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestAvailableDiscountList extends AbstractRequestBase
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
     * 页码
     *
     * @var int
     * @optional
     */
    private $page = 1;

    /**
     * 每页显示
     *
     * @var int
     * @optional
     */
    private $pageSize = 10;

    /**
     * 是否包含记录总数
     *
     * @var int
     * @optional
     */
    private $hasTotalCount = 1;

    /**
     * 投资券类型，1为返现券，2为加息券，3为黄金券，0表示不区分
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 年化投资额
     *
     * @var int
     * @optional
     */
    private $annualizedAmount = 0;

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
     * @return RequestAvailableDiscountList
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
     * @return RequestAvailableDiscountList
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
     * @return RequestAvailableDiscountList
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
     * @return RequestAvailableDiscountList
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
     * @return RequestAvailableDiscountList
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
     * @return RequestAvailableDiscountList
     */
    public function setDealTag($dealTag = '')
    {
        $this->dealTag = $dealTag;

        return $this;
    }
    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return RequestAvailableDiscountList
     */
    public function setPage($page = 1)
    {
        $this->page = $page;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return RequestAvailableDiscountList
     */
    public function setPageSize($pageSize = 10)
    {
        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return int
     */
    public function getHasTotalCount()
    {
        return $this->hasTotalCount;
    }

    /**
     * @param int $hasTotalCount
     * @return RequestAvailableDiscountList
     */
    public function setHasTotalCount($hasTotalCount = 1)
    {
        $this->hasTotalCount = $hasTotalCount;

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
     * @return RequestAvailableDiscountList
     */
    public function setType($type = 0)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getAnnualizedAmount()
    {
        return $this->annualizedAmount;
    }

    /**
     * @param int $annualizedAmount
     * @return RequestAvailableDiscountList
     */
    public function setAnnualizedAmount($annualizedAmount = 0)
    {
        $this->annualizedAmount = $annualizedAmount;

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
     * @return RequestAvailableDiscountList
     */
    public function setConsumeType($consumeType = 1)
    {
        $this->consumeType = $consumeType;

        return $this;
    }

}