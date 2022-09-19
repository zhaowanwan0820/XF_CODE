<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 审核未通过返回数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseApplyFundRefuse extends ResponseBase
{
    /**
     * 退款payId
     *
     * @var string
     * @required
     */
    private $payId;

    /**
     * @return string
     */
    public function getPayId()
    {
        return $this->payId;
    }

    /**
     * @param string $payId
     * @return ResponseApplyFundRefuse
     */
    public function setPayId($payId)
    {
        \Assert\Assertion::string($payId);

        $this->payId = $payId;

        return $this;
    }

}