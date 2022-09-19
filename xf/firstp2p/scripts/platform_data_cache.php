<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 2017/2/7
 * Time: 下午6:32
 */

require_once dirname(__FILE__).'/../app/init.php';

use core\service\PlatformPublishService;
use core\service\PaymentService;
use core\service\UserCarryService;
use core\dao\PaymentNoticeModel;
use core\dao\UserModel;
use core\dao\UserCarryModel;
use core\dao\DealModel;
use libs\utils\Logger;

//生成平台统计数据缓存

class PlatformDataCache {

    public function run() {
        //指定时间,5月20日以后的
        $time = strtotime("2016-5-20 00:00:00");
        //指定类型,普通标,通知贷
        $dealType = '0,1';

        try{
            $publishService = new PlatformPublishService();
            if($publishService->create($dealType,$time)){
                Logger::info("平台披露信息生成成功! ".date('Y-m-d H:i:s'));
            }
        } catch(\Exception $e){
            Logger::error("平台披露信息生成失败!".$e->getMessage());
            exit;
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new PlatformDataCache();
$obj->run();