<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:勋章领券
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class ResponseAcquireMedalCoupon extends ProtoBufferBase
{
    /**
     * rpc接口状态
     *
     * @var string
     * @required
     */
    private $resCode;

    /**
     * 错误信息
     *
     * @var string
     * @required
     */
    private $errorMsg;

    /**
     * 错误号
     *
     * @var string
     * @required
     */
    private $errorCode;

    /**
     * @return string
     */
    public function getResCode()
    {
        return $this->resCode;
    }

    /**
     * @param string $resCode
     * @return ResponseAcquireMedalCoupon
     */
    public function setResCode($resCode)
    {
        \Assert\Assertion::string($resCode);

        $this->resCode = $resCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     * @return ResponseAcquireMedalCoupon
     */
    public function setErrorMsg($errorMsg)
    {
        \Assert\Assertion::string($errorMsg);

        $this->errorMsg = $errorMsg;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param string $errorCode
     * @return ResponseAcquireMedalCoupon
     */
    public function setErrorCode($errorCode)
    {
        \Assert\Assertion::string($errorCode);

        $this->errorCode = $errorCode;

        return $this;
    }

}