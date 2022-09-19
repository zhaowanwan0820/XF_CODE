<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 添加分类
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestAddCategory extends ProtoBufferBase
{
    /**
     * 分类名称
     *
     * @var string
     * @required
     */
    private $typeName;

    /**
     * 分类标记
     *
     * @var string
     * @required
     */
    private $typeTag;

    /**
     * 个人/公司借款
     *
     * @var int
     * @optional
     */
    private $contractType = 0;

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
     * 当前合同版本号
     *
     * @var float
     * @optional
     */
    private $contractVersion = 1;

    /**
     * 调用系统类型 1:P2P,2:多投,3:黄金
     *
     * @var int
     * @optional
     */
    private $type = 1;

    /**
     * 标的类型 0:P2P,2:交易所,3:专享,5:小贷,100:黄金
     *
     * @var int
     * @required
     */
    private $sourceType;

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     * @return RequestAddCategory
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
     * @return RequestAddCategory
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
     * @return RequestAddCategory
     */
    public function setContractType($contractType = 0)
    {
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
     * @return RequestAddCategory
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
     * @return RequestAddCategory
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
     * @return RequestAddCategory
     */
    public function setContractVersion($contractVersion = 1)
    {
        $this->contractVersion = $contractVersion;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestAddCategory
     */
    public function setType($type = 1)
    {
        $this->type = $type;

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
     * @return RequestAddCategory
     */
    public function setSourceType($sourceType)
    {
        \Assert\Assertion::integer($sourceType);

        $this->sourceType = $sourceType;

        return $this;
    }

}