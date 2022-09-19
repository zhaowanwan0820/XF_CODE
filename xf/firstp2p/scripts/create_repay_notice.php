<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 2017/26
 * Time: 下午2:47
 */

require_once dirname(__FILE__).'/../app/init.php';

use libs\utils\Logger;
use libs\rpc\Rpc;

class CreateRepayNotice {

    public function run() {
        $rpc = new Rpc();
        try{
            //默认一周过期
            //更新列表页
            \SiteApp::init()->dataCache->call($rpc, 'local', array('DealRepayService\getRepayDealList', array(10,1)), 604800,true);
            //更新首页
            \SiteApp::init()->dataCache->call($rpc, 'local', array('DealRepayService\getRepayDealList', array(11,1)), 604800,true);

        } catch(\Exception $e){
            Logger::error("保存还款公告数据失败!".$e->getMessage());
            \libs\utils\Alarm::push('repay_notice', '保存还款公告数据失败!', $e->getMessage());
            exit;
        }

        Logger::info("生成还款公告数据成功! ".date('Y-m-d H:i:s'));
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new CreateRepayNotice();
$obj->run();