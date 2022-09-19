<?php
/**
 * 同步网贷信息到存管
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;
use core\service\SupervisionDealService;
use core\dao\UserModel;
use core\dao\DealExtModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use core\dao\DealRepayModel;
use core\service\UserService;
use core\service\P2pDepositoryService;
use core\service\P2pDealReportService;
use core\dao\EnterpriseModel;
use core\service\P2pIdempotentService;
use NCFGroup\Common\Library\Idworker;

$ss = new SupervisionDealService();

$limit = isset($argv[1]) ? intval($argv[1]) : 100;
$specificIds = isset($argv[2]) ? $argv[2] : '';


$deals = getSyncDeal($limit,$specificIds);

foreach($deals as $dealInfo){
    try{
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealInfo['id'],false);
        $userModel = UserModel::instance()->find($dealInfo['user_id']);
        $userService = new UserService($userModel);
        $isEnterpriseUser = $userService->isEnterpriseUser();
        $userType = $isEnterpriseUser ? 2 : 1; // 用户类型

        if($userType == 2) { // 企业用户
            $enterpriseModel = new EnterpriseModel();
            $enterpriseInfo = $enterpriseModel->getEnterpriseInfoByUserID($dealInfo['user_id']);
            $borrName = $enterpriseInfo->company_name; // 借款方名称--公司名称
            $borrCertType = 'BLC';//$enterpriseInfo->credentials_type;
        }else{
            $borrName = $userModel->real_name; // 用户名
            $borrCertType = UserModel::$idCardType[$userModel->id_type];
        }

        $params = array(
            'bidId' => $dealInfo['id'],
            'name' => $dealInfo['name'],
            'amount' => bcmul($dealInfo['borrow_amount'],100),
            'userId' => $dealInfo['user_id'], //借款人P2P用户ID
            'bidRate' => bcdiv($dealInfo['rate'],100,2), //标的年利率 0.18
            'bidType' => '01', // 标的类型 01-信用 02-抵押 03-债权转让 99-其他
            'cycle' => ($dealInfo['loantype'] == 5) ? $dealInfo['repay_time'] : $dealInfo['repay_time'] * DealModel::DAY_OF_MONTH, // 借款周期(天数)
            'repaymentType' => getLoanType($dealInfo['loantype']), // 还款方式 01-一次还本付息 02-等额本金 03-等额本息 04-按期付息到期还本 99-其他
            'borrPurpose' => !empty($dealExt['use_info']) ?  $dealExt['use_info'] : '日常消费', // 借款用途
            'productType' => '08',
            'borrName' => $borrName, // 借款方名称
            'borrUserType' => $userType, // 借款人用户类型 1:个人|2:企业
            'borrCertType' => $borrCertType, // 借款方证件类型 身份证:IDC|港澳台身份证:GAT|军官证:MILIARY|护照:PASS_PORT|营业执照:BLC
            'borrCertNo' => $userModel->idno,    // 借款方证件号码 借款企业营业执照编号
        );

        $res = $ss->dealImport($params);
        if(!$res){
            throw  new \Exception("报备失败 params:".json_encode($params));
        }
    }catch (\Exception $ex){
        Logger::error("sync_p2p_info fail:".$ex->getMessage());
        continue;
    }
    Logger::info("sync_p2p_info deal_report succ dealId:".$dealInfo['id'].",params:".json_encode($params));
    syncDealLoad($dealInfo['id']);
}

function syncDealLoad($dealId){
    $deal = DealModel::instance()->find($dealId);

    try{
        $GLOBALS['db']->startTrans();

        $res1 = DealModel::instance()->updateReportStatus($deal['id'],1);
        if($res1 === false) {
            throw new \Exception("更新标的报备信息失败 dealId:".$deal['id']);
        }

        // 消费贷无代充值结构的要添加代充值借机构
        if(!$deal['generation_recharge_id'] && $deal['type_id'] == 29){
            $res2 = $deal->update(array('generation_recharge_id' => 399));
            if(!$res2){
                throw  new \Exception("代充值结构更新失败");
            }
        }

        // 更改还款方式为代充值
        if($deal['type_id'] == 29){
            $data = array("repay_type" => 3);
            $condition = " deal_id =".$dealId . " AND status = 0";
            $res3 = DealRepayModel::instance()->updateBy($data,$condition);
            if(!$res3){
                throw  new \Exception("repay_type 更改失败");
            }
        }


        $jobModel = new JobsModel();
        $jobModel->priority = JobsModel::PRIORITY_ORDERSPLIT_REQUEST;
        $function = '\core\service\P2pDealBidService::syncBidDataToBank';

        $loadList = getSyncLoad($dealId);
        foreach($loadList as $load){
            $orderId = isset($load['order_id']) ? $load['order_id'] : Idworker::instance()->getId();
            $loadId = $load['load_id'];
            $userId = $load['user_id'];
            $money = $load['money'];
            $isInsertIde = isset($load['order_id']) ? 0 : 1;

            $param = array('orderId' => $orderId,'dealId'=>$dealId ,'loadId' =>$loadId,'userId'=>$userId,'money'=>$money,'isInsertIde' => $isInsertIde);
            $res = $jobModel->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }
            Logger::info("sync_p2p_info succ loadId:".$loadId);
        }
        $GLOBALS['db']->commit();
    }catch (\Exception $ex){
        Logger::error("sync_p2p_info err:".$ex->getMessage());
        $GLOBALS['db']->rollback();
        return false;
    }
    return true;
}


// 网贷、还款中、未报备、有效，且标的类型不等于 产融贷(16)、公益标(25)
function getSyncDeal($limit,$specificIds){
    $cond = "";
    if($specificIds){
        $cond = " AND id IN ($specificIds)";
    }
    $sql = "SELECT * FROM `firstp2p_deal` WHERE  deal_type=0 AND report_status=0 $cond AND is_delete=0  LIMIT ".$limit;
    return Db::getInstance('firstp2p')->getAll($sql);
}

function getSyncLoad($dealId){
    $loadList = getSyncLoadFromIde($dealId);
    if(empty($loadList)){
        $sql = "SELECT id as load_id,user_id,deal_id,money FROM `firstp2p_deal_load` WHERE deal_id=".$dealId;
        $loadList = Db::getInstance('firstp2p')->getAll($sql);
    }
    return $loadList;
}

function getSyncLoadFromIde($dealId){
    $sql = "SELECT order_id,loan_user_id as user_id,deal_id,load_id,money FROM `firstp2p_supervision_idempotent` WHERE deal_id=".$dealId." AND type=1 AND result=1";
    $loadListMoved =  Db::getInstance('firstp2p_moved')->getAll($sql);
    $loadListNew = Db::getInstance('firstp2p')->getAll($sql);
    return array_merge($loadListMoved , $loadListNew);
}


function getLoanType($loanType) {
    $confLoanType = array(
        1 => '03',
        2 => '03',
        3 => '01',
        4 => '04',
        5 => '01',
        6 => '04',
        7 => '99',
        8 => '03',
        9 => '02',
        10 => '02',
    );
    return $confLoanType[$loanType];
}
