<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获得region地区列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestRegionList extends ProtoBufferBase
{
    /**
     * 最大regionLevel
     *
     * @var int
     * @optional
     */
    private $regionLevel = 3;

    /**
     * @return int
     */
    public function getRegionLevel()
    {
        return $this->regionLevel;
    }

    /**
     * @param int $regionLevel
     * @return RequestRegionList
     */
    public function setRegionLevel($regionLevel = 3)
    {
        $this->regionLevel = $regionLevel;

        return $this;
    }

}