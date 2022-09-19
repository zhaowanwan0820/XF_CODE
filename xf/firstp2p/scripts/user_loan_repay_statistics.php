<?php
/**
 * @desc 核对用户表资产与loan_repay_statics 表的值
 * 如果不等，将 loan_repay_statics 表覆盖
 * User: jinhaidong
 * Date: 2016/3/2 14:51
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\UserLoanRepayStatisticsService;
use core\dao\UserLoanRepayStatisticsModel;

class UserLoanRepayStatistics {

    const LOAN_REPAY_SYNC_MAX_ID = 'LOAN_REPAY_SYNC_MAX_ID';

    public function run() {
        global $argv;

        if(!isset($argv[1]) || !in_array($argv[1],array('checkAll','checkOne','delKey','resetUserAssetBatch'))) {
            exit("Please input the method:checkAll|checkOne|delKey|resetUserAssetBatch");
        }
        $method = $argv[1];
        if($method == 'checkOne' && !isset($argv[2])) {
            exit('Please input the user id');
        }
        $args = array_slice($argv,2);
        $this->$method($args);
    }

    public function delKey() {
        $maxId = \SiteApp::init()->dataCache->getRedisInstance()->get(self::LOAN_REPAY_SYNC_MAX_ID);
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, ' Now the redis max id is:'.$maxId)));
        \SiteApp::init()->dataCache->getRedisInstance()->del(self::LOAN_REPAY_SYNC_MAX_ID);
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'Del the redis max id success')));
        $maxId = \SiteApp::init()->dataCache->getRedisInstance()->get(self::LOAN_REPAY_SYNC_MAX_ID);
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, ' Now the redis max id is:'.$maxId)));
    }
    public function checkAll() {
        $redisKey = self::LOAN_REPAY_SYNC_MAX_ID;
        $idOffset = \SiteApp::init()->dataCache->getRedisInstance()->get($redisKey);
        if(!$idOffset) {
            $idOffset = 0;
        }

        while(true) {
            $sql = "SELECT id,user_id FROM `firstp2p_user_loan_repay_statistics` where id > {$idOffset} ORDER  BY id ASC limit 5000";
            $rows = $GLOBALS['db']->get_slave()->getAll($sql);

            if(empty($rows)) {
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'Check finish!')));
                break;
            }

            foreach($rows as $row) {
                $user_id = $row['user_id'];
                $id = $row['id'];
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "begin check id:{$id},uid:{$user_id}")));
                $this->checkUserAsset($user_id);
                $setRes = \SiteApp::init()->dataCache->getRedisInstance()->set($redisKey,$id);
                if($setRes) {
                    $idOffset = $id;
                }
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "end check id:{$id},uid:{$user_id}")));
            }
        }
    }
    public function checkOne($args) {
        $uid = intval($args[0]);
        if(!$uid) {
            return false;
        }
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, "The uid:{$uid} check start!")));
        $this->checkUserAsset($uid);
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, "The uid:{$uid} check finish!")));
    }

    public function checkUserAsset($uid) {
        $oldData = $this->getUserAssetFromLoanRepay($uid);
        $newData = $this->getUserAssetFromLoanRepayStatistics($uid);
        $isEqual = true;
        foreach($oldData as $key=>$val) {
            if(bccomp($val,$newData[$key],2) != 0) {
                $isEqual = false;
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, 'user summary unequal begin to sync from loan_repay uid:'.$uid." oldData:".json_encode($oldData)." newData:".json_encode($newData))));
                UserLoanRepayStatisticsModel::instance()->resetUserAsset($uid,$oldData);
                break;
            }
        }
        if($isEqual) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $uid, "user summary isequal don't need to be synchronized uid:".$uid)));
        }
    }

    public function getUserAssetFromLoanRepay($uid) {
        return UserLoanRepayStatisticsModel::instance()->getUserAssetFromLoanRepay($uid);
    }

    public function getUserAssetFromLoanRepayStatistics($uid) {
        $res = UserLoanRepayStatisticsModel::instance()->getUserAssets($uid);
        return !$res ? array() : $res->getRow();
    }

    public function resetUserAssetBatch() {
        $users = file( dirname(__FILE__) . "/user_loan_repay_statistics_reset.php");
        if(!$users) {
            exit;
        }
        foreach($users as $uid) {
            $uid = trim($uid);
            $this->checkUserAsset($uid);
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new UserLoanRepayStatistics();
$obj->run();