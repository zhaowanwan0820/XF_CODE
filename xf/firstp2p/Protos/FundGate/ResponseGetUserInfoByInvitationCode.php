<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取邀请码对应的用户信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class ResponseGetUserInfoByInvitationCode extends ResponseBase
{
    /**
     * 邀请码开关
     *
     * @var int
     * @required
     */
    private $switch;

    /**
     * 请求是否成功，0为成功，1位失败
     *
     * @var int
     * @required
     */
    private $resCode;

    /**
     * 错误消息
     *
     * @var string
     * @required
     */
    private $resMsg;

    /**
     * 邀请码
     *
     * @var string
     * @required
     */
    private $shortAlias;

    /**
     * 推荐人姓名
     *
     * @var int
     * @required
     */
    private $referUserId;

    /**
     * 推荐人姓名
     *
     * @var string
     * @required
     */
    private $referUserName;

    /**
     * 机构id
     *
     * @var int
     * @required
     */
    private $agencyUserId;

    /**
     * 组id
     *
     * @var int
     * @required
     */
    private $groupId;

    /**
     * 是否过期，true在有效期，false未在有效期
     *
     * @var boolean
     * @required
     */
    private $isValid;

    /**
     * 开始时间
     *
     * @var date
     * @required
     */
    private $validBegin;

    /**
     * 结束时间
     *
     * @var date
     * @required
     */
    private $validEnd;

    /**
     * 邀请码文案
     *
     * @var string
     * @required
     */
    private $remark;

    /**
     * @return int
     */
    public function getSwitch()
    {
        return $this->switch;
    }

    /**
     * @param int $switch
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setSwitch($switch)
    {
        \Assert\Assertion::integer($switch);

        $this->switch = $switch;

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
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setResCode($resCode)
    {
        \Assert\Assertion::integer($resCode);

        $this->resCode = $resCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getResMsg()
    {
        return $this->resMsg;
    }

    /**
     * @param string $resMsg
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setResMsg($resMsg)
    {
        \Assert\Assertion::string($resMsg);

        $this->resMsg = $resMsg;

        return $this;
    }
    /**
     * @return string
     */
    public function getShortAlias()
    {
        return $this->shortAlias;
    }

    /**
     * @param string $shortAlias
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setShortAlias($shortAlias)
    {
        \Assert\Assertion::string($shortAlias);

        $this->shortAlias = $shortAlias;

        return $this;
    }
    /**
     * @return int
     */
    public function getReferUserId()
    {
        return $this->referUserId;
    }

    /**
     * @param int $referUserId
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setReferUserId($referUserId)
    {
        \Assert\Assertion::integer($referUserId);

        $this->referUserId = $referUserId;

        return $this;
    }
    /**
     * @return string
     */
    public function getReferUserName()
    {
        return $this->referUserName;
    }

    /**
     * @param string $referUserName
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setReferUserName($referUserName)
    {
        \Assert\Assertion::string($referUserName);

        $this->referUserName = $referUserName;

        return $this;
    }
    /**
     * @return int
     */
    public function getAgencyUserId()
    {
        return $this->agencyUserId;
    }

    /**
     * @param int $agencyUserId
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setAgencyUserId($agencyUserId)
    {
        \Assert\Assertion::integer($agencyUserId);

        $this->agencyUserId = $agencyUserId;

        return $this;
    }
    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setGroupId($groupId)
    {
        \Assert\Assertion::integer($groupId);

        $this->groupId = $groupId;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getIsValid()
    {
        return $this->isValid;
    }

    /**
     * @param boolean $isValid
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setIsValid($isValid)
    {
        \Assert\Assertion::boolean($isValid);

        $this->isValid = $isValid;

        return $this;
    }
    /**
     * @return date
     */
    public function getValidBegin()
    {
        return $this->validBegin;
    }

    /**
     * @param date $validBegin
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setValidBegin(date $validBegin)
    {
        $this->validBegin = $validBegin;

        return $this;
    }
    /**
     * @return date
     */
    public function getValidEnd()
    {
        return $this->validEnd;
    }

    /**
     * @param date $validEnd
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setValidEnd(date $validEnd)
    {
        $this->validEnd = $validEnd;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return ResponseGetUserInfoByInvitationCode
     */
    public function setRemark($remark)
    {
        \Assert\Assertion::string($remark);

        $this->remark = $remark;

        return $this;
    }

}