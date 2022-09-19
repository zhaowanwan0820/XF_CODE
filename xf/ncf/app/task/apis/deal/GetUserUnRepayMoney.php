<?php

namespace task\apis\deal;

use core\dao\deal\DealModel;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use libs\web\Form;
use libs\utils\Page;
use libs\utils\Logger;
use core\service\deal\DealService;
use task\lib\ApiAction;

class GetUserUnRepayMoney extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $uids = $params['uids'];
        $ds = new DealService();
        $unRepayMoney = $ds->getUnrepayP2pMoneyByUids($uids);
        $this->json_data = $unRepayMoney;
    }
}
