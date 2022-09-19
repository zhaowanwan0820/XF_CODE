<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 用户订单列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetUserOrderList extends ResponseBase
{
    /**
     * 订单
     *
     * @var array
     * @optional
     */
    private $orders = NULL;

    /**
     * 总页数
     *
     * @var int
     * @optional
     */
    private $totalPage = 0;

    /**
     * 总条数
     *
     * @var int
     * @optional
     */
    private $totalSize = 0;

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param array $orders
     * @return ResponseGetUserOrderList
     */
    public function setOrders(array $orders = NULL)
    {
        $this->orders = $orders;

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
     * @return ResponseGetUserOrderList
     */
    public function setTotalPage($totalPage = 0)
    {
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
     * @return ResponseGetUserOrderList
     */
    public function setTotalSize($totalSize = 0)
    {
        $this->totalSize = $totalSize;

        return $this;
    }

}