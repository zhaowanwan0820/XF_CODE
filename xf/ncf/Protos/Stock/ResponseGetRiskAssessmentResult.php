<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取最新风评等级结果
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetRiskAssessmentResult extends ResponseBase
{
    /**
     * 风评结果
     *
     * @var string
     * @optional
     */
    private $riskAssessmentLevel = '';

    /**
     * @return string
     */
    public function getRiskAssessmentLevel()
    {
        return $this->riskAssessmentLevel;
    }

    /**
     * @param string $riskAssessmentLevel
     * @return ResponseGetRiskAssessmentResult
     */
    public function setRiskAssessmentLevel($riskAssessmentLevel = '')
    {
        $this->riskAssessmentLevel = $riskAssessmentLevel;

        return $this;
    }

}