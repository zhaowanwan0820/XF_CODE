<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 第三方交互-投资接口返回定义
 *
 * 由代码生成器生成, 不可人为修改
 * @author guofeng3
 */
class ResponseThirdPartyInvest extends ProtoBufferBase
{
    /**
     * 众筹付款单号
     *
     * @var string
     * @required
     */
    private $outOrderId;

    /**
     * 00业务受理成功，01业务受理失败
     *
     * @var string
     * @required
     */
    private $respCode;

    /**
     * 受理失败原因
     *
     * @var string
     * @optional
     */
    private $respMsg = '';

    /**
     * 付款单状态，N(初始状态)I(处理中)S(成功)F(失败)
     *
     * @var string
     * @required
     */
    private $orderStatus;

    /**
     * @return string
     */
    public function getOutOrderId()
    {
        return $this->outOrderId;
    }

    /**
     * @param string $outOrderId
     * @return ResponseThirdPartyInvest
     */
    public function setOutOrderId($outOrderId)
    {
        \Assert\Assertion::string($outOrderId);

        $this->outOrderId = $outOrderId;

        return $this;
    }
    /**
     * @return string
     */
    public function getRespCode()
    {
        return $this->respCode;
    }

    /**
     * @param string $respCode
     * @return ResponseThirdPartyInvest
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
     * @return ResponseThirdPartyInvest
     */
    public function setRespMsg($respMsg = '')
    {
        $this->respMsg = $respMsg;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    /**
     * @param string $orderStatus
     * @return ResponseThirdPartyInvest
     */
    public function setOrderStatus($orderStatus)
    {
        \Assert\Assertion::string($orderStatus);

        $this->orderStatus = $orderStatus;

        return $this;
    }

}