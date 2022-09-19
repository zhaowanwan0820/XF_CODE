<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取公募基金公告列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetAnnouncementList extends AbstractRequestBase
{
    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $pageNo = 1;

    /**
     * 每页记录数
     *
     * @var int
     * @optional
     */
    private $pageSize = 15;

    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetAnnouncementList
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

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
     * @return RequestGetAnnouncementList
     */
    public function setPageNo($pageNo = 1)
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
     * @return RequestGetAnnouncementList
     */
    public function setPageSize($pageSize = 15)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

}