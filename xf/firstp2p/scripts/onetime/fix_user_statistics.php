<?php
/**
 * 修复提前还款取消导致的用户回款日历变为负值的问题
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);
require dirname(__FILE__).'/../../app/init.php';

use \libs\Db\Db;
use \libs\utils\Logger;

$userIds = isset($argv[1]) ? trim($argv[1]) : 0;
$minId = isset($argv[2]) ? intval($argv[2]) : 0;
$maxId = isset($argv[3]) ? intval($argv[3]) : 0;

//$arr = Db::getInstance('firstp2p')->getAll("select deal_id from firstp2p_deal_tag where tag_id in(42,44)");
//$zdxIds = array();
//foreach($arr as $item){
//    $zdxIds[]=$item['deal_id'];
//}
//$zdxIds = implode(",",$zdxIds);

if($userIds){
    $userIdArr = explode(",",$userIds);
    foreach($userIdArr as $userId){
        resetUserAsset($userId);
    }
}else{
    if(!$minId || !$maxId){
        die("Error params!");
    }

    $tmpMaxId = $minId;
    while(true){
        $tmpMaxId+=1000;

        $sql = "SELECT * FROM `firstp2p_user_loan_repay_statistics` where id >= {$minId} AND  id < {$tmpMaxId}";
        $result = Db::getInstance('firstp2p')->getAll($sql);

        $minId = $tmpMaxId;
        if(!$result){
            Logger::info("resetUserAsset finish");
            continue;
        }else{
            foreach ($result as $k=>$v){
                $cgData['id'] = $v['id'];
                $cgData['cg_norepay_principal'] = $v['cg_norepay_principal'];
                $cgData['cg_norepay_earnings'] = $v['cg_norepay_earnings'];
                resetUserAsset($v['user_id'],$cgData);
            }
        }

        if($tmpMaxId > $maxId){
            break;
        }
        usleep(100000);
    }


}

function resetUserAsset($uid,$cgData=array()){
    $cond = " AND deal_id NOT IN (select deal_id from firstp2p_deal_tag where tag_id in(42,44))";
    $sql = "SELECT sum(money) as m,type FROM `firstp2p_deal_loan_repay` WHERE loan_user_id={$uid} {$cond} AND deal_type=0 AND status=0 GROUP BY type";

    $result = Db::getInstance('firstp2p')->getAll($sql);

    if(!$result && $cgData['cg_norepay_principal'] == 0 && $cgData['cg_norepay_earnings'] == 0){
        Logger::info("resetUserAsset result empty uid:$uid,id:".$cgData['id']);
        return true;
    }

    $cg_norepay_principal = 0;
    $cg_norepay_earnings = 0;
    foreach($result as $row){
        if($row['type'] == 1){
            $cg_norepay_principal = $row['m'];
        }elseif($row['type'] == 2){
            $cg_norepay_earnings = $row['m'];
        }
    }
    if(!empty($cgData) && $cg_norepay_principal == $cgData['cg_norepay_principal'] && $cg_norepay_earnings == $cgData['cg_norepay_earnings']){
        Logger::info("resetUserAsset no need update uid:{$uid},id:".$cgData['id']);
        return true;
    }

    $updateSql = "update firstp2p_user_loan_repay_statistics set cg_norepay_principal = {$cg_norepay_principal} ,cg_norepay_earnings = {$cg_norepay_earnings}  where user_id=".$uid;
    $updateRes = Db::getInstance('firstp2p')->query($updateSql);

    if($updateRes){
        Logger::info("resetUserAsset succ uid:{$uid},id:".$cgData['id']);
    }else{
        Logger::info("resetUserAsset err uid:{$uid},id:".$cgData['id']);
    }
}