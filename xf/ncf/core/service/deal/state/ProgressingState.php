<?php
/**
 * ProcessingState
 *
 * 进行中状态的操作
 * 1、上标参数覆盖
 * 2、收费后放款的标的 向咨询方发送手续费总金额的通知
 */

namespace core\service\deal\state;

use core\dao\deal\DealModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\dao\dealqueue\DealQueueModel;
use core\enum\DealEnum;
use core\enum\MsgbusEnum;
use core\service\deal\depository\ReportDepositoryService;
use core\service\deal\state\State;
use core\service\dealqueue\DealParamsConfService;
use core\service\deal\DealTagService;
use libs\utils\Logger;
use core\service\msgbus\MsgbusService;

class ProgressingState extends State{

    function work(StateManager $sm) {
        $deal = $sm->getDeal();
        $dealId = $deal->id;

        $isUpdate = ($deal->report_status == DealEnum::DEAL_REPORT_STATUS_YES) ? true : false;

        $startTrans = false;
        try {
            Logger::info(__CLASS__ . "," .__FUNCTION__ .  ",line:" . __LINE__. ", dealId:{$dealId}");

            //标的报备
            $s = new ReportDepositoryService();
            $s->dealReportRequest($deal,$isUpdate);


            $GLOBALS['db']->startTrans();
            $startTrans = true;

            // 由于的运营的人比较懒，3个月以上的标的加一个TAG
            $dealTagService = new DealTagService();
            $res = $dealTagService->autoAddTags($dealId, $deal);
            if (!$res) {
                throw new \Exception('自动打标Tag失败');
            }


            $dqm = DealQueueInfoModel::instance()->getDealQueueByDealId($dealId);
            if($dqm && $dqm->deal_params_conf_id){
                /** 标的在进行中状态时如果有上标队列 应用上标队列参数 */
                $params_conf_service = new DealParamsConfService();
                if (false === $params_conf_service->applyDealParamsConfByDealId($dqm->deal_params_conf_id, $dealId)) {
                    throw new \Exception('应用上标队列参数失败');
                }
            }

            $message = array('dealId'=>$dealId);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_PROGRESSING,$message);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . "," .__FUNCTION__ . ",line:" . __LINE__ ."," . $ex->getMessage()." dealId:{$dealId}");
            $startTrans && $GLOBALS['db']->rollback();
            $this->setErrMsg($ex->getMessage());
            return false;
        }
        return true;
    }
}
