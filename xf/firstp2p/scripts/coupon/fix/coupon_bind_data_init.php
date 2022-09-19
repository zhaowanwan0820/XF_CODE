<?php
/**
 * coupon_deal_data_init.php
 *
 * 新建coupon_bind表后，初始化用户和优惠码绑定关系，只上线后执行一次.
 *
 * @date 2015-07-09
 * @author <zhaoxiaoan@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use core\service\CouponBindService;
set_time_limit(0);

class initCouponBind{
    public function run($argv){
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(" | ", array_merge($log_info, array('script start'))));
        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
        $count_sql = 'SELECT MAX(`id`) FROM '.$db_prefix.'user';
        $list_count = $GLOBALS['db']->get_slave()->getOne($count_sql);
        if (empty($list_count)){
            Logger::info(implode(" | ", array_merge($log_info, array('data is empty'))));
            return false;
        }
        $start_id = 1;
        if ( !empty($argv) && is_numeric($argv[1]) && $argv[1]> 0){
            $start_id = $argv[1];
        }
        $current_user_id = $start_id;
        // 尽量减少漏掉的新用户
        $list_count = $list_count+2000;

        if ( !empty($argv) && is_numeric($argv[2]) && $argv[2]> 0){
            $list_count = $argv[2];
        }
        for($i=$start_id;$i<=$list_count;$i++) {

            $select_user_sql = 'SELECT id FROM '.$db_prefix.'user WHERE id='.$i;
            $user_info = $GLOBALS['db']->get_slave()->getRow($select_user_sql, true);
            if (empty($user_info)) {
                Logger::info(implode(" | ", array_merge($log_info, array('userid is empty ' . $i))));
                continue;
            }
            $couponService = new CouponBindService();
            $ret = $couponService->addInitTask($i);
            unset($select_user_sql);
            $current_user_id = $i;
            Logger::info(implode(" | ", array_merge($log_info, array('current_user_id is '.$current_user_id,' result '.$ret))));
        }
        Logger::info(implode(" | ", array_merge($log_info, array('complete last userid '.$current_user_id))));
    }
}
$initCouponBindObj = new initCouponBind();
$initCouponBindObj->run($argv);
exit;
