<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 用户Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ProtoUser extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户名
     *
     * @var string
     * @required
     */
    private $userName;

    /**
     * 基金类型
     *
     * @var int
     * @optional
     */
    private $type = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ProtoUser
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
     * @return ProtoUser
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return ProtoUser
     */
    public function setType($type = NULL)
    {
        $this->type = $type;

        return $this;
    }

}