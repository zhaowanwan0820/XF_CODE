<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取对象列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class RequestGetList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @optional
     */
    private $pageable = NULL;

    /**
     * 查询条件
     *
     * @var array
     * @optional
     */
    private $condition = NULL;

    /**
     * 是否用于导出
     *
     * @var int
     * @optional
     */
    private $isExport = 0;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable = NULL)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return array
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param array $condition
     * @return RequestGetList
     */
    public function setCondition(array $condition = NULL)
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
     * @return RequestGetList
     */
    public function setIsExport($isExport = 0)
    {
        $this->isExport = $isExport;

        return $this;
    }

}