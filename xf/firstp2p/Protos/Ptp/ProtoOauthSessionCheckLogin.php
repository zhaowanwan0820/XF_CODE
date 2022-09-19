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
class ProtoOauthSessionCheckLogin extends ProtoBufferBase
{
    /**
     * session_id
     *
     * @var string
     * @required
     */
    private $session_id;

    /**
     * 终端
     *
     * @var string
     * @optional
     */
    private $terminal = 'web';

    /**
     * @return string
     */
    public function getSession_id()
    {
        return $this->session_id;
    }

    /**
     * @param string $session_id
     * @return ProtoOauthSessionCheckLogin
     */
    public function setSession_id($session_id)
    {
        \Assert\Assertion::string($session_id);

        $this->session_id = $session_id;

        return $this;
    }
    /**
     * @return string
     */
    public function getTerminal()
    {
        return $this->terminal;
    }

    /**
     * @param string $terminal
     * @return ProtoOauthSessionCheckLogin
     */
    public function setTerminal($terminal = 'web')
    {
        $this->terminal = $terminal;

        return $this;
    }

}