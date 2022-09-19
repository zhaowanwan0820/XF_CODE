<?php
namespace task\controllers\dealcreate;

use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;

/**
 * 上标完成之后的初始化服务
 * Class Create
 * @package task\controllers\deal
 */
class InitDeal extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $ds = new DealService();
            $ds->initDeal($dealId);
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}