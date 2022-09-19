<?php
/**
 * 晚上22点 跑该脚本，同步p2p标的信息到智多鑫
 * 0 22 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php duotou_sync_p2p_deal.php
 * @author wangchuanlu
 * @date 2017-02-22
*/

require_once(dirname(__FILE__) . '/../app/init.php');
set_time_limit(0);

use libs\utils\Logger;
use libs\utils\Rpc;
use libs\utils\Alarm;
use NCFGroup\Protos\Duotou\RequestCommon;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use core\dao\DealModel;
use core\service\DealService;

class duotou_sync_p2p_deal {

    public function run() {
        $dealList = DealModel::instance()->getZDXProgressDealList();
        foreach ($dealList as $dealInfo) {
            $request = new \NCFGroup\Protos\Duotou\RequestCommon();
            $vars = array(
                'id' => $dealInfo['id'],
                'money' => $dealInfo['borrow_amount'],
                'name' => $dealInfo['name'],
            );
            $request->setVars($vars);
            $rpc = new \libs\utils\Rpc('duotouRpc');
            $response = $rpc->go("\NCFGroup\Duotou\Services\P2pDeal", "syncP2pDeal", $request);
            if(!$response) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "网络错误")));
                Alarm::push(CommonEnum::DT_SYNC_P2P,'网络错误，同步p2p信息到智多鑫失败');
            }
            if($response['errCode'] != 0) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail errCode:".$response['errCode']." errMsg:".$response['errMsg'])));
                Alarm::push(CommonEnum::DT_SYNC_P2P,'同步p2p信息到智多鑫失败');
            }
        }
    }
}

$obj = new duotou_sync_p2p_deal();
$obj->run();
