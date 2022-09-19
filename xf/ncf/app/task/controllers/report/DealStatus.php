<?php
namespace task\controllers\report;

use core\dao\report\ReportDealStatusModel;
use core\dao\report\ReportRecordModel;
use core\service\report\ReportDealStatus;
use core\service\report\ReportRecord;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\dao\report\ReportDealModel;
use core\enum\MsgbusEnum;
use core\dao\jobs\JobsModel;
use core\enum\JobsEnum;
use core\dao\deal\DealModel;

class DealStatus extends BaseAction {

    public function invoke() {

        $params = json_decode($this->getParams(),true);
        $topic = $this->getTopic();
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            $dealId = $params['dealId'];

            $dealModel = new ReportDealModel();
            if ($dealId && ($dealModel->hasDeal($dealId) == false)){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 此标的未进入上报列表，不能进行标的状态操作 ".json_encode($params));
                return true;
            }
            $firstp2pStatus = $this->getFirstp2pStatus($topic);
            $dealInfo = DealModel::instance()->getDealInfo($dealId);
            $update = array(
                'current_status'=> $firstp2pStatus,
                'end_time' => ($dealInfo->success_time==0) ? 0 : $dealInfo->success_time+28800,
                'repay_start_time' => ($dealInfo->repay_start_time==0) ? 0 : $dealInfo->repay_start_time+28800,
            );
            $dealRes =  $dealModel->updateBy($update,"deal_id=".$dealId);
            if (!$dealRes){
                throw new \Exception('saveDealFirstp2pStatus error');
            }

            $status = $this->getDealStatus($topic);
            $statusModel = new ReportDealStatusModel();
            if($statusModel->hasDealOrStatus($dealId,$status) === true){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 此标的状态数据已进入上报列表 ".json_encode($params));
                return true;
            }

            $reportStatus = new ReportDealStatus($dealId,$status);
            $dealStatus = $reportStatus->collectData();

            $recordModel = new ReportRecordModel();
            $reportRecord = new ReportRecord($dealId);
            $record = $reportRecord->collectData(2);  //1.标的信息  2.标的状态信息

            //开始事务
            $GLOBALS['db']->startTrans();
            $statusRes = $statusModel->saveData($dealStatus);
            if(!$statusRes){
                throw new \PDOException('saveDealStatus error!');
            }
            $record['record_id'] = $statusRes;
            $recordRes = $recordModel->saveData($record);
            if(!$recordRes){
                throw new \PDOException('saveStatusRecord error!');
            }

            $jobs = new JobsModel();
            $jobs->priority = JobsEnum::PRIORITY_DATA_REPORT_IFA_DEAL_STATUS;
            $function = '\core\service\report\ReportService::reportIfaDeal';
            $param = array('id' => $recordRes);
            $startTime = time() - 28800 + 120;
            $res = JobsModel::instance()->addJob($function, $param, $startTime);
            if ($res == false){
                throw new \PDOException('报送ifa deal status数据jobs添加失败!');
            }

            $GLOBALS['db']->commit();
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 标的状态添加成功 ".json_encode($params));
        }catch(\PDOException $ex){
            $GLOBALS['db']->rollback();
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 标的状态添加失败 ".json_encode($params));
            throw new \Exception('saveData error');
        }catch(\Exception $e){
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            Logger::error($e->getMessage());
        }
    }

    private function getDealStatus($topic){
        // 0待等材料，1进行中，2满标，3流标，4还款中，5已还清
        //报送要求：06、07 状态接收后产品自动 下线不展示。若项目状态为 03、04、05 不能传 送 06、07 状态。01 募集前 02 募集中03 还款中04 正常还款已结清05 逾期还款已结清06 流标07 项目取消08 逾期未结清 说明:若提前还款已结清的情况，也报送 04 正 常还款已结清
        $s = array(
            MsgbusEnum::TOPIC_DEAL_REPORT_DEAL_PROGRESSING => '02',
            MsgbusEnum::TOPIC_DEAL_FULL => '02',
            MsgbusEnum::TOPIC_DEAL_FAIL => '06',
            MsgbusEnum::TOPIC_DEAL_MAKE_LOANS => '03',
            MsgbusEnum::TOPIC_DEAL_REPAY_OVER => '04',
            MsgbusEnum::TOPIC_DEAL_PREPAY_FINISH => '04'     //标的提前还款完成消息

        );
        return $s[$topic];
    }
    private function getFirstp2pStatus($topic){
        // 0待等材料，1进行中，2满标，3流标，4还款中，5已还清
        $s = array(
            MsgbusEnum::TOPIC_DEAL_REPORT_DEAL_PROGRESSING => 1,
            MsgbusEnum::TOPIC_DEAL_FULL => 2,
            MsgbusEnum::TOPIC_DEAL_FAIL => 3,
            MsgbusEnum::TOPIC_DEAL_MAKE_LOANS => 4,
            MsgbusEnum::TOPIC_DEAL_REPAY_OVER => 5,
            MsgbusEnum::TOPIC_DEAL_PREPAY_FINISH => 5,
        );
        return $s[$topic];
    }

}