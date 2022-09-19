<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 股票列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestStockList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 股票代码
     *
     * @var string
     * @optional
     */
    private $stockCode = '';

    /**
     * 股票名称
     *
     * @var string
     * @optional
     */
    private $stockName = '';

    /**
     * 股票名称首拼字母
     *
     * @var string
     * @optional
     */
    private $stockFirstLetter = '';

    /**
     * 股票名称拼音
     *
     * @var string
     * @optional
     */
    private $stockPinyin = '';

    /**
     * 股票交易所
     *
     * @var int
     * @optional
     */
    private $stockExchange = -1;

    /**
     * 股票代码类型
     *
     * @var int
     * @optional
     */
    private $stockType = -1;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestStockList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getStockCode()
    {
        return $this->stockCode;
    }

    /**
     * @param string $stockCode
     * @return RequestStockList
     */
    public function setStockCode($stockCode = '')
    {
        $this->stockCode = $stockCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getStockName()
    {
        return $this->stockName;
    }

    /**
     * @param string $stockName
     * @return RequestStockList
     */
    public function setStockName($stockName = '')
    {
        $this->stockName = $stockName;

        return $this;
    }
    /**
     * @return string
     */
    public function getStockFirstLetter()
    {
        return $this->stockFirstLetter;
    }

    /**
     * @param string $stockFirstLetter
     * @return RequestStockList
     */
    public function setStockFirstLetter($stockFirstLetter = '')
    {
        $this->stockFirstLetter = $stockFirstLetter;

        return $this;
    }
    /**
     * @return string
     */
    public function getStockPinyin()
    {
        return $this->stockPinyin;
    }

    /**
     * @param string $stockPinyin
     * @return RequestStockList
     */
    public function setStockPinyin($stockPinyin = '')
    {
        $this->stockPinyin = $stockPinyin;

        return $this;
    }
    /**
     * @return int
     */
    public function getStockExchange()
    {
        return $this->stockExchange;
    }

    /**
     * @param int $stockExchange
     * @return RequestStockList
     */
    public function setStockExchange($stockExchange = -1)
    {
        $this->stockExchange = $stockExchange;

        return $this;
    }
    /**
     * @return int
     */
    public function getStockType()
    {
        return $this->stockType;
    }

    /**
     * @param int $stockType
     * @return RequestStockList
     */
    public function setStockType($stockType = -1)
    {
        $this->stockType = $stockType;

        return $this;
    }

}
