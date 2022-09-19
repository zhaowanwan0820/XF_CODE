<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照合同分类标记type_tag取得模板列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseGetCategoriesLikeTypeTag extends ProtoBufferBase
{
    /**
     * 模板列表
     *
     * @var array
     * @optional
     */
    private $list = NULL;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetCategoriesLikeTypeTag
     */
    public function setList(array $list = NULL)
    {
        $this->list = $list;

        return $this;
    }

}