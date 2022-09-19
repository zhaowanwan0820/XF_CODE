<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获得券商开户各个阶段数量统计
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class ResponseGetTraderAccountNum extends ResponseBase
{
    /**
     * 开户各个阶段统计
     *
     * @var array
     * @required
     */
    private $stageArray;

    /**
     * 券商列表
     *
     * @var array
     * @required
     */
    private $traderArray;

    /**
     * 来源列表
     *
     * @var array
     * @required
     */
    private $sourceArray;

    /**
     * @return array
     */
    public function getStageArray()
    {
        return $this->stageArray;
    }

    /**
     * @param array $stageArray
     * @return ResponseGetTraderAccountNum
     */
    public function setStageArray(array $stageArray)
    {
        $this->stageArray = $stageArray;

        return $this;
    }
    /**
     * @return array
     */
    public function getTraderArray()
    {
        return $this->traderArray;
    }

    /**
     * @param array $traderArray
     * @return ResponseGetTraderAccountNum
     */
    public function setTraderArray(array $traderArray)
    {
        $this->traderArray = $traderArray;

        return $this;
    }
    /**
     * @return array
     */
    public function getSourceArray()
    {
        return $this->sourceArray;
    }

    /**
     * @param array $sourceArray
     * @return ResponseGetTraderAccountNum
     */
    public function setSourceArray(array $sourceArray)
    {
        $this->sourceArray = $sourceArray;

        return $this;
    }

}