<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取商铺兑换记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class ResponseGetConfirmedCouponList extends ResponseBase
{
    /**
     * 优惠券列表
     *
     * @var array
     * @required
     */
    private $couponList;

    /**
     * @return array
     */
    public function getCouponList()
    {
        return $this->couponList;
    }

    /**
     * @param array $couponList
     * @return ResponseGetConfirmedCouponList
     */
    public function setCouponList(array $couponList)
    {
        $this->couponList = $couponList;

        return $this;
    }

}