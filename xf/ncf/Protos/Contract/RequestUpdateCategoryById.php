<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 更新分类
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestUpdateCategoryById extends ProtoBufferBase
{
    /**
     * 分类id
     *
     * @var int
     * @required
     */
    private $categoryId;

    /**
     * 分类名称
     *
     * @var string
     * @required
     */
    private $typeName;

    /**
     * 分类名称
     *
     * @var string
     * @required
     */
    private $typeTag;

    /**
     * 个人/公司借款
     *
     * @var int
     * @required
     */
    private $contractType;

    /**
     * 是否删除
     *
     * @var int
     * @optional
     */
    private $isDelete = 0;

    /**
     * 使用状态
     *
     * @var int
     * @optional
     */
    private $useStatus = 1;

    /**
     * 当前使用的合同版本号
     *
     * @var float
     * @optional
     */
    private $contractVersion = 1;

    /**
     * 标的类型 0:P2P,2:交易所,3:专享,5:小贷,100:黄金
     *
     * @var int
     * @required
     */
    private $sourceType;

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return RequestUpdateCategoryById
     */
    public function setCategoryId($categoryId)
    {
        \Assert\Assertion::integer($categoryId);

        $this->categoryId = $categoryId;

        return $this;
    }
    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     * @return RequestUpdateCategoryById
     */
    public function setTypeName($typeName)
    {
        \Assert\Assertion::string($typeName);

        $this->typeName = $typeName;

        return $this;
    }
    /**
     * @return string
     */
    public function getTypeTag()
    {
        return $this->typeTag;
    }

    /**
     * @param string $typeTag
     * @return RequestUpdateCategoryById
     */
    public function setTypeTag($typeTag)
    {
        \Assert\Assertion::string($typeTag);

        $this->typeTag = $typeTag;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * @param int $contractType
     * @return RequestUpdateCategoryById
     */
    public function setContractType($contractType)
    {
        \Assert\Assertion::integer($contractType);

        $this->contractType = $contractType;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * @param int $isDelete
     * @return RequestUpdateCategoryById
     */
    public function setIsDelete($isDelete = 0)
    {
        $this->isDelete = $isDelete;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseStatus()
    {
        return $this->useStatus;
    }

    /**
     * @param int $useStatus
     * @return RequestUpdateCategoryById
     */
    public function setUseStatus($useStatus = 1)
    {
        $this->useStatus = $useStatus;

        return $this;
    }
    /**
     * @return float
     */
    public function getContractVersion()
    {
        return $this->contractVersion;
    }

    /**
     * @param float $contractVersion
     * @return RequestUpdateCategoryById
     */
    public function setContractVersion($contractVersion = 1)
    {
        $this->contractVersion = $contractVersion;

        return $this;
    }
    /**
     * @return int
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param int $sourceType
     * @return RequestUpdateCategoryById
     */
    public function setSourceType($sourceType)
    {
        \Assert\Assertion::integer($sourceType);

        $this->sourceType = $sourceType;

        return $this;
    }

}