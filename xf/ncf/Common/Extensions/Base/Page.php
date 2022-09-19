<?php

/**
 * ProtoPage
 * page query result
 * User: ChengQ
 * Date: 10/9/14
 * Time: 14:26
 */

namespace NCFGroup\Common\Extensions\Base;

class Page extends ProtoBufferBase
{

    private $page;

    private $data;

    private $total;

    private $pageNo;

    private $pageSize;

    private $totalPage;

    /**
     * @param ProtoPageable $page
     * @param array $data
     * @param $total
     */
    public function __construct(Pageable $page, $total, Array $data = null)
    {
        $this->page = $page;
        $this->data = $data;
        $this->total = $total;
        $this->pageNo = $this->getPageNo();
        $this->pageSize = $this->getPageSize();
        $this->totalPage = $this->getTotalPage();
    }

    public function getPageNo()
    {
        if (empty($this->pageNo)) {
            $this->pageNo = $this->page == null ? 0 : $this->page->getPageNo();
        }
        return $this->pageNo;
    }

    public function getPageSize()
    {
        if (empty($this->pageSize)) {
            $this->pageSize = $this->page == null ? 0 : $this->page->getPageSize();
        }
        return $this->pageSize;
    }

    public function getTotalPage()
    {
        if (empty($this->totalPage)) {
            $this->totalPage = $this->getPageSize() == 0 ? 0 : ceil($this->total / $this->getPageSize());
        }
        return $this->totalPage;
    }

    /**
     * @var int
     * @return totalSize
     */
    public function getTotalSize()
    {
        return $this->total;
    }

    /**
     * Returns if there is a previous page.
     *
     * @return boolean if there is a previous page
     */
    public function hasPreviousPage()
    {
        return $this->getPageNo() > 1;
    }

    /**
     * Returns whether the current page is the first one.
     *
     * @return
     */
    public function isFirstPage()
    {
        return !$this->hasPreviousPage();
    }

    /**
     * Returns if there is a next page.
     *
     * @return if there is a next page
     */
    public function hasNextPage()
    {
        return $this->getPageNo() < $this->getTotalPage();
    }

    /**
     * Returns whether the current page is the last one.
     *
     * @return
     */
    public function isLastPage()
    {
        return !$this->hasNextPage();
    }

    /**
     * Returns whether the  has content at all.
     *
     * @return content;
     */
    public function getContent()
    {
        return $this->data;
    }

    /**
     * Returns whether the  has content at all.
     *
     * @return boolean
     */
    public function hasContent()
    {
        return !empty($this->data);
    }
}
