<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 用户重置密码proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author longbo
 */
class RequestResetPassword extends ProtoBufferBase
{
    /**
     * 分站ID
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * UserID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 新密码
     *
     * @var string
     * @required
     */
    private $password;

    /**
     * 确认密码
     *
     * @var string
     * @required
     */
    private $confirmPassword;

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestResetPassword
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

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
     * @return RequestResetPassword
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
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return RequestResetPassword
     */
    public function setPassword($password)
    {
        \Assert\Assertion::string($password);

        $this->password = $password;

        return $this;
    }
    /**
     * @return string
     */
    public function getConfirmPassword()
    {
        return $this->confirmPassword;
    }

    /**
     * @param string $confirmPassword
     * @return RequestResetPassword
     */
    public function setConfirmPassword($confirmPassword)
    {
        \Assert\Assertion::string($confirmPassword);

        $this->confirmPassword = $confirmPassword;

        return $this;
    }

}