<?php
/**
 * 随鑫约
 */

namespace task\apis\deal;

use task\lib\ApiAction;
use core\service\dealload\DealLoadService;
use core\service\deal\DealService;
use libs\utils\Finance;

class ReserveMy extends ApiAction
{
    public function invoke()
    {
    	$param = $this->getParam();

    }

}