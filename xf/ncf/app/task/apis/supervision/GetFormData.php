<?php
namespace task\apis\supervision;

use task\lib\ApiAction;
use libs\utils\Logger;
use core\service\account\AccountService;
use core\service\supervision\SupervisionTransitService;

class GetFormData extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        Logger::info('GetFormDataParams:'.json_encode($param));
        $accountId = AccountService::initAccount($param['userId'], $param['userPurpose']);
        if (empty($accountId)) {
            return false;
        }
        $transitService = new SupervisionTransitService();
        $supervisionRes = $transitService->formFactory($param['srv'], $accountId, $param['param'], $param['from']);
        $this->json_data = $supervisionRes;
    }
}
