<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照分类id取得分类信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetCategoryById extends ProtoBufferBase
{
    /**
     * 分类ID
     *
     * @var int
     * @required
     */
    private $categoryId;

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return RequestGetCategoryById
     */
    public function setCategoryId($categoryId)
    {
        \Assert\Assertion::integer($categoryId);

        $this->categoryId = $categoryId;

        return $this;
    }

}