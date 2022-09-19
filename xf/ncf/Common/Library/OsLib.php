<?php
namespace NCFGroup\Common\Library;

class OsLib
{

    /**
     * 获得当前操作系统（Linux）的CPU/内存/硬盘使用率
     *
     * @return Array($cpu_usage, $mem_usage, $hd_usage)
     */
    public static function getSystemUsageInfo()
    {
        $fp = popen('top -b -n 1 | grep -E "Cpu\(s\):|Mem:"', "r"); // 获取某一时刻系统cpu和内存使用情况
        $rs = "";
        while (!feof($fp)) {
            $rs .= fread($fp, 1024);
        }
        pclose($fp);
        $sys_info = explode("n", $rs);
        print_r($sys_info);
        $cpu_info = explode("  ", $sys_info[0]);
        $mem_info = explode(" ", $sys_info[1]);

        $cpu_usage = substr($cpu_info[1], 0, 4);

        $mem_total = trim($mem_info[3], 'k');
        $mem_used = trim($mem_info[6], 'k');
        $mem_usage = round(100 * intval($mem_used) / intval($mem_total), 1) . '%';

        $fp = popen("df -lh", "r");
        $rs = fread($fp, 1024);
        pclose($fp);
        $hd_info = explode("n", $rs);
        // print_r($hd_info);
        $hd = explode(" ", $hd_info[1]);
        // print_r($hd);
        $hd_usage = $hd[21];
        return array(
            'cpu' => $cpu_usage,
            'mem' => $mem_usage,
            'hd' => $hd_usage
        );
    }

    /**
     * 获得系统的Uptime
     *
     * @static
     *
     * @return string uptime的时间
     */
    public static function getSystemUptime()
    {
        $fp = popen('uptime', "r");
        $rs = "";
        while (!feof($fp)) {
            $rs .= fread($fp, 1024);
        }
        pclose($fp);

        $arr = explode(',  ', $rs);
        $uptimeInfoStr = $arr[0];
        $arr = explode(' up ', $uptimeInfoStr);
        return $arr[1];
    }

    /**
     * 获得系统的负载情况
     *
     * @static
     *
     * @return Array($1MinuteAvgLoad, 5MinuteAvgLoad, 15MinuteAvgLoad)
     */
    public static function getSystemLoadInfo()
    {
        $fp = popen('uptime', "r");
        $rs = "";
        while (!feof($fp)) {
            $rs .= fread($fp, 1024);
        }
        pclose($fp);

        $arr = explode('load average: ', $rs);
        $loadInfoStr = trim($arr[1]);
        $arr = explode(', ', $loadInfoStr);
        return $arr;
    }

    /**
     * 获得系统的内存负载情况（单位KB）
     *
     * @static
     *
     * @return Array($totalMemory, $freeMemory, $usedMemory, $usagePercent)
     */
    public static function getSystemMemoryInfo()
    {
        if (false === ($str = @file("/proc/meminfo")))
            return false;
        $memoryTotalInfo = $str[0];
        $memoryUsageInfo = $str[1];

        $arr = explode(" ", $memoryTotalInfo);
        $memoryTotal = $arr[count($arr) - 2];

        $arr = explode(" ", $memoryUsageInfo);
        $memoryFree = $arr[count($arr) - 2];

        $memoryUsage = intval($memoryTotal) - intval($memoryFree);
        $memoryUsagePercent = floatval($memoryUsage) / floatval($memoryTotal);

        return array(
            $memoryTotal,
            $memoryFree,
            $memoryUsage,
            $memoryUsagePercent
        );
    }

    /**
     * 获得系统的CPU使用比率（百分比）
     *
     * @return number CPU使用比率
     */
    public static function getCpuUsageInfo()
    {
        $fp = popen('top -b -n 1 | grep -E "Cpu\(s\):"', "r");
        $rs = "";
        while (!feof($fp)) {
            $rs .= fread($fp, 1024);
        }
        pclose($fp);

        $arr = explode(':', $rs);
        $rs = $arr[1];

        $arr = explode(',', $rs);
        $idleCPU = trim($arr[3]);

        $arr = explode(' ', $idleCPU);
        return 100 - floatval($arr[0]);
    }

    /**
     * 获得网络总进/出口带宽，可能有多个网口和带宽
     *
     * @return Array(Array($ethName, $receiveBytes, $receivePackages, $sendBytes, $sendPackages))
     */
    public static function getNetIOInfo()
    {
        if (false === ($str = @file("/proc/net/dev")))
            return false;
        $str = implode('', $str);
        $pattern = "/(eth[0-9]+):\s*([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)/";
        preg_match_all($pattern, $str, $out);

        $totalEthCount = count($out[1]);
        $result = array();
        for ($n = 0; $n < $totalEthCount; $n++) {
            $ethName = $out[1][$n];
            $receiveBytes = $out[2][$n];
            $receivePackages = $out[3][$n];
            $sendBytes = $out[10][$n];
            $sendPackages = $out[11][$n];
            $result[] = array(
                $ethName,
                $receiveBytes,
                $receivePackages,
                $sendBytes,
                $sendPackages
            );
        }
        return $result;
    }
}