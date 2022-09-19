<?php

require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\service\ContractNewService;

set_time_limit(0);
ini_set('memory_limit', '1024M');

if(isset($argv[1]) && ($argv[1] != 0)){
    $dealIds = explode(',',$argv[1]);
}

foreach($dealIds as $dealId) {
    $contractService = new ContractNewService();
    $res = $contractService->signDealContNew($dealId,4,0);

    if($res){
        echo $dealId." success! \n";
    }else{
        echo $dealId." fail! \n";
    }
}