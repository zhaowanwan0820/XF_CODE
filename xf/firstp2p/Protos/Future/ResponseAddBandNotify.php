<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 追加保障金通知结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseAddBandNotify extends AbstractRequestBase
{
    /**
     * 返回结果
     *
     * @var int
     * @required
     */
    private $status;

    /**
     * 追加保证金未通过审核退款payID
     *
     * @var string
     * @required
     */
    private $payId;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ResponseAddBandNotify
     */
    public function setStatus($status)
    {
        \Assert\Assertion::integer($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->payId;
    }

    /**
     * @param string $payId
     * @return ResponseAddBandNotify
     */
    public function setPayId($payId)
    {
        \Assert\Assertion::string($payId);

        $this->payId = $payId;

        return $this;
    }

}