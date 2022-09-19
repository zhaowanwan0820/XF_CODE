<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 获取分享链接地址
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestDiscountHexIds extends ProtoBufferBase
{
    /**
     * 投资劵Ids
     *
     * @var array
     * @required
     */
    private $discountIds;

    /**
     * @return array
     */
    public function getDiscountIds()
    {
        return $this->discountIds;
    }

    /**
     * @param array $discountIds
     * @return RequestDiscountHexIds
     */
    public function setDiscountIds(array $discountIds)
    {
        $this->discountIds = $discountIds;

        return $this;
    }

}