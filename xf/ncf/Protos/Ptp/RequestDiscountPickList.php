<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取可用投资劵列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestDiscountPickList extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 标ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 投资金额
     *
     * @var float
     * @optional
     */
    private $money = '0';

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $page = '1';

    /**
     * 分页个数
     *
     * @var int
     * @optional
     */
    private $count = '10';

    /**
     * 分站ID
     *
     * @var int
     * @optional
     */
    private $siteId = '1';

    /**
     * 劵类型
     *
     * @var int
     * @optional
     */
    private $type = '1';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestDiscountPickList
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
     * @return RequestDiscountPickList
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return float
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param float $money
     * @return RequestDiscountPickList
     */
    public function setMoney($money = '0')
    {
        $this->money = $money;

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
     * @return RequestDiscountPickList
     */
    public function setPage($page = '1')
    {
        $this->page = $page;

        return $this;
    }
    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return RequestDiscountPickList
     */
    public function setCount($count = '10')
    {
        $this->count = $count;

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
     * @return RequestDiscountPickList
     */
    public function setSiteId($siteId = '1')
    {
        $this->siteId = $siteId;

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
     * @return RequestDiscountPickList
     */
    public function setType($type = '1')
    {
        $this->type = $type;

        return $this;
    }

}