<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取零售店配置列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Vincent <daiyuxin@ucfgroup.com>
 */
class RequestGetStoreTagConfList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * id
     *
     * @var int
     * @optional
     */
    private $id = '';

    /**
     * tagid
     *
     * @var int
     * @optional
     */
    private $tagId = '';

    /**
     * 供应商Ids
     *
     * @var string
     * @optional
     */
    private $supplierIds = '';

    /**
     * 状态
     *
     * @var int
     * @optional
     */
    private $status = '';

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
     * @return RequestGetStoreTagConfList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestGetStoreTagConfList
     */
    public function setId($id = '')
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getTagId()
    {
        return $this->tagId;
    }

    /**
     * @param int $tagId
     * @return RequestGetStoreTagConfList
     */
    public function setTagId($tagId = '')
    {
        $this->tagId = $tagId;

        return $this;
    }
    /**
     * @return string
     */
    public function getSupplierIds()
    {
        return $this->supplierIds;
    }

    /**
     * @param string $supplierIds
     * @return RequestGetStoreTagConfList
     */
    public function setSupplierIds($supplierIds = '')
    {
        $this->supplierIds = $supplierIds;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return RequestGetStoreTagConfList
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

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
     * @return RequestGetStoreTagConfList
     */
    public function setCondition($condition = '')
    {
        $this->condition = $condition;

        return $this;
    }

}