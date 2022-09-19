<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取券码明细列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class RequestGetCouponListDetail extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 券码id
     *
     * @var int
     * @optional
     */
    private $couponId = 0;

    /**
     * 券码编号
     *
     * @var string
     * @optional
     */
    private $couponNumber = '';

    /**
     * 开始时间
     *
     * @var string
     * @optional
     */
    private $startTime = '';

    /**
     * 结束时间
     *
     * @var string
     * @optional
     */
    private $endTime = '';

    /**
     * 导出
     *
     * @var string
     * @optional
     */
    private $export = '';

    /**
     * 领取人ID
     *
     * @var int
     * @optional
     */
    private $ownerUserId = '';

    /**
     * 零售店ID
     *
     * @var int
     * @optional
     */
    private $storeUserId = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetCouponListDetail
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * @param int $couponId
     * @return RequestGetCouponListDetail
     */
    public function setCouponId($couponId = 0)
    {
        $this->couponId = $couponId;

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
     * @return RequestGetCouponListDetail
     */
    public function setCouponNumber($couponNumber = '')
    {
        $this->couponNumber = $couponNumber;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param string $startTime
     * @return RequestGetCouponListDetail
     */
    public function setStartTime($startTime = '')
    {
        $this->startTime = $startTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param string $endTime
     * @return RequestGetCouponListDetail
     */
    public function setEndTime($endTime = '')
    {
        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getExport()
    {
        return $this->export;
    }

    /**
     * @param string $export
     * @return RequestGetCouponListDetail
     */
    public function setExport($export = '')
    {
        $this->export = $export;

        return $this;
    }
    /**
     * @return int
     */
    public function getOwnerUserId()
    {
        return $this->ownerUserId;
    }

    /**
     * @param int $ownerUserId
     * @return RequestGetCouponListDetail
     */
    public function setOwnerUserId($ownerUserId = '')
    {
        $this->ownerUserId = $ownerUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStoreUserId()
    {
        return $this->storeUserId;
    }

    /**
     * @param int $storeUserId
     * @return RequestGetCouponListDetail
     */
    public function setStoreUserId($storeUserId = '')
    {
        $this->storeUserId = $storeUserId;

        return $this;
    }

}