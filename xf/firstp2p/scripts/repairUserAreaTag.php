<?php
/**
 * 对老的用户进行地域tag 修复
 * 本脚本只执行一次
 *
 * User: jinhaidong
 * Date: 2015/8/19 14:14
 */

error_reporting(2047);
ini_set('display_errors',1);

require_once dirname(__FILE__)."/../app/init.php";
use core\event\UserMobileAreaEvent;

$idFile = "/tmp/id.txt";
if(!file_exists($idFile)) {
    $rs = file_put_contents($idFile,3240000);
    if(!$rs) {
        die($idFile." can not write");
    }
}

$maxId = file_get_contents($idFile);
if(!$maxId) {
    die('Please input the id in '.$idFile);
}

while($maxId) {
    $sql = "select id as user_id,mobile from firstp2p_user where id < ".$maxId ." order by id desc limit 1000";
    echo $sql."\n";
    $rows = $GLOBALS['db']->get_slave()->getAll($sql);
    if(empty($rows)) {
        echo "rows is empty\n";
        break;
    }
    foreach($rows as $arr) {
        $userId = $arr['user_id'];
        $mobile = $arr['mobile'];
        echo $userId."==".$mobile."\n";
        try{
            $event = new UserMobileAreaEvent($userId,$mobile);
            $res = $event->execute();
        }catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            $res = false;
        }

        if($res) {
            echo "userId:".$userId." mobile:".$mobile ." tag success\n";
        }else{
            echo "userId:".$userId." mobile:".$mobile ." tag error\n";
        }
        $maxId = $arr['user_id'];
        file_put_contents($idFile,$arr['user_id']);
    }
}