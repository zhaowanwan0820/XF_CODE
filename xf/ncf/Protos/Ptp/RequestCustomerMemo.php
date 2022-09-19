<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 客户备注
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class RequestCustomerMemo extends AbstractRequestBase
{
    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 客户ID
     *
     * @var int
     * @required
     */
    private $customerId;

    /**
     * 备注
     *
     * @var string
     * @required
     */
    private $memo;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestCustomerMemo
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
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $customerId
     * @return RequestCustomerMemo
     */
    public function setCustomerId($customerId)
    {
        \Assert\Assertion::integer($customerId);

        $this->customerId = $customerId;

        return $this;
    }
    /**
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @param string $memo
     * @return RequestCustomerMemo
     */
    public function setMemo($memo)
    {
        \Assert\Assertion::string($memo);

        $this->memo = $memo;

        return $this;
    }

}