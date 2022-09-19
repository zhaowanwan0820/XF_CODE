<?php
/**
 * @desc 统计在线用户数
 * ----------------------------------------------------------------------------
 * 统计规则使用redis的有效KEY来统计在线人数，redis的session有效期为30分钟
 * ----------------------------------------------------------------------------
 * 1、使用DBSIZE命令来统计
 * 2、统计结果写入文件
 * 3、脚本每5分钟执行一次
 * 4、文件格式为"$time\t$times\n"
 * 5、推送目录
 * ----------------------------------------------------------------------------
 * 定时脚本设置方式*\/5 * * * * /project path/scripts/statis/online_persons.php
 */
set_time_limit(0);
ini_set('memory_limit','512M');

require_once dirname(__FILE__)."/../../app/init.php";
define("FILE_PREFIX", '/logger/online_count_');

class OnlinePersons {

    private $redis = '';
    public function getRedis() {
        if (!$this->redis) {
            $this->redis = new Redis();
            $result = $this->redis->connect('se-redis1.wxlc.org', '6379', 10);
        }
        return $this->redis;
    }

    public function dbsize() {
        return $this->getRedis()->dbsize();
    }

    public function counts($minutes = 15) {
        $seconds = (30 - $minutes) * 60;
        $keys = $this->getRedis()->keys('*');
        $count = 0;
        foreach ($keys as $key) {
            if ($this->getRedis()->ttl($key) > $seconds) {
                $count++;
            }
        }
        return $count;
    }

    public function writeToFile($data, $delimiter = "\t") {
        $line = implode($delimiter, $data);
        file_put_contents(LOG_PATH.FILE_PREFIX.date('Y-m-d').".txt", "$line\n", FILE_APPEND);
    }

    public function run() {
        global $argv;
        if (intval($argv[1]) > 0) {
            $count = $this->counts(intval($argv[1]));
        } else {
            $count = $this->dbsize();
        }
        $this->writeToFile(array(time(), $count));
    }

}

$online = new OnlinePersons();
$online->run();
exit("done.\n");
