<?php
/**
 * Jobs class
 * 后台任务类
 */
namespace core\dao;

use libs\utils\Logger;

class JobsModel extends BaseModel
{
    const JOBS_STATUS_WAITING = 0;
    const JOBS_STATUS_PROCESS = 1;
    const JOBS_STATUS_SUCCESS = 2;
    const JOBS_STATUS_FAILED = 3;

    const ERRORCODE_NEEDDELAY = 1005;
    const ERRORMSG_NEEDDELAY = "Jobs Need To Be Back";

    //投标成功回调优先级
    const PRIORITY_BID_SUCCESS_CALLBACK = 150;
    //多投投标成功回调优先级
    const PRIORITY_DT_BID_SUCCESS = 140;
    //信贷上标回调优先级
    const PRIORITY_XD_BID_SUCCESS = 145;
    //存管相关
    const PRIORITY_SUPERVISION = 0;

    // 黄金放款优先级
    const PRIORITY_GOLD_MAKE_LOAN = 1001;

    // 黄金放款用户解冻优子任务先级
    const PRIORITY_GOLD_MAKE_LOAN_USER_LOG = 1002;

    // 黄金放款回款子任务先级计划
    const PRIORITY_GOLD_MAKE_LOAN_REPAY = 1003;

    // 黄金放款更新完成子任务状态先级计划
    const PRIORITY_GOLD_MAKE_LOAN_REPAY_COMPLETE_STATUS = 1004;

    //黄金投标成功回调优先级
    const PRIORITY_GOLD_BID_SUCCESS_CALLBACK = 1005;

    //黄金投标成功合同回调优先级
    const PRIORITY_GOLD_CONTRACT = 1006;

    //黄金流标优先级
    const PRIORITY_GOLD_FAILDEAL = 1007;

    // 智多鑫投资
    const PRIORITY_DTB_CALLBACK_BID = 88;

    const PRIORITY_DTB_COUPON = 85;

    // 智多鑫还款通知银行
    const PRIORITY_DTB_REPAY_BANK = 77;

    // 智多鑫还款资金记录
    const PRIORITY_DTB_REPAY_MONEY = 55;

    // 智多鑫匹配完成拉取债转数据
    const PRIORITY_DTB_GET_TRANSDATA = 333;

    //黄金变现优先级
    const PRIORITY_GOLD_WITHDRAW = 1008;

    // 报备标的还款通知
    const PRIORITY_P2P_REPAY_REQUEST = 199;

    // 代扣回调成功后的处理
    const PRIORITY_P2P_DK_CALLBACK = 200;

    const BID_CHECK_FULL_CONTRACT  = 122;//满标检查合同
    const SEND_CONTRACT_MSG = 124;//下发合同
    //  合同打时间戳
    const CONTRACT_TSA = 175;
    // 转移临时合同到正式合同记录
    const PRIORITY_CONTRACT = 176;
    const CONTRACT_JOBS_TSA_DT = 178; // 生成智多新/随心约 打戳jobs
    const CONTRACT_JOBS_TSA_RESERVATION = 179;  // 随心约合同打戳;

    // 享花标的还款通知
    const PRIORITY_XH_REPAY_NOTIFY = 201;

    // 订单拆分服务-请求存管
    const PRIORITY_ORDERSPLIT_REQUEST = 202;

    // 农担贷还款信息试算
    const PRIORITY_ND_REPAY_CALC = 203;

    // 农担贷还款-请求存管
    const PRIORITY_ND_REPAY_REQUEST = 204;

    // 农担贷还款-存管回调后处理
    const PRIORITY_ND_REPAY_CALLBACK = 205;

    // 标的放款
    const PRIORITY_DEAL_GRANT = 99;

    const PRIORITY_DEAL_REPAY = 90;

    // 立即还款
    const PRIORITY_ADD_BATH_RIGHT = 92;

    const PRIORITY_CREATE_PROJECT_REPAY_LIST = 91;  // 生成项目还款列表

    const PRIORITY_NOTICE_PARTNER = 1009;           // 通知第三方合作伙伴
    const PRIORITY_O2O_TRIGGER = 1010;              // o2o同步触发记录

    const REPAY_FREEZE_NOTIFY_SUDAI = 151;  // 回款冻结通知速贷
    const PRIORITY_PROJECT_REPAY = 110;             //专享1.75项目还款

    const PRIORITY_UPLOADTOJF = 120;             //上传到即富
    const PRIORITY_RESERVE_DISCOUNT = 130;             //随鑫约投资券
    const PRIORITY_RESERVE_PROTOCOL = 131;             //随鑫约预约协议

    const PRIORITY_ZX_TRANSFER = 666; // 专享盈嘉转账请求
    const PRIORITY_ZX_TRANSFER_CHECK = 667; // 专享盈嘉转账是否全部完成
    const PRIORITY_ZX_DF_RECORD = 668; // 代发记录计算jobs
    const PRIORITY_DARKMOON_EMAIL_SEND = 669;


    /**********************消息队列优先级**********************/
    const PRIORITY_MESSAGE_QUEUE                = 2000;  //通用消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_LOAN           = 2001;  //放款消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_REPAY          = 2002;  //还款消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_PREPAY         = 2003;  //提前还款消息队列优先级
    const PRIORITY_MESSAGE_QUEUE_PART_PREPAY    = 2004;  //部分提前还款消息队列优先级

    public static $statusCn = array(
        self::JOBS_STATUS_WAITING => '等待执行',
        self::JOBS_STATUS_PROCESS => '执行中',
        self::JOBS_STATUS_SUCCESS => '执行成功',
        self::JOBS_STATUS_FAILED => '执行失败',
    );

    const GET_JOBS_COUNT = 500;

    public static $jobs_mapping = array(
        '\\core\\service\\DealService::makeDealLoansJob' => '放款',
        '\\core\\service\\DealLoanRepayService::create' => '放款子任务',
        '\\core\\service\\DealRepayService::repay' => '正常还款',
        '\\core\\dao\\DealLoanRepayModel::repayDealLoanOne' => '正常还款子任务',
        '\\core\\service\\DealPrepayService::prepay' => '提前还款',
        '\\core\\dao\\DealPrepayModel::prepayByLoanId' => '提前还款子任务',
        '\\core\\service\\DealCompoundService::repayCompound' => '通知贷还款',
        '\\core\\service\\DealCompoundService::repayCompoundJobs' => '通知贷还款子任务',
        '\\core\\service\\DealService::DealFullChecker' => '满标生成合同',
        '\\core\\service\\ContractService::signDealContNew' => '合同签署',
        '\\core\\service\\CouponDealService::updatePaidDeal' => '更新所有标的结清状态',
        '\\core\\service\\CouponLogService::payForDeal' => '优惠码结算',
        '\\core\\service\\CouponLogService::updateRebateDaysForDeal' => '更新叠加通知贷的返利天数',
    );

    /**
     * 获取一批Jobs
     */
    public function getJobs($priority, $pidCount = 1, $pidOffset = 0)
    {
        $status = self::JOBS_STATUS_WAITING;
        $now = get_gmtime();

        $condition = "status='{$status}' AND priority='{$priority}' AND start_time<'{$now}' AND id%{$pidCount}={$pidOffset}";
        $condition .= ' ORDER BY id ASC LIMIT '.self::GET_JOBS_COUNT;

        $result = $this->findAll($condition);
        Logger::info("JobsWorkerGetJobs. priority:{$priority}, pidCount:{$pidCount}, pidOffset:{$pidOffset}, result:".count($result));

        return $result;
    }

    /**
     * 启动一条Job
     */
    public function start()
    {
        $this->timer_tida = microtime(true);

        $data = array(
            'status' => self::JOBS_STATUS_PROCESS,
            'begin_time' => get_gmtime(),
        );

        $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".self::JOBS_STATUS_WAITING);
        if ($GLOBALS['db']->affected_rows() != 1) {
            Logger::info("JobsWorkerSetStartFailed. id:{$this->id}");
            return false;
        }

        Logger::info("JobsWorkerStart. id:{$this->id}, function:{$this->function}");
        return true;
    }

    /**
     * 任务执行成功处理
     */
    public function success()
    {
        $data = array(
            'finish_time' => get_gmtime(),
            'job_cost' => round(microtime(true) - $this->timer_tida, 3),
            'status' => self::JOBS_STATUS_SUCCESS,
        );

        $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".self::JOBS_STATUS_PROCESS);
        if ($GLOBALS['db']->affected_rows() != 1) {
            Logger::info("JobsWorkerSetSuccessFailed. id:{$this->id}");
            throw new \Exception('任务置为成功状态失败');
        }

        Logger::info("JobsWorkerSuccess. id:{$this->id}, function:{$this->function}, cost:".round(microtime(true) - $this->timer_tida, 3));
    }

    /**
     * 任务执行失败处理
     */
    public function failed()
    {
        //如果需要重试
        if ($this->retry_cnt > 0) {
            $data = array(
                'retry_cnt' => $this->retry_cnt - 1,
                'status' => self::JOBS_STATUS_WAITING,
            );
            $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".self::JOBS_STATUS_PROCESS);

            \libs\utils\Monitor::add('JOBS_WORKER_RUN_RETRY');
            Logger::info("JobsWorkerRetry. id:{$this->id}, function:{$this->function}, count:{$this->retry_cnt}");
            return;
        }

        //失败处理
        $data = array(
            'finish_time' => get_gmtime(),
            'job_cost' => round(microtime(true) - $this->timer_tida, 3),
            'err_msg' => $this->err_msg,
            'status' => self::JOBS_STATUS_FAILED,
        );

        $GLOBALS['db']->update('firstp2p_jobs', $data, "id='{$this->id}' AND status=".self::JOBS_STATUS_PROCESS);
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
            $where = sprintf($where, self::JOBS_STATUS_WAITING, get_gmtime());
        }else{
            $where = " `status`='%d' AND `priority` BETWEEN %d AND %d AND (`start_time`='0' OR (`start_time`!='0' AND `start_time`<'%d')) LIMIT 1";
            $where = sprintf($where, self::JOBS_STATUS_WAITING, $low, $high, get_gmtime());
        }
        $params = array(
            'status' => self::JOBS_STATUS_PROCESS,
            'begin_time' => get_gmtime(),
        );
        return $this->updateReturnRow($params, $where);
    }

    /**
     * 运行一条任务
     * @return mixed
     * Edit By QingLongs
     */
    public function runJob()
    {
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
            if ($e->getCode() == self::ERRORCODE_NEEDDELAY) {
                $this->delayJobs();
                throw $e;
            } else {
                $this->err_msg = $e->getMessage();
                //$this->save();
            }
        }
    }

    public function delayJobs() {
        $this->status = 0;
        $this->start_time = get_gmtime() + 60;
        $this->save();

        if (get_gmtime() - $this->create_time > 1800) { // 超过半小时认为任务异常
            $this->_alarm();
        }
    }

    /**
     * 增加一条后台任务
     * @param  string    $func       调用方法名
     * @param  array     $params     调用参数
     * @param  int|false $start_time 计划开始执行时间
     * @return bool
     */
    public function addJob($func, $params=array(), $start_time=false, $retry_cnt = 3)
    {
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
    public function startJob()
    {
        $this->status = self::JOBS_STATUS_PROCESS;
        $this->begin_time = get_gmtime();
        $this->timer_tida = microtime(true);
        return $this->save();
    }

    /**
     * 完成一条任务
     * @param  bool $is_succ 是否成功
     * @return bool
     */
    public function finishJob($is_succ = true)
    {
        $this->finish_time = get_gmtime();
        $this->job_cost = bcsub(microtime(true), $this->timer_tida, 6).'s';
        $this->status = $is_succ === true ? self::JOBS_STATUS_SUCCESS : self::JOBS_STATUS_FAILED;
        if ($this->status == self::JOBS_STATUS_FAILED) {    // 如果失败
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
    public function retry()
    {
        if ($this->retry_cnt > 0) {
            $this->retry_cnt--;
            //$this->begin_time = get_gmtime();
            $this->status = self::JOBS_STATUS_WAITING;
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
