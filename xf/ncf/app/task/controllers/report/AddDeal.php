<?php
namespace task\controllers\report;

header("Content-type:text/html;charset=utf-8");
use core\dao\report\ReportRecordModel;
use core\enum\MsgbusEnum;
use core\service\report\ReportDeal;
use core\service\msgbus\MsgbusService;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\dao\report\ReportDealModel;
use core\service\report\ReportUser;
use core\service\report\ReportCompanyUser;
use core\service\report\ReportRecord;
use core\dao\report\ReportUserModel;
use core\dao\report\ReportCompanyUserModel;
use core\dao\jobs\JobsModel;
use core\enum\JobsEnum;

class AddDeal extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            if (!$dealId){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 标的为空 ".json_encode($params));
                return true;
            }
            $dealModeal = new ReportDealModel();
            if($dealModeal->hasDeal($dealId) === true){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 此标的数据已进入上报列表 ".json_encode($params));
                return true;
            }
            $reportDeal = new ReportDeal($dealId);
            $dealInfo = $reportDeal->collectData();

            $borrowerType = $dealInfo['borrower_type'];
            if ($borrowerType == '01'){     //0为自然人
                $userModel = new ReportUserModel();
                $reportUser = new ReportUser($dealId);

            }else{
                $userModel = new ReportCompanyUserModel();
                $reportUser = new ReportCompanyUser($dealId);

            }
            $userInfo = $reportUser->collectData();

            $recordModel = new ReportRecordModel();
            $reportRecord = new ReportRecord($dealId);
            $record = $reportRecord->collectData(1);  //1.标的信息  2.标的状态信息

            //开始事务
            $GLOBALS['db']->startTrans();
            $dealRes = $dealModeal->saveData($dealInfo);
            if(!$dealRes){
                throw new \PDOException('saveDeal error!');
            }
            $userRes = $userModel->saveData($userInfo);
            if(!$userRes){
                throw new \PDOException('saveUser error!');
            }
            $record['record_id'] = $dealRes;
            $recordRes = $recordModel->saveData($record);
            if(!$recordRes){
                throw new \PDOException('saveRecord error!');
            }


            $jobs = new JobsModel();
            $jobs->priority = JobsEnum::PRIORITY_DATA_REPORT_IFA_DEAL;
            $function = '\core\service\report\ReportService::reportIfaDeal';
            $param = array('id' => $recordRes);
            $startTime = time() - 28800 + 60;
            $res = JobsModel::instance()->addJob($function, $param, $startTime);
            if ($res == false){
                throw new \PDOException('报送ifa deal数据jobs添加失败!');
            }

            $GLOBALS['db']->commit();
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 标的添加成功 ".json_encode($params));
            $message = array('dealId'=>$dealId);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_REPORT_DEAL_PROGRESSING,$message);
        }catch(\PDOException $ex){
            $GLOBALS['db']->rollback();
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", 标的添加失败 ".json_encode($params));
            throw new \Exception('saveData error');
        }catch(\Exception $e){
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            Logger::error($e->getMessage());
        }
    }




}