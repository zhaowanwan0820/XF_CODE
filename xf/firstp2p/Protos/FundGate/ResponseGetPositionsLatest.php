<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取公募基金最新的持仓数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetPositionsLatest extends ResponseBase
{
    /**
     * 股票持仓明细列表
     *
     * @var array
     * @required
     */
    private $stockList;

    /**
     * 债券持仓明细列表
     *
     * @var array
     * @required
     */
    private $bondList;

    /**
     * 各类资产分布明细列表
     *
     * @var array
     * @required
     */
    private $assetAllocationList;

    /**
     * @return array
     */
    public function getStockList()
    {
        return $this->stockList;
    }

    /**
     * @param array $stockList
     * @return ResponseGetPositionsLatest
     */
    public function setStockList(array $stockList)
    {
        $this->stockList = $stockList;

        return $this;
    }
    /**
     * @return array
     */
    public function getBondList()
    {
        return $this->bondList;
    }

    /**
     * @param array $bondList
     * @return ResponseGetPositionsLatest
     */
    public function setBondList(array $bondList)
    {
        $this->bondList = $bondList;

        return $this;
    }
    /**
     * @return array
     */
    public function getAssetAllocationList()
    {
        return $this->assetAllocationList;
    }

    /**
     * @param array $assetAllocationList
     * @return ResponseGetPositionsLatest
     */
    public function setAssetAllocationList(array $assetAllocationList)
    {
        $this->assetAllocationList = $assetAllocationList;

        return $this;
    }

}