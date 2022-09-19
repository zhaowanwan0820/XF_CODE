<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 时间戳回调
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestSignTsaCallback extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 合同编号
     *
     * @var string
     * @required
     */
    private $number;

    /**
     * 来源类型(0:P2P,1:通知贷,2:交易所,3:专享)
     *
     * @var int
     * @optional
     */
    private $sourceType = 0;

    /**
     * 0:deal,1:project
     *
     * @var int
     * @optional
     */
    private $type = 0;

    /**
     * 项目id
     *
     * @var int
     * @optional
     */
    private $projectId = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestSignTsaCallback
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
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return RequestSignTsaCallback
     */
    public function setNumber($number)
    {
        \Assert\Assertion::string($number);

        $this->number = $number;

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
     * @return RequestSignTsaCallback
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

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
     * @return RequestSignTsaCallback
     */
    public function setType($type = 0)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestSignTsaCallback
     */
    public function setProjectId($projectId = 0)
    {
        $this->projectId = $projectId;

        return $this;
    }

}