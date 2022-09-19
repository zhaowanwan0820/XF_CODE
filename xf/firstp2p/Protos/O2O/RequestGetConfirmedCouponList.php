<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取商铺兑换记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestGetConfirmedCouponList extends AbstractRequestBase
{
    /**
     * 页码
     *
     * @var int
     * @required
     */
    private $page;

    /**
     * 每页数量
     *
     * @var int
     * @required
     */
    private $pageSize;

    /**
     * 商店用户id
     *
     * @var int
     * @required
     */
    private $storeId;

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return RequestGetConfirmedCouponList
     */
    public function setPage($page)
    {
        \Assert\Assertion::integer($page);

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
     * @return RequestGetConfirmedCouponList
     */
    public function setPageSize($pageSize)
    {
        \Assert\Assertion::integer($pageSize);

        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return RequestGetConfirmedCouponList
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::integer($storeId);

        $this->storeId = $storeId;

        return $this;
    }

}