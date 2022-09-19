<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 保存合同模板标识
 *
 * 由代码生成器生成, 不可人为修改
 * @author fanjingwen
 */
class RequestSaveContractTplIdentifier extends ProtoBufferBase
{
    /**
     * 模板标识 id
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * 模板标识 名称
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 模板标识 标题
     *
     * @var string
     * @required
     */
    private $title;

    /**
     * 签署方
     *
     * @var int
     * @required
     */
    private $signRole;

    /**
     * 平台用户id(用于盖章)
     *
     * @var int
     * @optional
     */
    private $platformUserId = 0;

    /**
     * 签署方
     *
     * @var int
     * @required
     */
    private $contractSendNode;

    /**
     * 是否投资时（用户）可见，0：否；1：是
     *
     * @var int
     * @required
     */
    private $isSeenWhenBid;

    /**
     * 服务类型：1：标的；2：项目
     *
     * @var int
     * @required
     */
    private $serviceType;

    /**
     * 合同类型，如：借款合同、委托协议
     *
     * @var int
     * @required
     */
    private $contractType;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestSaveContractTplIdentifier
     */
    public function setId($id = 0)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RequestSaveContractTplIdentifier
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return RequestSaveContractTplIdentifier
     */
    public function setTitle($title)
    {
        \Assert\Assertion::string($title);

        $this->title = $title;

        return $this;
    }
    /**
     * @return int
     */
    public function getSignRole()
    {
        return $this->signRole;
    }

    /**
     * @param int $signRole
     * @return RequestSaveContractTplIdentifier
     */
    public function setSignRole($signRole)
    {
        \Assert\Assertion::integer($signRole);

        $this->signRole = $signRole;

        return $this;
    }
    /**
     * @return int
     */
    public function getPlatformUserId()
    {
        return $this->platformUserId;
    }

    /**
     * @param int $platformUserId
     * @return RequestSaveContractTplIdentifier
     */
    public function setPlatformUserId($platformUserId = 0)
    {
        $this->platformUserId = $platformUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractSendNode()
    {
        return $this->contractSendNode;
    }

    /**
     * @param int $contractSendNode
     * @return RequestSaveContractTplIdentifier
     */
    public function setContractSendNode($contractSendNode)
    {
        \Assert\Assertion::integer($contractSendNode);

        $this->contractSendNode = $contractSendNode;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsSeenWhenBid()
    {
        return $this->isSeenWhenBid;
    }

    /**
     * @param int $isSeenWhenBid
     * @return RequestSaveContractTplIdentifier
     */
    public function setIsSeenWhenBid($isSeenWhenBid)
    {
        \Assert\Assertion::integer($isSeenWhenBid);

        $this->isSeenWhenBid = $isSeenWhenBid;

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
     * @return RequestSaveContractTplIdentifier
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
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * @param int $contractType
     * @return RequestSaveContractTplIdentifier
     */
    public function setContractType($contractType)
    {
        \Assert\Assertion::integer($contractType);

        $this->contractType = $contractType;

        return $this;
    }

}