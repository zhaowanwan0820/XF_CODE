<?php
/**
 * @desc  jobs重试
 * User: 王鉴通
 * Date: 2016/8/11 12:48
 * warning!运行此脚本的任务优先级必须为幂等任务,无法保证幂等的任务,请不要使用!
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\JobsModel;
use libs\utils\Logger;

class JobsRetry{

    public function run($priority)
    {
        $jobsModel = new JobsModel();
        //取失败的jobs记录
        $createTime = time() - 7200 - 8 * 3600; //只捞取创建时间在当前时间前2小时的记录
        $startTime = time() - 2400 - 8 * 3600; //开始时间未当前时间前40分钟执行失败的
        $endTime = time() - 1200 - 8 * 3600; //结束时间为当前时间前20分钟执行失败的
        $jobs = $jobsModel->findAllBySql('SELECT id FROM firstp2p_jobs WHERE priority = '.$priority.' AND create_time > '.$createTime.' AND finish_time > '.$startTime.' AND finish_time < '.$endTime.' AND status = 3;');
        foreach($jobs as $job){
            //重试
            $updateSql = 'UPDATE firstp2p_jobs SET status = 0 WHERE id = '.$job['id'];
            if($jobsModel->execute($updateSql)){
                continue;
            }else{
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"jobs:".$job['id']."捞取失败!")));
            }
        }
        echo "success!";
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');
if(count($argv) == 2){
    $priority = intval($argv[1]);
    if($priority > 0){
        $obj = new JobsRetry();
        $obj->run($priority);
    }else{
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"jobs优先级参数不正确")));
        exit();
    }
}else{
    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"参数个数不正确!")));
    exit();
}
