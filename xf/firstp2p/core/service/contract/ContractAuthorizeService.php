<?php
/**
 * P2P为第三方提供合同 eg.download
 */

namespace core\service\contract;

use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use libs\utils\Rpc;
use core\dao\FastDfsModel;
use core\dao\ContractFilesWithNumModel;
use libs\utils\Logger;

use core\service\ContractInvokerService;


class ContractAuthorizeService
{
    /**
     * 获取授权方指定日期标的列表
     * @param string $type 第三方标记
     * @param string $date 日期
     * @return array $result 返回结果集
     */
    public function getDealList($type,$date)
    {

        $typeId = DealLoanTypeModel::instance()->getIdByTag($type);
        $startTime = to_timespan($date." 00:00:00");
        $todayTime= to_timespan(date('Y-m-d 00:00:00'));
        if($startTime >= $todayTime){
            return false;
        }

        $sql = "SELECT id FROM `firstp2p_deal` where repay_start_time=".$startTime." AND deal_status IN (4,5) AND type_id = ".$typeId;
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        return $rows;

    }

    public function getContractByDealId($dealId,$type){

        $typeId = DealLoanTypeModel::instance()->getIdByTag($type);

        $dealId = intval($dealId);
        $rpc = new Rpc('contractRpc');

        $deal = DealModel::instance()->find($dealId);

        if($deal['type_id'] !== $typeId){
            //type ID 不匹配
            return false;
        }

        $method = "getContractByDealId";
        $contractRequest = new RequestGetContractByDealId();
        $contractRequest->setDealId(intval($dealId));

        $contractRequest->setSourceType($deal['deal_type']);
        $response = $rpc->go("\NCFGroup\Contract\Services\Contract",$method,$contractRequest);
        if($response->errCode == 0){
            $list = $response->list;
            return $list;
        }else{
            return false;
        }
    }

    public function downContract($num,$dealId,$type,$contractId){

        if(empty($num)||empty($dealId)||empty($type)||empty($contractId)){
            return false;
        }

        $dealId = intval($dealId);

        $typeId = DealLoanTypeModel::instance()->getIdByTag($type);

        $deal = DealModel::instance()->find($dealId);

        if($deal['type_id'] !== $typeId){
            //type ID 不匹配
            return false;
        }

        $contract_invoker = new ContractInvokerService();

        $contractInfo = $contract_invoker->getOneFetchedContract('viewer',$contractId,$dealId);
        if(empty($contractInfo)){
            return false;
        }

        if(in_array($deal['deal_status'],array(DealModel::$DEAL_STATUS['repaying'],DealModel::$DEAL_STATUS['repaid']))){
            $contract = $contract_invoker->download('filer', intval($contractId), $dealId, 1);
            exit();
        }else{
            return false;
        }

        //屏蔽时间戳合同获取逻辑

//        $ret = ContractFilesWithNumModel::instance()->getSignedByContractNum($num);
//
//        if(!empty($ret) && !empty($ret[0])) {
//            $fileInfo = array();
//            $fileInfo = $ret[0];
//
//            $dfs = new FastDfsModel();
//            $fileContent = $dfs->readTobuff($fileInfo['group_id'], $fileInfo['path']);
//            if (!empty($fileContent)) {
//                header("Content-type: application/octet-stream");
//                header('Content-Disposition: attachment; filename="' . $num . '.pdf"');
//                echo $fileContent;
//                exit;
//            } else {
//                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__," fast dfs readTobuff null!".$num." dfs error:".$dfs->getError())));
//                return false;
//            }
//        }else if($contractId > 0){
//            $contract_invoker = new ContractInvokerService();
//            $contract = $contract_invoker->download('filer', intval($contractId), $dealId, 1);
//            exit();
//        }
//        return false;
    }
}
