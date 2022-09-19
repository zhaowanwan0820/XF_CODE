<?php
/**
 * @desc 用户新首页通过邀请码key 找到pageId 每2分钟执行一次
 * User: jinhaidong
 * Date: 2017-6-19 10:47:23
 */
require_once dirname(__FILE__).'/../app/init.php';


use libs\utils\Logger;
use libs\utils\Alarm;
use core\service\NewUserPageService;
use core\dao\NewUserPageModel;

class NewUserPageCache {

    public function run(){
        $m = new NewUserPageModel();
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",getRedisInstance failed");
            return false;
        }
        $keyPrefix = NewUserPageService::KEY_PREFIX_PAGE_CACHE;
        $list = $m->findAllViaSlave(array(),true,'id,invite_codes');
        foreach($list as $val){
            $inviteCodes = explode(",",$val['invite_codes']);
            foreach($inviteCodes as $code){
                $redis->set($keyPrefix.$code,$val['id']);
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",邀请码缓存设置成功 inviteCode:{$code},pageId:".$val['id']);
            }
        }
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new NewUserPageCache();
$obj->run();