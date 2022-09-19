<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 网盟用户相关
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoWebUnionUser extends AbstractRequestBase
{
    /**
     * 用户手机号码
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * 用户名
     *
     * @var string
     * @optional
     */
    private $username = '';

    /**
     * 网贷天眼信用户自动生成新密码
     *
     * @var string
     * @optional
     */
    private $registerPwd = '';

    /**
     * 登录密码
     *
     * @var string
     * @optional
     */
    private $userPwd = '';

    /**
     * 标的id
     *
     * @var int
     * @optional
     */
    private $dealId = 0;

    /**
     * 获取最近一天交易记录的userid串
     *
     * @var string
     * @optional
     */
    private $userIds = '';

    /**
     * 查询时间
     *
     * @var int
     * @optional
     */
    private $sendTime = 0;

    /**
     * 邀请码
     *
     * @var string
     * @optional
     */
    private $inviteCode = '';

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return ProtoWebUnionUser
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return ProtoWebUnionUser
     */
    public function setUsername($username = '')
    {
        $this->username = $username;

        return $this;
    }
    /**
     * @return string
     */
    public function getRegisterPwd()
    {
        return $this->registerPwd;
    }

    /**
     * @param string $registerPwd
     * @return ProtoWebUnionUser
     */
    public function setRegisterPwd($registerPwd = '')
    {
        $this->registerPwd = $registerPwd;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserPwd()
    {
        return $this->userPwd;
    }

    /**
     * @param string $userPwd
     * @return ProtoWebUnionUser
     */
    public function setUserPwd($userPwd = '')
    {
        $this->userPwd = $userPwd;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ProtoWebUnionUser
     */
    public function setDealId($dealId = 0)
    {
        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserIds()
    {
        return $this->userIds;
    }

    /**
     * @param string $userIds
     * @return ProtoWebUnionUser
     */
    public function setUserIds($userIds = '')
    {
        $this->userIds = $userIds;

        return $this;
    }
    /**
     * @return int
     */
    public function getSendTime()
    {
        return $this->sendTime;
    }

    /**
     * @param int $sendTime
     * @return ProtoWebUnionUser
     */
    public function setSendTime($sendTime = 0)
    {
        $this->sendTime = $sendTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getInviteCode()
    {
        return $this->inviteCode;
    }

    /**
     * @param string $inviteCode
     * @return ProtoWebUnionUser
     */
    public function setInviteCode($inviteCode = '')
    {
        $this->inviteCode = $inviteCode;

        return $this;
    }

}