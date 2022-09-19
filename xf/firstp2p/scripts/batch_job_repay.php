<?php
/**
 * @desc  还款批作业控制台 每15分钟跑一次
 * User: jinhaidong
 * Date: 2016/1/5 14:51
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\DealProjectService;

use core\dao\JobsModel;
use core\dao\DealLoanTypeModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\DealAgencyModel;
use libs\utils\Logger;
use core\service\DealService;
use core\service\DealRepayAccountService;
use NCFGroup\Common\Library\Idworker;
use core\service\BwlistService;

class BatchJobRepay {

    public function run($typeId = false) {
        $begin15 = time()-900; // 取最近15分钟
        $end15 = time();

        $repayJobDatas = $this->HasNotFinishRepayBatchJob($begin15,$end15,$typeId);

        if(!$repayJobDatas) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"没有需要执行的还款批作业startTime:{$begin15},endTime:{$end15}")));
            exit();
        }


        foreach($repayJobDatas as $repayJobData){
            if($repayJobData['next_repay_time']) {
                $startTime = 0;
                $endTime  =  to_timespan(date('Y-m-d',$repayJobData['next_repay_time']));
            }else{
                $startTime = to_timespan(date("Y-m-d") . "00:00:00");
                $endTime  =  to_timespan(date("Y-m-d") . " 23:59:59");
            }

            $notRepays = $this->getNotRepay($startTime,$endTime,$repayJobData['deal_type'],$repayJobData['repay_mode']); // 取得今日待还款列表
            if(empty($notRepays)) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"待还款列表为空(不含扣负) startTime:{$startTime},endTime:{$endTime}")));
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
     * 是否有未完成的批作业
     */
    private function HasNotFinishRepayBatchJob($startTime,$endTime,$typeId,$repay_mode) {
        $sql = "SELECT * FROM `firstp2p_batch_job`  WHERE job_status=1 AND job_type=1 AND job_ids is null AND is_right_now !=1   AND job_interval_start <=$endTime AND job_interval_end >=$startTime";
        if($typeId){
            $sql.= " AND deal_type={$typeId}";
        }

        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $repayData = array();

        foreach($rows as $row) {
            $runTime = date('Y-m-d')." ".$row['job_run_time'];
            $runTime = strtotime($runTime);
            if($runTime >= $startTime && $runTime <=$endTime) {
                $repayData[] = $row;
            }
        }
        return $repayData;
    }

    /**
     * 取得今日未完成的还款
     */
    private function getNotRepay($startTime,$endTime,$typeId) {

        $loanTypeCond = "";

        if($typeId <> 0){
            $loanTypeCond = " AND t2.`type_id` = {$typeId}";
        }


        $where_contract_ids = '';
        if($repay_mode){
            $contract_ids = BwlistService::getValueList(DealRepayModel::DEAL_REPAY_MODE_WHITE_TYPE_KEY);
            if (!empty($contract_ids)){
                foreach($contract_ids as $con_id){
                    $deal_repay_mode_contract_white[$con_id['value']] = $con_id['value'];
                }
                if (!empty($deal_repay_mode_contract_white)){
                    $where_contract_ids = implode(',',$deal_repay_mode_contract_white);
                }
                // 节前
                if ($repay_mode == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_BEFORE){
                    $loanTypeCond .= " and t2.`contract_tpl_type` not in ({$where_contract_ids}) ";
                }
                //节后
                if ($repay_mode == DealRepayModel::DEAL_REPAY_MODE_HOLIDAY_AFTER){
                    $loanTypeCond .= " and t2.`contract_tpl_type` in ({$where_contract_ids}) ";
                }
            }
        }


        $sql = "SELECT t1.`id`,t1.`repay_time`, t1.`repay_money`, t1.`user_id`,t1.deal_id
                 FROM firstp2p_deal_repay t1
                 LEFT JOIN firstp2p_deal t2
                 ON t1.`deal_id` = t2.`id`
                 AND t1.`repay_time` <= {$endTime}  AND t1.repay_time >={$startTime} AND t1.`status` = 0 WHERE t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4 AND t2.`is_during_repay` = 0".$loanTypeCond." ORDER by t2.`id` desc";

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
            //如果标的为消费分期或消费贷,则使用代垫机构关联用户计算用户余额是否足够偿还,否则使用借款用户账户计算
            $dealInfo = $deal->find($row['deal_id']);

            //过滤专享1.75标的
            if($dealProjectService->isProjectEntrustZX($dealInfo['project_id'])){
                continue;
            }
            if($dealProjectService->isProjectYJ175($dealInfo['project_id'])){
                continue;
            }

            if($dealService->isDealPartRepay($row['deal_id'],$row['id'])){
                continue;
            }

            $isP2pPath = $dealService->isP2pPath($dealInfo);
            $isND = $dealService->isDealND($row['deal_id']);

            $repayInfo = DealRepayModel::instance()->find($row['id']);
            $repayAccountType = DealRepayAccountService::instance($dealInfo)->setRepay($repayInfo)->getRepayAccount();
            if($isND) { //农担贷借款人还款
                $repayAccountType = 0;
            }

            if($repayAccountType === false){
                Logger::error(__CLASS__ . ",". __FUNCTION__ .",标的唯一标识不存在 dealId:".$row['deal_id']);
                continue;
            }

            $userId = $dealService->getRepayUserAccount($row['deal_id'],$repayAccountType);
            $userInfo = $user->find($userId);
            if(!$userInfo){
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"用户信息不存在!","deal_id:".$row['deal_id'],"repay_id:".$row['id'],"userId:".$userId)));
                continue;
            }

            // 进行余额预扣减
            if($isP2pPath){
                $userMoneyInfo = $userService->getMoneyInfo($userInfo);
                $userMoneyArr[$userInfo['id']]['p2p'] = isset($userMoneyArr[$userInfo['id']]['p2p']) ? bcsub($userMoneyArr[$userInfo['id']]['p2p'],$row['repay_money'],2) : bcsub($userMoneyInfo['bank'],$row['repay_money'],2);
            }else{
                $userMoneyArr[$userInfo['id']]['normal'] = isset($userMoneyArr[$userInfo['id']]['normal']) ? bcsub($userMoneyArr[$userInfo['id']]['normal'],$row['repay_money'],2) : bcsub($userInfo['money'],$row['repay_money'],2);
            }

            $compMoney = $isP2pPath ? $userMoneyArr[$userInfo['id']]['p2p']:$userMoneyArr[$userInfo['id']]['normal'];

            if(!$isND && (bccomp($compMoney,'0.00') < 0)){//余额不足 不进行强制还款
                if($isP2pPath){
                    $userMoneyArr[$userInfo['id']]['p2p'] = bcadd($compMoney,$row['repay_money'],2);
                }else{
                    $userMoneyArr[$userInfo['id']]['normal'] = bcadd($compMoney,$row['repay_money'],2);
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"账户余额不足","uid:".$userInfo['id'].",compMoney:{$compMoney},repayMoney:".$row['repay_money'])));
                continue;
            }
            $this->doRepay($row['id'],$row['deal_id'],$repayAccountType);
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$typeId = isset($argv[1]) ? intval($argv[1]) : false;

$obj = new BatchJobRepay();
$obj->run($typeId);
