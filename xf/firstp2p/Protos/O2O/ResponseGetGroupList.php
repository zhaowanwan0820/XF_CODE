<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:获取券组列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class ResponseGetGroupList extends ProtoBufferBase
{
    /**
     * 券组列表
     *
     * @var array
     * @required
     */
    private $groupList;

    /**
     * @return array
     */
    public function getGroupList()
    {
        return $this->groupList;
    }

    /**
     * @param array $groupList
     * @return ResponseGetGroupList
     */
    public function setGroupList(array $groupList)
    {
        $this->groupList = $groupList;

        return $this;
    }

}