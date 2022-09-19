<?php
/**
 * coupon_register.php
 * 注册邀请码没有coupon_log 补刀脚本
 * @date 2015-11-20
 * @author <wangzhen3@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../../app/init.php');
use libs\utils\Logger;
use core\service\CouponService;
use core\service\CouponLogService;
set_time_limit(0);

class coupon_register{
    public function run(){
        $log_info = array(__CLASS__, __FUNCTION__);

        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
        $opts  = getopt("d:o:");
        $time  = intval($opts['d'])?intval($opts['d'])*86400:259200;//默认三天
        $offset  = intval($opts['o'])?intval($opts['o'])*60:86400;//默认一天

        $sql = "select id,invite_code,create_time from ".$db_prefix."user" . " where create_time >=" .(get_gmtime() - $time) . " and create_time <=" .(get_gmtime() - $offset) . " and invite_code !=''";
        $sql .= " and not exists (select consume_user_id from ".$db_prefix."coupon_log_reg  where consume_user_id = ".$db_prefix."user.id and deal_load_id = 0)";

        $result  = $GLOBALS['db']->get_slave()->getAll($sql ,true);

        if(!empty($result))
        {
            $coupon_service = new CouponService();
            foreach($result as $val)
            {
                $res = $coupon_service->regCoupon($val['id'], $val['invite_code'],CouponLogService::ADD_TYPE_ADMIN);
                if($res)
                {
                    Logger::info(implode(" | ", array_merge($log_info,array('data:'.json_encode($val),"注册邀请码未进入coupon_log脚本处理成功"))));
                }
            }
         }
    }
}
$coupon_register = new coupon_register();
$coupon_register->run();
exit;
