<?php
namespace task\controllers\ifapush;

use core\service\ifapush\PushDealRepay;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\ifapush\PushUser;
use core\dao\ifapush\IfaDealModel;


class DealRepay extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];

            $ifd = new IfaDealModel();
/*            if($ifd->isNeedReport($dealId) === false){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 不需要上报此标的数据 ".json_encode($params));
                return true;
            }*/

            $pu = new PushDealRepay($dealId);
            $saveRes = $pu->saveData();
            if(!$saveRes){
                throw new \Exception('saveData error!');
            }
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
            Logger::error($ex->getMessage());
        }
    }
}