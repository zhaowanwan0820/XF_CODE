<?php
//'`which php` flushInvestUserProfile [2016-06-20] , default time'."\n";

ini_set('display_errors', 'On'); error_reporting(E_ALL);


set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once dirname(__FILE__).'/../../app/init.php';



$startTime = to_timespan(date("Y-m-d") . "00:00:00") - 86400;
$endTime = to_timespan(date("Y-m-d") . "00:00:00");
if(!empty($argv[1])){
    $day = strtotime($argv[1]);
    $startTime = to_timespan(date("Y-m-d",$day))-86400;
    $endTime = to_timespan(date("Y-m-d",$day));
}

use core\service\UserProfileService;
use core\dao\UserModel;

$userProfileService = new UserProfileService();
function flushBatch($ups,$startTime,$endTime,$offset,$num){
    try{
        $allUserSql = "SELECT DISTINCT(user_id) FROM firstp2p_deal_load WHERE deal_id>120000 AND create_time>=$startTime AND create_time<$endTime LIMIT $offset,$num";
        $ret = \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($allUserSql);
        $num = 0;
        foreach($ret as $one){
            $ups->flushSingleUserData($one['user_id']);
            $num++;
        }
        return $num;
    }catch(Exception $e){
        return 0;
    }
}

$num = 0;
do{
    $num = flushBatch($userProfileService,$startTime,$endTime,$num,500);
    echo "$num ,, batch  \n";
}while($num!=0);


