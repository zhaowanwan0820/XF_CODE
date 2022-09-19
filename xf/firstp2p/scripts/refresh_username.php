<?php
/**
 *刷用户名，目的去除用户名中的手机号.
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    define('REFRESH_LOG_PATH','/tmp/username/');
    error_reporting(0);
    if(!isset($argv[1])){
        die("未输入[处理函数]参数");
    }
    if(!function_exists($argv[1])){
        die("无此函数:{$argv[1]}".PHP_EOL);
    }
    make_dir();
    $argv[1]($argv);

/**
 *  按指定的user表id范围刷用户名
 *  控制台参数： refresh_username fromId toId limit
 *  控制台命令： php refresh_username.php refresh_username 1 50 2
 *
 */
function refresh_username($argv){
    if(!isset($argv[2])||!isset($argv[3])||!isset($argv[4])){
        die('参数错误: refresh_username fromId toId limit'.PHP_EOL);
    }
    $fromId=$argv[2]; $toId=$argv[3]; $limit=$argv[4];
    common_log("refresh_username begin:{$fromId}-{$toId}:" .date("H:i:s"),'summery');

    $startTime = microtime(true);
    $readRowNum = 0;
    $updateRowNum = 0;
    $userNameDataFile = fopen(REFRESH_LOG_PATH  ."user_{$fromId}_{$toId}_".date('Y-m-d').".csv", 'a') or die("Unable to create  username data file!");
    while($fromId<$toId){
        $toIdPerLoop = $fromId+$limit;
        if($toIdPerLoop>$toId){
            $toIdPerLoop = $toId;
        }
        $summary =  do_refresh($fromId,$toIdPerLoop,$userNameDataFile);
        $readRowNum  +=$summary['readRowNum'];
        $updateRowNum +=$summary['updateRowNum'];
        $fromId=$toIdPerLoop;
    }
    fclose($userNameDataFile);
    common_log("refresh_username end:{$fromId}-{$toId}:" .date("H:i:s") ." [读取行数：{$readRowNum},更新行数:{$updateRowNum}],耗时:".round(microtime(true) - $startTime, 4),'summery');
}

/**
 *  执行过程中如果有失败的，根据记录的error sql 日志 重新执行
 *  控制台参数: fix_data fileName
 *  控制台命令：php refresh_username.php fix_data fileName
 *
 */
function fix_data($argv){
    if(!isset($argv[2])){
        die('参数错误: fix_data fileName '.PHP_EOL);
    }
    $fileName=$argv[2];
    common_log("fix_data begin:{$fileName},时间:" .date("H:i:s"),'fix_summery');
    $fpr = fopen( REFRESH_LOG_PATH . $fileName, 'r') or die("Unable to open recovery {$fileName}!");
    $updateNum = 0;
    while (!feof($fpr)) {
        $oneLine = fgets($fpr);
        if(!empty($oneLine)){
            try{
                $res =  $GLOBALS['db']->getInstance('firstp2p','master')->query($oneLine);
                if(!$res){
                    notime_log($oneLine,"fix_error");
                }else{
                    $updateNum++;
                    common_log($oneLine,'fix');
                }
            }catch (\Exception $e){
                notime_log($oneLine,"fix_exception");
                common_log($e->getTraceAsString(),'fix_exception_info');
            }
        }
    }
    common_log("fix_data end:{$fileName},更新行数:{$updateNum},时间:" .date("H:i:s"),'fix_summery');
    fclose($fpr);

}
/**
 * 如果上线后，发现有漏掉的表或字段，用此函数快速修复.
 * 读漏掉表中的user_id，根据user_id查询user表获取user_name,然后刷新漏掉表的user_name
 * 控制台参数: repair_data tableName fieldName conditionFieldName fromId toId limit
 * 控制台命令: php refresh_username.php repair_data tableName fieldName user_id 6 6 5
 */
function repair_data($argv){
    if(!isset($argv[2])||!isset($argv[3])||!isset($argv[4])||!$argv[5]||!$argv[6]||!$argv[7]){
        die('参数错误: repair_data tableName fieldName conditionFieldName fromId toId limit'.PHP_EOL);
    }
    $tableName=$argv[2];
    $fieldName=$argv[3];
    $conditionFieldName=$argv[4];
    $fromId = $argv[5];
    $toId = $argv[6];
    $limit=$argv[7];
    $readNum = 0;
    $updateNum = 0;

    common_log("repair_data begin:{$tableName},{$fieldName},{$conditionFieldName},{$fromId},{$toId},{$limit}:","repair_summery");

    try{
        while($fromId<$toId){
            $toIdPerLoop = $fromId+$limit;
            if($toIdPerLoop>$toId){
                $toIdPerLoop=$toId;
            }
            $sql = "SELECT id,{$conditionFieldName} FROM `{$tableName}` WHERE id>={$fromId} AND id<{$toIdPerLoop} ORDER BY id ASC ";
            common_log($sql);
            $res =  $GLOBALS['db']->getInstance('firstp2p','slave')->getAll($sql);
            $fromId=$toIdPerLoop;
            foreach($res as $value){
                $id = $value['id'];
                $userId = $value["{$conditionFieldName}"];
                $userSql = "SELECT id,user_name,mobile FROM `firstp2p_user` WHERE id={$userId}";
                $userData =  $GLOBALS['db']->getInstance('firstp2p','slave')->getRow($userSql);
                if($userData){
                    $userName = $userData['user_name'];
                    $mobile = $userData['mobile'];
                    if(!empty($userName)&&!empty($mobile)&&strlen($userName)==12&&strpos($userName,'m')===0){
                        $repair_sql =  "UPDATE `{$tableName}` SET `{$fieldName}` = '{$userName}' WHERE id={$id}";
                        $updateRes =  $GLOBALS['db']->getInstance('firstp2p','master')->query($repair_sql);
                        if(!$updateRes){
                            notime_log($repair_sql.";".PHP_EOL,"error_repair_update_{$tableName}");
                        }else{
                            $updateNum++;
                            notime_log($repair_sql.";".PHP_EOL,"repair_update_{$tableName}");
                        }
                    }
                }

            }
            $readNum +=count($res);
        }
        common_log("repair_data end: {$tableName},读取行数:{$readNum},更新行数:{$updateNum}","repair_summery");
    }catch (\Exception $e){
        notime_log($repair_sql.";".PHP_EOL,"exception_repair_update_{$tableName}");
        common_log($e->getTraceAsString().PHP_EOL,"exception_repair_info_{$tableName}");
    }
}



function do_refresh($fromId,$toId,$dataFile){
    $whereStr = " WHERE id>={$fromId} AND id<{$toId}";
    $sql = "SELECT id,user_name,mobile FROM `firstp2p_user` {$whereStr} ORDER BY id ASC ";
    common_log($sql);
    $res =  $GLOBALS['db']->getInstance('firstp2p','master')->getAll($sql);
    $readRowNum = count($res);
    $updateRowNum = 0;
    foreach($res as $key =>$row){
            $userName =  $row['user_name'];
            $mobile =  $row['mobile'];
            $userId = $row['id'];
            if(!empty($userName)&&!empty($mobile)&&($userName=='m'.$mobile||$userName=='H'.$mobile)){
                $newUserName = gen_user_name();
                do_update($userName,$newUserName,$userId,$dataFile);
                $updateRowNum++;

            }
    }
    return array('readRowNum'=>$readRowNum,'updateRowNum'=>$updateRowNum);
}


function do_update($userName,$newUserName,$userId,$dataFile){
    $checkNum=0;
    $oneUserStartTime=microtime(true);
    try{
        $userSql = "UPDATE `firstp2p_user` SET `user_name` = '{$newUserName}' WHERE id = {$userId}";
        $res = $GLOBALS['db']->getInstance('firstp2p','master')->query($userSql);
        if(!$res){
            notime_log($userSql.";".PHP_EOL,"error_update_firstp2p_user");
            return;
        }else{
            $checkNum++;
            notime_log($userSql.";".PHP_EOL,"update_firstp2p_user");
            fputcsv($dataFile,array($userId,$userName,$newUserName));
        }
    }catch (\Exception $e){
        notime_log($userSql.";".PHP_EOL,"exception_update_firstp2p_user");
        common_log($e->getTraceAsString().PHP_EOL,"exception_info_firstp2p_user");
        return;
    }
    notime_log($userId.PHP_EOL,'all_complete_check_1');

    $config=array(
        array('tableName'=>'firstp2p_user_yifang','fieldName'=>'user_name','con'=>'user_name'),
        array('tableName'=>'firstp2p_agency_user','fieldName'=>'user_name','con'=>'user_id'),
        array('tableName'=>'firstp2p_withdraw_limit','fieldName'=>'user_name','con'=>'user_id'),
        array('tableName'=>'firstp2p_user_freeze_money','fieldName'=>'user_name','con'=>'user_id'),
        array('tableName'=>'firstp2p_withdraw_limit_record','fieldName'=>'user_name','con'=>'user_id'),
        array('tableName'=>'firstp2p_deal_load','fieldName'=>'user_name','con'=>'user_id'),
        array('tableName'=>'firstp2p_coupon_bind','fieldName'=>'user_name','con'=>'user_id'),
        array('tableName'=>'firstp2p_coupon_bind','fieldName'=>'refer_user_name','con'=>'refer_user_id'),
        array('tableName'=>'firstp2p_interest_extra_log','fieldName'=>'user_name','con'=>'user_id'),
      //  array('tableName'=>'firstp2p_interest_extra_log','fieldName'=>'out_user_name','con'=>'out_user_id'),
        array('tableName'=>'firstp2p_coupon_log_reg','fieldName'=>'consume_user_name','con'=>'consume_user_id'),
        array('tableName'=>'firstp2p_coupon_log_reg','fieldName'=>'refer_user_name','con'=>'refer_user_id'),
        array('tableName'=>'firstp2p_coupon_log_duotou','fieldName'=>'consume_user_name','con'=>'consume_user_id'),
        array('tableName'=>'firstp2p_coupon_log_duotou','fieldName'=>'refer_user_name','con'=>'refer_user_id'),
     //   array('tableName'=>'firstp2p_coupon_extra_log','fieldName'=>'consume_user_name','con'=>'consume_user_id'),
        array('tableName'=>'firstp2p_coupon_log','fieldName'=>'consume_user_name','con'=>'consume_user_id'),
        array('tableName'=>'firstp2p_coupon_log','fieldName'=>'refer_user_name','con'=>'refer_user_id'),
    );

    foreach($config as $value){
        $startTime = microtime(true);
        try{
            if($value['tableName']=='firstp2p_user_yifang'){
                $sql = "UPDATE `{$value['tableName']}` SET `{$value['fieldName']}` = '{$newUserName}' WHERE {$value['con']} = '{$userName}'";
            }else{
                $sql = "UPDATE `{$value['tableName']}` SET `{$value['fieldName']}` = '{$newUserName}' WHERE {$value['con']} = {$userId}";
            }
            $res =  $GLOBALS['db']->getInstance('firstp2p','master')->query($sql);
            if(!$res){
                notime_log($sql.";".PHP_EOL,"error_update_{$value['tableName']}");
            }else{
                $checkNum++;
                notime_log($sql.";".PHP_EOL,"update_{$value['tableName']}");
            }
            common_log(json_encode(array('cost'=>round(microtime(true) - $startTime, 4),'table'=>$value['tableName'],'userId'=>$userId)),'cost');
        }catch (\Exception $e){
            notime_log($sql.";".PHP_EOL,"exception_update_{$value['tableName']}");
            common_log($e->getTraceAsString().PHP_EOL,"exception_info_{$value['tableName']}");
        }
    }
    common_log(json_encode(array('cost'=>round(microtime(true) - $oneUserStartTime, 4),'userId'=>$userId)),'cost');
    notime_log($userId.PHP_EOL,'all_complete_check_2');
    if($checkNum!=16){
        \libs\utils\Monitor::add('REFRESH_USERNAME_FAIL');
        common_log($userId.'_'.$checkNum,'one_complete_check');
    }
}
function gen_user_name(){
    $userDao = core\dao\UserModel::instance();
    for($i=0;$i<20;$i++){
        $username = 'm'.mt_rand(10000000000, 99999999999);
        if($userDao->isUserExistsByUsername($username) === false){
            return $username;
        }
    }
    return false;
}

function common_log($data,$filePrefix='select'){
    Logger::wLog($data,Logger::INFO,Logger::FILE,REFRESH_LOG_PATH.$filePrefix.'_'.date('y_m_d') .'.log');
}
function notime_log($data,$filePrefix){
    $fileName = REFRESH_LOG_PATH . $filePrefix .'_'.date('y_m_d') .'.log';
    file_put_contents($fileName,$data,FILE_APPEND);
}

function make_dir(){
    if (!file_exists(REFRESH_LOG_PATH)) {
        @mkdir(REFRESH_LOG_PATH,0755,true);
    }

}
