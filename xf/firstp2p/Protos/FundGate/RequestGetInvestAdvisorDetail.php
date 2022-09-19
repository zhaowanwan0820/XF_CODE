<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取公募基金管理人详细资料
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetInvestAdvisorDetail extends AbstractRequestBase
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
     * @return RequestGetInvestAdvisorDetail
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }

}