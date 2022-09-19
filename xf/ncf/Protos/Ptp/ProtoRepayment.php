<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * DealId
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoRepayment extends ProtoBufferBase
{
    /**
     * dealId
     *
     * @var int
     * @optional
     */
    private $dealId = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ProtoRepayment
     */
    public function setDealId($dealId = 0)
    {
        $this->dealId = $dealId;

        return $this;
    }

}