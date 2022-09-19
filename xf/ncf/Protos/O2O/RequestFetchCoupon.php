<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:向合作方获取
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestFetchCoupon extends ProtoBufferBase
{
    /**
     * 网信给合作方分配的唯一key
     *
     * @var string
     * @required
     */
    private $clientId;

    /**
     * 优惠券开始时间
     *
     * @var int
     * @required
     */
    private $beginTime;

    /**
     * 优惠券结束时间
     *
     * @var int
     * @required
     */
    private $endTime;

    /**
     * 优惠券金额
     *
     * @var string
     * @optional
     */
    private $price = '';

    /**
     * 合作方商品编号
     *
     * @var string
     * @optional
     */
    private $productId = '';

    /**
     * 合作方其他信息
     *
     * @var string
     * @optional
     */
    private $extra = '';

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return RequestFetchCoupon
     */
    public function setClientId($clientId)
    {
        \Assert\Assertion::string($clientId);

        $this->clientId = $clientId;

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
     * @return RequestFetchCoupon
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
     * @return RequestFetchCoupon
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
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return RequestFetchCoupon
     */
    public function setPrice($price = '')
    {
        $this->price = $price;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     * @return RequestFetchCoupon
     */
    public function setProductId($productId = '')
    {
        $this->productId = $productId;

        return $this;
    }
    /**
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     * @return RequestFetchCoupon
     */
    public function setExtra($extra = '')
    {
        $this->extra = $extra;

        return $this;
    }

}