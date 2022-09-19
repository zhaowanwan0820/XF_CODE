<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取可用基金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangdongjie <yangdongjie@ucfgroup.com>
 */
class RequestGetYxAvailableFundList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 查询条件
     *
     * @var string
     * @optional
     */
    private $condition = '';

    /**
     * 收益指标
     *
     * @var string
     * @optional
     */
    private $yieldIndex = '';

    /**
     * 用户id
     *
     * @var int
     * @optional
     */
    private $userId = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetYxAvailableFundList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return RequestGetYxAvailableFundList
     */
    public function setCondition($condition = '')
    {
        $this->condition = $condition;

        return $this;
    }
    /**
     * @return string
     */
    public function getYieldIndex()
    {
        return $this->yieldIndex;
    }

    /**
     * @param string $yieldIndex
     * @return RequestGetYxAvailableFundList
     */
    public function setYieldIndex($yieldIndex = '')
    {
        $this->yieldIndex = $yieldIndex;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetYxAvailableFundList
     */
    public function setUserId($userId = '')
    {
        $this->userId = $userId;

        return $this;
    }

}