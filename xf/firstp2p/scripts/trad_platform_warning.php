<?php
/**
 * 异步更新交易平台预警数据
 */

use libs\rpc\Rpc;
use libs\utils\Logger;
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
echo "\t*** start ".date('Y-m-d H:i:s')." ***\n";
set_time_limit(0);

class TradPlatformWarning {
    public function run () {

        try {
            $rpc = new Rpc();
            $start = microtime(true);
            $trad_plat = $rpc->local('DealProjectService\getPlatManagement', array());
            $time_trad_plat = round(microtime(true) - $start, 4);
            $trad_product = $rpc->local('DealProjectService\getProductManagement', array());
            $time = round(microtime(true) - $start, 4);
            //根据平台更新结果判断是否触发报警:0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示

            $cache = \SiteApp::init()->cache;
            $expire_time = 24*3600;

            if (!empty($trad_plat)) {
                foreach ($trad_plat as $key => $value) {
                    if ($value['level'] == 2) {
                        $rekey = 'trad_platform_waring_key_email_'.$key;
                        $title = '平台用款限额预警';
                        $content = $value['advisory_name'].'可用用款限额低于10%，请及时处理';
                        if ($cache->get($rekey) != 1) {
                            $msgcenter = new msgcenter();
                            $msgcenter->setMsg('1040406303@qq.com', '', $content, false,$title);
                            $msgcenter->setMsg('guolei@ucfgroup.com', '', $content, false,$title);
                            $msgcenter->setMsg('zangmeijie@ucfgroup.com', '', $content, false,$title);
                            $msgcenter->setMsg('yuxiaolin@ucfgroup.com', '', $content, false,$title);
                            $result = $msgcenter->save();
                            $cache->set($rekey,1,$expire_time);
                            if ($result) {
                                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_platform success email',$rekey,$result,$content)));
                            }
                        }
                    } elseif ($value['level'] == 3) {
                        $rekey = 'trad_platform_waring_key_sms_'.$key;
                        $content = array('0'=>$value['advisory_name'].'可用用款限额低于5%，请及时处理');
                        if ($cache->get($rekey) != 1) {
                            $msgcenter = new msgcenter();
                            $msgcenter->setMsg('15232738474', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $msgcenter->setMsg('17326832726', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $msgcenter->setMsg('15166055799', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $msgcenter->setMsg('13520349919', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $result = $msgcenter->save();
                            $cache->set($rekey,1,$expire_time);
                            if ($result) {
                                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_platform success sms',$rekey,$result,$content)));
                            }
                        }
                    }
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_platform 耗时：'.$time_trad_plat)));
            }
            //根据产品更新结果判断是否触发报警:0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示

            if (!empty($trad_product)) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_product 耗时：'.($time - $time_trad_plat))));
                foreach ($trad_product as $key => $value) {
                    if ($value['level'] == 2) {
                        $rekey = 'trad_product_waring_key_email_'.$key;
                        $title = '平台用款限额预警';
                        $content = $value['product_name'].'可用用款限额低于10%，请及时处理';
                        if ($cache->get($rekey) != 1) {
                            $msgcenter = new msgcenter();
                            $msgcenter->setMsg('1040406303@qq.com', '', $content, false,$title);
                            $msgcenter->setMsg('guolei@ucfgroup.com', '', $content, false,$title);
                            $msgcenter->setMsg('zangmeijie@ucfgroup.com', '', $content, false,$title);
                            $msgcenter->setMsg('yuxiaolin@ucfgroup.com', '', $content, false,$title);
                            $result = $msgcenter->save();
                            $cache->set($rekey,1,$expire_time);
                            if ($result) {
                                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_product success email',$key,$result,$content)));
                            }
                        }
                    } elseif ($value['level'] == 3) {
                        $rekey = 'trad_product_waring_key_sms_'.$key;
                        $content = array('0'=>$value['product_name'].'可用用款限额低于5%，请及时处理');
                        if ($cache->get($rekey) != 1) {
                            $msgcenter = new msgcenter();
                            $msgcenter->setMsg('15232738474', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $msgcenter->setMsg('17326832726', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $msgcenter->setMsg('15166055799', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $msgcenter->setMsg('13520349919', '', $content, 'TPL_SMS_ZZ_ALARM_NOTIFY');
                            $result = $msgcenter->save();
                            $cache->set($rekey,1,$expire_time);
                            if ($result) {
                                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,'trad_product success sms',$rekey,$result,$content)));
                            }
                        }
                    }
                }
            }

            if(empty($trad_plat)){
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"tradePlatformWarning err")));
            }elseif (empty($trad_product)) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"tradeProductWarning err")));
            }else{
                Logger::info("tradePlatformWarning success 共耗时".$time);
            }
        } catch (\Exception $ex) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage())));
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"trad_platform_warning run,共耗时：".$time)));
    }
}
echo "\t*** end ".date('Y-m-d H:i:s')." ***\n";
(new TradPlatformWarning)->run();
