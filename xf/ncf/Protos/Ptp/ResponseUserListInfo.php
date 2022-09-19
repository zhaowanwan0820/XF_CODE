<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 用户列表proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author longbo
 */
class ResponseUserListInfo extends ProtoBufferBase
{
    /**
     * User列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseUserListInfo
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}