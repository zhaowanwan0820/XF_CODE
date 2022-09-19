<?php
namespace task\controllers\ifapush;

use core\service\account\AccountService;
use core\dao\ifapush\IfaDealModel;
use core\dao\ifapush\IfaLenderLogModel;
use core\service\ifapush\PushLenderLog;
use libs\utils\Logger;
use libs\db\Db;
use task\controllers\BaseAction;
use core\dao\ifapush\IfaUserModel;
use core\dao\deal\DealModel;
use core\enum\DealEnum;


class LenderLog extends BaseAction
{
    public function invoke()
    {
        $params = json_decode($this->getParams(), true);

        $db = Db::getInstance('firstp2p');

        try {
            $db->startTrans();

            Logger::info(__CLASS__.','.__FUNCTION__.','.__LINE__.', Task receive params '.json_encode($params));
            $userId = $params['user_id'];
            $partition = $userId % 64;
            $logInfo = $params['log_info'];
            $extra = json_decode($params['biz_token'],true);
            $dealId = isset($extra['dealId']) ? $extra['dealId'] : -1;

            $pu = new PushLenderLog($userId,$logInfo,$dealId,$params);

            if (!in_array($logInfo, array_keys($pu->allowUserLogInfo))) { //交易类型过滤
                Logger::info(__CLASS__ . ',' . __FUNCTION__ . ',' . __LINE__ . ', 不需要上报此交易类型数据 ' . json_encode($params));
                return true;
            }

            $accountInfo = AccountService::getInfoByIds([$userId],false);
            $userType = $accountInfo[$userId]['accountType'];
            if (empty($userType) || !in_array($userType, $pu->allowUserPurpose)) { //用户类型过滤
                Logger::info(__CLASS__.','.__FUNCTION__.','.__LINE__.', 不需要上报此类型用户数据, accountInfo: '.json_encode($accountInfo));
                return true;
            }
            $userModel =  new IfaUserModel();
            if (!$userModel->hasUser($userId)){
                Logger::info(__CLASS__.','.__FUNCTION__.','.__LINE__.', 此用户数据未进入ifa_user报备列表, userId: '.$userId);
                return true;
            }
            $ifd = new IfaDealModel();
            if (($dealId != -1) && !$ifd->isNeedReport($dealId)){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 涉及标的未进入上报列表 ".json_encode($params));
                return true;
            }

            $transId = 'LOG'.$partition.'_'.$params['id'];
            $ifl = new IfaLenderLogModel();
            if($ifl->isNeedReport($transId) === false){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 此交易已进入上报列表 ".json_encode($params));
                return true;
            }

            $saveRes = $pu->saveData();
            if (!$saveRes) {
                throw new \Exception('saveData error!');
            }

            //标的已还清逻辑触发(当投资记录状态为还款,transType为8时,增加推送一条投资已结束的资金记录)
            $dealInfo = DealModel::instance()->findViaSlave($dealId);
            if(($dealInfo['deal_status'] == DealEnum::DEAL_STATUS_REPAID) && ($pu->getTransTypeInfo() == 8)){
                $logInfo = '投资结束';
                $paramsRepaid = array(
                    'id' => 'R_'.$dealId.'_'.$this->params['id'],
                    'user_id' => $userId,
                    'money' => '0.00',
                    'lockMoney' => '0.00',
                    'log_time' => $params['log_time'],
                );
                $puRepaid = new PushLenderLog($userId,$logInfo,$dealId,$paramsRepaid);

                $saveRepaidRes = $puRepaid->saveData();
                if (!$saveRepaidRes) {
                    throw new \Exception('saveRepaidData error!');
                }
            }
            $db->commit();

        } catch (\Exception $ex) {
            $db->rollback();
            Logger::error(__CLASS__.','.__FUNCTION__.','.__LINE__.', push lender log error: '.$ex->getMessage());
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}