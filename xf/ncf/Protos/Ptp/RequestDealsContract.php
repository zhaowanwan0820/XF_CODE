<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 已投项目查看合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class RequestDealsContract extends AbstractRequestBase
{
    /**
     * 合同id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户名称
     *
     * @var string
     * @required
     */
    private $userName;

    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestDealsContract
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

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
     * @return RequestDealsContract
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return RequestDealsContract
     */
    public function setUserName($userName)
    {
        \Assert\Assertion::string($userName);

        $this->userName = $userName;

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
     * @return RequestDealsContract
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }

}