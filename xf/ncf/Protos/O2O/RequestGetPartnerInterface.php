<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:获取合作方接口配置
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestGetPartnerInterface extends ProtoBufferBase
{
    /**
     * 合作方标识
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestGetPartnerInterface
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }

}