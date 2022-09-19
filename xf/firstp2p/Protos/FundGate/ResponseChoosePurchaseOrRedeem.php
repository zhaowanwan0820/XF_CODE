<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 选择申购或赎回
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class ResponseChoosePurchaseOrRedeem extends ResponseBase
{
    /**
     * 用户购买基金列表
     *
     * @var array<ProtoPurchaseFund>
     * @required
     */
    private $funds;

    /**
     * 当前页
     *
     * @var int
     * @required
     */
    private $pageNo;

    /**
     * 每页条数
     *
     * @var int
     * @required
     */
    private $pageSize;

    /**
     * 总页数
     *
     * @var int
     * @required
     */
    private $totalPage;

    /**
     * 总条数
     *
     * @var int
     * @required
     */
    private $totalSize;

    /**
     * @return array<ProtoPurchaseFund>
     */
    public function getFunds()
    {
        return $this->funds;
    }

    /**
     * @param array<ProtoPurchaseFund> $funds
     * @return ResponseChoosePurchaseOrRedeem
     */
    public function setFunds(array $funds)
    {
        $this->funds = $funds;

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
     * @return ResponseChoosePurchaseOrRedeem
     */
    public function setPageNo($pageNo)
    {
        \Assert\Assertion::integer($pageNo);

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
     * @return ResponseChoosePurchaseOrRedeem
     */
    public function setPageSize($pageSize)
    {
        \Assert\Assertion::integer($pageSize);

        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalPage()
    {
        return $this->totalPage;
    }

    /**
     * @param int $totalPage
     * @return ResponseChoosePurchaseOrRedeem
     */
    public function setTotalPage($totalPage)
    {
        \Assert\Assertion::integer($totalPage);

        $this->totalPage = $totalPage;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalSize()
    {
        return $this->totalSize;
    }

    /**
     * @param int $totalSize
     * @return ResponseChoosePurchaseOrRedeem
     */
    public function setTotalSize($totalSize)
    {
        \Assert\Assertion::integer($totalSize);

        $this->totalSize = $totalSize;

        return $this;
    }

}