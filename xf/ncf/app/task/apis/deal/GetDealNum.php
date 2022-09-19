<?php

namespace task\apis\deal;

use core\dao\deal\DealModel;
use task\lib\ApiAction;

class GetDealNum extends ApiAction
{
    public function invoke()
    {
        $ds = new  DealModel();
        $deals = $ds->getDealCategoryNum();
        $this->json_data = $deals;
    }
}
