<?php
/**
 * P2P为第三方提供合同 eg.download
 */

namespace core\service\contract;

use libs\db\Db;
use libs\utils\Logger;
use libs\fastdfs\FastDfsService;

use core\enum\DealEnum;
use core\enum\contract\ContractServiceEnum;

use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealModel;
use core\dao\contract\ContractFilesWithNumModel;

use core\service\contract\ContractInvokerService;
use core\service\deal\DealService;

class ContractAuthorizeService{
    /**
     * 获取授权方指定日期标的列表
     * @param string $type 产品类别  type_tag
     * @param string $date 日期
     * @return array $result 返回结果集
     */
    public function getDealList($typeArr,$date){
        $typeIdArr = DealLoanTypeModel::instance()->getIdListByTag($typeArr);
        $startTime = to_timespan($date." 00:00:00");
        $todayTime= to_timespan(date('Y-m-d 00:00:00'));
        if($startTime >= $todayTime){
            return false;
        }

        $sql = "SELECT id FROM `firstp2p_deal` where repay_start_time=".$startTime." AND deal_status IN (4,5) AND type_id IN (".implode(',',$typeIdArr) .")";
        $deals = DealModel::instance()->findAllBySqlViaSlave($sql,true);
        $movedDeals = Db::getInstance(DealEnum::DEAL_MOVED_DB_NAME, 'slave')->getAll($sql);
        return array_merge($deals,$movedDeals);

    }

    /**
     * 获取标的的合同记录(所有的合同记录 不包括合同模板)
     * @param string $type 产品类别  type_tag
     * @param string $date 日期
     * @return array $result 返回结果集
     */
    public function getContractByDealId($dealId,$typeIdArr){
        $typeIdArr = DealLoanTypeModel::instance()->getIdListByTag($typeIdArr);
        $dealId = intval($dealId);
        $deal = (new DealService())->getDealInfo($dealId);
        if(!in_array($deal['type_id'], $typeIdArr)){
            //type ID 不匹配
            return false;
        }

        $response = ContractService::getContractByDealId(intval($dealId),null,null,ContractServiceEnum::SOURCE_TYPE_PH);
        if(empty($response)){
            return false;
        }
        return $response;
    }

    /**
     * 获取授权方指定日期标的列表
     * @param string $num 合同编号
     * @param string $dealId 标的id
     * @param string $type 产品类别  type_tag
     * @param string $contractId 合同id
     * @return array $result 返回结果集
     */
    public function downContract($num,$dealId,$typeArr,$contractId){
        if(empty($num)||empty($dealId)||empty($typeArr)||empty($contractId)){
            return false;
        }
        $dealId = intval($dealId);
        $typeIdArr = DealLoanTypeModel::instance()->getIdListByTag($typeArr);
        $deal = (new DealService())->getDealInfo($dealId);
        if(!in_array($deal['type_id'], $typeIdArr)){
            //type ID 不匹配
            return false;
        }

        $contract_invoker = new ContractInvokerService();
        $contractInfo = $contract_invoker->getOneFetchedContract('viewer',$contractId,$dealId);
        if(empty($contractInfo)){
            return false;
        }

        if(in_array($deal['deal_status'], array(DealEnum::$DEAL_STATUS['repaying'],DealEnum::$DEAL_STATUS['repaid']))){
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
