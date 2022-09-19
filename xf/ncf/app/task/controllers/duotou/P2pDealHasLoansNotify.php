<?php

namespace task\controllers\duotou;

use task\controllers\BaseAction;
use core\service\duotou\DtDealService;
use libs\utils\Logger;

class P2pDealHasLoansNotify extends BaseAction
{
    public function invoke()
    {
        $params = json_decode($this->getParams(), true);
        try {
            Logger::info('Task receive params '.json_encode($params));

            $dealId = $params['dealId'];
            if (!$dealId) {
                throw new \Exception('参数错误');
            }

            $service = new DtDealService();
            $res = $service->p2pDealHasLoansNotify($dealId);
            if (!$res) {
                throw new \Exception('p2p放款通知智多鑫失败');
            }
            $this->json_data = $res;
        } catch (\Exception $ex) {
            Logger::error(implode(',', array(__CLASS__, __FUNCTION__, __LINE__, $ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
