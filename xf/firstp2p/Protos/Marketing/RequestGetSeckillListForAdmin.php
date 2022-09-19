<?php
namespace NCFGroup\Protos\Marketing;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 秒杀admin后台列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestGetSeckillListForAdmin extends ProtoBufferBase
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
     * @return RequestGetSeckillListForAdmin
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
     * @return RequestGetSeckillListForAdmin
     */
    public function setCondition($condition = '')
    {
        $this->condition = $condition;

        return $this;
    }

}