<?php
/**
 * 累积发送用户返利短信
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;
use core\dao\UserLogModel;
use core\service\MsgBoxService;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use libs\sms\SmsServer;

set_time_limit(0);
class sms_send_user_log_daily {

    public $page_size = 10;
    public $page = 0;

    // 增加 id > id_begin 缩小检索范围, 检索范围取偏移量和总数百分比的最大值
    public $id_offset = 500000; // 最大id偏移量, 默认50w，即取最后50w条
    public $id_percent = 1; // 总数的百分比, 默认1%，即1kw数据取最后10w条

    // user log 类型对应的短信模板
    public static $sms_template_type = array(
        '优惠码返利' => 'TPL_SMS_USER_LOG_COUPON_REBATE_NEW',
        '服务奖励'=>'TPL_SMS_USER_LOG_COUPON_REBATE_NEW'
    );

    function run($argv=array()){
        $log = array(__CLASS__, __FUNCTION__);
        Logger::wLog(array_merge($log, array('start')));
        if (empty($argv[1])) {
            // 默认取前一天的记录
            $start_time = strtotime("-1 day");
            $start_date = date("Y-m-d", $start_time);
        } else {
            $start_date = $argv[1];
        }

        // cache expire_time
        // 过期时间28天
        $expire_time = 86400 * 28;
        $couponRebateSwitch = app_conf('COUPON_REREFER_REBATE_BY_RED_TRUN_ON');
        // 昨天19点
        $start_time = $couponRebateSwitch == 1 ? strtotime($start_date.' 19:00:00') : to_timespan($start_date.' 19:00:00');
        $end_time = $start_time + 24 * 3600;
        //$end_time = to_timespan(date("Y-m-d").' 18:59:00');

        $cache = \SiteApp::init()->cache;
        $cache_key = 'sms_send_user_log_'.$start_date.'_';

        if ($couponRebateSwitch == 1) {
            // 开启红包返利
            $id_max = $GLOBALS['db']->get_slave()->getOne("SELECT max(id) FROM firstp2p_oto_allowance_log");
            // 每天可能产生的记录总数为200000
            $dayTotalCount = 200000;
            $i = 1;
            // 红包返利的券组id
            $couponGroupId = app_conf('COUPON_GROUP_ID_REFERER_REBATE');
            // 下面的算法需要优化，对于每日的统计，性能足够
            do {
                $id_start = $id_max - $dayTotalCount * $i;
                // 缩小结果集
                $record_id = $GLOBALS['db']->get_slave()->getOne("SELECT max(id) FROM `firstp2p_oto_allowance_log`
                    WHERE `id` > '{$id_start}' AND `create_time`<='{$start_time}' AND `gift_group_id`='{$couponGroupId}'");

                if ($record_id) {
                    $id_start = $record_id;
                }

                $i++;
            } while ($i < 6);

            $sql = "SELECT `to_user_id` as user_id, '服务奖励' as new_log_info, sum(`allowance_money`) as sum_money
                FROM `firstp2p_oto_allowance_log`
                WHERE id > '{$id_start}' AND create_time>='{$start_time}' AND create_time<'{$end_time}' AND gift_group_id='{$couponGroupId}'
                GROUP BY user_id";

            $list_uid = $GLOBALS['db']->get_slave()->getAll($sql);
        } else {
            //设置id偏移量
            $config_id_offset = app_conf('SMS_USER_LOG_ID_OFFSET');
            $config_id_percent = app_conf('SMS_USER_LOG_ID_PERCENT');
            $this->id_offset = empty($config_id_offset) ? $this->id_offset : $config_id_offset;
            $this->id_percent = empty($config_id_percent) ? $this->id_percent : $config_id_percent;

            $config_log_type = app_conf('SMS_USER_LOG_TYPE');
            $config_log_type = empty($config_log_type) ? 0 : $config_log_type;
            if (empty($config_log_type)){
                echo '后台查询配置的用户资金类型为空';
                Logger::wLog(array_merge($log, array('admin config empty')));
                exit;
            }

            $log_type = explode(',', $config_log_type);
            $log_type = implode('\',\'', $log_type);
            $user_log_total_tables = $GLOBALS['db_hash']['user_log']['cnt']-1;
            $temp_list_uid = array();
            for ($i=0; $i<=$user_log_total_tables; $i++) {
                $id_max = $GLOBALS['db']->get_slave()->getOne("SELECT max(id) FROM firstp2p_user_log_{$i}");
                $id_begin = ($this->id_offset > intval($id_max * $this->id_percent/100)) ? ($id_max - $this->id_offset) : intval($id_max * (100-$this->id_percent) / 100);
                $sql = "SELECT user_id,(case log_info when '投资返利' then '优惠码返利' when '邀请返利' then '优惠码返利'  when '注册返利'  then '优惠码返利' else log_info end ) new_log_info , sum(money)
, sum(money) AS sum_money FROM firstp2p_user_log_{$i} WHERE id>'{$id_begin}' AND log_time>='$start_time' AND log_time<'$end_time' and log_info IN ('$log_type') group by user_id,new_log_info";
                $list_uid = $GLOBALS['db']->get_slave()->getAll($sql);
                $temp_list_uid = array_merge($temp_list_uid,$list_uid);
            }
            $list_uid = $temp_list_uid;
        }

        if (empty($list_uid)){
            // 记录处理ids
            Logger::wLog(array_merge($log, array('data empty')));
            exit;
        }
        $count = 1;
        $sleep_config = app_conf('SMS_SEND_USER_LOG_SLEEP_CONF');
        if (!empty($sleep_config)){
            list($sleep,$sleep_count) = explode(',',$sleep_config);
        }

        // 默认sleep1秒
        $sleep = empty($sleep) ? 10 : $sleep;
        // 默认1000条sleep1秒
        $sleep_count = empty($sleep_count) ? 500 : $sleep_count;
        foreach ($list_uid as $user_info_value) {
            if ($count % $sleep_count == 0) {
                sleep($sleep);
            }

            $msgcenter_obj = new msgcenter();
            $ruid = $cache->get($cache_key.'_'.$user_info_value['user_id'].'_'.$user_info_value['new_log_info']);
            if ($ruid > 0) continue;
            if (bccomp($user_info_value['sum_money'],'0.00',2) > 0) {
                $sql = "SELECT `mobile`,`site_id`,user_type,id FROM " . DB_PREFIX . "user WHERE id='{$user_info_value['user_id']}' AND `is_effect`=1 AND `is_delete`=0 ";
                $user_info = $GLOBALS['db']->get_slave()->getRow($sql, true);
                if (empty($user_info['mobile']) && $user_info['user_type'] != \core\dao\UserModel::USER_TYPE_ENTERPRISE){
                    continue;
                }
                if (isset(self::$sms_template_type[$user_info_value['new_log_info']])) {
                    /*author:liuzhenpeng, modify:系统触发短信签名, date:2015-10-28*/
                    $user_info['site_id'] = empty($user_info['site_id']) ? 1 : $user_info['site_id'];
                    // SMSSend 贴息返利短息
                    if ($user_info['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                    {
                        $_mobile = 'enterprise';
                        $accountTitle = get_company_shortname($user_info['id']); // by fanjingwen
                    } else {
                        $_mobile = $user_info['mobile'];
                        $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                    }
                    $sms_data = array(
                        'account_title' => $accountTitle,
                        'total_amount' => format_price($user_info_value['sum_money']),
                    );

                    $sms_title = $couponRebateSwitch == 1 ? "用户按天{$user_info_value['new_log_info']}通知" : "用户按天返利{$user_info_value['new_log_info']}通知";
                    $send_ret = SmsServer::instance()->send($_mobile, self::$sms_template_type[$user_info_value['new_log_info']], $sms_data, $user_info_value['user_id'], $user_info['site_id']);
                    if (!empty($send_ret) && $send_ret['code'] == 0){
                        $cache->set($cache_key.'_'.$user_info_value['user_id'].'_'.$user_info_value['new_log_info'],1,$expire_time);
                    }
                    // 添加手机推送
                    try {
                        $msgbox = new MsgBoxService();
                        $title = ($couponRebateSwitch == 1) ? '服务奖励' : '返利到账';
                        $content = ($couponRebateSwitch == 1)
                            ? '您今日共收到' . $sms_data['total_amount'] . '服务奖励。'
                            : '您今日共有' . $sms_data['total_amount'] . '返利到账,详情请查看返利明细。';

                        $structured_content = array(
                            'main_content' => $content,
                            'money' => sprintf("+%s", number_format($user_info_value['sum_money'], 2)),
                            //'turn_type' => MsgBoxEnum::TURN_TYPE_REBATE_DETAIL,
                            'turn_type' => 0,
                        );

                        $msgbox->create($user_info_value['user_id'], MsgBoxEnum::TYPE_SERVICE_REBATE, $title, $content, $structured_content);
                    }catch (\Exception $e){
                        //
                    }
                    Logger::wLog(json_encode(array_merge($log,array($count,$is_msgcenter_send_ret,$user_info_value['user_id']))));
                    $count++;
                }

            }

        }

        Logger::wLog(array_merge($log,array('succ',$count)));

    }

}
$sms_send_rebate_obj = new sms_send_user_log_daily();
$sms_send_rebate_obj->run($argv);
