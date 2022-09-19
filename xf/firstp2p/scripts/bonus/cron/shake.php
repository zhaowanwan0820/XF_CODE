<?php
/**
 *-----------------------------------------------------------------------
 * 项目背景：
 *  用户流程描述：
 *  1、  看电视，打开微信摇一摇，进入芝麻开门互动页面；
 *  2、  观众在互动页面中，参与竞猜活动，获得抽奖机会；
 *  3、  抽奖获得网信理财投资红包（无使用限制），页面提示观众获得的红包金额，并输入手机号领取；
 *  4、  点击立即领取，将中奖金额和手机号发送给网信理财，用户注册后将投资红包，直接发放到手机号对应账户上；
 *  5、  页面跳转至领取成功页面，用户点击“立即使用”跳转至WAP站首页。
 * 脚本功能：
 *  从redis队列中读取中奖用户信息，写入到数据库，每个手机号只能领取一次
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../../app/init.php';
use libs\utils\Logger;
use core\service\BonusService;

class Shake {

    /**
     * 配置信息
     */
    public $config = array();

    /**
     * 是否校验数据的唯一性
     */
    public $check_only = false;

    /**
     * redis
     */
    private $redis = null;

    const REDIS_QUEUE_PREFIX = 'zmkm_queue_activity';


    public function __construct($ip = '', $check_only = 0) {

        if(!filter_var($ip, FILTER_VALIDATE_IP)) {
            exit("invalid ip address!\n");
        }
        $this->ip = $ip;
        $this->check_only = $check_only;

    }

    public function getRedis() {

        if (false === ($this->redis instanceof Redis)) {
            $this->redis = new Redis();
            $this->redis->connect($this->ip, 6379);
            $this->redis->select(8);
        }
        return $this->redis;

    }

    public function run() {

        while (1) {
            $current_pid = getmypid();
            file_put_contents('/tmp/bonus_status/'.$current_pid, time());
            $data = json_decode($this->getRedis()->rpop(self::REDIS_QUEUE_PREFIX), true);
            $mobile = $data['mobile'];
            $money  = $data['money'];
            if (empty($mobile)) {
                /*if (date('w') != 2) {//活动为周二
                    exit("活动在每周二!\n");
                }*/
                sleep(10);
                continue;
            }
            if ($this->check_only) {
                $bonus = \core\dao\BonusModel::instance()->findBy("mobile='$mobile' AND type=16", "id");
                if (!empty($bonus)) {
                    $message = sprintf("result=0\tmobile=%s\tmoney=%s\tmessage=dumplication", substr_replace($mobile, '****', 3, 4), $money);
                    Logger::wLog($message.PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH."bonus_shake_".date('Ymd').'.log');
                    continue;
                }
            }
            $bonus_service = new BonusService();
            if (!preg_match("/^1[0-9]{10}$/", $mobile)) {
                Logger::wLog("mobile=$mobile\t手机号错误".PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH."bonus_shake_".date('Ymd').'.log');
                continue;
            }

            $result = $bonus_service->generateXslb($mobile, $money, 15);//10元红包，有效期7天
            $message = sprintf("result=%s\tmobile=%s\tmoney=%s\tmessage=ok", $result, substr_replace($mobile, '****', 3, 4), $money);
            Logger::wLog($message.PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH."bonus_shake_".date('Ymd').'.log');
        }
    }
}

$ip = '';
if (isset($argv[1])) {
    $ip = $argv[1];
}
$check_only = 0;
if (isset($argv[2])) {
    $check_only = true;
}
$push = new Shake($ip, $check_only);
$push->run();
echo "done.\n";

