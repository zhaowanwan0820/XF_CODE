<?php

namespace task\controllers\ifapush;

use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\ifapush\PushTransfer;
use core\dao\ifapush\IfaTransferModel;

class Transfer extends BaseAction
{
    public function invoke()
    {
        $params = json_decode($this->getParams(), true);
        try {
            Logger::info(__CLASS__.','.__FUNCTION__.','.__LINE__.', Task receive params '.json_encode($params));
            $orderId = $params['orderId'];
            $contractId = $params['contractId'];
            $ifu = new IfaTransferModel();
            if (false === $ifu->isNeedReport($orderId)) {
                Logger::info(__CLASS__.','.__FUNCTION__.','.__LINE__.', 不需要上报此数据 '.json_encode($params));
                return true;
            }

            $pu = new PushTransfer($orderId,$contractId);
            $saveRes = $pu->saveData();
            if (!$saveRes) {
                throw new \Exception('saveData error!');
            }
        } catch (\Exception $ex) {
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
            Logger::error($ex->getMessage());
        }
    }
}
