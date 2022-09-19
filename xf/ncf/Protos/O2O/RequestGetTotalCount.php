<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 通用的获取列表数据总数请求接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author <yanbingrong@ucfgroup.com>
 */
class RequestGetTotalCount extends AbstractRequestBase
{
    /**
     * 查询条件
     *
     * @var string
     * @optional
     */
    private $condition = '';

    /**
     * 是否取最新数据
     *
     * @var int
     * @optional
     */
    private $fresh = 0;

    /**
     * 券状态
     *
     * @var string
     * @optional
     */
    private $status = '';

    /**
     * 是否取归档表数据
     *
     * @var int
     * @optional
     */
    private $isArchive = 0;

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return RequestGetTotalCount
     */
    public function setCondition($condition = '')
    {
        $this->condition = $condition;

        return $this;
    }
    /**
     * @return int
     */
    public function getFresh()
    {
        return $this->fresh;
    }

    /**
     * @param int $fresh
     * @return RequestGetTotalCount
     */
    public function setFresh($fresh = 0)
    {
        $this->fresh = $fresh;

        return $this;
    }
    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return RequestGetTotalCount
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsArchive()
    {
        return $this->isArchive;
    }

    /**
     * @param int $isArchive
     * @return RequestGetTotalCount
     */
    public function setIsArchive($isArchive = 0)
    {
        $this->isArchive = $isArchive;

        return $this;
    }

}