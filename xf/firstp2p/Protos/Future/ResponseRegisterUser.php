<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 关联网信与融牛用户
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseRegisterUser extends ResponseBase
{
    /**
     * 注册结果
     *
     * @var int
     * @optional
     */
    private $flag = 1;

    /**
     * 错误信息
     *
     * @var string
     * @required
     */
    private $message;

    /**
     * 网信用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 融牛用户ID
     *
     * @var string
     * @required
     */
    private $rnUserId;

    /**
     * @return int
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @param int $flag
     * @return ResponseRegisterUser
     */
    public function setFlag($flag = 1)
    {
        $this->flag = $flag;

        return $this;
    }
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return ResponseRegisterUser
     */
    public function setMessage($message)
    {
        \Assert\Assertion::string($message);

        $this->message = $message;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return ResponseRegisterUser
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getRnUserId()
    {
        return $this->rnUserId;
    }

    /**
     * @param string $rnUserId
     * @return ResponseRegisterUser
     */
    public function setRnUserId($rnUserId)
    {
        \Assert\Assertion::string($rnUserId);

        $this->rnUserId = $rnUserId;

        return $this;
    }

}