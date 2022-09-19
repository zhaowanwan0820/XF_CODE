<?php
/**
 * CouponStatService.php
 * @author wenyanlei@ucfgroup.com
 */
namespace core\service;
use libs\db\MysqlDb;

class CouponStatService extends BaseService {

    protected $url;

    public function __construct() {
        $this->url = app_conf('STATISTICS_BAIZE_URL') . "/api/ncfrs0bzir";
    }

    /**
     * 获取所有优惠码投资记录
     */
    public function makeCouponLoadData() {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $db = $GLOBALS['db'];
        //if (app_conf('ENV_FLAG') == 'online') {
            $db = new MysqlDb("10.10.10.72:3306", "bzplan_read", "Ic7rI2UeLE", "firstp2p", "utf8");
        //}

        $memory_start = memory_get_usage();
        $start_time = to_timespan('20140601');

        $sql = "select ru.idno, ru.id as refer_user_id, ru.user_name as refer_user_name, ru.real_name as refer_real_name,
        l.short_alias, l.deal_id, l.deal_load_id, l.deal_load_money, case when d.create_time>0 then d.create_time else l.create_time end as deal_load_time,
        du.id as deal_load_user_id, du.user_name as deal_load_user_name, du.real_name as deal_load_real_name, du.invite_code as signup_short_alias, du.create_time as signup_time
        from firstp2p_user ru, firstp2p_user du, firstp2p_coupon_log l left join firstp2p_deal_load d on d.id=l.deal_load_id
        where l.refer_user_id=ru.id and l.consume_user_id=du.id and l.create_time>{$start_time} and (d.create_time>1401523200 or d.create_time is null)
        and l.is_delete=0 and ru.is_delete=0 and du.is_delete=0 order by l.id asc";

        $coupon_deal_load = $db->getAll($sql);
        $return = array('code' => 0, 'msg' => array());



        if ($coupon_deal_load) {
            $deal_load_user = array();
            foreach ($coupon_deal_load as &$data_one) {

                $data_one['deal_load_date'] = to_date($data_one['deal_load_time'], 'Y-m-d');
                $data_one['deal_load_time'] = strtotime(to_date($data_one['deal_load_time']));
                $data_one['signup_date'] = to_date($data_one['signup_time'], 'Y-m-d');
                $data_one['signup_time'] = strtotime(to_date($data_one['signup_time']));

                $data_one['is_first_deal_load'] = 0;
                if ($data_one['deal_load_id'] > 0) {
                    $data_one['is_first_deal_load'] = isset($deal_load_user[$data_one['deal_load_user_id']]) ? 0 : 1;
                    $deal_load_user[$data_one['deal_load_user_id']] = $data_one['deal_load_user_id'];
                }
            }
            $memory_mid =  memory_get_usage();
            echo "\r\n共" . count($coupon_deal_load) . "条数据[".intval(($memory_mid - $memory_start)/(1024*1024))."mb]";
            echo "\r\nurl：" . $this->url;

            //分批推送
            $lot_size = (app_conf('ENV_FLAG') == 'online') ? 1000 : 500; //每次推送数据记录数
            $lot_count = ceil(count($coupon_deal_load) / $lot_size);
            for ($i = 0; $i < $lot_count; $i++) {
                $send_data = array_slice($coupon_deal_load, ($lot_size * $i), $lot_size);
                if (empty($send_data)) {
                    break;
                }
                $this->sendBaize($send_data, $i);
            }
            $memory_end =  memory_get_usage();
            echo "\r\n共消耗内存：".intval(($memory_end - $memory_start)/(1024*1024))."mb\r\n";
            unset($coupon_deal_load);
        } else {
            echo "\r\n数据为空";
        }
        return $return;
    }

    public function sendBaize($coupon_deal_load, $batch_no = 0) {
        $json_data = json_encode($coupon_deal_load);


        $data = array(
            'pid' => 'ncf', // 固定值
            'prd' => 'firstp2p', // 固定值
            'ainfo' => 'deal_load_coupon',
            'is_truncate' => $batch_no == 0 ? 1 : 0,
            'date' => date("Y-m-d"), // 数据日期
            'data' => $json_data
        );
        $data['sign'] = md5("ABC123" . $data['date'] . $data['data']); // 数据校验码

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);


        $msg_info = "[" . $batch_no . "][" . count($coupon_deal_load) . "条],[" . strlen($json_data) . "]";
        if (curl_errno($ch)) {
            echo "\r\n\r\n数据写入白泽失败{$msg_info}:" . curl_error($ch);
        } else {
            echo "\r\n\r\n数据写入白泽成功{$msg_info}:" . $output;
            //$return['code'] = 1;
        }
        curl_close($ch);
    }
}
