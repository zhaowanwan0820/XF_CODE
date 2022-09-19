<?php
/**
 * Jobs class
 * 后台任务类
 */
namespace core\dao\jobs;

use core\dao\BaseModel;
use libs\utils\Logger;
use core\enum\JobsEnum;

class JobsModel extends BaseModel {

    /**
     * 获取一批Jobs
     */
    public function getJobs($priority, $pidCount = 1, $pidOffset = 0) {
        $status = JobsEnum::JOBS_STATUS_WAITING;
        $now = get_gmtime();

        $condition = "status='{$status}' AND priority='{$priority}' AND start_time<'{$now}' AND id%{$pidCount}={$pidOffset}";
        $condition .= ' ORDER BY id ASC LIMIT '.JobsEnum::GET_JOBS_COUNT;

        $result = $this->findAll($condition);
        Logger::info("JobsWorkerGetJobs. priority:{$priority}, pidCount:{$pidCount}, pidOffset:{$pidOffset}, result:".count($result));
        return $result;
    }


    /**
     * 启动一条Job
     */
    public function start() {
        $this->timer_tida = microtime(true);

        $data = array(
            'status' => JobsEnum::JOBS_STATUS_PROCESS,
            'begin_time' => get_gmtime(),
        );

        $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".JobsEnum::JOBS_STATUS_WAITING);
        if ($GLOBALS['db']->affected_rows() != 1) {
            Logger::info("JobsWorkerSetStartFailed. id:{$this->id}");
            return false;
        }

        Logger::info("JobsWorkerStart. id:{$this->id}, function:{$this->function}");
        return true;
    }

    /**
     * 运行一条任务
     * @return mixed
     * Edit By QingLongs
     */
    public function runJob() {
        $str_func = $this->function;
        if (empty($str_func)) {
            throw new \Exception('function为空');
        }

        $params = $this->params;
        $arr_params = empty($params) ? false : json_decode($params, true);
        $arr_tmp = explode("::", $str_func);

        try {
            if (count($arr_tmp) == 1) {
                return call_user_func_array($str_func, $arr_params);
            } elseif (count($arr_tmp) == 2) {
                $class_func = array(new $arr_tmp[0],$arr_tmp[1]);

                return call_user_func_array($class_func, $arr_params);
            } else {
                return false;
            }
        } catch(\Exception $e) {
            if ($e->getCode() == JobsEnum::ERRORCODE_NEEDDELAY) {
                throw $e;
            } else {
                $this->err_msg = $e->getMessage();
            }
        }
    }


    /**
     * 延迟执行任务
     */
    public function delayJobs() {
        $this->status = 0;
        $this->start_time = get_gmtime() + 30;
        $this->save();

        if (get_gmtime() - $this->create_time > 3600) { // 超过1小时认为任务异常
            $this->_alarm();
        }
    }

    /**
     * 任务执行成功处理
     */
    public function success(){
        $data = array(
            'finish_time' => get_gmtime(),
            'job_cost' => round(microtime(true) - $this->timer_tida, 3),
            'status' => JobsEnum::JOBS_STATUS_SUCCESS,
        );

        $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".JobsEnum::JOBS_STATUS_PROCESS);
        if ($GLOBALS['db']->affected_rows() != 1) {
            Logger::info("JobsWorkerSetSuccessFailed. id:{$this->id}");
            throw new \Exception('任务置为成功状态失败');
        }
        Logger::info("JobsWorkerSuccess. id:{$this->id}, function:{$this->function}, cost:".round(microtime(true) - $this->timer_tida, 3));
    }


    /**
     * 任务执行失败处理
     */
    public function failed() {
        //如果需要重试
        if ($this->retry_cnt > 0) {
            $data = array(
                'retry_cnt' => $this->retry_cnt - 1,
                'status' => JobsEnum::JOBS_STATUS_WAITING,
            );
            $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".JobsEnum::JOBS_STATUS_PROCESS);

            \libs\utils\Monitor::add('JOBS_WORKER_RUN_RETRY');
            Logger::info("JobsWorkerRetry. id:{$this->id}, function:{$this->function}, count:{$this->retry_cnt}");
            return;
        }

        //失败处理
        $data = array(
            'finish_time' => get_gmtime(),
            'job_cost' => round(microtime(true) - $this->timer_tida, 3),
            'err_msg' => $this->err_msg,
            'status' => JobsEnum::JOBS_STATUS_FAILED,
        );

        $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".JobsEnum::JOBS_STATUS_PROCESS);
        if ($GLOBALS['db']->affected_rows() != 1) {
            Logger::info("JobsWorkerSetFailedFailed. id:{$this->id}");
            throw new \Exception('任务置为失败状态失败');
        }

        \libs\utils\Monitor::add('JOBS_WORKER_RUN_FAILED');
        \libs\utils\Alarm::push('deal', $this->function, $this->err_msg);
    }

    /**
     * 获取一条有效任务
     * @return object|null
     */
    public function getOneJob($low=0,$high=0)
    {
        if(empty($high)){
            $where = " `status`='%d' AND (`start_time`='0' OR (`start_time`!='0' AND `start_time`<'%d')) LIMIT 1";
            $where = sprintf($where, JobsEnum::JOBS_STATUS_WAITING, get_gmtime());
        }else{
            $where = " `status`='%d' AND `priority` BETWEEN %d AND %d AND (`start_time`='0' OR (`start_time`!='0' AND `start_time`<'%d')) LIMIT 1";
            $where = sprintf($where, JobsEnum::JOBS_STATUS_WAITING, $low, $high, get_gmtime());
        }
        $params = array(
            'status' => JobsEnum::JOBS_STATUS_PROCESS,
            'begin_time' => get_gmtime(),
        );
        return $this->updateReturnRow($params, $where);
    }




    /**
     * 增加一条后台任务
     * @param  string    $func       调用方法名
     * @param  array     $params     调用参数
     * @param  int|false $start_time 计划开始执行时间
     * @return bool
     */
    public function addJob($func, $params=array(), $start_time=false, $retry_cnt = 3){
        $this->function = addslashes($func);
        $this->params = empty($params) ? "" : addslashes(json_encode($params));
        $this->create_time = get_gmtime();
        $this->start_time = $start_time;
        $this->retry_cnt = $retry_cnt;
        return $this->insert();
    }

    /**
     * 启动一条任务
     * @return bool
     */

    public function startJob(){
        $this->status = JobsEnum::JOBS_STATUS_PROCESS;
        $this->begin_time = get_gmtime();
        $this->timer_tida = microtime(true);
        return $this->save();
    }

    /**
     * 完成一条任务
     * @param  bool $is_succ 是否成功
     * @return bool
     */
    public function finishJob($is_succ = true) {
        $this->finish_time = get_gmtime();
        $this->job_cost = bcsub(microtime(true), $this->timer_tida, 6).'s';
        $this->status = $is_succ === true ? JobsEnum::JOBS_STATUS_SUCCESS : JobsEnum::JOBS_STATUS_FAILED;
        if ($this->status == JobsEnum::JOBS_STATUS_FAILED) {    // 如果失败
            $this->retry();
        }
        return $this->save();
    }

    /**
     * retry
     * 失败重试
     *
     * @access public
     * @return void
     */
    public function retry(){
        if ($this->retry_cnt > 0) {
            $this->retry_cnt--;
            //$this->begin_time = get_gmtime();
            $this->status = JobsEnum::JOBS_STATUS_WAITING;
            $this->start_time = get_gmtime() + 60;
            echo "retry {$this->retry_cnt}\n";
        } else {
            //$data_arr = array($this->id,$this->function, $this->params, $this->retry_cnt);
            $this->_alarm();
        }
    }

    /**
     * _alarm
     *
     * 报警处理函数
     * @access private
     * @return void
     */
    private function _alarm()
    {
        if (strlen(trim($this->err_msg)) > 0 ) {
            $content = "id:%s 操作:%s 参数:%s ERROR:%s";
            $content = sprintf($content, $this->id,$this->function, $this->params, $this->err_msg);
        } else {
            $content = "id:%s 操作:%s 参数:%s";
            $content = sprintf($content, $this->id,$this->function, $this->params);
        }

        \libs\utils\Alarm::push('deal', '异步任务异常', $content);

    }

    public function getList($start_time, $end_time=false) {
        $condition = "`create_time` >= '{$start_time}'";
        if ($end_time) {
            $condition .= " AND `create_time` < '{$end_time}'";
        }

        $sql = "SELECT `function`, COUNT(`id`) AS c, `status`, `priority`, AVG(`job_cost`) AS cost FROM " . $this->tableName() . " WHERE " . $condition . " GROUP BY `function`,`status`";

        $list = $this->findAllBySql($sql, true, array(), true);

        $result = array();
        foreach ($list as $v) {
            $name = self::$jobs_mapping[$v['function']];
            if (!$name) {
                continue;
            }
            $result[$name]['function'] = $v['function'];
            $result[$name]['name'] = $name;
            $result[$name]['total'] += $v['c'];
            $result[$name]['priority'] = $v['priority'];
            if ($v['status'] == 0) {
                $result[$name]['wait'] = $v['c'];
            } elseif ($v['status'] == 2) {
                $result[$name]['done'] = $v['c'];
                $result[$name]['cost'] = $v['cost'];
            }
        }
        ksort($result);
        return $result;
    }

    public function getListByFunc($func, $start_time, $end_time=false) {
        $condition = "`function` = '" . addslashes($func) . "' AND `create_time` >= '{$start_time}'";
        if ($end_time) {
            $condition .= " AND `create_time` <= '{$end_time}'";
        }
        $sql = "SELECT `function`, COUNT(`id`) AS c, AVG(`job_cost`) AS cost,from_unixtime(`create_time`+28800, '%Y-%m-%d') AS date FROM " . $this->tableName() . " WHERE {$condition} GROUP BY `function`, `date` ORDER BY `function`, `date`";

        $list = $this->findAllBySql($sql, true, array(), true);

        foreach ($list as $v) {
            $cnt[$v['date']] = $v['c'];
            $cost[$v['date']] = $v['cost'];
        }

        return array('count'=>$cnt, 'cost'=>$cost);
    }
}
