<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取补发券码列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class RequestGetCouponReissueList extends AbstractRequestBase
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
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetCouponReissueList
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
     * @return RequestGetCouponReissueList
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
     * @return RequestGetCouponReissueList
     */
    public function setIsExport($isExport = 0)
    {
        $this->isExport = $isExport;

        return $this;
    }

}