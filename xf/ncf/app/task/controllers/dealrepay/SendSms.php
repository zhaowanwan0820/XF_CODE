<?php
namespace task\controllers\dealrepay;

use core\dao\deal\DealModel;
use core\dao\repay\DealRepayModel;
use core\service\user\UserService;
use libs\sms\SmsServer;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\repay\DealRepayMsgService;


/**
 * 还款完成--回款短信整合
 * Class SendNotify
 * @package task\controllers\dealcreate
 */
class SendSms extends BaseAction {

    public function invoke() {
        return true;
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $repayId = $params['repayId'];
            $nextRepayId = $params['nextRepayId'];
            if(!$dealId || !$repayId){
                throw new \Exception('参数错误');
            }

            // TODO 临时方案解决,上线观察下
            $key = "MSG_BUS_DEAL_REPAY_SMS" . $dealId."_".$repayId;
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $res =  $redis->get($key);
            if($res){
                throw new \Exception('请勿重复处理');
            }else{
                $redis->set($key,1);
                $redis->expire($key, 3600);
            }

            $deal = DealModel::instance()->getDealInfo($dealId,true);
            $repay = DealRepayModel::instance()->findViaSlave($repayId);

            $user = UserService::getUserById($deal['user_id']);

            //短信通知 普惠不给借款人发短信
//            if(app_conf("SMS_ON")==1 && app_conf('SMS_SEND_REPAY')==1){
//                $notice = array(
//                    "site_name" => app_conf('SHOP_TITLE'),
//                    "real_name" => $user['real_name'],
//                    "repay"     => $repay->repay_money,
//                );
//                $smsRes =  SmsServer::instance()->send($user['mobile'], 'TPL_DEAL_LOAD_REPAY_SMS', $notice);
//                if(!$smsRes){
//                    throw new \Exception('短信通知借款人失败 mobile:'.$user['mobile']);
//                }
//            }


            DealRepayMsgService::sendSms($dealId,$repayId);
            Logger::info("Task dealrepay SendSms dealId:{$dealId},repayId:{$repayId}");

        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
