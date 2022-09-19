<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 用户名密码登录，放回用户信息及token
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangyiming
 */
class ResponseLoginNew extends ProtoBufferBase
{
    /**
     * 用户ID
     *
     * @var int
     * @optional
     */
    private $userId = 0;

    /**
     * 登录token
     *
     * @var string
     * @optional
     */
    private $token = NULL;

    /**
     * 用户信息
     *
     * @var array
     * @optional
     */
    private $userInfo = NULL;

    /**
     * 结果码
     *
     * @var int
     * @required
     */
    private $resCode;

    /**
     * 错误码，默认为0代表成功
     *
     * @var int
     * @optional
     */
    private $errCode = 0;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $errMsg = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ResponseLoginNew
     */
    public function setUserId($userId = 0)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return ResponseLoginNew
     */
    public function setToken($token = NULL)
    {
        $this->token = $token;

        return $this;
    }
    /**
     * @return array
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param array $userInfo
     * @return ResponseLoginNew
     */
    public function setUserInfo(array $userInfo = NULL)
    {
        $this->userInfo = $userInfo;

        return $this;
    }
    /**
     * @return int
     */
    public function getResCode()
    {
        return $this->resCode;
    }

    /**
     * @param int $resCode
     * @return ResponseLoginNew
     */
    public function setResCode($resCode)
    {
        \Assert\Assertion::integer($resCode);

        $this->resCode = $resCode;

        return $this;
    }
    /**
     * @return int
     */
    public function getErrCode()
    {
        return $this->errCode;
    }

    /**
     * @param int $errCode
     * @return ResponseLoginNew
     */
    public function setErrCode($errCode = 0)
    {
        $this->errCode = $errCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }

    /**
     * @param string $errMsg
     * @return ResponseLoginNew
     */
    public function setErrMsg($errMsg = NULL)
    {
        $this->errMsg = $errMsg;

        return $this;
    }

}
