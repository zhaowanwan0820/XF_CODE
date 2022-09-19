<?php
namespace NCFGroup\Protos\Commonservice;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * MobileService:获取手机归属信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jinhaidong
 */
class ProtoMobile extends ProtoBufferBase
{
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
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return ProtoMobile
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }

}