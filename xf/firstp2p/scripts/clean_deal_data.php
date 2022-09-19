<?php
/**
 * @desc  数据清洗 DBA 要求每次更新部分数据，循环更新 中间要sleep 防止主从延迟严重 故采取以下方式
 *
 * 1、把loan_repay 中 type = 8,9 的如果标的是通知贷 更改deal_type=1
 * 2、如果标的对应项目的产品大类='盈嘉'  则更改deal,deal_project,deal_load,deal_repay,deal_prepay,deal_loan_repay 中deal_type 为3
 * SELECT count(*) FROM `firstp2p_deal` WHERE project_id in (SELECT id FROM `firstp2p_deal_project` WHERE product_class='盈嘉')

 *
 * php scripts/clean_deal_data.php cleanCompound
 * User: jinhaidong
 * Date: 2016-11-10 10:48:22
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\DealModel;
use core\dao\DealAgencyModel;
use libs\utils\Logger;

class CleanDealData {

    const VIP_PROJECT_CLASS = '盈嘉'; // 专享项目大类

    const VIP_DEAL_OFFSET_KEY = 'VIP_DEAL_OFFSET_KEY'; // 缓存域
    const VIP_DEAL_LOAN_REPAY_OFFSET = 'VIP_DEAL_LOAN_REPAY_OFFSET'; // 记录loan_repay 更新情况
    const VIP_DEAL_LOAD_OFFSET = 'VIP_DEAL_LOAD_OFFSET'; // 记录deal_load 更新情况
    const VIP_COMPOUND_OFFSET = 'VIP_COMPOUND_OFFSET'; // 记录 通知贷 更新情况


    public function run($params = array()) {
        $method = $params[1];
        if(!method_exists(__CLASS__,$method)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"method:{$method} not exists")));
            exit;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"method:{$method} start!")));
        switch($method) {
            case 'delCacheKey':
                $this->delCacheKey(self::VIP_DEAL_OFFSET_KEY,$params[2]);
                break;
            case 'getCacheKey':
                $this->getCacheKey($params[2]);
                break;
            case 'cleanCompound':
                $dealIds = isset($params[2]) ? explode(",",$params[2]) : array();
                $this->cleanCompound($dealIds);
                break;
            case 'cleanDealLoanRepay':
                $dealIds = isset($params[2]) ? explode(",",$params[2]) : array();
                $this->cleanDealLoanRepay($dealIds);
                break;
            case 'cleanDealLoad':
                $vipDealIds = isset($params[2]) ? explode(",",$params[2]) : array();
                $this->cleanDealLoad($vipDealIds);
                break;
            default:
                $this->$method();
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"method:{$method} finish!")));
    }


    /**
     * 检查deal_loan_repay 是否已经全部更新完毕
     */
    public function checkDealLoanRepay(){
        $vipDealIds = $this->getVipDealIds();
        $cnt = count($vipDealIds);

        $notFinishCnt = 0;
        for($i=0;$i<$cnt;) {
            $ids = array_slice($vipDealIds,$i,100);
            if(empty($ids)){
                break;
            }
            $i+=100;
            $ids_str = implode(",",$ids);
            $sql = "SELECT count(*) as cnt FROM `firstp2p_deal_loan_repay` WHERE deal_id IN ($ids_str) AND  `deal_type` !=3";
            $res = $GLOBALS['db']->getOne($sql);
            if($res > 0) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__," loan_repay check not finish ids:{$ids_str}")));
                $notFinishCnt+=$res;
            }
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__," loan_repay check not finish number:{$notFinishCnt}")));
    }


    /**
     * 清洗通知贷--deal_loan_repay表
     * 每个标的休息0.5s
     * @param $dealIds
     */
    private function cleanCompound($dealIds) {
        $redisKey = self::VIP_DEAL_OFFSET_KEY;
        $redisKeyRegin = self::VIP_COMPOUND_OFFSET;
        $minDealId = \SiteApp::init()->dataCache->getRedisInstance()->hGet($redisKey, $redisKeyRegin);

        $dealIds = !empty($dealIds) ? $dealIds : $this->getCompoundDealIds($minDealId);

        foreach($dealIds as $dealId) {
            $sql = "UPDATE `firstp2p_deal_loan_repay` SET deal_type=1 WHERE deal_id=".$dealId;
            $res = $GLOBALS['db']->query($sql);
            if(!$res){
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "compound deal_loan_repay clean error deal_id:{$dealId}")));
                break;
            }
            \SiteApp::init()->dataCache->getRedisInstance()->hSet($redisKey, $redisKeyRegin, $dealId);

            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "compound deal_loan_repay clean success deal_id:{$dealId}")));
            usleep(500000); // 休息500ms
        }
    }

    /**
     * 清洗deal 每次执行1000条
     * @param int $endId 从哪开始执行
     * @param int $maxId 执行到的最大ID
     */

    private function cleanDeal() {
        $sql = sprintf("UPDATE `firstp2p_deal` SET deal_type=3 WHERE project_id IN (SELECT id FROM `firstp2p_deal_project` WHERE product_class='%s')",self::VIP_PROJECT_CLASS);
        $res = $GLOBALS['db']->query($sql);
        if(!$res){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"cleanDeal error")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"cleanDeal finish")));
        }
    }

    // 清洗项目
    private function cleanDealProject() {
        $sql = sprintf("UPDATE `firstp2p_deal_project` SET deal_type = 3 WHERE product_class='%s'",self::VIP_PROJECT_CLASS);
        $res = $GLOBALS['db']->query($sql);
        if($res) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"deal_project clean success!")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"deal_project clean error!")));
        }
        return $res;
    }

    /**
     * 清洗deal_load
     * @param $vipDealIds 专享标ID数组
     */
    private function cleanDealLoad(array $vipDealIds = array()) {
        $redisKey = self::VIP_DEAL_OFFSET_KEY;
        $redisKeyRegin = self::VIP_DEAL_LOAD_OFFSET;

        $minDealId = \SiteApp::init()->dataCache->getRedisInstance()->hGet($redisKey, $redisKeyRegin);
        if(!$minDealId) {
            $minDealId = 0;
        }

        $vipDealIds = !empty($vipDealIds) ? $vipDealIds : $this->getVipDealIds($minDealId);
        foreach($vipDealIds as $dealId) {
            $sql = "UPDATE `firstp2p_deal_load` SET deal_type=3 WHERE deal_id=".$dealId;
            $res = $GLOBALS['db']->query($sql);
            if(!$res){
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "deal_load clean error deal_id:{$dealId}")));
                break;
            }

            \SiteApp::init()->dataCache->getRedisInstance()->hSet($redisKey, $redisKeyRegin, $dealId);
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "deal_load clean success deal_id:{$dealId}")));
            usleep(500000);
        }
    }

    // 清洗deal_repay
    private function cleanDealRepay($deal_id){
        $sql = "UPDATE `firstp2p_deal_repay` SET deal_type=3 WHERE deal_id IN (SELECT id FROM `firstp2p_deal` WHERE project_id IN (SELECT id FROM `firstp2p_deal_project` WHERE product_class='%s'))";
        $sql = sprintf($sql,self::VIP_PROJECT_CLASS);

        $res = $GLOBALS['db']->query($sql);
        if(!$res) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"deal_repay clean error!")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"deal_repay clean success!")));
        }
        return $res;
    }

    // 清洗deal_prepay
    private function cleanDealPrepay(){
        $sql = "UPDATE `firstp2p_deal_prepay` SET deal_type=3 WHERE deal_id IN (SELECT id FROM `firstp2p_deal` WHERE project_id IN (SELECT id FROM `firstp2p_deal_project` WHERE product_class='%s'))";
        $sql = sprintf($sql,self::VIP_PROJECT_CLASS);
        $res = $GLOBALS['db']->query($sql);
        if(!$res) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"deal_prepay clean error!")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"deal_prepay clean success!")));
        }
        return $res;
    }

    /**
     * 清洗deal_loan_repay
     * @param array $vipDealIds 专享的deal_id
     * @return bool
     */
    private function cleanDealLoanRepay($vipDealIds=array()){
        $redisKey = self::VIP_DEAL_OFFSET_KEY;
        $redisKeyRegin = self::VIP_DEAL_LOAN_REPAY_OFFSET;

        $minDealId = \SiteApp::init()->dataCache->getRedisInstance()->hGet($redisKey, $redisKeyRegin);
        if(!$minDealId) {
            $minDealId = 0;
        }

        $vipDealIds = !empty($vipDealIds) ? $vipDealIds : $this->getVipDealIds($minDealId);
        if(empty($vipDealIds)) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "deal_loan_repay clean finish!")));
            return true;
        }

        foreach($vipDealIds as $dealId) {
            $sql = "UPDATE `firstp2p_deal_loan_repay` SET deal_type=3 WHERE deal_id=".$dealId;

            $res = $GLOBALS['db']->query($sql);
            if(!$res){
                \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "deal_loan_repay clean error deal_id:{$dealId}")));
                break;
            }

            \SiteApp::init()->dataCache->getRedisInstance()->hSet($redisKey, $redisKeyRegin, $dealId);
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "deal_loan_repay clean success deal_id:{$dealId}")));
            usleep(500000);
        }
    }

    /**
     * 获取专享标的
     * 可指定min_deal_id 来按照位置获取
     * @param int $min_deal_id
     * @return mixed
     */
    private function getVipDealIds($min_deal_id=0) {
        $sql = "SELECT id FROM `firstp2p_deal` WHERE project_id IN (SELECT id FROM `firstp2p_deal_project` WHERE product_class='%s') AND id > %d ORDER BY id ASC";
        $sql = sprintf($sql,self::VIP_PROJECT_CLASS,$min_deal_id);

        $res = $GLOBALS['db']->getAll($sql);
        $data = array();
        foreach($res as $val) {
            $data[]=$val['id'];
        }
        return $data;
    }

    /**
     * 获取通知贷标的
     * @return array
     */
    private function getCompoundDealIds($min_deal_id=0) {
        $min_deal_id = intval($min_deal_id);
        $sql = "SELECT id FROM `firstp2p_deal` WHERE deal_type=1 AND id > $min_deal_id";
        $res = $GLOBALS['db']->getAll($sql);
        $data = array();
        foreach($res as $val) {
            $data[]=$val['id'];
        }
        return $data;
    }

    // 清除缓存key
    public function delCacheKey($region,$key) {
        try{
            $res = \SiteApp::init()->dataCache->getRedisInstance()->hDel($region,$key);
            if(!$res) {
                throw new \Exception("redis key del fail key:{$key}");
            }
        }catch (\Exception $ex) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " redis key del fail ".$ex->getMessage())));
            exit;
        }
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " redis key del success")));
    }

    public function getCacheKey($key) {
        try{
            $res = \SiteApp::init()->dataCache->getRedisInstance()->hGetAll($key);
            if(!$res) {
                throw new \Exception("redis key del fail key:{$key}");
            }
        }catch (\Exception $ex) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " redis key del fail ".$ex->getMessage())));
            exit;
        }
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, " redis key:{$key} value:".var_export($res,true))));
        var_dump($res);
    }

}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new CleanDealData();
$obj->run($argv);