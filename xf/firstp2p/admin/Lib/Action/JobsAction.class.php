<?php
/**
 * JobsAction class file.
 *
 * @author wangqunqiang@ucfgroup.com
 * */
use core\service\JobsService;
use core\dao\JobsModel;


//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define('__DEBUG', false);

class JobsAction extends CommonAction{
    public function __construct() {
        parent::__construct();
    }


    public function wait() {
        $_GET['status'] = JobsModel::JOBS_STATUS_WAITING;
        $this->index();
    }
    public function process() {
        $_GET['status'] = JobsModel::JOBS_STATUS_PROCESS;
        $this->index();
    }
    public function succ() {
        $_GET['status'] = JobsModel::JOBS_STATUS_SUCCESS;
        $this->index();
    }
    public function fail() {
        $_GET['status'] = JobsModel::JOBS_STATUS_FAILED;
        $this->index();
    }

    // 今日执行情况
    public function today() {
        $time = strtotime(date('Y-m-d', get_gmtime()));
        $list = JobsModel::instance()->getList($time);

        $func = $_REQUEST['func'];
        if ($func) {
            $start_time = $time - 86400*7;
            $list_week = JobsModel::instance()->getListByFunc($func, $start_time, get_gmtime());

            $count = $list_week['count'];
            $cost = $list_week['cost'];

            $arr_date = array();
            for ($i=0; $i<=7; $i++) {
                $d = date('Y-m-d', $start_time + 86400*$i);
                $arr_date[$i] = $d;
                $arr_count[$i] = $count[$d] ? $count[$d] : '0';
                $arr_cost[$i] = $cost[$d] ? $cost[$d] : '0';
            }

            $this->assign('arr_date', $arr_date);
            $this->assign('count', $arr_count);
            $this->assign('cost', $arr_cost);

        }
        $this->assign('list', $list);
        $this->display('today');
    }

    public function index() {
        $map = array();
        if(isset($_GET['status'])) {
            $map['status'] =  intval($_GET['status']);
        }
        else {
            $map['status'] = 0;
        }
        if(isset($_REQUEST['priority']) && $_REQUEST['priority']!='' ){
            $map['priority']=intval($_REQUEST['priority']);
        }
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $statusCn = JobsModel::$statusCn[$map['status']];
        $GLOBALS['statusCn'] = JobsModel::$statusCn;
        $this->_list(MI('Jobs'), $map);
        $this->assign('statusCn', $statusCn);
        $this->assign('status', $status);
        $this->assign('p', $p);
        $this->display('index');
    }

    public function view() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }
        $jobsService = new JobsService;
        $job = $jobsService->load($id);
        if (empty($job)) {
            $this->error('记录不存在');
        }
        $job['status'] = JobsModel::$statusCn[$job['status']];
        $job['created'] = empty($job['create_time']) ? '-' : date('Y-m-d H:i:s', $job['create_time'] + 28800);
        $job['begined'] = empty($job['begin_time']) ? '-' : date('Y-m-d H:i:s', $job['begin_time'] + 28800);
        $job['started'] = empty($job['start_time']) ? '-' : date('Y-m-d H:i:s', $job['start_time'] + 28800);
        $job['finished'] = empty($job['finish_time']) ? '-' : date('Y-m-d H:i:s', $job['finish_time'] + 28800);
        $this->assign('job', $job);
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('p', $p);
        $this->display();
    }

    public function multi_redo() {
        $ids = isset($_GET['id']) ? $_GET['id'] : 0;
        if (empty($ids)) {
            $this->error('无效的参数');
        }

        $jobsService = new JobsService;
        $id_arr = explode(",", $ids);
        foreach ($id_arr as $k => $v) {
            try {
                $jobsService->redo($v);
            }
            catch(\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->success('加入队列成功');
    }

    public function redo() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $status = isset($_GET['id']) ? intval($_GET['status']) : 0;
        if (empty($id)) {
            $this->error('无效的参数');
        }
        $jobsService = new JobsService;
        try {
            $jobsService->redo($id);
        }
        catch(\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('加入队列成功');
    }
}
