<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 已投项目查看项目合同
 *
 * 由代码生成器生成, 不可人为修改
 * @author Steven
 */
class RequestProjectContract extends AbstractRequestBase
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
     * 项目ID
     *
     * @var int
     * @required
     */
    private $projectId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestProjectContract
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
     * @return RequestProjectContract
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
     * @return RequestProjectContract
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
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     * @return RequestProjectContract
     */
    public function setProjectId($projectId)
    {
        \Assert\Assertion::integer($projectId);

        $this->projectId = $projectId;

        return $this;
    }

}