<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取可用基金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class RequestGetAvailableFundList extends AbstractRequestBase
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
     * 版本标识
     *
     * @var int
     * @required
     */
    private $version;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetAvailableFundList
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
     * @return RequestGetAvailableFundList
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
     * @return RequestGetAvailableFundList
     */
    public function setYieldIndex($yieldIndex = '')
    {
        $this->yieldIndex = $yieldIndex;

        return $this;
    }
    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return RequestGetAvailableFundList
     */
    public function setVersion($version)
    {
        \Assert\Assertion::integer($version);

        $this->version = $version;

        return $this;
    }

}