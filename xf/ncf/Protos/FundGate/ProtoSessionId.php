<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * SessionId
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ProtoSessionId extends ProtoBufferBase
{
    /**
     * SessionId
     *
     * @var string
     * @required
     */
    private $sessionId;

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return ProtoSessionId
     */
    public function setSessionId($sessionId)
    {
        \Assert\Assertion::string($sessionId);

        $this->sessionId = $sessionId;

        return $this;
    }

}