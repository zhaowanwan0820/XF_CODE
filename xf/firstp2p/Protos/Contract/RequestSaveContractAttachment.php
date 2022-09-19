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
class RequestSaveContractAttachment extends ProtoBufferBase
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
     * @return RequestSaveContractAttachment
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
     * @return RequestSaveContractAttachment
     */
    public function setJsonData($jsonData)
    {
        \Assert\Assertion::string($jsonData);

        $this->jsonData = $jsonData;

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
     * @return RequestSaveContractAttachment
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}