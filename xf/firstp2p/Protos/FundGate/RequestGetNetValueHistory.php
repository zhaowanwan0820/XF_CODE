<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取公募基金净值历史数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetNetValueHistory extends AbstractRequestBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 起始日期
     *
     * @var string
     * @required
     */
    private $startDay;

    /**
     * 截止日期
     *
     * @var string
     * @required
     */
    private $endDay;

    /**
     * 排序方式
     *
     * @var string
     * @optional
     */
    private $sort = 'asc';

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $pageNo = 0;

    /**
     * 每页记录数
     *
     * @var int
     * @optional
     */
    private $pageSize = 0;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetNetValueHistory
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartDay()
    {
        return $this->startDay;
    }

    /**
     * @param string $startDay
     * @return RequestGetNetValueHistory
     */
    public function setStartDay($startDay)
    {
        \Assert\Assertion::string($startDay);

        $this->startDay = $startDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndDay()
    {
        return $this->endDay;
    }

    /**
     * @param string $endDay
     * @return RequestGetNetValueHistory
     */
    public function setEndDay($endDay)
    {
        \Assert\Assertion::string($endDay);

        $this->endDay = $endDay;

        return $this;
    }
    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $sort
     * @return RequestGetNetValueHistory
     */
    public function setSort($sort = 'asc')
    {
        $this->sort = $sort;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * @param int $pageNo
     * @return RequestGetNetValueHistory
     */
    public function setPageNo($pageNo = 0)
    {
        $this->pageNo = $pageNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return RequestGetNetValueHistory
     */
    public function setPageSize($pageSize = 0)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

}