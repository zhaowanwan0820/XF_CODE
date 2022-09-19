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

$id = isset($argv[1]) ? intval($argv[1]) : 0;
$limit = isset($argv[2]) ? intval($argv[2]) : 100;


if(isset($argv[3])){
    $dealArr = explode(",",$argv[3]);
    $deals = array();
    foreach($dealArr as $dealId){
        $deals[] = array("id" => $dealId);
    }
}else{
    $deals = getSyncDeal($id,$limit);
}


$jobModel = new JobsModel();
$jobModel->priority = JobsModel::PRIORITY_ORDERSPLIT_REQUEST;
$function = '\core\service\P2pDealBidService::syncDealCreditImport';

foreach($deals as $deal){
    //$syncLoadInfo = getSyncLoadFromZDX($deal['id']);
    $syncLoadInfo = getSyncLoad($deal['id']);

    if(empty($syncLoadInfo)){
        continue;
    }
    try {
        $GLOBALS['db']->startTrans();
        foreach($syncLoadInfo as $userId => $info){
            $params = array('userId' => $userId,'bidId'=>$info['bidId'] ,'sumAmount' =>$info['sumAmount'],'leftAmount'=>$info['leftAmount']);
            $res = $jobModel->addJob($function, $params);
            if ($res === false) {
                throw new \Exception("加入jobs失败 params:".json_encode($params));
            }
            Logger::info("dealCreditImport succ params:".json_encode($params));
        }
        $GLOBALS['db']->commit();
    }catch (\Exception $ex){
        Logger::error("dealCreditImport fail:".$ex->getMessage());
        $GLOBALS['db']->rollback();
        return false;
    }
    Logger::info("dealCreditImport succ 当前ID:".$deal['id']);
}

// 网贷、还款中、未报备、有效，且标的类型不等于 产融贷(16)、公益标(25)
function getSyncDeal($id,$limit){
    $sql = "SELECT id FROM `firstp2p_deal` WHERE id IN (SELECT deal_id FROM `firstp2p_deal_tag` WHERE tag_id=42) AND deal_status in (1,2,4,5) limit ".$limit;
    return Db::getInstance('firstp2p')->getAll($sql);
}

function getSyncLoad($dealId){
    $sql = "SELECT loan_user_id,deal_id,sum(money) as money,status FROM `firstp2p_deal_loan_repay` WHERE deal_id=".$dealId."   AND type=1 GROUP BY loan_user_id,status";
    $arr = Db::getInstance('firstp2p')->getAll($sql);
    $data = array();
    foreach($arr as $ar) {
        if(!isset($data[$ar['loan_user_id']]['sumAmount'])){
            $data[$ar['loan_user_id']]['sumAmount'] = 0;
        }
        $data[$ar['loan_user_id']]['userId'] = $ar['loan_user_id'];
        $data[$ar['loan_user_id']]['bidId'] = $ar['deal_id'];
        $data[$ar['loan_user_id']]['sumAmount'] = bcadd($data[$ar['loan_user_id']]['sumAmount'],$ar['money'],2);
        if ($ar['status'] == 0) {
            $data[$ar['loan_user_id']]['leftAmount'] = $ar['money'];
        }
    }
    return $data;
}

function getSyncLoadFromZDX($dealId){
    $request = new \NCFGroup\Protos\Duotou\RequestCommon();
    $rpc = new \libs\utils\Rpc('duotouRpc');
    $request->setVars(array("p2pDealId"=>$dealId));

    $response = $rpc->go('NCFGroup\Duotou\Services\LoanMapping','getP2pDealRealLoanInfos',$request);
    $data = array();
    if(!$response || empty($response['data'])){
        Logger::error("dealCreditImport fail:从智多鑫获取数据失败 dealId:".$dealId);
    }else{
        foreach($response['data'] as $k=>$v){
            $data[$v['user_id']]['userId'] = $v['user_id'];
            $data[$v['user_id']]['bidId'] = $v['p2p_deal_id'];
            $data[$v['user_id']]['sumAmount'] = $v['money'];
            $data[$v['user_id']]['leftAmount'] = $v['remain_money'];
        }
    }
    return $data;
}