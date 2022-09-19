<?php

/*
 * 批量提前还款
 * 更新还款中正在还款状态为还款中状态
 * 参数1,标id,以','分隔
 *
 */
require_once dirname(__FILE__).'/../../app/init.php';
require_once dirname(__FILE__).'/../../libs/common/app.php';
require_once dirname(__FILE__).'/../../libs/common/functions.php';
require_once dirname(__FILE__).'/../../system/libs/msgcenter.php';

use core\dao\DealModel;
use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '4096M');

if(isset($argv[1]) && ($argv[1] != 0)){
    $dealIds = explode(',',$argv[1]);
}else{
    echo '参数错误!';
}

if(count($dealIds) > 0){
    foreach($dealIds as $dealId){
        $dealModel = new DealModel();
        $deal = $dealModel->find($dealId);
        if(!empty($deal)){
            $res = $deal->changeRepayStatus(core\dao\DealModel::NOT_DURING_REPAY);

            if(!$res){
                echo $dealId."change status fail!". "\n";
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$dealId."change status fail!")));
            }else{
                echo $dealId." change status success!". "\n";
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$dealId." change status success!")));
            }
        }
    }
}

echo "script success !";