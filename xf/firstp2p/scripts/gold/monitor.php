<?php
/**
 * monitor.php
 * 15分钟或者半小时执行一次
 * 黄金监控
 *
 */


require_once(dirname(__FILE__) . '/../../app/init.php');

use libs\sms\SmsServer;

FP::import("libs.utils.logger");
FP::import("libs.common.dict");

set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(E_ALL ^ E_NOTICE);


class monitor {

    static public $alarmList = array(
            array(
                    'mobile'=>'GOLD_INVENTORY_MOBILE',//告警接收方手机号字典
                    'function'=>'checkInventory',
                    'className'=>__CLASS__,
                    'intervalTime'=> '3600',//告警间隔1小时
            ),
            array(
                    'mobile'=>'GOLD_INTEREST_MOBILE',//告警接收方手机号字典
                    'function'=>'checkInterestMoney',
                    'className'=>__CLASS__,
                    'intervalTime'=> '3600',//告警间隔1小时
            ),
            array(
                    'mobile'=>'GOLD_WITHDRAW_MOBILE',//告警接收方手机号字典
                    'function'=>'checkWithdrawMoney',
                    'className'=>__CLASS__,
                    'intervalTime'=> '3600',//告警间隔1小时
            )
    );


    static public $dealCurrent = array();

    /**
     * 获取黄金活期
     * @return array
     */
    static private function getDealCurrent(){
        if(empty(self::$dealCurrent)){
            $goldService = new core\service\GoldService();
            self::$dealCurrent = $goldService->getDealCurrentInfo();
        }
        return self::$dealCurrent;
    }

    public function run(){

        foreach (self::$alarmList as $list){
            try {
               $result = call_user_func_array(array($list['className'], $list['function']),array());
               if($result !== true){
                   $list['msg'] = $result;
                   $this->sendMsg($list);
               }
            } catch (\Exception $e) {
                \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$list);
            }
        }

    }

    /**
     * 发送告警短信
     * @param array $list
     */
    private function sendMsg($list){
        $key = 'gold_monitor_'.$list['function'].'_'.$list['className'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        //接收短信的名单为黄金开发管理人员和指定的运营人员
        $warn_mobiles = array_merge((array)dict::get('GOLD_WARN_MOBILE'),(array)dict::get($list['mobile']));
        foreach ($warn_mobiles as $mobile){
            if(is_numeric($mobile) && $redis->get($key.'_'.$mobile) != $mobile){
                SmsServer::instance()->send($mobile, 'TPL_COMPATIBLE', array($list['msg']));
                $redis->setex($key.'_'.$mobile,$list['intervalTime'],$mobile);
            }
        }
    }


    /**
     * 检查黄金存库
     * @throws Exception
     */
    private function checkInventory(){
        $dealCurrent = self::getDealCurrent();
        $goldService = new core\service\GoldService();
        $gold = $goldService->getGoldByUserId($dealCurrent['userId']);
        if($gold <= app_conf('GOLD_INVENTORY')){
            return "您好，黄金运营账号[{$dealCurrent['userId']}]黄金库存量剩余".$gold."克，请及时充值，谢谢";
        }
        return true;
    }


    /**
     * 检查付息账号
     * @throws Exception
     */
    private function checkInterestMoney(){
        $dealCurrent = self::getDealCurrent();
        $user = core\dao\UserModel::instance()->find($dealCurrent['interestUserId']);
        if($user['money'] <= app_conf('GOLD_INTEREST_MONEY')){
            return "您好，黄金付息账号[{$dealCurrent['interestUserId']}]资金剩余".$user['money']."元，请及时充值，谢谢";
        }
        return true;
    }

    /**
     * 检查变现账号
     * @throws Exception
     */
    private function checkWithdrawMoney(){
        $dealCurrent = self::getDealCurrent();
        $user = core\dao\UserModel::instance()->find($dealCurrent['withdrawUserId']);
        if($user['money'] <= app_conf('GOLD_WITHDRAW_MONEY')){
            return "您好，黄金变现账号[{$dealCurrent['withdrawUserId']}]资金剩余".$user['money']."元，请及时充值，谢谢";
        }
        return true;
    }
}

$monitor = new monitor();
$monitor->run();
