<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 授予用户勋章接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author dengyi
 */
class RequestGrantUserMedal extends ProtoBufferBase
{
    /**
     * userId
     *
     * @var integer
     * @required
     */
    private $userId;

    /**
     * medal Id
     *
     * @var integer
     * @required
     */
    private $medalId;

    /**
     * whether push message to user
     *
     * @var bool
     * @optional
     */
    private $isPush = false;

    /**
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param integer $userId
     * @return RequestGrantUserMedal
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return integer
     */
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param integer $medalId
     * @return RequestGrantUserMedal
     */
    public function setMedalId($medalId)
    {
        \Assert\Assertion::integer($medalId);

        $this->medalId = $medalId;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsPush()
    {
        return $this->isPush;
    }

    /**
     * @param bool $isPush
     * @return RequestGrantUserMedal
     */
    public function setIsPush($isPush = false)
    {
        $this->isPush = $isPush;

        return $this;
    }

}