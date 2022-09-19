<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取供应商列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Vincent <daiyuxin@ucfgroup.com>
 */
class RequestGetSupplierList extends AbstractRequestBase
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
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetSupplierList
     */
    public function setPageable($pageable)
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
     * @return RequestGetSupplierList
     */
    public function setCondition($condition = '')
    {
        $this->condition = $condition;

        return $this;
    }

}
