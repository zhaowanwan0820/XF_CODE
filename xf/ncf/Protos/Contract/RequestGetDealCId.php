<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取标的分类信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetDealCId extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

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
     * @return RequestGetDealCId
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetDealCId
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
     * @return RequestGetDealCId
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}