<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * Token
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhaohui
 */
class RequestUidByToken extends ProtoBufferBase
{
    /**
     * 理财师获取uid使用token
     *
     * @var string
     * @optional
     */
    private $lcsToken = '';

    /**
     * @return string
     */
    public function getLcsToken()
    {
        return $this->lcsToken;
    }

    /**
     * @param string $lcsToken
     * @return RequestUidByToken
     */
    public function setLcsToken($lcsToken = '')
    {
        $this->lcsToken = $lcsToken;

        return $this;
    }

}