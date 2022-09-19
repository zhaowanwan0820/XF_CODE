<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取风评等级结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseSubmitRiskAssessment extends ResponseBase
{
    /**
     * 风评结果
     *
     * @var string
     * @required
     */
    private $riskAssessmentLevel;

    /**
     * @return string
     */
    public function getRiskAssessmentLevel()
    {
        return $this->riskAssessmentLevel;
    }

    /**
     * @param string $riskAssessmentLevel
     * @return ResponseSubmitRiskAssessment
     */
    public function setRiskAssessmentLevel($riskAssessmentLevel)
    {
        \Assert\Assertion::string($riskAssessmentLevel);

        $this->riskAssessmentLevel = $riskAssessmentLevel;

        return $this;
    }

}