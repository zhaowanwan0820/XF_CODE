<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * TokenId
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ProtoToken extends ProtoBufferBase
{
    /**
     * TokenId
     *
     * @var string
     * @required
     */
    private $token;

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return ProtoToken
     */
    public function setToken($token)
    {
        \Assert\Assertion::string($token);

        $this->token = $token;

        return $this;
    }

}