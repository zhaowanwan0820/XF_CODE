<?php

namespace task\apis\duotou;

use task\lib\ApiAction;
use core\service\duotou\DtDepositoryService;
use libs\utils\Logger;

class DtIsAllDepositioryHandleFinished extends ApiAction
{
    public function invoke()
    {
        $service = new DtDepositoryService();
        $this->json_data = $service->isFinishDtTask();
    }
}
