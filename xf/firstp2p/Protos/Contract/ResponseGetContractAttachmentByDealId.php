<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据标id，发送合同附件信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class ResponseGetContractAttachmentByDealId extends ProtoBufferBase
{
    /**
     * 标的的id
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 合同附件地址的json格式信息
     *
     * @var string
     * @required
     */
    private $jsonData;

    /**
     * 获取状态(true:存在)
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ResponseGetContractAttachmentByDealId
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return string
     */
    public function getJsonData()
    {
        return $this->jsonData;
    }

    /**
     * @param string $jsonData
     * @return ResponseGetContractAttachmentByDealId
     */
    public function setJsonData($jsonData)
    {
        \Assert\Assertion::string($jsonData);

        $this->jsonData = $jsonData;

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
     * @return ResponseGetContractAttachmentByDealId
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }

}