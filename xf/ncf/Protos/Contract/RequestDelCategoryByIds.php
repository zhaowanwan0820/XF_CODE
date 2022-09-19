<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 批量或单独删除合同分类（逻辑删除）
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestDelCategoryByIds extends ProtoBufferBase
{
    /**
     * 分类ID数组
     *
     * @var array
     * @required
     */
    private $ids;

    /**
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @param array $ids
     * @return RequestDelCategoryByIds
     */
    public function setIds(array $ids)
    {
        $this->ids = $ids;

        return $this;
    }

}