<?php
namespace core\service\msgbus;

use core\enum\MsgbusEnum;
use core\service\makeloans\MakeLoansMsgService;
use core\service\repay\DealPrepayMsgService;
use core\service\repay\DealRepayMsgService;
use NCFGroup\Common\Library\Msgbus;
use libs\utils\Logger;
use core\enum\JobsEnum;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;
use core\service\BaseService;


class MsgbusService extends BaseService {

    /**
     * 短信邮件等不要求强一致性的建议设置 $precise = false 在主业务成功后调用
     * 对于强一致性要求的 如：优惠码、红包等 $precise = true 并在事务中调用次方法
     * @param $topic  对应kafka topic
     * @param $message  消息体
     * @param bool $precise 默认true 要求强一致性，false 不要求强一致性
     * @return bool
     * @throws \Exception
     */
    public static function produce($topic, $message,$precise = true){
        if($precise === false){
            return self::msgJobs($topic,$message);
        }

        $jb = new JobsModel();
        $func = '\core\service\msgbus\MsgbusService::msgJobs';
        $params = array(
            'topic' => $topic,
            'message' => $message,
        );
        $jb->priority = JobsEnum::JOBS_PRIORITY_MSGBUS;
        $res = $jb->addJob($func,$params);
        if(!$res){
            throw new \Exception('add insert jobs fail');
        }
        return true;
    }

    /**
     * 以前由消息队列实现的方式改为jobs方式实现
     * @param $topic
     * @param $message
     * @throws \Exception
     */
    public static function handleMsg($topic,$message){
        Logger::info(__CLASS__ . "," .__FUNCTION__ ."," ."topic:".$topic.",message:".json_encode($message));
        switch ($topic) {
            case MsgbusEnum::TOPIC_DEAL_MAKE_LOANS:
                $s = new MakeLoansMsgService();
                $dealId = $message['dealId'];
                $s->sendSms($dealId);
                $s->sendMsg($dealId);
                break;
            case MsgbusEnum::TOPIC_DEAL_REPAY_FINISH:
                $dealId = $message['dealId'];
                $repayId= $message['repayId'];
                $nextRepayId = $message['nextRepayId'];
                DealRepayMsgService::sendSms($dealId,$repayId);
                DealRepayMsgService::sendMsgBox($dealId, $repayId, $nextRepayId);
                break;
            case MsgbusEnum::TOPIC_DEAL_PREPAY_FINISH:
                $dealId = $message['dealId'];
                $repayId= $message['repayId'];
                DealRepayMsgService::sendSms($dealId,$repayId);
                DealPrepayMsgService::sendMsgBox($dealId,$repayId);
                break;
            case MsgbusEnum::TOPIC_DEAL_FULL:
                $dealId = $message['dealId'];
                $deal = DealModel::instance()->find($dealId);
                send_full_failed_deal_message($deal,'full');
                break;
            default:
                break;
        }
        Logger::info(__CLASS__ . "," .__FUNCTION__ ."," ."topic:".$topic.",message:".json_encode($message).",finish!");
        return true;
    }

    public static function msgJobs($topic,$message) {
        Msgbus::instance()->produce($topic, $message);

        try {
            self::handleMsg($topic, $message);
        } catch (\Exception $e ){
            Logger::error(__CLASS__ . "," .__FUNCTION__ ."," ."topic:".$topic.",message:".json_encode($message));
            return true;
        }
        return true;
    }
}
