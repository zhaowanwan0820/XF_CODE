<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 搜索客户对象类型
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong@
 */
class ProtoSearchCustomer extends ProtoBufferBase
{
    /**
     * 客户ID
     *
     * @var string
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
     * 真实姓名
     *
     * @var string
     * @required
     */
    private $realName;

    /**
     * 手机号码
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return ProtoSearchCustomer
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
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return ProtoSearchCustomer
     */
    public function setUserName($userName)
    {
        \Assert\Assertion::string($userName);

        $this->userName = $userName;

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
     * @return ProtoSearchCustomer
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
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return ProtoSearchCustomer
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }

}