<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 终止合约返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseStopContract extends ResponseBase
{
    /**
     * 支付订单号
     *
     * @var string
     * @optional
     */
    private $payId = '0';

    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->payId;
    }

    /**
     * @param string $payId
     * @return ResponseStopContract
     */
    public function setPayId($payId = '0')
    {
        $this->payId = $payId;

        return $this;
    }

}