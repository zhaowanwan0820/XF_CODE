<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取指定券组列表的库存
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong <yanbingrong@ucfgroup.com>
 */
class RequestGetCouponGroupUnusedCount extends AbstractRequestBase
{
    /**
     * 券组ID
     *
     * @var array
     * @required
     */
    private $couponGroupIds;

    /**
     * @return array
     */
    public function getCouponGroupIds()
    {
        return $this->couponGroupIds;
    }

    /**
     * @param array $couponGroupIds
     * @return RequestGetCouponGroupUnusedCount
     */
    public function setCouponGroupIds(array $couponGroupIds)
    {
        $this->couponGroupIds = $couponGroupIds;

        return $this;
    }

}