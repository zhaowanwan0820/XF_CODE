<?php
namespace task\controllers\supervision;

use task\controllers\BaseAction;
use core\service\supervision\SupervisionFinanceService;
use libs\utils\Logger;

/**
 * 延迟提现命中之后的异步通知
 */
class LimitNotify extends BaseAction {
    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            SupervisionFinanceService::LimitNotice($params);
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
