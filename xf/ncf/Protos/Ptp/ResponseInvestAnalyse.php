<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 投资分析数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseInvestAnalyse extends ResponseBase
{
    /**
     * 在投总金额
     *
     * @var string
     * @required
     */
    private $totalInvested;

    /**
     * 数据详情
     *
     * @var array
     * @required
     */
    private $details;

    /**
     * @return string
     */
    public function getTotalInvested()
    {
        return $this->totalInvested;
    }

    /**
     * @param string $totalInvested
     * @return ResponseInvestAnalyse
     */
    public function setTotalInvested($totalInvested)
    {
        \Assert\Assertion::string($totalInvested);

        $this->totalInvested = $totalInvested;

        return $this;
    }
    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param array $details
     * @return ResponseInvestAnalyse
     */
    public function setDetails(array $details)
    {
        $this->details = $details;

        return $this;
    }

}