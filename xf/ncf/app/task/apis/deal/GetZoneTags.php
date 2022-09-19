<?php

namespace task\apis\deal;

use libs\web\Form;
use task\lib\ApiAction;
use core\dao\tag\TagModel;
use core\service\deal\DealService;

class GetZoneTags extends ApiAction
{
    public function invoke()
    {
        $result = \core\dao\ConfModel::instance()->get(DealService::ZONE_KEY)['value'];
        if ($result) {
            $result = explode(',', $result);
        }
        $this->json_data = $result;
    }
}
