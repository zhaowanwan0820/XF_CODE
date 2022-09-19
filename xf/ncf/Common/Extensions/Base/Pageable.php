<?php
/**
 * ProtoPage for pageable
 * User: ChengQ
 * Date: 10/2/14
 * Time: 11:37
 */

namespace NCFGroup\Common\Extensions\Base;

use \NCFGroup\Common\Extensions\Base\ProtoBufferBase;

use \Assert\Assertion as Assert;

class Pageable extends ProtoBufferBase
{

    const DEFAULT_PAGE_NO = 1;

    const DEFAULT_PAGE_SIZE = 15;

    private $pageNo;

    private $pageSize;

    private $sort;


    public function __construct($pageNo = self::DEFAULT_PAGE_NO, $pageSize = self::DEFAULT_PAGE_SIZE, $sort = null)
    {
        $this->setPageNo($pageNo);
        $this->setPageSize($pageSize);
        $this->setSort($sort);
    }

    /**
     * return pageNo
     * @return int
     */
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * set pageNo
     * @param int
     */
    public function setPageNo($pageNo)
    {
        Assert::min($pageNo, self::DEFAULT_PAGE_NO, '$pageNo can not less then ' . self::DEFAULT_PAGE_NO);
        $this->pageNo = $pageNo;
        return $this;
    }

    /**
     * @var int
     * @return PageSize
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * set pageSize
     * @param int pageSize
     */
    public function setPageSize($pageSize)
    {
        Assert::min($pageSize, 1, '$pageSize can not less then 1');
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @return {@link ProtoSort}
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param {@link ProtoSort} $sort
     */
    public function setSort($sort = null)
    {
        $this->sort = $sort;
    }

}
