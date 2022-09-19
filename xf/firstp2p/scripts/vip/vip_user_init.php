<?php
/**
 * 功能：
 * 初始化用户会员等级，默认初始化所有会员
 * 必须参数：
 * 无
 * 可选参数
 *  -u 用户ID
 *-----------------------------------------------------------------------
 * @version 1.0 wangshijie@ucfgroup.com
 *-----------------------------------------------------------------------
 */

// ini_set('display_errors', 1);
// error_reporting(E_ERROR);

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';
use core\service\vip\VipService;
use libs\utils\Logger;

class UserInit
{
    /**
     * 用户ID
     * @var intval
     */
    private $userId = 0;

    /**
     * 文件名称
     * @var string
     */
    private $fileName = '';

    /**
     * 开始用户ID
     * @var integer
     */
    private $startUid = 0;

    /**
     * 结束用户ID
     * @var integer
     */
    private $endUid = 0;

    /**
     * 初始化类型，用与读取权重值
     * @var string
     */
    private $sourceType = '';

    /**
     * 经验值
     * @var integer
     */
    private $point = 0;

    public function __construct($userId, $fileName, $startUid, $endUid, $sourceType, $point) {
        $this->userId     = $userId;
        $this->fileName   = $fileName;
        $this->startUid   = $startUid;
        $this->endUid     = $endUid;
        $this->point      = $point;
        $this->sourceType = $sourceType;

        if ($this->sourceType == '') {
            die("请指定初始化类型\n");
        }
    }

    private function download($url)
    {
        $fileName = '/tmp/vip_init_data_'.md5($url).'.csv';
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
        return (substr($staticHost, 0, 4) == 'http' ? '' : 'http:') . $staticHost . $name;
    }

    public function run() {
        $vipService = new VipService();
        if ($this->userId > 0 && $this->point > 0) {
            try {
                $token = sprintf('initialize:%s', $this->userId);
                $result = $vipService->updateVipPoint($this->userId, $this->point, $this->sourceType, $token, '初始化');
                if ($result) {
                    exit("初始化用户{$this->userId}成功。\n");
                }
            } catch (\Exception $e) {
                Logger::error(implode(' | ', ["VIP_USER_INIT_EXCEPTION:", __CLASS__, $this->userId, $this->point, json_encode($e)]));
                echo "初始化用户{$this->userId}失败，{$e->getMessage()}。\n";
                exit(0);
            }
        } else {
            $successAccount = 0;

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

            while (!feof($handle)) {
                list($logId, $userId, $point) = fgetcsv($handle);
                if ($userId <= 0) {
                    continue;
                }
                if (empty($logId) || empty($userId) || empty($point)) {
                    continue;
                }

                if (!empty($this->startUid) && $userId < $this->startUid) {
                    continue;
                }

                if (!empty($this->endUid) && $userId > $this->endUid) {
                    continue;
                }

                $token = sprintf('initialize:%s', $userId);

                try {
                    $result = $vipService->updateVipPoint($userId, $point, $this->sourceType, $token, '初始化', 0, $logId);
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
}

$shortopts = "";
$shortopts .= "u:"; // 用户ID

$longopts = array(
    "start-uid:", // 开始的用户ID
    "end-uid:", // 结束的用户ID
    "file-name:", // 上传的文件名称
    "point:", //经验值数量
    "source-type:", //来源类型
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php init.php [args...]
    -u 用户ID
    --start-uid 开始的ID
    --end-uid 结束的ID
    --file-name 上传的文件名称
    --source-type 来源类型，用于读取权重值
    --point 初始化用户经验值
    --help 帮助
HELP;
    exit($str);
}

$userId     = isset($opts['u']) ? intval($opts['u']) : 0;
$fileName   = isset($opts['file-name']) ? $opts['file-name'] : false;
$startUid   = isset($opts['start-uid']) ? $opts['start-uid'] : false;
$endUid     = isset($opts['end-uid']) ? $opts['end-uid'] : false;
$point      = isset($opts['point']) ? intval($opts['point']) : 0;
$sourceType = isset($opts['source-type']) ? $opts['source-type'] : 0;

$vip = new UserInit($userId, $fileName, $startUid, $endUid, $sourceType, $point);
$vip->run();
