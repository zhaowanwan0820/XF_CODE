<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 更新标的合同分类ID
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestUpdateDealCId extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 分类ID
     *
     * @var int
     * @required
     */
    private $categoryId;

    /**
     * 合同版本号
     *
     * @var float
     * @optional
     */
    private $contractVersion = 1;

    /**
     * 类型(0:p2p;1:多投)
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 来源类型(0:P2P,1:通知贷,2:交易所,3:专享)
     *
     * @var int
     * @optional
     */
    private $sourceType = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestUpdateDealCId
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return RequestUpdateDealCId
     */
    public function setCategoryId($categoryId)
    {
        \Assert\Assertion::integer($categoryId);

        $this->categoryId = $categoryId;

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
     * @return RequestUpdateDealCId
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
     * @return RequestUpdateDealCId
     */
    public function setType($type = 0)
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
     * @return RequestUpdateDealCId
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}