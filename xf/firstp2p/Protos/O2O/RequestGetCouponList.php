<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取券码列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class RequestGetCouponList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 券码ID
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
     * 来源
     *
     * @var int
     * @optional
     */
    private $source = '';

    /**
     * 状态
     *
     * @var int
     * @optional
     */
    private $status = '';

    /**
     * 导出
     *
     * @var string
     * @optional
     */
    private $export = '';

    /**
     * 券码第三方标识
     *
     * @var string
     * @optional
     */
    private $couponProvider = '';

    /**
     * 券组编号
     *
     * @var int
     * @optional
     */
    private $couponGroupId = '';

    /**
     * 领取人ID
     *
     * @var int
     * @optional
     */
    private $ownerUserId = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetCouponList
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
     * @return RequestGetCouponList
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
     * @return RequestGetCouponList
     */
    public function setCouponNumber($couponNumber = '')
    {
        $this->couponNumber = $couponNumber;

        return $this;
    }
    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     * @return RequestGetCouponList
     */
    public function setSource($source = '')
    {
        $this->source = $source;

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
     * @return RequestGetCouponList
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

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
     * @return RequestGetCouponList
     */
    public function setExport($export = '')
    {
        $this->export = $export;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestGetCouponList
     */
    public function setCouponProvider($couponProvider = '')
    {
        $this->couponProvider = $couponProvider;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param int $couponGroupId
     * @return RequestGetCouponList
     */
    public function setCouponGroupId($couponGroupId = '')
    {
        $this->couponGroupId = $couponGroupId;

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
     * @return RequestGetCouponList
     */
    public function setOwnerUserId($ownerUserId = '')
    {
        $this->ownerUserId = $ownerUserId;

        return $this;
    }

}