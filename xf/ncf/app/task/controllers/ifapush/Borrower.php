<?php
namespace task\controllers\ifapush;

use core\dao\deal\DealModel;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\ifapush\PushUser;
use core\dao\ifapush\IfaUserModel;


class Borrower extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $dealInfo = DealModel::instance()->getDealInfo($dealId);
            $borrowerId  = $dealInfo['user_id'];
            
            $ifu = new IfaUserModel();
            if($ifu->isNeedReport($borrowerId,$dealId) === false){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 不需要上报此用户数据 ".json_encode($params));
                return true;
            }

            $pu = new PushUser($borrowerId);
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