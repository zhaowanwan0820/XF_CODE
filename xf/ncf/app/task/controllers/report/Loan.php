<?php
namespace task\controllers\report;
header("Content-type:text/html;charset=utf-8");
/**
 * 百行
 * 数据范围：网信普惠-零售类消费贷业务（个体经营贷不报），即仅推送个人类借款数据
 * 内容包含：借款人放款信息
 */
use core\dao\report\ReportRecordModel;
use core\service\report\ReportRecord;
use libs\utils\Logger;
use task\controllers\BaseAction;
use core\dao\report\ReportDealModel;
use core\dao\jobs\JobsModel;
use core\enum\JobsEnum;
use core\service\report\ReportService;

class Loan extends BaseAction{

    public function invoke(){

        $params = json_decode($this->getParams(), true);

        try {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", Task receive params " . json_encode($params));
            $dealId = $params['dealId'];

            $dealModel = new ReportDealModel();
            $dealInfo = $dealModel->getDealInfo($dealId);
            if (!$dealInfo){
                Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", 此标的未进入上报列表" . json_encode($params));
                return true;
            }
            if ($dealInfo['borrower_type'] != '01'){
                Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", 不需上报此类型用户数据" . json_encode($params));
                return true;
            }

            $recordModel = new ReportRecordModel();
            $reportRecord = new ReportRecord($dealId);
            $record = $reportRecord->collectData(3);  //1.标的信息  2.标的状态信息 3.百行放款  4.百行还款
            $record['record_id'] = $dealInfo['id'];

            $GLOBALS['db']->startTrans();
            $recordRes = $recordModel->saveData($record);
            if (!$recordRes) {
                throw new \PDOException('saveBaihangLoanRecord error!');
            }

            $jobs = new JobsModel();
            $jobs->priority = JobsEnum::PRIORITY_DATA_REPORT_BAIHANG_LOAN;
            $function = '\core\service\report\ReportService::reportBaihang';
            $param = array('id' => $recordRes);
            $startTime = time() + 60;
            $res = JobsModel::instance()->addJob($function, $param, $startTime);
            if ($res == false) {
                throw new \PDOException('报送百行放款数据jobs添加失败!');
            }
            $GLOBALS['db']->commit();
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", 放款状态添加成功 " . json_encode($params));
        } catch (\PDOException $ex) {
            $GLOBALS['db']->rollback();
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", 放款状态添加失败 " . json_encode($params));
            throw new \Exception('saveData error');
        } catch (\Exception $e) {
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            Logger::error($e->getMessage());
        }
    }
}