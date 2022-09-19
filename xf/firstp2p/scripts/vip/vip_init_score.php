<?php
/**
 * 功能：
 * 初始化用户经验值，默认初始化所有会员
 * 必须参数：
 * 无
 * 可选参数
 *  -u 用户ID
 *-----------------------------------------------------------------------
 *-----------------------------------------------------------------------
 */

// ini_set('display_errors', 1);
// error_reporting(E_ERROR);

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';
use core\service\vip\VipPointLogService;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class ScoreInit
{
    private $index = 0;
    private $fileName='';

    public function __construct($index) {
        $this->index     = $index;
    }

    private function download($url)
    {
        $fileName = '/tmp/vip_init_score_'.md5($url).'.csv';
        if (file_exists($fileName)) {
            @unlink($fileName);
        }

        $file = fopen($url, "r");
        while (!feof($file)) {
            file_put_contents($fileName, fread($file, 8192), FILE_APPEND);
        }
        fclose($file);
        return $fileName;
    }

    private function getFileUrl($name)
    {
        $staticHost = app_conf('STATIC_HOST');
        return (substr($staticHost, 0, 4) == 'http' ? '' : 'http:') . $staticHost . '/'.$name;
    }

    public function run() {
        $fileMap = array(
            1 => array('month' => 201609, 'file' => 'attachment/201710/20/14/f483ab481484248081b478c25d7a34d9/23b4ebda9467d45c25c41b1ea32a8164.csv', 'left' => -1),
            2 => array('month' => 201610, 'file' => 'attachment/201710/20/14/6ef7dcdc574f11a186c3d49e20cb7a68/17f7c92fffbdd8e0b301b2b9abd3b276.csv', 'left' => 0),
            3 => array('month' => 201611, 'file' => 'attachment/201710/20/14/58ef673420669df135566592ef5661c0/f809e8eb41f837ddedb209413e16ccd0.csv', 'left' => 1),
            4 => array('month' => 201612, 'file' => 'attachment/201710/20/14/f44d8092680d63c5750a9ba8e3092f2d/61ebf44d4e251a865f4a951135dfb00a.csv', 'left' => 2),
            5 => array('month' => 201701, 'file' => 'attachment/201710/20/14/46889b2e74189ab07db656e8e23602c2/5e44eb17c4e9c2297b06f12933cb5d68.csv', 'left' => 3),
            6 => array('month' => 201702, 'file' => 'attachment/201710/20/14/ada89b556de1d31b871fb0790c2cf702/a4492e434abf6ad8dd5add143a92ba90.csv', 'left' => 4),
            7 => array('month' => 201703, 'file' => 'attachment/201710/20/14/badcedbd5b1a51353bce95b6440fe9ca/4b1816712cc24275b68bda4a667ad92e.csv', 'left' => 5),
            8 => array('month' => 201704, 'file' => 'attachment/201710/20/14/906e24c596bfa218eb820907dcbfbdc5/94375c04285febf336c4445800d0cffe.csv', 'left' => 6),
            9 => array('month' => 201705, 'file' => 'attachment/201710/20/15/41a6415c22b0efc97f29097afcc71ecc/5dff1d798c6e455369ab1b17f720cbfd.csv', 'left' => 7),
            10 => array('month' => 201706, 'file' => 'attachment/201710/20/15/800f51ec94df9e32dcbfd610253ee1df/959c53a9ef42582ff94db6ec61a177d6.csv', 'left' => 8),
            11 => array('month' => 201707, 'file' => 'attachment/201710/20/15/1fe8ccea3423885e8b5c168e956c60f5/da86e7a2c6eb8546a3d850352ad2cf6c.csv', 'left' => 9),
            12 => array('month' => 201708, 'file' => 'attachment/201710/20/15/04870da62351ca65da33508460ba5b4f/90eaf1607183a885a4fbd3fe95dc05ff.csv', 'left' => 10),
            13 => array('month' => 201709, 'file' => 'attachment/201710/20/15/72008bd3d9ea50e846b0234670f8ccb6/4c814d46e95c5b7574c06224111205a6.csv', 'left' => 11),
        );
        $successAccount = 0;
        $this->fileName = $fileMap[$this->index]['file'];
        $month = $fileMap[$this->index]['month'];
        $this->createTime = strtotime($month."01");
        $countMonth = $fileMap[$this->index]['left'];

        $file = '';
        if ($this->fileName) {
            $filePath = $this->getFileUrl($this->fileName);
            $file = $this->download($filePath);
        } else {
            die("请输入文件名称.\n");
        }

        if (!file_exists($file)) {
            die("同步数据的文件不存在");
        }

        Logger::info(implode('|', [__CLASS__, __METHOD__, 'START']));

        $handle = fopen($file, 'r');
        $vipPointLogService = new VipPointLogService();

        while (!feof($handle)) {
            list($logId, $userId, $point) = fgetcsv($handle);
            if ($userId <= 0) {
                continue;
            }
            if (empty($logId) || empty($userId) || empty($point)) {
                continue;
            }

            $token = sprintf('initialize:%s_%s', date('Y-m-d',$this->createTime), $userId);
            $info  = '初始化-'.date('Y年m月',$this->createTime).'经验值';

            try {
                $result = $vipPointLogService->acquirePoint($userId, $point, VipEnum::VIP_SOURCE_VALUE_INIT, 0, $info, $token, $point, 1, $countMonth, 0, $this->createTime);
                $str =implode(' | ', ["VIP_USER_INITIALIZE:", $token, $userId, $point]);
                echo $str."\n";
                Logger::info(implode(' | ', ["VIP_USER_INITIALIZE:", $token, $userId, $point]));
            } catch (\Exception $e) {
                Logger::error(implode(' | ', ["VIP_USER_INIT_EXCEPTION:", __CLASS__, $userId, $point, json_encode($e)]));
                continue;
            }
            if ($result) {
                $successAccount++;
            }
        }
        fclose($handle);

        Logger::info(implode('|', [__CLASS__, __METHOD__, 'END', "RSYNC_USER_COUNT:$successAccount"]));
        echo "执行成功，初始化用户数：$successAccount\n";
        exit(0);
    }
}

$shortopts = "";
$shortopts .= "u:"; // 用户ID

$longopts = array(
    "index:", //索引数
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_init_score.php [args...]
    --index=1 月份
    --help 帮助
HELP;
    exit($str);
}

$index   = isset($opts['index']) ? intval($opts['index']) : 0;

$vip = new ScoreInit($index);
$vip->run();
