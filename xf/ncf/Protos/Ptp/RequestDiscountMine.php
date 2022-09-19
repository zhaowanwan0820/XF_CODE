<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取用户投资劵列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestDiscountMine extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 状态
     *
     * @var int
     * @optional
     */
    private $status = '0';

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
     * @return RequestDiscountMine
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return RequestDiscountMine
     */
    public function setStatus($status = '0')
    {
        $this->status = $status;

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
     * @return RequestDiscountMine
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
     * @return RequestDiscountMine
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
     * @return RequestDiscountMine
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
     * @return RequestDiscountMine
     */
    public function setType($type = '1')
    {
        $this->type = $type;

        return $this;
    }

}