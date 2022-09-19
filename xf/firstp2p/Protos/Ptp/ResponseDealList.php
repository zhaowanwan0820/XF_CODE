<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 标列表接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ResponseDealList extends ProtoBufferBase
{
    /**
     * 标项目列表
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
     * @return ResponseDealList
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}