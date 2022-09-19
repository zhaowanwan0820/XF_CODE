<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 保存对应标的的合同附件路径
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseSaveContractAttachment extends ProtoBufferBase
{
    /**
     * 标的的id
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 保存状态
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * 错误信息，没有为空
     *
     * @var string
     * @optional
     */
    private $errorMsg = NULL;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ResponseSaveContractAttachment
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     * @return ResponseSaveContractAttachment
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     * @return ResponseSaveContractAttachment
     */
    public function setErrorMsg($errorMsg = NULL)
    {
        $this->errorMsg = $errorMsg;

        return $this;
    }

}