<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 2017/6/7
 * Time: 下午12:30
 */

require_once dirname(__FILE__).'/../app/init.php';

use core\dao\JobsModel;
use core\dao\DealModel;

use core\service\ContractInvokerService;
use core\service\GoldService;

use libs\utils\Logger;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

class BatchJobGoldTs {
    public function run()
    {
        $gs = new GoldService();
        $goldDeals = $gs->getLoanDealIds();
        foreach($goldDeals as $deal) {
            try {
                if (!ContractInvokerService::signAllContractByServiceId('signer', $deal, ContractServiceEnum::SERVICE_TYPE_GOLD_DEAL)) {
                    throw new \Exception("黄金合同打戳失败 deal_id" . $deal);
                }
            } catch (\Exception $ex) {
                Logger::error("GoldTs | run | fail " . $ex->getMessage());
                continue;
            }
        }

    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new BatchJobGoldTs();
$obj->run();