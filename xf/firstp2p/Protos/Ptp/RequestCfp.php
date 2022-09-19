<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 理财师客户相关
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestCfp extends ProtoBufferBase
{
    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $cfpId;

    /**
     * 客户ID
     *
     * @var int
     * @required
     */
    private $customerId;

    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestCfp
     */
    public function setCfpId($cfpId)
    {
        \Assert\Assertion::integer($cfpId);

        $this->cfpId = $cfpId;

        return $this;
    }
    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $customerId
     * @return RequestCfp
     */
    public function setCustomerId($customerId)
    {
        \Assert\Assertion::integer($customerId);

        $this->customerId = $customerId;

        return $this;
    }

}