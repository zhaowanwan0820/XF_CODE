<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取邀请码信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class ResponseGetInvitationCode extends ResponseBase
{
    /**
     * 邀请码开关
     *
     * @var int
     * @required
     */
    private $switch;

    /**
     * 邀请码
     *
     * @var string
     * @required
     */
    private $invitationCode;

    /**
     * 邀请码详细信息
     *
     * @var array
     * @required
     */
    private $detailedInformation;

    /**
     * @return int
     */
    public function getSwitch()
    {
        return $this->switch;
    }

    /**
     * @param int $switch
     * @return ResponseGetInvitationCode
     */
    public function setSwitch($switch)
    {
        \Assert\Assertion::integer($switch);

        $this->switch = $switch;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvitationCode()
    {
        return $this->invitationCode;
    }

    /**
     * @param string $invitationCode
     * @return ResponseGetInvitationCode
     */
    public function setInvitationCode($invitationCode)
    {
        \Assert\Assertion::string($invitationCode);

        $this->invitationCode = $invitationCode;

        return $this;
    }
    /**
     * @return array
     */
    public function getDetailedInformation()
    {
        return $this->detailedInformation;
    }

    /**
     * @param array $detailedInformation
     * @return ResponseGetInvitationCode
     */
    public function setDetailedInformation(array $detailedInformation)
    {
        $this->detailedInformation = $detailedInformation;

        return $this;
    }

}