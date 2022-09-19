<?php
/**
 * 红包任务
 */
namespace core\service;

use libs\utils\Logger;
use core\dao\UserModel;
use core\dao\BonusJobsModel;
use core\service\BonusService;

class BonusJobsService extends BaseService {

    //日志文件前缀
    const JOB_LOG_PREFIX = 'log/logger/bonusjob_';

    /**
     * 获取job
     * @param int $id
     */
    public function getJobById($id){
        return BonusJobsModel::instance()->find($id);
    }

    /**
     * 获取某个规则下的发送列表
     * @param int $id 合同id
     * @return array
     */
    public function getUserByJob($job_id){
        $job = BonusJobsModel::instance()->find($job_id);
        if($job){
            return UserModel::instance()->getUserListByJob($job['user_group'], $job['user_tag'], $job['tag_relation']);
        }
        return false;
    }

    /**
     * 执行任务
     * @param array $params
     * @return boolean
     */
    public function runSendJob($id){

        $id = intval($id);
        $job = $this->getJobById($id);

        $log_file = APP_ROOT_PATH.'/'.self::JOB_LOG_PREFIX.$id.'_'.date('y_m_d').'.log';
        if($job['is_effect'] == 0){
            Logger::wLog('任务为无效状态', Logger::INFO, Logger::FILE, $log_file);
            return false;
        }

        if($job['end_time'] < get_gmtime()){
            Logger::wLog('任务已过期', Logger::INFO, Logger::FILE, $log_file);
            return false;
        }

        $list = $this->getUserByJob($id);
        $list_chunk = array_chunk($list, 500);
        $bonus_service = new BonusService($id);//绑定batch_id

        foreach($list_chunk as $list){
            foreach ($list as $user){
                $user_count = 0;
                for($i = 0; $i < $job->group_count; $i++){
                    $res = $bonus_service->generation($user['id'], 0, 0, 0.25, 0, 1, $job->group_money, $job->bonus_count, $job->group_validity);
                    if($res){
                        $user_count++;
                    }
                }
                Logger::wLog(sprintf("mobile:%s | success:%d", $user['mobile'], $user_count), Logger::INFO, Logger::FILE, $log_file);
                if (!$job['send_sms']) {
                    continue;
                }

                // TODO 发短信
                $params = array();
                if (!empty($job['sms_tpl_params'])) {
                    $paramsConf = explode(',' , $job['sms_tpl_params']);
                    foreach ($paramsConf as $conf) {
                        $field = strtolower($conf);
                        if (!isset($job[$field])) {
                            continue;
                        }
                        $params[$field] = $job[$field];
                    }
                }
                $params = array('。');
                \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_BONUS_JOBS', $params, $user['id']);

            }
            sleep(1);
        }
        return true;
    }
}
