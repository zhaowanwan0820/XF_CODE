<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照投资记录获取合同列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetRoleContractByDealId extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 类型（0:投资人,1借款人,2:担保,3:资产管理)
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $agencyId;

    /**
     * 标的ID
     *
     * @var int
     * @optional
     */
    private $pageNo = NULL;

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
     * @return RequestGetRoleContractByDealId
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetRoleContractByDealId
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

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
     * @return RequestGetRoleContractByDealId
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * @param int $agencyId
     * @return RequestGetRoleContractByDealId
     */
    public function setAgencyId($agencyId)
    {
        \Assert\Assertion::integer($agencyId);

        $this->agencyId = $agencyId;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * @param int $pageNo
     * @return RequestGetRoleContractByDealId
     */
    public function setPageNo($pageNo = NULL)
    {
        $this->pageNo = $pageNo;

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
     * @return RequestGetRoleContractByDealId
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}