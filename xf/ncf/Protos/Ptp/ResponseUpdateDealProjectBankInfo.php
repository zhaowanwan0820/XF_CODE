<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 更改项目信息中的银行卡账号
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseUpdateDealProjectBankInfo extends ProtoBufferBase
{
    /**
     * 更新结果
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * 会受影响的标的数量
     *
     * @var int
     * @required
     */
    private $affectedDealCount;

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     * @return ResponseUpdateDealProjectBankInfo
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getAffectedDealCount()
    {
        return $this->affectedDealCount;
    }

    /**
     * @param int $affectedDealCount
     * @return ResponseUpdateDealProjectBankInfo
     */
    public function setAffectedDealCount($affectedDealCount)
    {
        \Assert\Assertion::integer($affectedDealCount);

        $this->affectedDealCount = $affectedDealCount;

        return $this;
    }

}