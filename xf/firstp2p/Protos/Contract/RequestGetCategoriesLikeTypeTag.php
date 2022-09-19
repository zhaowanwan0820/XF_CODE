<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按合同分类标记type_tag取得分类信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class RequestGetCategoriesLikeTypeTag extends ProtoBufferBase
{
    /**
     * 合同分类标记
     *
     * @var string
     * @required
     */
    private $typeTag;

    /**
     * @return string
     */
    public function getTypeTag()
    {
        return $this->typeTag;
    }

    /**
     * @param string $typeTag
     * @return RequestGetCategoriesLikeTypeTag
     */
    public function setTypeTag($typeTag)
    {
        \Assert\Assertion::string($typeTag);

        $this->typeTag = $typeTag;

        return $this;
    }

}