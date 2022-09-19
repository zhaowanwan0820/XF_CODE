<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 发送生成合同记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestSendContract extends ProtoBufferBase
{
    /**
     * 借款用户ID
     *
     * @var int
     * @required
     */
    private $borrowUserId;

    /**
     * 出借人用户ID
     *
     * @var int
     * @required
     */
    private $lenderUserId;

    /**
     * 担保方用户ID
     *
     * @var int
     * @required
     */
    private $guaranteeAgencyId;

    /**
     * 资产管理方用户ID
     *
     * @var int
     * @required
     */
    private $advisoryAgencyId;

    /**
     * 委托机构ID
     *
     * @var int
     * @required
     */
    private $entrustAgencyId;

    /**
     * 渠道机构ID
     *
     * @var int
     * @required
     */
    private $canalAgencyId;

    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 标的ID
     *
     * @var int
     * @optional
     */
    private $projectId = 0;

    /**
     * 标的投资ID
     *
     * @var int
     * @required
     */
    private $dealLoadId;

    /**
     * 是否专享标
     *
     * @var boolean
     * @optional
     */
    private $isZX = false;

    /**
     * 标的类型(0:P2P;1:DT)
     *
     * @var int
     * @required
     */
    private $dealType;

    /**
     * 是否满标
     *
     * @var boolean
     * @optional
     */
    private $isFull = false;

    /**
     * 来源类型(0:P2P,1:通知贷,2:交易所,3:专享)
     *
     * @var int
     * @optional
     */
    private $sourceType = 0;

    /**
     * 创建时间
     *
     * @var string
     * @optional
     */
    private $createTime = NULL;

    /**
     * 临时合同id
     *
     * @var int
     * @optional
     */
    private $tmpContractId = NULL;

    /**
     * 代签(二进制位标识),默认:0无需代签
     *
     * @var int
     * @optional
     */
    private $autoSign = 0;

    /**
     * @return int
     */
    public function getBorrowUserId()
    {
        return $this->borrowUserId;
    }

    /**
     * @param int $borrowUserId
     * @return RequestSendContract
     */
    public function setBorrowUserId($borrowUserId)
    {
        \Assert\Assertion::integer($borrowUserId);

        $this->borrowUserId = $borrowUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getLenderUserId()
    {
        return $this->lenderUserId;
    }

    /**
     * @param int $lenderUserId
     * @return RequestSendContract
     */
    public function setLenderUserId($lenderUserId)
    {
        \Assert\Assertion::integer($lenderUserId);

        $this->lenderUserId = $lenderUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getGuaranteeAgencyId()
    {
        return $this->guaranteeAgencyId;
    }

    /**
     * @param int $guaranteeAgencyId
     * @return RequestSendContract
     */
    public function setGuaranteeAgencyId($guaranteeAgencyId)
    {
        \Assert\Assertion::integer($guaranteeAgencyId);

        $this->guaranteeAgencyId = $guaranteeAgencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getAdvisoryAgencyId()
    {
        return $this->advisoryAgencyId;
    }

    /**
     * @param int $advisoryAgencyId
     * @return RequestSendContract
     */
    public function setAdvisoryAgencyId($advisoryAgencyId)
    {
        \Assert\Assertion::integer($advisoryAgencyId);

        $this->advisoryAgencyId = $advisoryAgencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getEntrustAgencyId()
    {
        return $this->entrustAgencyId;
    }

    /**
     * @param int $entrustAgencyId
     * @return RequestSendContract
     */
    public function setEntrustAgencyId($entrustAgencyId)
    {
        \Assert\Assertion::integer($entrustAgencyId);

        $this->entrustAgencyId = $entrustAgencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getCanalAgencyId()
    {
        return $this->canalAgencyId;
    }

    /**
     * @param int $canalAgencyId
     * @return RequestSendContract
     */
    public function setCanalAgencyId($canalAgencyId)
    {
        \Assert\Assertion::integer($canalAgencyId);

        $this->canalAgencyId = $canalAgencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestSendContract
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
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestSendContract
     */
    public function setProjectId($projectId = 0)
    {
        $this->projectId = $projectId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoadId()
    {
        return $this->dealLoadId;
    }

    /**
     * @param int $dealLoadId
     * @return RequestSendContract
     */
    public function setDealLoadId($dealLoadId)
    {
        \Assert\Assertion::integer($dealLoadId);

        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getIsZX()
    {
        return $this->isZX;
    }

    /**
     * @param boolean $isZX
     * @return RequestSendContract
     */
    public function setIsZX($isZX = false)
    {
        $this->isZX = $isZX;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return RequestSendContract
     */
    public function setDealType($dealType)
    {
        \Assert\Assertion::integer($dealType);

        $this->dealType = $dealType;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getIsFull()
    {
        return $this->isFull;
    }

    /**
     * @param boolean $isFull
     * @return RequestSendContract
     */
    public function setIsFull($isFull = false)
    {
        $this->isFull = $isFull;

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
     * @return RequestSendContract
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }
    /**
     * @return string
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param string $createTime
     * @return RequestSendContract
     */
    public function setCreateTime($createTime = NULL)
    {
        $this->createTime = $createTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getTmpContractId()
    {
        return $this->tmpContractId;
    }

    /**
     * @param int $tmpContractId
     * @return RequestSendContract
     */
    public function setTmpContractId($tmpContractId = NULL)
    {
        $this->tmpContractId = $tmpContractId;

        return $this;
    }
    /**
     * @return int
     */
    public function getAutoSign()
    {
        return $this->autoSign;
    }

    /**
     * @param int $autoSign
     * @return RequestSendContract
     */
    public function setAutoSign($autoSign = 0)
    {
        $this->autoSign = $autoSign;

        return $this;
    }

}