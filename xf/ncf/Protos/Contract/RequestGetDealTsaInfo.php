<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照标的id获取时间戳信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetDealTsaInfo extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

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
     * @return RequestGetDealTsaInfo
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
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param int $sourceType
     * @return RequestGetDealTsaInfo
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}