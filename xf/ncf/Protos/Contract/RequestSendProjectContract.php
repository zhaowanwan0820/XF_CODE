<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 发送生成项目合同记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestSendProjectContract extends ProtoBufferBase
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
     * 委托机构ID
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
    private $projectId;

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
     * 创建时间
     *
     * @var string
     * @optional
     */
    private $createTime = NULL;

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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
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
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestSendProjectContract
     */
    public function setProjectId($projectId)
    {
        \Assert\Assertion::integer($projectId);

        $this->projectId = $projectId;

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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
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
     * @return RequestSendProjectContract
     */
    public function setCreateTime($createTime = NULL)
    {
        $this->createTime = $createTime;

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
     * @return RequestSendProjectContract
     */
    public function setAutoSign($autoSign = 0)
    {
        $this->autoSign = $autoSign;

        return $this;
    }

}