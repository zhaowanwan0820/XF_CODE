<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 邀请码使用投资返利记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei
 */
class ProtoCouponUsedLog extends ProtoBufferBase
{
    /**
     * 用户名
     *
     * @var string
     * @required
     */
    private $userName;

    /**
     * 用户真实姓名
     *
     * @var string
     * @required
     */
    private $realName;

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return ProtoCouponUsedLog
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
     * @return ProtoCouponUsedLog
     */
    public function setRealName($realName)
    {
        \Assert\Assertion::string($realName);

        $this->realName = $realName;

        return $this;
    }

}