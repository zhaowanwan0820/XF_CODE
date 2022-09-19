<?php
/**
 * contract_auto_agency_sign.php
 * 合同实时代签脚本，每隔五分钟执行一次
 * 脚本部署例子
 * @date 2016-05-04
 * @author <wangzhen3@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');
use core\service\ContractService;
set_time_limit(0);

class contract_auto_agency_sign{
    public function run(){
        $contractService = new ContractService();
        $contractService->autoAgencySignContract();
    }
}
$contract_auto_agency_sign = new contract_auto_agency_sign();
$contract_auto_agency_sign->run();
exit;