<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 资金记录服务化
 *
 * 由代码生成器生成, 不可人为修改
 * @author guofeng3
 */
class ResponseUserMoney extends ProtoBufferBase
{
    /**
     * 业务响应状态
     *
     * @var string
     * @required
     */
    private $respCode;

    /**
     * 业务响应消息
     *
     * @var string
     * @required
     */
    private $respMsg;

    /**
     * @return string
     */
    public function getRespCode()
    {
        return $this->respCode;
    }

    /**
     * @param string $respCode
     * @return ResponseUserMoney
     */
    public function setRespCode($respCode)
    {
        \Assert\Assertion::string($respCode);

        $this->respCode = $respCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getRespMsg()
    {
        return $this->respMsg;
    }

    /**
     * @param string $respMsg
     * @return ResponseUserMoney
     */
    public function setRespMsg($respMsg)
    {
        \Assert\Assertion::string($respMsg);

        $this->respMsg = $respMsg;

        return $this;
    }

}