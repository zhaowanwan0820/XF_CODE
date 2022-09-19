<?php
/**
 * @desc  对于存管标的投资如果5分钟内未收到回调则取消投资
 * User: jinhaidong
 * Date: 2017-4-19 13:34:22
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\P2pDepositoryService;
use core\service\P2pIdempotentService;
use core\dao\JobsModel;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;
use libs\utils\Alarm;

class SupervisionRepairMoney {



    public function run() {
        $s = new \core\service\P2pDealGrantService();
        $dealIds = array(
            1272863,
            1272864
        );

        try{
            foreach($dealIds as $dealId){
                $param = array('deal_id' => $dealId, 'admin' => '', 'submit_uid' => 0);
                $res = $s->dealGrantRequest($dealId,$param);
            }
        }catch (\Exception $ex){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__," 模拟放款 dealId:".$dealId." errMsg:".$ex->getMessage())));
        }
    }


}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new SupervisionRepairMoney();
$obj->run();