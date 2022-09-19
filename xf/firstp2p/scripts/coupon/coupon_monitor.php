<?php
/**
 * coupon_monitor.php
 *
 * 优惠码返利监听程序
 * 0 9 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php coupon_monitor.php
 *
 * @date   2015-01-21
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../../app/init.php');

use core\dao\BaseModel;
use core\service\CouponService;
use libs\sms\SmsServer;

FP::import("libs.utils.logger");
FP::import("libs.libs.msgcenter");
FP::import("libs.common.dict");

set_time_limit(0);
ini_set('memory_limit', '256M');
error_reporting(E_ALL ^ E_NOTICE);

class CouponMonitor {

        public static $is_debug = false;

        /**
         * 报警 sql 配置
         * @var array
         */
        public static $check_list = array(
            array(
                'title' => '邀请码记录异步任务执行概览',
                'sql' => <<<ROGER
select from_unixtime(d.create_time+28800, '%Y-%m-%d') run_date, avg(l.create_time-d.create_time) avg_time, max(l.create_time-d.create_time) max_time from firstp2p_deal_load d, firstp2p_coupon_log l 
where d.id=l.deal_load_id and d.id >{id_begin}  group by run_date order by run_date desc
ROGER
            ,
                'days' => 7,
                'sql_id_begin' => 'select cast(max(id) as signed) - 200000 from firstp2p_deal_load',
            ),
            array(
                'title' => '投资记录与优惠码记录对应检查',
                'sql_select' => 'SELECT d.id, from_unixtime(d.create_time+28800) create_time',
                'sql_where' => <<<ROGER
from firstp2p_deal_load d left join firstp2p_coupon_log l on d.id=l.deal_load_id
where d.id >{id_begin} and d.create_time>'{time_start}' AND d.create_time<'{time_end}' and l.id is null
ROGER
            ,
                'days' => 3,
                'sql_id_begin' => 'select cast(max(id) as signed) - 200000 from firstp2p_deal_load',
            ),
            array(
                'title' => '通知贷优惠码脏数据检查',
                'sql_select' => 'SELECT id, pay_status, rebate_days, deal_repay_days, from_unixtime(create_time+28800)',
                'sql_where' => <<<FEDERER
from firstp2p_coupon_log
where id >{id_begin} and deal_type='1' and (rebate_days>7 or deal_repay_days<='0' or pay_status not in ('0','2','5') or add_type='2')
and create_time>'{time_start}' AND create_time<'{time_end}'
FEDERER
            ,
                'days' => 7,
                'sql_id_begin' => 'select cast(max(id) as signed) - 2000000 from firstp2p_coupon_log',
            ),
            array(
                'title' => '通知贷优惠码未结算数据检查',
                'sql_select' => 'SELECT id, pay_status, from_unixtime(create_time+28800)',
                'sql_where' => <<<FEDERER
from firstp2p_coupon_log l
where l.deal_type='1' and l.pay_status>'0'
and not exists (select 1 from firstp2p_coupon_pay_log p where l.deal_load_id=p.deal_load_id)
FEDERER
            ,
                'days' => 7,
            ),
            array(
                'title' => '标优惠码设置和标是否一致检查',
                'sql_select' => 'SELECT d.id deal_id ',
                'sql_where' => 'FROM firstp2p_deal d LEFT JOIN firstp2p_coupon_deal cd ON d.id = cd.deal_id WHERE d.is_delete =0 AND cd.deal_id IS NULL',
            ),
            array(
                'title' => 'jobs执行异常的优惠码任务',
                'sql_select' => 'SELECT id, status, from_unixtime(create_time+28800) create_time, function, params, err_msg',
                'sql_where' => "FROM  `firstp2p_jobs` WHERE STATUS in (0,3) AND `function` LIKE '%CouponLog%' AND create_time>'{time_start}'",
                'days' => 3,
            ),
            /* 邀请码体系升级，关闭
            array(
                'title' => '用户组和组等级检查',
                'sql_select' => 'SELECT u.id user_id, u.group_id user_group_id, u.coupon_level_id error_user_coupon_level_id, l.group_id group_id_of_error_level ',
                'sql_where' => 'FROM firstp2p_user u  LEFT JOIN firstp2p_coupon_level l ON u.coupon_level_id = l.id WHERE u.id >{id_begin} and u.group_id <> l.group_id AND u.is_delete = 0 ',
                //'count_warn' => 20,
                'sql_id_begin' => 'select cast(max(id) as signed) -100000 from firstp2p_user',
                'warn_extra_key' => 'GROUP_LEVEL',
            ),
            /* 贴息自2016年后不再使用，暂时关闭 --20190118
            array(
                'title' => '贴息标信息表脏数据',
                'sql_select' => 'SELECT e.id, e.deal_id, e.type, e.status, e.interest_days ',
                'sql_where' => <<<FEDERER
from firstp2p_interest_extra e left join firstp2p_deal d on e.deal_id=d.id
where d.name is null or e.success_time<>d.success_time or e.repay_start_time<>d.repay_start_time or e.interest_days<=0
FEDERER
            ),
            array(
                'title' => '贴息处理积压数据',
                'sql_select' => 'SELECT distinct(from_unixtime(create_time+28800)) create_time ',
                'sql_where' => "FROM firstp2p_interest_extra where `status`='1' AND create_time<'{time_end}'",
                'count_warn' => 400,
            ),
            array(
                'title' => '贴息记录结算脏数据',
                'sql_select' => 'select a.deal_id, el_count, dl_count ',
                'sql_where' => <<<ROGER
from (select el.deal_id, count(el.deal_load_id) el_count from firstp2p_interest_extra_log el where el.deal_id in
(select deal_id from firstp2p_interest_extra where status='2' and pay_date>date_sub(now(), interval 7 day)) AND el.income_type=0 group by el.deal_id) a,
(select dl.deal_id, count(dl.id) dl_count from firstp2p_deal_load dl where dl.deal_id in
(select deal_id from firstp2p_interest_extra where status='2' and pay_date>date_sub(now(), interval 7 day)) group by dl.deal_id) b
where a.deal_id=b.deal_id and a.el_count<>b.dl_count
ROGER
            ,
            ),
            */
            array(
                'title' => '邀请码记录重复数据检查',
                'sql_select' => 'SELECT deal_load_id, count(id) ',
                'sql_where' => "FROM firstp2p_coupon_log where id>'{id_begin}' and deal_load_id>0 group by deal_load_id having count(id)>1",
                'sql_id_begin' => 'select cast(max(id) as signed) - 2000000 from firstp2p_coupon_log',
            ),
            array(
                'title' => 'siteId记录异常检查',
                'sql_select' => 'SELECT id,deal_load_id ',
                'sql_where' => "FROM firstp2p_coupon_log where id>'{id_begin}' and deal_load_id>0  and site_id  = 0 ",
                'sql_id_begin' => 'select cast(max(id) as signed) - 2000000 from firstp2p_coupon_log',
            ),
            array(
                    'title' => '邀请码记录投资id为0',
                    'sql_select' => 'SELECT id,deal_load_id ',
                    'sql_where' => "FROM firstp2p_coupon_log where id>'{id_begin}' and deal_load_id=0",
                    'sql_id_begin' => 'select cast(max(id) as signed) - 2000000 from firstp2p_coupon_log',
                 ),
            //活期黄金相关账户资金报警
            /*array(
                    'title' =>'活期黄金变现账户的资金阈值检查',
                    'sql_select'=>'SELECT count(*) ',
                    'sql_where'=> "FROM firstp2p_user WHERE id = 9119114 and money<=10000",
                    'count_warn'=> 0,
                 ),*/
            /*array(
                    'title' =>'活期黄金结息账户的资金阈值检查',
                    'sql_select'=>'SELECT count(*) ',
                    'sql_where'=>"FROM firstp2p_user WHERE id = 9119098 and money<=300",
                    'count_warn'=> 0,
                 ),*/
        );

    public function run() {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, 'start')));
        $model = new BaseModel();
        foreach (self::$check_list as $k => $v) {
            $count_warn = isset($v['count_warn']) ? intval($v['count_warn']) : 0;
            $day_length = isset($v['days']) ? intval($v['days']) : 0;
            $time_end = mktime(-8, 0, 0, date("m"), date("d"), date("Y")); //进入零点时间
            $time_start = $time_end - 86400 * $day_length;
            $id_begin = 0;

            $sql_count = '';
            $sql = '';
            $count = false;
            $sms = '';
            if (isset($v['sql_where'])) {
               $sql = $v['sql_select'] . ' ' . $v['sql_where'];
               $sql_count = "SELECT COUNT(*) " . $v['sql_where'];
            } else {
                $sql = $v['sql'];
            }

            if (empty($sql)) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, json_encode($v), 'error:empty sql')));
                continue;
            }

            if (!empty($v['sql_id_begin'])) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $v['sql_id_begin'])));
                $id_begin = $model->countBySql($v['sql_id_begin'], array(), true);
            }

            // 如果有sql_count（sql_where），小于报警阀值则跳过继续执行下个检查，大于报警阀值则发短信邮件；没有sql_count，则按sql查询结果直接发送邮件。
            if (!empty($sql_count)) {
                $sql_count = str_replace(array('{time_start}', '{time_end}', '{id_begin}'), array($time_start, $time_end, $id_begin), $sql_count);
                $count = $model->countBySql($sql_count, array(), true);
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $count, $sql_count)));
            }
            if (!empty($sql_count) && $count <= $count_warn) {
                continue;
            }

            $sql = str_replace(array('{time_start}', '{time_end}', '{id_begin}'), array($time_start, $time_end, $id_begin), $sql);
            $sql .= ' LIMIT 30';

            $result = $model->findAllBySql($sql, true, false, true);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $count, $sql)));

            // 有count则做短信报警
            if (!empty($count)) {
                $sms = $v['title'] . "，出现错误数据{$count}条，" . date("Y-m-d");
                $sms .= empty($v['days']) ? '' :  "前{$v['days']}天";
            }

            $this->warn_msg($v, $count, $sms, $sql, $result);
        }
        if (self::$is_debug) {
            return;
        }
        $notice_title = "邀请码监控报警 每日监控任务执行完毕 " . date("Y-m-d H:i");
        $notice_content = "邀请码监控报警 每日监控任务执行完毕 " . date("Y-m-d H:i");
        CouponService::sendWarnMsg($notice_title, $notice_content);
    }


    /**
     * 发送报警通知
     */
    private function warn_msg($item, $count, $sms, $sql, $result) {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $sms, $sql)));
        if (self::$is_debug) {
            return;
        }
        $warn_list = dict::get('COUPON_WARN');
        if (isset($item['warn_extra_key']) && !empty($item['warn_extra_key'])) {
            $warn_list_extra = dict::get('COUPON_WARN' . '_' . strtoupper($item['warn_extra_key']));
            if (!empty($warn_list_extra)) {
                $warn_list = array_merge($warn_list, $warn_list_extra);
            }
        }
        if (empty($warn_list)) {
            return true;
        }
        $title = "邀请码监控报警 - {$item['title']}";
        $result  = json_encode($result);
        $result = str_replace('},', '},<br/>', $result);
        $content = $sms . '<p/>' . $sql . '<p/><p/><pre>' . $result . '</pre>';
        $msgcenter = new \msgcenter();
        foreach ($warn_list as $dest) {
            if (is_numeric($dest)) { // 短信, 没有count值不发
                if ($count) {
                    $rs = SmsServer::instance()->send($dest, 'TPL_COMPATIBLE', array($sms));
                }
            } else { // 邮件
                $rs = $msgcenter->setMsg($dest, 0, $content, false, $title);
            }
        }
        $rs = $msgcenter->save();
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, json_encode($item), $sms, json_encode($rs))));
    }

}

$monitor = new CouponMonitor();
$monitor->run();
