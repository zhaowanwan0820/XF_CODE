<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取基金所属的专题（可能为多个）
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetBelongedTopic extends AbstractRequestBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetBelongedTopic
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }

}