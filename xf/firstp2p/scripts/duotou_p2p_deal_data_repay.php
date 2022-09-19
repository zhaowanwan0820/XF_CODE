<?php

/**
 * 多投p2p数据还款，操作危险，认真核对后执行
 * /apps/product/php/bin/php duotou_p2p_deal_data_repay.php
 * @author wangchuanlu
 * @date 2017-07-05
*/

require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\dao\DealRepayModel;
use core\service\DealService;
use core\service\DtDealService;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class DuotouP2pDealDataRepay {

    public function run($dtProjectId,$repayUserId) {
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'projectId' => $dtProjectId , //智多鑫项目id
        );
        $request->setVars($vars);
        $rpc = new \libs\utils\Rpc('duotouRpc');
        //根据项目id获取需要清盘的还款信息
        $response = $rpc->go("\NCFGroup\Duotou\Services\DealRepay", "getLiquidationP2pDealRepayInfo", $request);
        if(!$response) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "网络错误")));
            return false;
        }
        if($response['errCode'] != 0) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail errCode:".$response['errCode']." errMsg:".$response['errMsg'])));
            return false;
        }

        $dealRepayModel = new DealRepayModel();
        $dtdealService = new DtDealService();
        $p2pDealList = $response['data'];
        $totalDealNum = count($p2pDealList);
        $succDealNum = 0;
        foreach ($p2pDealList as $p2pDeal) {
            $deal_service = new DealService();
            $deal = $deal_service->getDeal($p2pDeal['deal_id'], true, false);
            if(!$deal) { //标的不存在
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, " p2p标的不存在！")));
                continue ;
            }

            $nextRepayInfo = $dealRepayModel->getNextRepayByDealId($p2pDeal['deal_id']);
            if(empty($nextRepayInfo)) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, " p2p标的的下期还款信息不存在！deal_id: " . $p2pDeal['deal_id'])));
                continue ;
            }
            $request = new RequestCommon();
            $vars = array(
                'p2pDealId' => $p2pDeal['deal_id'],
                'dealRepayId' => $nextRepayInfo['id'],
                'principal' => bcdiv($p2pDeal['left_repay_money'],100,2), //未还款的金额全部还款
                'interest' => 0, //数据还款，利息不计算，直接为 0
                'dealStatus' => 5, //标的已还清
                'isLast' => 1, //最后一次还款
                'repayUserId' => $repayUserId, //还款用户id，代还款用户
            );

            $request->setVars($vars);
            $rpc = new \libs\utils\Rpc('duotouRpc');
            $response = $rpc->go('\NCFGroup\Duotou\Services\DealRepay', "repayDeal", $request);
            if(!$response) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "网络错误，还款失败")));
            }
            if($response['errCode'] != 0) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail errCode:".$response['errCode']." errMsg:".$response['errMsg'])));
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "还款成功:"." params:". json_encode($vars))));

            //调用p2p修改智多鑫底层资产相关信息 包括还款 tag等
            $res = $dtdealService->clearDealV3($p2pDeal['deal_id']);
            if(false == $res) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "调用p2p修改智多鑫底层资产相关信息失败！deal_id: " . $p2pDeal['deal_id'])));
                continue ;
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "调用p2p修改智多鑫底层资产相关信息成功！deal_id: ". $p2pDeal['deal_id'])));
            $succDealNum ++;
        }

        $result = '总执行还款标的数量：'.$totalDealNum.',成功执行数量：'.$succDealNum;
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $result)));
        echo $result;

        return true;
    }
}

if (!isset($argv[1])) {
    exit("请指定需要数据还款的智多鑫项目id");
}

$dtProjectId = intval($argv[1]);
$repayUserId = intval(app_conf('DT_YDT'));

if ($repayUserId <= 0) {
    exit("请配置代还款用户 DT_YDT 的userid");
}

$dealDataRepay = new DuotouP2pDealDataRepay();
$dealDataRepay->run($dtProjectId,$repayUserId);
