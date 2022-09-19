<?php
/**
 * user purchased fund response
 * User: wangjiansong
 * Date: 10/12/14
 * Time: 17:00
 */

namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\Page;

class ResponseGetUserOrders extends ResponseBase
{
    private $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
    }


    public function getOrders()
    {
        return $this->page->getContent();
    }

    public function getPageNo()
    {
        return $this->page->getPageNo();
    }

    public function getPageSize()
    {
        return $this->page->getPageSize();
    }

    public function getTotalPage()
    {
        return $this->page->getTotalPage();
    }

    public function getTotalSize()
    {
        return $this->page->getTotalSize();
    }

}
