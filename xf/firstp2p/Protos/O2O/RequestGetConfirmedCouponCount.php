<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取商铺兑换记录总数
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestGetConfirmedCouponCount extends AbstractRequestBase
{
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
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return RequestGetConfirmedCouponCount
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
     * @return RequestGetConfirmedCouponCount
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
     * @return RequestGetConfirmedCouponCount
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
     * @return RequestGetConfirmedCouponCount
     */
    public function setCouponNumber($couponNumber)
    {
        \Assert\Assertion::string($couponNumber);

        $this->couponNumber = $couponNumber;

        return $this;
    }

}