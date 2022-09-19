<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/9/19
 * Time: 9:47
 */


require(dirname(__FILE__) . '/../app/init.php');
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;

echo "\t*** start ".date('Y-m-d H:i:s')." ***\n";

set_time_limit(0);

class AppStepBonusCount {

    public function run() {
        $start = microtime(true);
        $minTime = mktime(0,0,0,date('m'),date('d'),date('Y'));//当天0:0:0秒
        try {
            $sql = 'SELECT COUNT(*) as count FROM '.DB_PREFIX.'app_steps_bonus where update_time > '.$minTime.' UNION ALL SELECT COUNT(*) as count FROM '.DB_PREFIX.'app_steps_bonus where is_award = 1 AND update_time >  '.$minTime ;
            $res = $GLOBALS['db']->get_slave()->getAll($sql);
            //获取领奖和单用户访问量
            $visitCount = $res[0]['count'];
            $awardCount = $res[1]['count'];
            //获取页面点击量
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $minTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
            $countKey = 'APPSTEPSBONUSSERVICE-GETSTEPINFO-COUNT'.$minTime   ;
            $kitCount = $redis -> get($countKey);
            $content = date ( "Y-m-d ") . '单用户访问量：'.$visitCount.'领奖人数：'.$awardCount.'点击量：'.$kitCount;
            $this->send_mail_sms($content);
            $time = round(microtime(true) - $start, 4);
            Logger::info(implode('|', array(__FILE__,__CLASS__,__FUNCTION__,'耗时：'.$time,$content)));

        } catch(Exception $e) {
            \libs\utils\Alarm::push('ZzjrWithdrawStatus','脚本执行异常', __FILE__.__CLASS__.__FUNCTION__.$e->getMessage());
            Logger::info(implode(" | ",array(__FILE__,__CLASS__,__FUNCTION__,'脚本执行异常',$e->getMessage())));
        }
    }
    public function send_mail_sms($content) {
        $msgcenter = new msgcenter();
        $msgcenter->setMsg('zhaohui3@ucfgroup.com', '', $content, false,'网信健步走统计');
        $msgcenter->setMsg('chaizhenyu@ucfgroup.com', '', $content, false,'网信健步走统计');
        $result = $msgcenter->save();
        return $result;
    }

}
echo "\t*** end ".date('Y-m-d H:i:s')." ***\n";
(new AppStepBonusCount)->run();
