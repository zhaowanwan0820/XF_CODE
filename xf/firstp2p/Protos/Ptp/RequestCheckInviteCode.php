<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 用户提交的邀请码信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangge
 */
class RequestCheckInviteCode extends AbstractRequestBase
{
    /**
     * 邀请码
     *
     * @var string
     * @required
     */
    private $inviteCode;

    /**
     * @return string
     */
    public function getInviteCode()
    {
        return $this->inviteCode;
    }

    /**
     * @param string $inviteCode
     * @return RequestCheckInviteCode
     */
    public function setInviteCode($inviteCode)
    {
        \Assert\Assertion::string($inviteCode);

        $this->inviteCode = $inviteCode;

        return $this;
    }

}