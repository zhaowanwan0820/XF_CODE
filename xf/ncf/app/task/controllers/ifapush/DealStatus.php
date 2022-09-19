<?php
namespace task\controllers\ifapush;

use core\enum\MsgbusEnum;
use core\service\ifapush\PushDeal;
use core\service\ifapush\PushDealStatus;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\ifapush\PushUser;
use core\dao\ifapush\IfaDealModel;


class DealStatus extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);

        $topic = $this->getTopic();


        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];

            $ifd = new IfaDealModel();
    /*        if($ifd->isNeedReport($dealId) === false){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 不需要上报此标的数据 ".json_encode($params));
                return true;
            }*/

            $status = $this->getDealStatus($topic);
            $pu = new PushDealStatus($dealId,$status);
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

    private function getDealStatus($topic){
        // 0待等材料，1进行中，2满标，3流标，4还款中，5已还清
        $s = array(
            MsgbusEnum::TOPIC_DEAL_PROGRESSING => 1,
            MsgbusEnum::TOPIC_DEAL_IFA_DEAL_PROGRESSING => 1,
            MsgbusEnum::TOPIC_DEAL_FULL => 2,
            MsgbusEnum::TOPIC_DEAL_FAIL => 3,
            MsgbusEnum::TOPIC_DEAL_MAKE_LOANS => 4,
            MsgbusEnum::TOPIC_DEAL_REPAY_OVER => 5,
            MsgbusEnum::TOPIC_DEAL_PREPAY_FINISH => 5,
        );
        return $s[$topic];
    }
}