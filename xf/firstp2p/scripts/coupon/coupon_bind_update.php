<?php
/**
 *
 *
 * 定时检查优惠码是否绑定
 *
 * @date 2015-07-14
 * @author <zhaoxiaoan@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\Logger;
use core\service\CouponBindService;
set_time_limit(0);
ini_set('memory_limit', '2048M');

class CouponBindFixed{
    public function run(){
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(" | ", array_merge($log_info, array('script start'))));
        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
       // $minid_sql = "SELECT min(u.id)  FROM {$db_prefix}user u LEFT JOIN  {$db_prefix}coupon_bind b ON u.id=b.user_id WHERE u.id >=$min_u_id AND  (b.user_id is null OR b.is_fixed=0)";
        //$minid  = $GLOBALS['db']->get_slave()->getOne($minid_sql);
        $minid= 7378210;
        if (empty($minid)) {
            Logger::info(implode(" | ", array_merge($log_info, array('min id is empty'))));
            return false;
        }

        $maxid_sql = "SELECT max(id) FROM {$db_prefix}user ";
        $maxid = $GLOBALS['db']->get_slave()->getOne($maxid_sql);
        if (empty($maxid)) {
            Logger::info(implode(" | ", array_merge($log_info, array('max id is empty'))));
            return false;
        }
        $maxid = $maxid + 4000;
        $where =  ' u.id >='.intval($minid).' AND u.id<'.intval($maxid).' ';
        $uids_sql = "SELECT u.id AS user_id FROM {$db_prefix}user u LEFT JOIN  {$db_prefix}coupon_bind b ON u.id=b.user_id WHERE (b.user_id is null OR b.is_fixed=0) and $where ";

        $uids = $GLOBALS['db']->get_slave()->getAll($uids_sql);
        if (empty($uids)){
            Logger::info(implode(" | ", array_merge($log_info, array('data is empty'))));
            return false;
        }
        // 记录所有
        //Logger::info(implode(" | ", array_merge($log_info, array('uids ',json_encode($uids)))));
        $couponBindService = new CouponBindService();
        foreach ($uids as $uid_value){
            // 检查并修复
            $ret = $couponBindService->init($uid_value['user_id']);
            Logger::info(implode(" | ", array_merge($log_info, array('uid  process ',$uid_value['user_id'],$ret))));
        }
    }
}

try {
    $initCouponBindObj = new CouponBindFixed();
    $initCouponBindObj->run();
    Logger::info(implode(" | ",array('CouponBindFixed','run',' script end')));
}catch (\Exception $e){
    \libs\utils\Alarm::push('CouponBindFixed', 'error:'.$e->getMessage());
    Logger::error(implode(" | ",array('CouponBindFixed','run',' script end','error:'.$e->getMessage())));
}
exit;
