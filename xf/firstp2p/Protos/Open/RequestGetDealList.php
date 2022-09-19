<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取对象列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Geng Kuan<gengkuan@ucfgroup.com>
 */
class RequestGetDealList extends AbstractRequestBase
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
     * 分站ID
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetDealList
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
     * @return RequestGetDealList
     */
    public function setCondition(array $condition = NULL)
    {
        $this->condition = $condition;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestGetDealList
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }

}