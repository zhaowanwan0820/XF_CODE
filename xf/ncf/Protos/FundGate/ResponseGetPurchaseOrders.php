<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 响应用户已购买基金
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ <qicheng@ucfgroup.com>
 */
class ResponseGetPurchaseOrders extends ResponseBase
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
     * 用户姓名
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 总资产
     *
     * @var float
     * @optional
     */
    private $totalAssets = 0;

    /**
     * 当前委托数量
     *
     * @var int
     * @optional
     */
    private $currentEntrustCount = 0;

    /**
     * @return array<ProtoPurchaseFund>
     */
    public function getFunds()
    {
        return $this->funds;
    }

    /**
     * @param array<ProtoPurchaseFund> $funds
     * @return ResponseGetPurchaseOrders
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
     * @return ResponseGetPurchaseOrders
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
     * @return ResponseGetPurchaseOrders
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
     * @return ResponseGetPurchaseOrders
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
     * @return ResponseGetPurchaseOrders
     */
    public function setTotalSize($totalSize)
    {
        \Assert\Assertion::integer($totalSize);

        $this->totalSize = $totalSize;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ResponseGetPurchaseOrders
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

        return $this;
    }
    /**
     * @return float
     */
    public function getTotalAssets()
    {
        return $this->totalAssets;
    }

    /**
     * @param float $totalAssets
     * @return ResponseGetPurchaseOrders
     */
    public function setTotalAssets($totalAssets = 0)
    {
        $this->totalAssets = $totalAssets;

        return $this;
    }
    /**
     * @return int
     */
    public function getCurrentEntrustCount()
    {
        return $this->currentEntrustCount;
    }

    /**
     * @param int $currentEntrustCount
     * @return ResponseGetPurchaseOrders
     */
    public function setCurrentEntrustCount($currentEntrustCount = 0)
    {
        $this->currentEntrustCount = $currentEntrustCount;

        return $this;
    }

}