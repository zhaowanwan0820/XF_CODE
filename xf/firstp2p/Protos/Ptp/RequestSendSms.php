<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 发送短信验证码
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangyiming
 */
class RequestSendSms extends ProtoBufferBase
{
    /**
     * 手机号码
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * 国家地区码
     *
     * @var string
     * @optional
     */
    private $countryCode = 'cn';

    /**
     * 注册邀请码
     *
     * @var string
     * @optional
     */
    private $invite = NULL;

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return RequestSendSms
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
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     * @return RequestSendSms
     */
    public function setCountryCode($countryCode = 'cn')
    {
        $this->countryCode = $countryCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvite()
    {
        return $this->invite;
    }

    /**
     * @param string $invite
     * @return RequestSendSms
     */
    public function setInvite($invite = NULL)
    {
        $this->invite = $invite;

        return $this;
    }

}