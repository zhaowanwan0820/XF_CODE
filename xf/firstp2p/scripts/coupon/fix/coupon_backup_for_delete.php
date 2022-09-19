<?php
/**
 *
 *
 * 从coupon_log 中删除流标的数据到bak表并删除旧表数据
 *
 * @date 2016-05-31
 * @author <zhaoxiaoan@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');

use core\service\CouponLogService;
use core\dao\CouponLogModel;
use libs\utils\Logger;

set_time_limit(0);

class backupForDelete
{
    public $pagesize = 1000;

    public $page = 0;

    public $total = 0;

    public $errMsg = '';


    public function run($argv)
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(" | ", array_merge($log_info, array('script start'))));
        $coupon_log_model_obj = new CouponLogModel();
        $list = $coupon_log_model_obj->getAuctionsBidList($argv[1]);
        if (empty($list)){
            Logger::info(implode(" | ", array_merge($log_info, array('script end list empty'))));
            return false;
        }
        $coupon_service = new CouponLogService();
        foreach ($list as $v){
            if (empty($v['id'])){
                continue;
            }
            $ret = $coupon_service->backupForDetele($v['id']);
            if ($ret===false){
                Logger::info(implode(" | ", array_merge($log_info, array('coupon_log id '.$v['id'],'deal_id '.$v['deal_id'].' unbackupForDelete'))));
                continue;
            }
            sleep(1);
        }

        Logger::info(implode(" | ", array_merge($log_info, array('script end'))));

    }
}
$backupForDeleteObj = new backupForDelete();
$backupForDeleteObj->run($argv);
exit;
