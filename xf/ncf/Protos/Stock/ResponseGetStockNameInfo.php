<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取股票名称信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class ResponseGetStockNameInfo extends ResponseBase
{
    /**
     * 股票名称信息
     *
     * @var array
     * @required
     */
    private $stocks;

    /**
     * @return array
     */
    public function getStocks()
    {
        return $this->stocks;
    }

    /**
     * @param array $stocks
     * @return ResponseGetStockNameInfo
     */
    public function setStocks(array $stocks)
    {
        $this->stocks = $stocks;

        return $this;
    }

}