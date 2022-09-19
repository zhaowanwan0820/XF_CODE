<?php
/**
 * BatchJobService.php
*
* @date 2018-12-07
* @author gengkuan <gengkuan@ucfgroup.com>
*/

namespace core\service\bathjob;

use core\service\deal\DealService;
use core\dao\deal\DealModel;
use core\dao\repay\DealRepayModel;
use libs\utils\Logger;
use core\service\repay\DealRepayService;
use core\service\account\AccountService;
use core\service\BaseService;
use core\service\deal\DealRepayAccountService;
use core\enum\DealRepayEnum;
use core\enum\BatchJobEnum;

/**
 * Class BatchJobService
 * @package core\service
 */

class BatchJobService extends BaseService {

    /**
     * 立即执行还款
     */
    public function addBathToJob($bath_id,$startTime,$endTime,$deal_type,$job_ids,$admInfo,$holiday_repay_type=0) {
        $notRepays = $this->getNotRepay($startTime,$endTime,$deal_type,$job_ids,$admInfo,$holiday_repay_type,false);
        if(empty($notRepays)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__," 待还款列表为空(不含扣负) startTime:{$startTime},endTime:{$endTime},bathId:{$bath_id}")));
        }
        return true;
    }
    /**
     * 取得今日未完成的还款
     */
    private function getNotRepay($startTime,$endTime,$typeId,$deal_ids,$admInfo,$holiday_repay_type=0,$checkMoney=true) {
        $holiday_repay_type = intval($holiday_repay_type);
        $loanTypeCond = "";
        $holidayRepayTypeCond="";
        if(!empty($deal_ids)){
            $loanTypeCond = $loanTypeCond ." AND t1.deal_id  in ( {$deal_ids} )";
        }else{
            if($typeId <> 0){
                $loanTypeCond =  $loanTypeCond." AND t2.`type_id` = {$typeId}";
            }
            if($holiday_repay_type != 0) {
                $holidayRepayTypeCond =  " AND t2.`holiday_repay_type` = {$holiday_repay_type} ";
            }
        }


        $sql = "SELECT t1.`id`,t1.`repay_time`, t1.`repay_money`, t1.`user_id`,t1.deal_id
                 FROM firstp2p_deal_repay t1
                 LEFT JOIN firstp2p_deal t2
                 ON t1.`deal_id` = t2.`id`
                 AND t1.`repay_time` <= {$endTime}  AND t1.repay_time >={$startTime} AND t1.`status` = 0 WHERE t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4 AND t2.`is_during_repay` = 0".$loanTypeCond.$holidayRepayTypeCond . " ORDER by t2.`id` desc";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $dealService = new DealService();
        $userMoneyArr = array();
        foreach($rows as $row) {
            //如果标的为消费分期或消费贷,则使用代垫机构关联用户计算用户余额是否足够偿还,否则使用借款用户账户计算
            $deal = DealModel::instance()->find($row['deal_id']);

            $repayInfo = DealRepayModel::instance()->find($row['id']);
            $repayAccountType = DealRepayAccountService::instance($deal)->setRepay($repayInfo)->getRepayAccount();
            $isND = $dealService->isDealND($row['deal_id']);
            if($isND) { //农担贷借款人还款
                $repayAccountType = DealRepayEnum::DEAL_REPAY_TYPE_PART_SELF;
            }
            if($repayAccountType === false){
                Logger::error(__CLASS__ . ",". __FUNCTION__ .",标的唯一标识不存在 dealId:".$row['deal_id']);
                continue;
            }
            $userId = $dealService->getRepayUserAccount($row['deal_id'],$repayAccountType);
            // 进行余额预扣减
            $accountType = $dealService->getRepayAccountType($repayAccountType);
            $userMoneyInfo = AccountService::getAccountMoney($userId, $accountType);

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"余额获取","uid:".$userId.",userMoney:".$userMoneyInfo['money'])));

            if(!isset($userMoneyArr[$userId])){
                $userMoneyArr[$userId] = $userMoneyInfo['money'];
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"余额获取","uid:".$userId.",userMoney:{$userMoneyArr[$userId]}")));

            $userMoneyArr[$userId] =  bcsub($userMoneyArr[$userId],$row['repay_money'],2);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"余额获取","uid:".$userId.",userMoney:{$userMoneyArr[$userId]},repayMoney:".$row['repay_money'])));

            $compMoney = $userMoneyArr[$userId];
            if(!$isND && (bccomp($compMoney,'0.00') < 0)){//余额不足 不进行强制还款
                $userMoneyArr[$userId] = bcadd($compMoney,$row['repay_money'],2);
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"账户余额不足","uid:".$userId.",compMoney:{$compMoney},repayMoney:".$row['repay_money'])));
                if($checkMoney){
                    continue;
                }
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"余额获取","uid:".$userId.",userMoney:{$userMoneyArr[$userId]}")));
            $rs = new DealRepayService();
            $rs->doRepay($row['deal_id'],$row['id'],$repayAccountType);
        }
    }
}
