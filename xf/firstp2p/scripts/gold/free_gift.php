<?php
/**
 * 阳光普照只执行一次
 *
 *
 *
 */


require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\utils\Logger;
FP::import("libs.common.dict");

set_time_limit(0);
ini_set('memory_limit', '2048M');
error_reporting(E_ALL ^ E_NOTICE);

use core\service\GoldService;
use core\service\UserService;
use NCFGroup\Common\Library\Idworker;



class freeGift {
    public static $group_id = '468,465,464,457,455,454,450,439,437,436,422,418,417,416,415,413,412,411,410,389,384,383,382,347,345,303,302,285,259,250,248,237,225,222,190,189,171,166,163,162,153,151,149,147,145,143,139,132,128,126,118,114,100,93,85,84,65,59,57,55,50,48,24,14,13,12,11';

    public static $user_cache_key = 'gold_shine_user_';

    public static $cache_time = 172800; // 缓存两天时间

    public static $buyAmount = '0.05';

    public static $operateUserId = '8229594';

    public function run($argv){
        $log = array(__CLASS__, __FUNCTION__);
        Logger::info(array_merge($log, array('start')));

        $group_id_list = explode(",",self::$group_id);
        if (empty($group_id_list)){
            Logger::error(implode(" | ",array_merge($log, array('group list is empty'))));
            return false;
        }
        $goldPriceService = (new core\service\GoldService())->getGoldPrice();
        if ($goldPriceService['errCode'] !=0 ){
            Logger::error(implode(" | ",array_merge($log, array('gold price error'))));
            return false;
        }
        $buyPrice = $goldPriceService['data']['gold_price'];
        if (isset($argv[1]) && $argv[1] == 0){
            // 只统计
            foreach($group_id_list as $group_id) {
                if (empty($group_id)){
                    Logger::error(implode(" | ",array_merge($log, array('group id is empty'))));
                    return false;
                }
                $sql = "select count(id)  from firstp2p_user where group_id='{$group_id}'";
                $group_total_user = $GLOBALS['db']->get_slave()->getOne($sql);


                Logger::info(implode(' | ',array_merge($log, array('stat group_id '.$group_id .' total '.$group_total_user))));
            }

            Logger::info(implode(' | ',array_merge($log, array('stat group_id done '))));
            return true;
        }

        // 开始执行处理数据
        foreach($group_id_list as $group_id) {
            Logger::info(implode(" | ",array_merge($log, array('group_id '.$group_id.' start'))));


            $sql = "select id from firstp2p_user where group_id='{$group_id}' ";

            $list = $GLOBALS['db']->get_slave()->getAll($sql);

            $ret = $this->processUser($list,$group_id,$buyPrice);
            if ($ret == false){
                echo 'process group id '.$group_id.' user list failed';
                exit;
            }
            Logger::info(implode(" | ",array_merge($log, array('group_id '.$group_id.' done'))));
        }
        // 组循环结束
        Logger::info(implode(" | ",array_merge($log, array('run  done'))));
    }


    public function processUser($user_list,$group_id,$buyPrice){
        $log = array(__CLASS__, __FUNCTION__);
        $cache = \SiteApp::init()->cache;
        if (empty($user_list)){
            Logger::error(implode(" | ",array_merge($log, array(' send  group id '.$group_id.' user list empty'))));
            return true;
        }

        // 开始循环组下面用户
        foreach($user_list as $user_info){
            if (empty($user_info['id'])){
                continue;
            }

            $check_user_service = new UserService($user_info['id']);
            $check_user_result = $check_user_service->isBindBankCard(array());
            if (empty($check_user_result['ret'])){
                Logger::error(implode(" | ",array_merge($log, array('checkuser faild'.json_encode($check_user_result)))));
                \libs\utils\Alarm::push('freeGift_dobid_error','errMsg: checkuser faild'.$user_info['id'], $check_user_result);
                continue;
            }
            $user_exist = $cache->get(self::$user_cache_key.$user_info['id']);
            if (!empty($user_exist)){
                Logger::info(implode(" | ",array_merge($log, array(' send  group id '.$group_id. ' user id '.$user_info['id'].' existed '))));
                continue;
            }
            try{
                    $goldService = new GoldService();
                    // 存在更新不存在插入
                    $ret = $goldService->Auth($user_info['id']);
                    if ($ret['errCode'] != 0){
                        Logger::error(implode(" | ",array_merge($log, array('create gold user '.$user_info['id'].' failed'))));
                        \libs\utils\Alarm::push('freeGift_dobid_error','errMsg: create gold user'.$user_info['id'], array('user_id '.$user_info['id']));
                        continue;
                    }
                $ret = $this->doBid($user_info['id'],self::$buyAmount,$buyPrice);
                if (empty($ret)){
                    throw new \Exception('user '.$user_info['id'].'dobid failed');
                }
                $cache->set(self::$user_cache_key.$user_info['id'],1,self::$cache_time);
            }catch (\Exception $e){
                Logger::error(implode(" | ",array_merge($log, array(' send  group id '.$group_id.' user id '.$user_info['id'].' failed error'.$e->getMessage()))));
                \libs\utils\Alarm::push('freeGift_dobid_error','errMsg:'.$e->getMessage(), array('group_id' => $group_id,'user_id' => $user_info['id']));
                continue;
            }

            Logger::info(implode(" | ",array_merge($log, array('group_id '.$group_id.' user id '.$user_info['id'].' done'))));
        }

        return true;
    }

    public function doBid($userId,$buyAmount,$buyPrice){
        $orderId = Idworker::instance()->getId();
        $goldBidRechargeService = new core\service\GoldBidRechargeService($userId,$buyAmount, $buyPrice,'',$orderId,self::$operateUserId);
        $result  = $goldBidRechargeService->doBid();
        return $result;
    }
}

$monitor = new freeGift();
$monitor->run($argv);

