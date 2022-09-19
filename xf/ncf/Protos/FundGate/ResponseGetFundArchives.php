<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取公募基金档案
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetFundArchives extends ResponseBase
{
    /**
     * 基金档案
     *
     * @var array
     * @required
     */
    private $fundArchives;

    /**
     * 基金净值最新表现数据
     *
     * @var array
     * @required
     */
    private $netValuePerformanceLatest;

    /**
     * @return array
     */
    public function getFundArchives()
    {
        return $this->fundArchives;
    }

    /**
     * @param array $fundArchives
     * @return ResponseGetFundArchives
     */
    public function setFundArchives(array $fundArchives)
    {
        $this->fundArchives = $fundArchives;

        return $this;
    }
    /**
     * @return array
     */
    public function getNetValuePerformanceLatest()
    {
        return $this->netValuePerformanceLatest;
    }

    /**
     * @param array $netValuePerformanceLatest
     * @return ResponseGetFundArchives
     */
    public function setNetValuePerformanceLatest(array $netValuePerformanceLatest)
    {
        $this->netValuePerformanceLatest = $netValuePerformanceLatest;

        return $this;
    }

}