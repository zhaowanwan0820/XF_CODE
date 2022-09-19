<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提交视频见证
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestVedioVerify extends AbstractRequestBase
{
    /**
     * 认证视频ID
     *
     * @var string
     * @required
     */
    private $verificationId;

    /**
     * 视频认证坐席ID
     *
     * @var string
     * @required
     */
    private $operatorId;

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getVerificationId()
    {
        return $this->verificationId;
    }

    /**
     * @param string $verificationId
     * @return RequestVedioVerify
     */
    public function setVerificationId($verificationId)
    {
        \Assert\Assertion::string($verificationId);

        $this->verificationId = $verificationId;

        return $this;
    }
    /**
     * @return string
     */
    public function getOperatorId()
    {
        return $this->operatorId;
    }

    /**
     * @param string $operatorId
     * @return RequestVedioVerify
     */
    public function setOperatorId($operatorId)
    {
        \Assert\Assertion::string($operatorId);

        $this->operatorId = $operatorId;

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
     * @return RequestVedioVerify
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}