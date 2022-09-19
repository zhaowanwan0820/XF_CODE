<?php
/**
 * BatchJobService.php
*
* @date 2018-12-07
* @author gengkuan <gengkuan@ucfgroup.com>
*/

namespace core\service;

use core\service\DealService;
use core\service\DealProjectService;
use core\service\DealRepayAccountService;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\DealRepayModel;
use libs\utils\Logger;
use core\service\BwlistService;


/**
 * Class BatchJobService
 * @package core\service
 */

class BatchJobService extends BaseService {

    /**
     * 立即执行还款
     */
    public function addBathToJob($bath_id,$startTime,$endTime,$deal_type,$job_ids,$repay_mode,$admInfo) {
        \FP::import('libs.utils.logger');
        $notRepays = $this->getNotRepay($startTime,$endTime,$deal_type,$job_ids,$repay_mode,$admInfo);
        if(empty($notRepays)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"待还款列表为空(不含扣负) startTime:{$startTime},endTime:{$endTime},bathId:{$bath_id}")));
        }
                return true;
    }
    /**
     * 取得今日未完成的还款
     */
    private function getNotRepay($startTime,$endTime,$typeId,$deal_ids,$repay_mode,$admInfo) {
        $loanTypeCond = "";
        if(!empty($deal_ids)){
            $loanTypeCond = $loanTypeCond ." AND t1.deal_id  in ( {$deal_ids} )";
        }else{
            if($typeId <> 0){
                $loanTypeCond =  $loanTypeCond." AND t2.`type_id` = {$typeId}";
            }
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
            $userMoneyArr[$userInfo['id']]['normal'] = isset($userMoneyArr[$userInfo['id']]['normal']) ? bcsub($userMoneyArr[$userInfo['id']]['normal'],$row['repay_money'],2) : bcsub($userInfo['money'],$row['repay_money'],2);
            $compMoney = $userMoneyArr[$userInfo['id']]['normal'];
            if(!$isND && (bccomp($compMoney,'0.00') < 0)){//余额不足 不进行强制还款
                $userMoneyArr[$userInfo['id']]['normal'] = bcadd($compMoney,$row['repay_money'],2);
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"账户余额不足","uid:".$userInfo['id'].",compMoney:{$compMoney},repayMoney:".$row['repay_money'])));
                continue;
            }
            $this->doRepay($row['id'],$row['deal_id'],$repayAccountType,$admInfo);
        }
    }
    /**
     * 还款
     */
    public function doRepay($repayId,$dealId,$repayType,$admInfo) {
        $deal = new DealModel();
        $deal = $deal->find($dealId);
        try{
            $GLOBALS['db']->startTrans();
            $param = array('deal_repay_id' => $repayId, 'ignore_impose_money' => true, 'admin' => $admInfo,'negative'=>0,'repayType'=>$repayType, 'submitUid' => 0, 'auditType' => 3);
            $job_model = new JobsModel();
            // 异步处理还款
            $function = '\core\service\DealRepayService::repay';
            $job_model->priority = JobsModel::PRIORITY_DEAL_REPAY;
            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }
            $res = $deal->changeRepayStatus(DealModel::DURING_REPAY);
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
}
