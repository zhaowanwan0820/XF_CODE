<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 审核通知返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseProfitsInform extends ResponseBase
{
    /**
     * 支付单号
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
     * @return ResponseProfitsInform
     */
    public function setPayId($payId = '0')
    {
        $this->payId = $payId;

        return $this;
    }

}