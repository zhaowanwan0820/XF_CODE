<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 通用的获取列表数据请求接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author <yanbingrong@ucfgroup.com>
 */
class RequestGetPagableData extends AbstractRequestBase
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
     * 是否用于导出
     *
     * @var int
     * @optional
     */
    private $isExport = 0;

    /**
     * 是否包含记录总数
     *
     * @var int
     * @optional
     */
    private $hasTotalCount = 1;

    /**
     * 是否取归档表数据
     *
     * @var int
     * @optional
     */
    private $isArchive = 0;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetPagableData
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
     * @return RequestGetPagableData
     */
    public function setCondition($condition = '')
    {
        $this->condition = $condition;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsExport()
    {
        return $this->isExport;
    }

    /**
     * @param int $isExport
     * @return RequestGetPagableData
     */
    public function setIsExport($isExport = 0)
    {
        $this->isExport = $isExport;

        return $this;
    }
    /**
     * @return int
     */
    public function getHasTotalCount()
    {
        return $this->hasTotalCount;
    }

    /**
     * @param int $hasTotalCount
     * @return RequestGetPagableData
     */
    public function setHasTotalCount($hasTotalCount = 1)
    {
        $this->hasTotalCount = $hasTotalCount;

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
     * @return RequestGetPagableData
     */
    public function setIsArchive($isArchive = 0)
    {
        $this->isArchive = $isArchive;

        return $this;
    }

}