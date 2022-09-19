<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * OpenAppInfo
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoOauthTokenCheckLogin extends ProtoBufferBase
{
    /**
     * token_id
     *
     * @var string
     * @required
     */
    private $token_id;

    /**
     * @return string
     */
    public function getToken_id()
    {
        return $this->token_id;
    }

    /**
     * @param string $token_id
     * @return ProtoOauthTokenCheckLogin
     */
    public function setToken_id($token_id)
    {
        \Assert\Assertion::string($token_id);

        $this->token_id = $token_id;

        return $this;
    }

}