<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户手机RequestProto
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestUserMobile extends AbstractRequestBase
{
    /**
     * 用户手机号码
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return RequestUserMobile
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }

}