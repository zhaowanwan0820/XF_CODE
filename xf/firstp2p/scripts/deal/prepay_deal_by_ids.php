<?php

/*
 * 批量提前还款
 * 参数1,还款方式 0:借款人,1:代垫,2:代偿,3:代充值
 * 参数2,计息结束时间 格式 2000-01-01
 * 参数3,标id,以','分隔
 *
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\service\DealPrepayService;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(isset($argv[1])){
    $repayType = intval($argv[1]);
}else{
    echo '参数错误!';
}

if(isset($argv[2]) && ($argv[2] != 0)){
    $date = $argv[2];
}else{
    echo '参数错误!';
}

if(isset($argv[3]) && ($argv[3] != 0)){
    $dealIds = explode(',',$argv[3]);
}else{
    echo '参数错误!';
}

if(count($dealIds) > 0){
    $dealPrepayService = new DealPrepayService();
    foreach($dealIds as $dealId){
        try{
            $res = $dealPrepayService->prepayPipeline(intval($dealId),$date,$repayType);
        }catch (\Exception $e) {
            $res = false;
        }

        if(!$res){
            echo $dealId." prepay fail!". "\n";
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$dealId." prepay fail!")));
        }else{
            echo $dealId." prepay success!". "\n";
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$dealId." prepay success!")));
        }
    }
}

echo "script success !";