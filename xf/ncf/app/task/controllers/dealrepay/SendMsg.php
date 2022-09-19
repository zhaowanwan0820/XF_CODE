<?php
namespace task\controllers\dealrepay;


use core\dao\deal\DealModel;
use core\dao\repay\DealRepayModel;
use core\enum\MsgBoxEnum;
use core\service\msgbox\MsgboxService;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\repay\DealRepayMsgService;

/**
 * 提前还款完成--给投资人发送回款站内信
 * Class SendNotify
 * @package task\controllers\dealcreate
 */

class SendMsg extends BaseAction {

    public function invoke() {
        return true;
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $repayId = $params['repayId'];

            if(!$dealId || !$repayId){
                throw new \Exception('参数错误');
            }

            $nextRepayId = $params['nextRepayId'];
            $deal = DealModel::instance()->getDealInfo($dealId,true);
            $repay = DealRepayModel::instance()->findViaSlave($repayId);


            $key = "MSG_BUS_DEAL_REPAY_MSG" . $dealId."_".$repayId;
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $res =  $redis->get($key);
            if($res){
                throw new \Exception('请勿重复处理');
            }else{
                $redis->set($key,1);
                $redis->expire($key, 3600);
            }

            if(!$deal || !$repay){
                throw new \Exception('未获取到标的或项目信息');
            }

            $content = "您好，您在".app_conf("SHOP_TITLE")."的融资项目“".$deal->name."”成功还款" . format_price($repay->repay_money, 0) . "元，";

            if($nextRepayId){
                $nextRepay =  DealRepayModel::instance()->findViaSlave($nextRepayId);
                $content .= "本融资项目的下个还款日为".to_date($nextRepay['repay_time'],"Y年m月d日")."，需要本息". format_price($nextRepay['repay_money'], 0) . "元。";
            } else{
                $content .= "本融资项目已还款完毕！";
            }

            //$ms = new MsgboxService();
            //$msRes = $ms->create($deal['user_id'], MsgBoxEnum::TYPE_REPAY_SUCCESS, '', $content);


            DealRepayMsgService::sendMsgBox($dealId, $repayId, $nextRepayId);
            Logger::info("Task dealrepay SendMsg dealId:{$dealId},repayId:{$repayId}");
        }catch (\Exception $ex){
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
