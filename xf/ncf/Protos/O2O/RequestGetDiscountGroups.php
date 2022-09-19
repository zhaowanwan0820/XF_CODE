<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:获取投资券组列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestGetDiscountGroups extends ProtoBufferBase
{
    /**
     * 券组编号
     *
     * @var array
     * @required
     */
    private $groupIds;

    /**
     * @return array
     */
    public function getGroupIds()
    {
        return $this->groupIds;
    }

    /**
     * @param array $groupIds
     * @return RequestGetDiscountGroups
     */
    public function setGroupIds(array $groupIds)
    {
        $this->groupIds = $groupIds;

        return $this;
    }

}