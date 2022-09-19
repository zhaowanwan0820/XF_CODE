<?php
/**
 * 晚上20点10分 跑该脚本，获取多投统计
 * 10 20 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php duotou_p2p_balance_stats.php
 * @author wangchuanlu
 * @date 2018-05-10
*/

require_once(dirname(__FILE__) . '/../app/init.php');
set_time_limit(0);

use libs\utils\Logger;
use libs\utils\Rpc;
use libs\utils\Alarm;
use NCFGroup\Protos\Duotou\RequestCommon;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use core\dao\SupervisionIdempotentModel;

class duotou_p2p_balance_stats {

    public function run() {
        $todayBeginTime = strtotime(date('Y-m-d 00:00:00'));
        $todayEndTime = $todayBeginTime + 86400;

        $countSql   = "SELECT count(*) as totalCount FROM `firstp2p_supervision_idempotent` WHERE type=7 AND result=1 AND create_time >= {$todayBeginTime}";
        $sumSql     = "SELECT sum(money) as totalMoney FROM `firstp2p_supervision_idempotent` WHERE type=7 AND result=1 AND create_time >= {$todayBeginTime}";
        $failOrdersSql = "SELECT order_id FROM `firstp2p_supervision_idempotent` WHERE type=7 AND result!=1 AND create_time >= {$todayBeginTime}";

        $countRes = SupervisionIdempotentModel::instance()->findBySql($countSql, array(),true);
        $count = $countRes['totalCount'];
        $sumRes = SupervisionIdempotentModel::instance()->findBySql($sumSql, array(),true);
        $sum = $sumRes['totalMoney'];

        $failOrderRes =  SupervisionIdempotentModel::instance()->findAllBySqlViaSlave($failOrdersSql,true);
        $failTokens = array();
        foreach($failOrderRes as $failOrder){
            $failTokens[] = $failOrder['order_id'];
        }

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'startTime' => $todayBeginTime,
            'endTime' => $todayEndTime,
            'failTokens' => implode(',',$failTokens),
        );

        try{
            $request->setVars($vars);
            $rpc = new \libs\utils\Rpc('duotouRpc');
            $response = $rpc->go("\NCFGroup\Duotou\Services\DealLoan", "getDealLoanStats", $request);
            if(!$response) {
                Alarm::push(CommonEnum::DT_SYNC_P2P,'网络错误，每日p2p多投对账失败');
                $errMsg = "网络错误，每日p2p多投对账失败";
                throw new \Exception($errMsg);
            }

            $resData = $response['data'];
            if($resData['count'] != $count) {
                Alarm::push(CommonEnum::DT_SYNC_P2P,'每日p2p多投对账，投资成功笔数不同');
                $errMsg = "每日p2p多投对账，投资成功笔数不同 p2p:{$count} duotou:{$resData['count']}";
                throw new \Exception($errMsg);
            }
            if(bccomp($resData['sum'] ,$sum,2)) {
                Alarm::push(CommonEnum::DT_SYNC_P2P,'每日p2p多投对账，投资成功金额不同');
                $errMsg = "每日p2p多投对账，投资成功金额不同 p2p:{$sum} duotou:{$resData['sum']}";
                throw new \Exception($errMsg);
            }

            if($resData['failCount'] > 0) {
                Alarm::push(CommonEnum::DT_SYNC_P2P,'每日p2p多投对账，多投系统有p2p失败订单');
                $errMsg = "每日p2p多投对账，多投系统有{$resData['failCount']}笔p2p失败订单";
                throw new \Exception($errMsg);
            }

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "每日p2p多投对账成功")));
        }catch(\Exception $ex){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail errCode:".$response['errCode']." errMsg:".$ex->getMessage())));
        }
    }
}

$obj = new duotou_p2p_balance_stats();
$obj->run();
