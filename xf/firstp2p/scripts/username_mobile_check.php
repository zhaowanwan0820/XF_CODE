<?php
/**
 *排查用户名是否与其他用户的手机号重复
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    define('REFRESH_LOG_PATH','/tmp/username_check/');
    error_reporting(E_ALL);ini_set('display_errors','On');
    make_dir();
    getUser();
function getUser(){
    $fromId =1;
    $toId = 2607717;
//    $toId = 4321;
    $limit= 10000;
    try{
        $userDataFile = fopen(REFRESH_LOG_PATH  ."user_{$fromId}_{$toId}_".date('Y-m-d').".csv", 'a') or die("Unable to create  username data file!");
        while($fromId<$toId){
            $toIdPerLoop = $fromId+$limit;
            if($toIdPerLoop>$toId){
                $toIdPerLoop=$toId;
            }
            $sql = "SELECT id,user_name,mobile FROM `firstp2p_user` WHERE id>={$fromId} AND id<{$toIdPerLoop} ORDER BY id ASC ";
            common_log($sql);
            $res =  $GLOBALS['db']->getInstance('firstp2p','slave')->getAll($sql);
            $fromId=$toIdPerLoop;
            foreach($res as $value){
                $id = $value['id'];
                $userName = $value['user_name'];
                $mobile = $value['mobile'];
                if(isMobile($userName)){
                    $repeatUserSql = "SELECT  id,user_name,mobile FROM `firstp2p_user` WHERE mobile='{$userName}' AND id<>{$id}";
                    $repeatUsers =  $GLOBALS['db']->getInstance('firstp2p','slave')->getAll($repeatUserSql);
                    if($repeatUsers && !empty($repeatUsers)){
                         $recorder = array(array($id,$userName,$mobile));
                        foreach($repeatUsers as $repeatUser){
                            $recorder[]=array($repeatUser['id'],$repeatUser['user_name'],$repeatUser['mobile']);
                        }
                        fputcsv($userDataFile,array(json_encode($recorder)));
                    }
                }
            }

        }
        fclose($userDataFile);
    }catch (\Exception $e){
        common_log($e->getTraceAsString().PHP_EOL,"usernamecheck_exception");
    }
}

function isMobile($userName){
    if (!$userName) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', $userName) ? true : false;
}

function common_log($data,$filePrefix='select'){
    Logger::wLog($data,Logger::INFO,Logger::FILE,REFRESH_LOG_PATH.$filePrefix.'_'.date('y_m_d') .'.log');
}

function make_dir(){
    if (!file_exists(REFRESH_LOG_PATH)) {
        @mkdir(REFRESH_LOG_PATH,0755,true);
    }
}
