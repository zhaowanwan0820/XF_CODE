<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 根据合同id 标id 获取合同模板信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class RequestGetContractInfoByContractId extends ProtoBufferBase
{
    /**
     * 服务 id 取决于 serviceType
     *
     * @var int
     * @required
     */
    private $serviceId;

    /**
     * 服务类型：1：普通标的；2：项目；
     *
     * @var int
     * @required
     */
    private $serviceType;

    /**
     * 合同 id
     *
     * @var int
     * @required
     */
    private $contractId;

    /**
     * 来源类型(0:P2P,1:通知贷,2:交易所,4:专享)
     *
     * @var int
     * @required
     */
    private $sourceType;

    /**
     * 其他辅助参数 用json传输
     *
     * @var string
     * @optional
     */
    private $other = '';

    /**
     * @return int
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param int $serviceId
     * @return RequestGetContractInfoByContractId
     */
    public function setServiceId($serviceId)
    {
        \Assert\Assertion::integer($serviceId);

        $this->serviceId = $serviceId;

        return $this;
    }
    /**
     * @return int
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * @param int $serviceType
     * @return RequestGetContractInfoByContractId
     */
    public function setServiceType($serviceType)
    {
        \Assert\Assertion::integer($serviceType);

        $this->serviceType = $serviceType;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * @param int $contractId
     * @return RequestGetContractInfoByContractId
     */
    public function setContractId($contractId)
    {
        \Assert\Assertion::integer($contractId);

        $this->contractId = $contractId;

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
     * @return RequestGetContractInfoByContractId
     */
    public function setSourceType($sourceType)
    {
        \Assert\Assertion::integer($sourceType);

        $this->sourceType = $sourceType;

        return $this;
    }
    /**
     * @return string
     */
    public function getOther()
    {
        return $this->other;
    }

    /**
     * @param string $other
     * @return RequestGetContractInfoByContractId
     */
    public function setOther($other = '')
    {
        $this->other = $other;

        return $this;
    }

}