<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取商铺兑换记录列表web端用
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestGetConfirmedCouponListForWeb extends AbstractRequestBase
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
     * 开始时间
     *
     * @var int
     * @required
     */
    private $beginTime;

    /**
     * 结束时间
     *
     * @var int
     * @required
     */
    private $endTime;

    /**
     * 查询的券码
     *
     * @var string
     * @required
     */
    private $couponNumber;

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return RequestGetConfirmedCouponListForWeb
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
     * @return RequestGetConfirmedCouponListForWeb
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
     * @return RequestGetConfirmedCouponListForWeb
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::integer($storeId);

        $this->storeId = $storeId;

        return $this;
    }
    /**
     * @return int
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param int $beginTime
     * @return RequestGetConfirmedCouponListForWeb
     */
    public function setBeginTime($beginTime)
    {
        \Assert\Assertion::integer($beginTime);

        $this->beginTime = $beginTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return RequestGetConfirmedCouponListForWeb
     */
    public function setEndTime($endTime)
    {
        \Assert\Assertion::integer($endTime);

        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponNumber()
    {
        return $this->couponNumber;
    }

    /**
     * @param string $couponNumber
     * @return RequestGetConfirmedCouponListForWeb
     */
    public function setCouponNumber($couponNumber)
    {
        \Assert\Assertion::string($couponNumber);

        $this->couponNumber = $couponNumber;

        return $this;
    }

}