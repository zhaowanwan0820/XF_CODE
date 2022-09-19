<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取分类列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseCategorys extends ProtoBufferBase
{
    /**
     * 分类列表
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
     * @return ResponseCategorys
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}