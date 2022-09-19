<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 注册网信融牛关联用户
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestRegisterUser extends AbstractRequestBase
{
    /**
     * 网信用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 用户手机号
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * 用户姓名
     *
     * @var string
     * @required
     */
    private $realName;

    /**
     * 身份证号
     *
     * @var string
     * @required
     */
    private $identifyNo;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestRegisterUser
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
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return RequestRegisterUser
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * @param string $realName
     * @return RequestRegisterUser
     */
    public function setRealName($realName)
    {
        \Assert\Assertion::string($realName);

        $this->realName = $realName;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdentifyNo()
    {
        return $this->identifyNo;
    }

    /**
     * @param string $identifyNo
     * @return RequestRegisterUser
     */
    public function setIdentifyNo($identifyNo)
    {
        \Assert\Assertion::string($identifyNo);

        $this->identifyNo = $identifyNo;

        return $this;
    }

}
