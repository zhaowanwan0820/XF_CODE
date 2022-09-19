<?php
/**
 * @desc  代扣还款批作业控制台 每15分钟跑一次
 * User: jinhaidong
 * Date: 2017-9-15 16:49:56
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\DealProjectService;

use core\dao\JobsModel;
use core\dao\DealLoanTypeModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealAgencyModel;
use libs\utils\Logger;
use core\service\DealService;
use core\service\P2pDealRepayService;
use core\service\P2pIdempotentService;
use NCFGroup\Common\Library\Idworker;

class BatchJobDkrepay {

    public function run() {
        $begin15 = time()-900; // 取最近15分钟
        $end15 = time();

        $repayJobDataArr = $this->HasNotFinishRepayBatchJob($begin15,$end15);
        if(!$repayJobDataArr) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"没有需要执行的代扣批作业startTime:{$begin15},endTime:{$end15}")));
            exit();
        }

        foreach($repayJobDataArr as $repayJobData){
            if($repayJobData['next_repay_time']) {
                $startTime = 0;
                $endTime  =  to_timespan(date('Y-m-d',$repayJobData['next_repay_time']));
            }else{
                $startTime = to_timespan(date("Y-m-d") . "00:00:00");
                $endTime  =  to_timespan(date("Y-m-d") . " 23:59:59");
            }

            if(!$repayJobData['deal_type']){
                continue;
            }

            $notRepays = $this->getNotRepay($startTime,$endTime,$repayJobData['deal_type']); // 取得今日待扣款列表
            if(empty($notRepays)) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"待扣款列表为空 startTime:{$startTime},endTime:{$endTime}")));
            }
        }
    }


    /**
     * 开始还款处理逻辑
     * @param $repayId
     * @param $dealId
     * @return bool
     */
    public function doRepay($repayId,$dealId,$repayType){
        $admInfo = array(
            'adm_name' => 'system',
            'adm_id' => 0,
        );

        $dealService = new DealService();
        $deal = new DealModel();
        $deal = $deal->find($dealId);

        try{
            $GLOBALS['db']->startTrans();

            $param = array('deal_repay_id' => $repayId, 'ignore_impose_money' => true, 'admin' => $admInfo,'negative'=>0,'repayType'=>$repayType, 'submitUid' => 0, 'auditType' => 3);

            $job_model = new JobsModel();
            if(!$dealService->isP2pPath($deal)) {
                // 异步处理还款
                $function = '\core\service\DealRepayService::repay';
                $job_model->priority = JobsModel::PRIORITY_DEAL_REPAY;
            }else{
                // p2p 还款逻辑
                $orderId = Idworker::instance()->getId();
                $function = '\core\service\P2pDealRepayService::dealRepayRequest';
                $param = array('orderId'=>$orderId,'dealRepayId'=>$repayId,'repayType'=>$repayType,'params'=>$param);
                $job_model->priority = JobsModel::PRIORITY_P2P_REPAY_REQUEST;
            }


            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }

            $res = $deal->changeRepayStatus(core\dao\DealModel::DURING_REPAY);
            if(!$res) {
                throw new \Exception("改变标的还款状态失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"deal_id:".$dealId,"repay_id:".$repayId)));
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"成功插入jobs并更改了还款状态","deal_id:".$dealId,"repay_id:".$repayId)));
        return true;
    }

    /**
     * 根据产品类别判断是否走借款人超级账户
     * @param object $deal deal 表返回的结果集
     * @return bool
     */
    private function isUseBorrowerAccount($deal)
    {
        $type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);
        // 是否在指定的几个 tag 中，指定 tag ：产融贷、房贷、应收贷、融艺贷、个人消费
        return in_array($type_tag, array(DealLoanTypeModel::TYPE_CR, DealLoanTypeModel::TYPE_FD, DealLoanTypeModel::TYPE_YSD, DealLoanTypeModel::TYPE_ARTD, DealLoanTypeModel::TYPE_GRXF));
    }

    /**
     * 是否有未完成的代扣批作业
     */
    private function HasNotFinishRepayBatchJob($startTime,$endTime) {
        $sql = "SELECT * FROM `firstp2p_batch_job`  WHERE job_status=1 AND job_type=5 AND job_interval_start <=$endTime AND job_interval_end >=$startTime";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $repayData = array();

        foreach($rows as $row) {
            $runTime = date('Y-m-d')." ".$row['job_run_time'];
            $runTime = strtotime($runTime);
            if($runTime >= $startTime && $runTime <=$endTime) {
                $repayData[] = $row;
            }else{
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"时间不符合规则","startTime:".$startTime,"endTime:".$endTime.",runTime:".$runTime)));
            }
        }
        return $repayData;
    }

    /**
     * 取得今日未完成的还款
     */
    private function getNotRepay($startTime,$endTime,$dealTypeId) {
        $sql = "SELECT t1.`id`,t1.`repay_time`, t1.`repay_money`, t1.`user_id`,t1.deal_id,t1.`repay_type`
                 FROM firstp2p_deal_repay t1
                 LEFT JOIN firstp2p_deal t2
                 ON t1.`deal_id` = t2.`id`
                 AND t1.repay_type=4 AND t1.`repay_time` <= {$endTime}  AND t1.repay_time >={$startTime} AND t1.`status` = 0
                 WHERE t2.type_id={$dealTypeId}  AND t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4 AND t2.`is_during_repay` = 0 ORDER by t2.`id` desc";

        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $user = new UserModel();
        $deal = new DealModel();
        $dealService = new DealService();
        $userService = new \core\service\UserService();
        $dealLoanType = new DealLoanTypeModel();
        $dealAgency = new DealAgencyModel();

        $dealProjectService = new DealProjectService();

        $userMoneyArr = array();
        foreach($rows as $row) {
            $dealInfo = $deal->find($row['deal_id']);
            $dealId = $row['deal_id'];
            $repayId = $row['id'];
            $isP2pPath = $dealService->isP2pPath($dealInfo);
            if(!$isP2pPath){
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"未报备报的不能进行代扣还款","deal_id:".$dealId,"repay_id:".$repayId)));
                continue;
            }
            //对同一借款人多笔借款订单，不可以同时发起多笔代扣还款
            $duringRepayCount = DealModel::instance()->getDuringRepayCount($row['user_id']);
            if($duringRepayCount >= 1){
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"该用户正在进行其他项目的还款,无法进行代扣还款","deal_id:".$dealId,"repay_id:".$repayId)));
                continue;
            }


            $userInfo = $user->find($dealInfo['user_id']);
            $userMoneyInfo = $userService->getMoneyInfo($userInfo);
            $userBankMoney = $userMoneyInfo['bank'];
            $needTransMoney = bcsub($row['repay_money'],$userBankMoney,2);
            if($needTransMoney > 0) {
                $orderId = Idworker::instance()->getId();
                //从银行卡中扣款
                //expireTime为关单时间，目前为调用时间往后延半个小时
                $param = array(
                    'orderId' => $orderId,
                    'userId' => $dealInfo['user_id'],
                    'dealId' => $row['deal_id'],
                    'repayId' => $row['id'],
                    'money' => $row['repay_money'],
                    'expireTime' => date('YmdHis', time() + 30 * 60),
                );

                try{
                    $GLOBALS['db']->startTrans();
                    $job_model = new JobsModel();
                    $function = '\core\service\P2pDealRepayService::dealDkRepayRequest';
                    $job_model->priority = JobsModel::PRIORITY_DEAL_REPAY;
                    $jobRes = $job_model->addJob($function, $param);
                    if ($jobRes === false) {
                        throw new \Exception("加入Jobs失败");
                    }
                    $dealInfo->changeRepayStatus(core\dao\DealModel::DURING_REPAY);
                    $GLOBALS['db']->commit();
                }catch (\Exception $ex){
                    $GLOBALS['db']->rollback();
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage()," orderId:{$orderId},dealId:".$row['deal_id'].",repayId:".$row['id'])));
                }
                continue;
            }
            $this->doRepay($row['id'],$row['deal_id'],$row['repay_type']);
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new BatchJobDkrepay();
$obj->run();
