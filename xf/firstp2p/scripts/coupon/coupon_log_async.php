<?php
/**
 * coupon_log_async.php
 * deal_load 数据进入coupon_log 监控脚本
 * 脚本部署例子
 * php coupon_log_async.php -t 45 -o 30 -a 180 执行当前时间往前（45+30）分钟 到 当前执行时间往前30分钟之间的数据,如果遇到180分钟以上未处理的数据，发告警
 * php coupon_log_async.php -d 1000 指定执行deal_load_id =100 的数据
 * @date 2015-08-21
 * @author <wangzhen3@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../../app/init.php');
use libs\utils\Logger;
use core\service\CouponService;
set_time_limit(0);
//error_reporting(E_ALL &~E_DEPRECATED);

class coupon_log_async{

    // 取最近500w个deal_load id
    const MAX_ID_OFFSET = 5000000;

    public function run(){
        $log_info = array(__CLASS__, __FUNCTION__);

        $db_prefix = $GLOBALS['sys_config']['DB_PREFIX'];
        $opts  = getopt("t:o:d:a:");
        $time    = isset($opts['t'])&&intval($opts['t'])?intval($opts['t']):45;
        $offset = isset($opts['o'])&&intval($opts['o'])?intval($opts['o']):30;
        $alarm  = isset($opts['a'])&&intval($opts['a'])?intval($opts['a']):180;
        $deal_load_id = isset($opts['d'])&&intval($opts['d'])?intval($opts['d']):0;
        $end_time = get_gmtime() - $offset*60;
        $start_time = $end_time - $time*60;
        
        // 取deal_load id起点
        $sql_max_id = "select max(id) from firstp2p_deal_load";
        $max_id  = $GLOBALS['db']->get_slave()->getOne($sql_max_id);
        $max_id_offset = intval($max_id - self::MAX_ID_OFFSET);

        $sql = "select dl.id,dl.user_id,dl.short_alias,dl.create_time from ".$db_prefix."deal_load dl ";
        $sql .= " left join ".$db_prefix."coupon_log cg on dl.id= cg.deal_load_id ";
        $sql .= " where dl.id>'{$max_id_offset}' and cg.deal_load_id is null";

        if(empty($deal_load_id))
        {
           $sql .= " and dl.create_time >" .$start_time ." and dl.create_time <= ".$end_time ;
        }
        else
        {
            $sql .= " and dl.id= ".$deal_load_id;
        }

        $result  = $GLOBALS['db']->get_slave()->getAll($sql ,true);

        if(!empty($result))
        {
            foreach($result as $val)
            {
                $deal_load_id =$val['id'];
                $couponService = new CouponService();
                $res = $couponService -> consume($deal_load_id, '', 0, array(),CouponService::COUPON_SYNCHRONOUS);

                $delay_time = get_gmtime() - $val['create_time'];
                if($delay_time >= $alarm*60)//超过设置的时间没有进coupon_log发警告日志
                {
                    \libs\utils\Alarm::push('coupon_log_async', 'coupon_log数据延迟超过'.ceil($delay_time/60).'分钟,处理'.($res?'成功':'失败'), json_encode($val));
                }

                Logger::info(implode(" | ", array_merge($log_info,array('data:'.json_encode($val),"deal_load 数据进入coupon_log 监控脚本处理".($res?'成功':'失败')))));
            }
         }
    }
}
$coupon_log_async = new coupon_log_async();
$coupon_log_async->run();
exit;
