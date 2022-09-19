<?php
/**
 * DtP2pMappingStatsService.php
 * 多投p2p资产匹配统计服务
 * @date 2018-01-26
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */
namespace core\service\duotou;

use libs\utils\Rpc;
use core\dao\UserModel;
use core\service\DealService;
use NCFGroup\Protos\Duotou\RequestCommon;

class DtP2pMappingStatsService {

    /**
     * 底层资产统计匹配统计
     * @param $userId
     * @param $p2pDealId
     * @param int $pageNum
     * @param int $pageSize
     * @return mixed
     * @throws \Exception
     */
    public function getP2pDealMappingStats($userId,$p2pDealId,$pageNum=1,$pageSize=1000){
        $dealService = new DealService();

        //标不是自己的不让看
        $dealInfo = $dealService->getDeal($p2pDealId, true, false);
        if(empty($dealInfo) || ($dealInfo['user_id'] != $userId) || ($dealInfo['isDtb'] != 1)) {
            return false;
        }

        $mappingStats = array();
        $totalPage = 1;
        $totalNum = 0;

        $request = new RequestCommon();
        $vars = array(
            'p2pDealId' => $p2pDealId,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        );
        $request->setVars($vars);
        $rpc = new Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\P2pDeal', "getP2pDealMappingStats", $request);
        if(!empty($response) && !empty($response['data'])){
            $datas = $response['data']['list'];
            $totalNum = $response['data']['totalNum'];
            $totalPage = $response['data']['totalPage'];
            foreach ($datas as $data) {
                $deal = $dealService->getDeal($data['p2p_deal_id'], true, false);
                if(!empty($deal)) {
                    $data['mapping_date'] = $data['mapping_date'];
                    $data['mapping_date_show'] = date('Y-m-d',strtotime($data['mapping_date']));
//                    $dealInfo = $deal->getRow();
//                    $data['deal_info'] = $dealInfo;
                    $mappingStats[] = $data;
                }
            }
        }

        $return['list'] = $mappingStats;
        $return['totalNum'] = $totalNum;
        $return['totalPage'] = $totalPage;
        return $return;
    }

    /**
     * 底层资产统计匹配统计明细
     * @param $userId
     * @param $p2pDealId
     * @param $mappingDate
     * @param int $pageNum
     * @param int $pageSize
     * @return mixed
     * @throws \Exception
     */
    public function getP2pDealMappingStatsDetail($userId,$p2pDealId,$mappingDate,$pageNum=1,$pageSize=1000){
        $dealService = new DealService();
        $userModel = new UserModel();
        //标不是自己的不让看
        $dealInfo = $dealService->getDeal($p2pDealId, true, false);
        if(empty($dealInfo) || ($dealInfo['user_id'] != $userId) || ($dealInfo['isDtb'] != 1)) {
            return false;
        }

        $mappingStatsDetails = array();
        $totalPage = 1;
        $totalNum = 0;

        $request = new RequestCommon();
        $vars = array(
            'p2pDealId' => $p2pDealId,
            'mappingDate' => $mappingDate,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        );
        $request->setVars($vars);
        $rpc = new Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\P2pDeal', "getP2pDealMappingStatsDetail",$request);
        if(!empty($response) && !empty($response['data'])){
            $datas = $response['data']['list'];
            $totalNum = $response['data']['totalNum'];
            $totalPage = $response['data']['totalPage'];
            $dealService = new DealService();
            foreach ($datas as $data) {
                $deal = $dealService->getDeal($data['p2p_deal_id'], true, false);
                if(!empty($deal)) {
                    $user = $userModel->find($data['user_id']);
                    $data['user_name'] = $user['real_name'];
//                    $dealInfo = $deal->getRow();
//                    $data['deal_info'] = $dealInfo;
                    $mappingStatsDetails[] = $data;
                }
            }
        }

        $mappingStats = $response['data']['mappingStats'];
        $mappingStats['mappingDate'] = date('Y-m-d',strtotime($mappingStats['mappingDate']));
        $return['list'] = $mappingStatsDetails;
        $return['mappingStats'] = $mappingStats;
        $return['totalNum'] = $totalNum;
        $return['totalPage'] = $totalPage;
        return $return;
    }

    /**
     * 拼接多投统计标识
     * @param $dealList
     */
    public function appendDtStats($dealList) {
        $dealService = new DealService();
        foreach ($dealList as & $deal) {
            $deal['has_dt_stats'] = 0;
            //打了智多鑫tag的并且在处于满标或者还款中的标的
            if(in_array($deal['deal_status'],array(2,4)) && $dealService->isDealDT($deal['id'])) {
                $deal['has_dt_stats'] = 1;
            }
        }
        return $dealList;
    }
}
