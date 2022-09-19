<?php
namespace task\controllers\ifapush;

use core\enum\MsgbusEnum;
use core\service\ifapush\PushDeal;
use core\service\msgbus\MsgbusService;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\ifapush\PushUser;
use core\dao\ifapush\IfaDealModel;


class AddDeal extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];

            $ifd = new IfaDealModel();
            if($ifd->isNeedReport($dealId) === true){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 不需要上报此标的数据 ".json_encode($params));
                return true;
            }

            $pu = new PushDeal($dealId);
            $saveRes = $pu->saveData();
            if(!$saveRes){
                throw new \Exception('saveData error!');
            }

            $message = array('dealId'=>$dealId);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_IFA_DEAL_PROGRESSING,$message);
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
            Logger::error($ex->getMessage());
        }
    }
}