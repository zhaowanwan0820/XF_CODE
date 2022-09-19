<?php
/**
 * 功能：
 *  类似从Redis队列中读取用户中奖信息入库的需求，统一使用该脚本作为消费者
 * 必须参数：
 *  -h Reids ip 地址
 *  -q Reids 队列名
 * 可选参数
 *  -d 脚本结束时间
 *  -w 脚本worker数
 *-----------------------------------------------------------------------
 * @version 1.0 zhangzhuyan@ucfgroup.com
 *-----------------------------------------------------------------------
 */

// ini_set('display_errors', 1);
// error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../../app/init.php';

use core\service\BonusService;
use core\dao\BonusModel;
use core\dao\UserModel;
use libs\lock\LockFactory;

class Shake {

    /**
     * 是否校验数据的唯一性
     */
    public $check_only = false;

    /**
     * 是否验证信用领红包权限
     * @var boolean
     */
    private $checkNew = false;

    /**
     * redis
     */
    private $redis = null;

    /**
     * Reids Queue Key
     * @var string
     */
    private $redisQueueKey = '';

    /**
     * 当前红包信息
     * @var null
     */
    private $bonus = null;

    /**
     * 结束时间
     * @var integer
     */
    private $deadline = 0;

    /**
     * 多进程锁
     * @var boolean
     */
    private $checkMult = false;

    /**
     * 红包配置
     * @var array
     */
    private $bonusConfig = array(
        'yaoyiyao' => array(
            'type' => BonusModel::BONUS_YAOYIYAO,
            'expireDay' => 15,
            'moneyLimit' => 50,
        ),
        'tty' => array(
            'type' => BonusModel::BONUS_DAYDAYYAO,
            'expireDay' => 7,
            'moneyLimit' => 20,
        ),
        'nianhui' => array(
            'type' => BonusModel::BONUS_PLATFORM_AWARD,
            'expireDay' => 2,
            'moneyLimit' => 2016,
        ),
    );

    /**
     * 队列后缀
     */
    const QUEUE_POSTFIX = '_queue_activity';

    public function __construct($ip = '', $from = '', $check_only = 0, $checkNew = false,
                                $deadline = 0, $checkMult = false)
    {
        if (!isset($this->bonusConfig[$from])) {
            exit ('provider is not allow!' . PHP_EOL);
        }

        if(!filter_var($ip, FILTER_VALIDATE_IP)) {
            exit("invalid ip address!" . PHP_EOL);
        }

        $this->ip = $ip;
        $this->check_only = $check_only;
        $this->checkNew = $checkNew;
        $this->redisQueueKey = $from . self::QUEUE_POSTFIX;
        $this->bonus = $this->bonusConfig[$from];
        $this->deadline = $deadline;
        $this->checkMult = $checkMult;

    }

    /**
     * 获取redis
     * @return [type] [description]
     */
    public function getRedis()
    {

        if (false === ($this->redis instanceof Redis)) {
            $this->redis = new Redis();
            $this->redis->connect($this->ip, 6379);
            $this->redis->select(8);
        }
        return $this->redis;

    }

    public function run()
    {
        while (1) {
            $err = false;
            try {

                if ($this->deadline > 0 && time() > $this->deadline) {
                    $this->log('INFO', 'script exit');
                    exit(0);
                }

                $data = json_decode($this->getRedis()->rpop($this->redisQueueKey), true);
                $mobile = $data['mobile'];
                $money  = $data['money'];
                $trytimes = intval($data['trytimes']);

                if (empty($mobile)) {
                    sleep(5);
                    continue;
                }

                // 重试次数大于3次记录日志，放弃入库
                if ($trytimes >= 3) {
                    $this->log('WARNING', 'RetryError', $mobile, $money);
                    continue;
                }

                // redis pop 日志
                $this->log('INFO', 'Pop', $mobile, $money);

                // 红包金额超限
                $moneyLimit = $this->bonus['moneyLimit'];
                if ($money > $moneyLimit || $money <= 0) {
                    $this->log('WARNING', 'MoneyError', $mobile, $money);
                    continue;
                }

                // 防止多进程读取到相同数据（Reids队列中相同记录重复插入）
                $lock = null;
                if ($this->checkMult) {
                    $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
                    $lock_key = 'bonus_cron_activity_' . $mobile .'_'. $this->bonus['type'] .'_'. $money;
                    if (!$lock->getLock($lock_key, 600)) { // 防止重复插入
                        $this->log('WARNING', 'ListReportRepeat', $mobile, $money);
                    }
                }

                // 重复领取检验
                if ($this->check_only) {
                    $bonus = BonusModel::instance()->findBy("mobile='$mobile' AND type={$this->bonus['type']}", "id");
                    if (!empty($bonus)) {
                        if ($lock) $lock->releaseLock($lock_key);
                        $this->log('WARNING', 'Dumplication', $mobile, $money);
                        continue;
                    }
                }

                // 新老用户检验
                if ($this->checkNew) {
                    $owner = UserModel::instance()->findBy('mobile=":mobile"', 'id', array(':mobile' => $mobile));
                    if (intval($owner['id']) > 0) {
                        if ($lock) $lock->releaseLock($lock_key);
                        $this->log('WARNING', 'NotNewUser', $mobile, $money);
                        continue;
                    }
                }

                // 插入红包
                $bonus_service = new BonusService();
                $result = $bonus_service->generateXslb($mobile, $money, $this->bonus['expireDay'], $this->bonus['type']);//10元红包，有效期7天
                if ($lock) $lock->releaseLock($lock_key);
                if ($result == 1) {
                    $this->log('SUCCESS', 'OK', $mobile, $money);
                } else {
                    throw new Exception("InsertError_{$result}");
                }

            } catch (Exception $e) {

                $this->log('ERROR', $e->getMessage());
                $err = true;

            }

            if (!$err) continue;

            // 有异常抛出，增加重试逻辑，随后退出重启脚本，资源重连
            try {
                $data['trytimes'] = $trytimes + 1;// 重试次数
                $this->getRedis()->lpush($this->redisQueueKey, json_encode($data));
            } catch (Exception $e) {
                $this->log('ERROR', $e->getMessage());
                $this->log('ERROR', 'RetryError', $mobile, $money);
            }
            exit(1);

        }
    }

    /**
     * 记录日志
     * @param  string $level  日志级别
     * @param  string $msg
     * @param  string $mobile
     * @param  string $money
     */
    private function log($level, $msg, $mobile='', $money='')
    {
        $logPath = __DIR__.'/../../../log/ACTIVITY_'.date('Ymd').".log";
        $_msg = '';
        if (!empty($mobile)) {
            $_msg = "mobile:{$mobile}\tmoney:{$money}\tbonus:{$this->bonus['type']}\t";
        }
        $msg = $_msg . 'msg:' . $msg;
        error_log(date('Y/m/d H:i:s') . " [{$level}]\t" . $msg . PHP_EOL, 3, $logPath);
    }
}

/**
* 命令行执行处理
*/
class Cli
{
    /**
     * 是否后台执行
     * @var boolean
     */
    private $isDeamon = false;

    /**
     * worker执行的任务类
     * @var null
     */
    private $childObj = null;

    /**
     * workerNum
     * @var integer
     */
    private $workerNum = 1;

    function __construct($childObj = null, $isDeamon = false, $workerNum = 1)
    {
        $this->childObj = $childObj;
        $this->isDeamon = $isDeamon;
        $this->workerNum = is_numeric($workerNum) && $workerNum > 0 ? intval($workerNum) : 1;

        if (!method_exists($childObj, 'run')) exit('childObj mast have run method!');
    }

    /**
     * 生成master worker
     * @return [type] [description]
     */
    public function run()
    {
        if ($this->isDeamon) $this->deamonize();

        for ($i=0; $i < $this->workerNum; $i++) {
            $pid = pcntl_fork();
            if ($pid == 0) {
                echo 'worker开始执行,PID:' . posix_getpid() . PHP_EOL;
                $this->childObj->run();
                exit(0);
            } elseif ($pid > 0) {
                continue;
            } else {
                exit('fork fail!');
            }
        }
        $this->monitor();

    }

    /**
     * 程序deamon化
     * @return [type] [description]
     */
    private function deamonize()
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            exit(0);
        } elseif ($pid < 0) {
            exit('deamon fork fail!');
        }
        posix_setsid();
        echo '程序deamon化完成,PID:' . posix_getpid() . PHP_EOL;
    }

    /**
     * master监控
     * @return [type] [description]
     */
    private function monitor()
    {
        echo 'master监控开始' . PHP_EOL;
        while (($pid = pcntl_wait($status)) !== 0) {
            if ($status === 0 && pcntl_wifexited($status)) { // 子进程正常退出
                exit(0);
            } else {
                sleep(1);// 子进程异常退出后，休息一下再干活儿
                $pid = pcntl_fork();
                if ($pid == 0) {
                    $this->childObj->run();
                } elseif ($pid > 0) {
                    continue;
                } else {
                    exit('monitor fork fail!');
                }
            }
        }
    }
}

$shortopts = "";
$shortopts .= "h:"; // Redis ip
$shortopts .= "f:"; // 渠道key
$shortopts .= "d:"; // 脚本结束时间
$shortopts .= "t:"; // 脚本执行分钟
$shortopts .= "w:"; // worker数

$longopts = array(
    "can-repeat", // 是否检验用户重复红包
    "without-distinguish-user", // 是否检验新用户
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
    -h Reids IP
    -f 渠道key
    -d 脚本结束时间
    -t 脚本持续时长，-d参数优先
    -w worker数，默认为1
    --can-repeat 不检查重复插入红包，默认不能重复插入
    --without-distinguish-user 不检查新老用户，默认老用户不得插入

HELP;
    exit($str);
}


// 参数检验
if (!isset($opts['h'])) {
    exit('-h(Reids ip) is required!' . PHP_EOL);
} else {
    if(!filter_var($opts['h'], FILTER_VALIDATE_IP)) exit('-h(Reids ip) is wrong!' . PHP_EOL);
}
if (!isset($opts['f'])) exit('-f(from which provider) is required!' . PHP_EOL);
if (isset($opts['d'])) {
    $time = strtotime($opts['d']);
    if (!$time) exit('-d(deadline) format is wrong, please input like "2015/10/29 11:11"' . PHP_EOL);
    if ($time <= time()) exit('-d(deadline) please input future time' . PHP_EOL);
}


$ip = $opts['h'];
$from = $opts['f'];
$deadline = isset($opts['d']) ? strtotime($opts['d']) : 0;
if ($deadline == 0 && isset($opts['t']) && intval($opts['t']) > 0) {
    $deadline = time() + intval($opts['t']) * 60;
}
$workerNum = isset($opts['w']) ? intval($opts['w']) : 1;
$checkOnly = isset($opts['can-repeat']) ? false : true;
$checkNew = isset($opts['without-distinguish-user']) ? false : true;
$checkMult = $workerNum > 1 ? true : false;

$push = new Shake($ip, $from, $checkOnly, $checkNew, $deadline, $checkMult);
$cli = new Cli($push, true, $workerNum);
$cli->run();

