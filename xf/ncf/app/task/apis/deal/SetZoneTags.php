<?php

namespace task\apis\deal;

use libs\web\Form;
use task\lib\ApiAction;
use core\dao\ConfModel;
use core\dao\tag\TagModel;
use core\service\deal\DealService;

class SetZoneTags extends ApiAction
{
    public function invoke()
    {
        $params = $this->getParam();

        $tags = $params['tags'];
        $result = \core\dao\ConfModel::instance()->set(DealService::ZONE_KEY, implode(',', $tags));
        $this->json_data = $result;
    }
}