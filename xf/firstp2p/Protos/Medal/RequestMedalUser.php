<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户的勋章墙
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi <dengyi@ucfgroup.com>
 */
class RequestMedalUser extends AbstractRequestBase
{
    /**
     * 用户的ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 勋章的ID
     *
     * @var int
     * @optional
     */
    private $medalId = 0;

    /**
     * 用户的Tag
     *
     * @var array
     * @optional
     */
    private $userTag = NULL;

    /**
     * 用户邀请人的Tag
     *
     * @var array
     * @optional
     */
    private $inviterTag = NULL;

    /**
     * 用户的注册时间
     *
     * @var int
     * @optional
     */
    private $userRegisterTime = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestMedalUser
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
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param int $medalId
     * @return RequestMedalUser
     */
    public function setMedalId($medalId = 0)
    {
        $this->medalId = $medalId;

        return $this;
    }
    /**
     * @return array
     */
    public function getUserTag()
    {
        return $this->userTag;
    }

    /**
     * @param array $userTag
     * @return RequestMedalUser
     */
    public function setUserTag(array $userTag = NULL)
    {
        $this->userTag = $userTag;

        return $this;
    }
    /**
     * @return array
     */
    public function getInviterTag()
    {
        return $this->inviterTag;
    }

    /**
     * @param array $inviterTag
     * @return RequestMedalUser
     */
    public function setInviterTag(array $inviterTag = NULL)
    {
        $this->inviterTag = $inviterTag;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserRegisterTime()
    {
        return $this->userRegisterTime;
    }

    /**
     * @param int $userRegisterTime
     * @return RequestMedalUser
     */
    public function setUserRegisterTime($userRegisterTime = 0)
    {
        $this->userRegisterTime = $userRegisterTime;

        return $this;
    }

}