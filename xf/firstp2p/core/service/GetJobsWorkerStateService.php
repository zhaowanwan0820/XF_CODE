<?php
/**
 * GetJobsWorkerStateService.php
 * JobsWorker执行状况监测Service
 * @date 2016-01-28
 * @author 樊靖雯 <fanjingwen@ucfgourp.com>
 */

namespace core\service;


class GetJobsWorkerStateService {

    const REGULAR_EXP_PATH = "\/scripts\/jobs_worker.php"; // 匹配检索结果字段
    /**
     * function:获得本机JobsWorker进程的运行状态
     * @return:string:进程COMMAND
     */
    public function getJobsWorkerStatus()
    {
        // 筛选出jobs_worker.php进程
        $handle = popen("ps aux | grep jobs_worker.php | grep -v grep" , "r");
        if (false == $handle){
            print_r("shell 命令执行出错！");
            exit(1);
        }

        // 将筛选结果拼接成单行字符串
        $contents = fgets($handle);
        while (!feof($handle)) {
            $contents .= fgets($handle);
        }
        pclose($handle);

        // 利用正则表达式，获取进程信息中有用的部分 (COMMAND+priority)
        $pattern = '/' . self::REGULAR_EXP_PATH . '\s*\d*/';
        $arrPro = array();
        preg_match_all($pattern , $contents , $arrPro);

        // 将数组信息按行排列
        $strPro = "";
        $count = count($arrPro[0]);
        for ($num = 0; $num < $count; ++$num) {
            $strPro .= $arrPro[0][$num] . "<br />";
        }
        
        return $strPro;
    }

    /**
     * function:获取本地数据库配置
     * return:array:php配置文件返回的数组
     */
    public function getDbConfig()
    {
        //$dbConf = include "../conf/db.conf.php"; //self::DB_CONF_PATH;
        $sysConf = $GLOBALS['sys_config'];
        return $sysConf;
    }
}
?>
