<?php
/* VarzAdapter.php ---
 *
 * Filename: VarzAdapter.php
 * Description:用于程序中埋点的varz统计
 * Author: zhounew
 * Created: 14-9-25 下午5:37
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup
 */

namespace NCFGroup\Common\Extensions\Varz;

use NCFGroup\Common\Extensions\Cache\LocalCache;
use NCFGroup\Common\Extensions\Cache\RedisCache;
use NCFGroup\Common\Extensions\Cache\CacheInterface;
use NCFGroup\Common\Library\OsLib;

/**
 * Class VarzAdapter
 *
 * 用于程序中埋点的varz统计。
 * 使用方法：
 *
 * <code>
 *    $varz = new VarzAdapter("bk");
 *    $varz->startMonitor();
 *    return $varz;
 * </code>
 *
 * @package NCFGroup\Common\Extensions\Varz
 */
class VarzAdapter
{

    protected $yacService;

    protected $debug;

    protected $varzPrefix = '';

    protected $ipcKey;

    protected $semId;

    protected $systemInfo;

    protected static $globalYacService;

    protected $enableSystemStatistics;

    const SYS_PREFIX = "sys_";
    public static $SYS_VARZ_FUNC_MAPPING = array(
        "ip"            => "VarzAdapter::calForCurrentIP",
        "os_info"       => "VarzAdapter::calForOsInfo",
        "php_runmode"   => "VarzAdapter::calForPHPRunMode",
        "php_version"   => "VarzAdapter::calForPHPVersion",
        "php_container" => "VarzAdapter::calForPHPContainer",
        "uptime"        => "VarzAdapter::calForUpTime",
        "load"          => "VarzAdapter::calForSystemLoad",
        "memory"        => "VarzAdapter::calForMemory",
        "cpu_usage"     => "VarzAdapter::calForSystemCpuUsage",
        "net_io"        => "VarzAdapter::calForNetIO");

    /**
     * 构造函数
     *
     * @param string $prefix  输出的varz的前缀
     * @param bool $enableSystemStatistics  是否输出系统的一些参数
     */
    public function __construct($prefix = "", CacheInterface $cache = null, $enableSystemStatistics = true)
    {
        if($cache === null) {
            $this->yacService = new LocalCache();
        } else {
            $this->yacService = $cache;
        }
        $this->yacService->setPrefix("varz");
        self::$globalYacService = $this->yacService;
        $this->ipcKey = "9999999999999";
        $this->semId = sem_get($this->ipcKey);
        $this->varzPrefix = $prefix;
        $this->systemInfo = array();
        $this->enableSystemStatistics = $enableSystemStatistics;
    }

    /**
     * 启动监控，把所有varz列表写入内存中，开始注册当前时间。
     */
    public function startMonitor()
    {
        if (!is_array($this->getVarzList())) {
            $this->yacService->set("__varz_list__", array());
        }

        $isinit = $this->yacService->get("__{$this->varzPrefix}_varz_init__");
        if (!$isinit) {
            $this->yacService->set("__{$this->varzPrefix}_varz_init__", 1);
            $this->register("total_requests", 0, "VarzAdapter::calForRead");
            $this->register("datetime", 0, "VarzAdapter::calForDateTime");
        }
        // error_log(var_export($this->yacService->get('application_id'), true));
        if($this->getVarz('application_id') == null) {
            $this->setVarz('ip', self::calForCurrentIP('ip'));
            $this->setVarz('os', php_uname('s'));
            $this->setVarz('appName', 'fundgate');
            $this->setVarz('phpInfo', self::calForPHPRunMode('php_runmode') . "," . self::calForPHPVersion('php_version') . "," . self::calForPHPContainer('php_container'));
        }
    }

    /**
     * 输出系统级的一些参数，如内存/CPU/网口流量等
     */
    private function outputSystemInfo() {

        foreach(VarzAdapter::$SYS_VARZ_FUNC_MAPPING as $varz=>$calFunction) {
            $value = call_user_func_array('\NCFGroup\Common\Extensions\Varz\\' . $calFunction, array(
                $varz
            ));
            echo VarzAdapter::SYS_PREFIX . $varz . '=' . $value . PHP_EOL;
        }
    }

    /**
     * 获得当前系统级参数的个数
     *
     * @return int
     */
    private function getSystemInfoVarzCount() {
        return count(VarzAdapter::$SYS_VARZ_FUNC_MAPPING);
    }

    /**
     * 获得当前内存中注册的varz列表（不包括系统级参数）
     *
     * @return array 已注册的varz 列表
     */
    private function getVarzList()
    {
        return $this->yacService->get("__varz_list__");
    }

    /**
     * 注册一个varz，并设定其默认值以及计算函数
     *
     * @param string $varzName 参数名
     * @param int $default_value 默认值
     * @param string $calFunction 计算函数
     */
    public function register($varzName, $default_value = 0, $calFunction = "VarzAdapter::calForRead")
    {
        $varz = "{$this->varzPrefix}_" . $varzName;
        $varzList = (array) $this->getVarzList();
        if (!array_key_exists($varz, $varzList)) {
            $varzList[$varz] = $calFunction;
            $this->yacService->set("__varz_list__", $varzList);
        }
        $this->yacService->set($varz, $default_value);
    }

    /**
     * 改变当前varz值，自增方法
     *
     * @param string $varzName 参数名
     * @param int $inc 自增的数值
     */
    public function increaseVarz($varzName, $inc = 1)
    {
        // 添加信号量，保证单机的原子操作
        sem_acquire($this->semId);
        $varz = "{$this->varzPrefix}_" . $varzName;

        $value = $this->yacService->get($varz);
        if (!$value) {
            $this->register($varzName, 1, "VarzAdapter::calForRead");
        } else {
            $this->yacService->set($varz, $value + $inc);
        }
        sem_release($this->semId);
    }

    /**
     * 改变当前varz值，赋值方法
     *
     * @param string $varzName 参数名
     * @param int $varzValue 赋值数值
     */
    public function setVarz($varzName, $varzValue)
    {
        // 添加信号量，保证单机的原子操作
        sem_acquire($this->semId);
        $varz = "{$this->varzPrefix}_" . $varzName;
        $value = $this->yacService->get($varz);
        if (!$value) {
            $this->register($varzName, $varzValue, "VarzAdapter::calForRead");
        } else {
            $this->yacService->set($varz, $varzValue);
        }
        sem_release($this->semId);
    }

    public function getVarz($varzName)
    {
        $varz = "{$this->varzPrefix}_" . $varzName;
        $value = $this->yacService->get($varz);
        return $value;
    }

    /**
     * varz计算方法 - 直接读取
     *
     * @param string $varz 参数名
     * @return int 读取的数值
     */
    public static function calForRead($varz)
    {
        if (self::$globalYacService) {
            return self::$globalYacService->get($varz);
        } else {
            return 0;
        }
    }

    /**
     * varz计算方法 - 读取当前内存
     *
     * @param string $varz 参数名
     * @return string 内存值。e.g. 16363500,12181736,4181764,0.25555437406423，分别表示
     *     所有内存, 空闲内存, 已使用内存, 使用内存率，单位为：KB。
     */
    public static function calForMemory($varz)
    {
        return implode(',', OsLib::getSystemMemoryInfo());
    }

    /**
     * varz计算方法 - 读取当前时间
     *
     * @param string $varz 参数名
     * @return string 当前Unix时间戳和微秒数。e.g. 1412316500.4729。
     */
    public static function calForDateTime($varz)
    {
        return microtime(true);
    }

    /**
     * varz计算方法 - 读取当前服务器IP
     *
     * @param string $varz 参数名
     * @return string 当前服务器IP。e.g. 127.0.0.1:80
     */
    public static function calForCurrentIP($varz='')
    {
        if (PHP_SAPI == 'cli') {
            $serverName = gethostbyname(php_uname('n'));
            $serverPort = '80';
            return $serverName . ':' . $serverPort;
        }
        return gethostbyname($_SERVER['SERVER_NAME']) . ':' . $_SERVER['SERVER_PORT'];
    }

    /**
     * varz计算方法 - 读取当前OS信息
     *
     * @param string $varz 参数名
     * @return string 当前OS信息。e.g. Linux zhounew-Lenovo-Product 3.13.0-36-generic #63-Ubuntu SMP Wed Sep 3 21:30:07 UTC 2014 x86_64
     */
    public static function calForOsInfo($varz)
    {
        return php_uname();
    }

    /**
     * varz计算方法 - 读取当前PHP运行模式
     *
     * @param string $varz 参数名
     * @return string 当前PHP运行模式。e.g. fpm-fcgi
     */
    public static function calForPHPRunMode($varz)
    {
        return php_sapi_name();
    }

    /**
     * varz计算方法 - 读取当前PHP版本
     *
     * @param string $varz 参数名
     * @return string 当前PHP版本。e.g. fpm-fcgi
     */
    public static function calForPHPVersion($varz)
    {
        return PHP_VERSION;
    }

    /**
     * varz计算方法 - 读取当前PHP运行容器
     *
     * @param string $varz 参数名
     * @return string 当前PHP运行容器。e.g. nginx/1.6.0
     */
    public static function calForPHPContainer($varz)
    {
        if(PHP_SAPI == 'cli') {
            return 'nginx/1.2.4';
        }
        return $_SERVER['SERVER_SOFTWARE'];
    }

    /**
     * varz计算方法 - 读取当前服务的uptime
     *
     * @param string $varz 参数名
     * @return string 当前服务的uptime。e.g. 16:38，表示16个小时38分。
     */
    public static function calForUpTime($varz)
    {
        return OsLib::getSystemUptime();
    }

    /**
     * varz计算方法 - 读取当前系统负载
     *
     * @param string $varz 参数名
     * @return string 当前系统负载。e.g. 0.58,0.41,0.46，表示1分钟的负载, 5分钟的负载, 15分钟的负载。
     */
    public static function calForSystemLoad($varz)
    {
        return implode(',', OsLib::getSystemLoadInfo());
    }

    /**
     * varz计算方法 - 读取当前CPU使用率
     *
     * @param string $varz 参数名
     * @return string 当前CPU使用率。e.g. 2.8，表示2.8%使用率。
     */
    public static function calForSystemCpuUsage($varz)
    {
        return OsLib::getCpuUsageInfo();
    }

    /**
     * varz计算方法 - 读取当前网络IO情况
     *
     * @param string $varz 参数名
     * @return string 当前网络IO情况。e.g. (eth0,218506258,437848,8310972,78193)，表示:
     *     网口名, 收到的字节数, 收到的包数, 发送的字节数, 发送的包数。
     */
    public static function calForNetIO($varz)
    {
        $netIOInfoArray = OsLib::getNetIOInfo();
        $outputArr = array();
        foreach ($netIOInfoArray as $netIOInfo) {
            $s = "(" . implode(',', $netIOInfo) . ")";
            $outputArr[] = $s;
        }
        return implode(',', $outputArr);
    }


    public function dump()
    {
        $varzList = $this->getVarzList();
        $array = array();
        if ($varzList) {
            $totalVarzCount = count($varzList);
            if ($this->enableSystemStatistics) {
                $totalVarzCount += $this->getSystemInfoVarzCount();
            }
            foreach ($varzList as $varz => $calFunction) {
                $value = call_user_func_array('\NCFGroup\Common\Extensions\Varz\\' . $calFunction, array(
                    $varz
                ));
                $array[$varz] = $value;
            }
            if ($this->enableSystemStatistics) {
                // $this->outputSystemInfo();
            }
        }
        return $array;
    }

    /**
     * 输出varz清单
     */
    public function output()
    {
        $varzList = $this->getVarzList();
        if ($varzList) {
            $totalVarzCount = count($varzList);
            if ($this->enableSystemStatistics) {
                $totalVarzCount += $this->getSystemInfoVarzCount();
            }
            echo "total_varz_count=" . count($varzList) . PHP_EOL;
            foreach ($varzList as $varz => $calFunction) {
                $value = call_user_func_array('\NCFGroup\Common\Extensions\Varz\\' . $calFunction, array(
                    $varz
                ));
                echo $varz . '=' . $value . PHP_EOL;
            }
            if ($this->enableSystemStatistics) {
                $this->outputSystemInfo();
            }
        } else {
            echo "total_varz_count=0";
        }
    }

    /**
     * 使内存中的所有varz全部失效，清零。
     */
    public function flush()
    {
       return $this->yacService->flush();
    }
}
