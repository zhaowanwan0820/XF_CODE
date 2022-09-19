<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * accessToken
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoAccessToken extends ProtoBufferBase
{
    /**
     * accessToken
     *
     * @var string
     * @required
     */
    private $accessToken;

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     * @return ProtoAccessToken
     */
    public function setAccessToken($accessToken)
    {
        \Assert\Assertion::string($accessToken);

        $this->accessToken = $accessToken;

        return $this;
    }

}