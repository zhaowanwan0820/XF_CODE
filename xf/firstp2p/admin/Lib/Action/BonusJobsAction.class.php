<?php
/**
 * 红包任务管理
 */
use core\dao\JobsModel;
// for gearman  8
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use NCFGroup\Common\Library\Date\XDateTime;
use core\event\BonusBatchEvent;

class BonusJobsAction extends CommonAction {

    private $use_type = array(
            '0' => '仅限投资',
    );

    public function __construct(){
        parent::__construct();
        $this->assign('use_type', $this->use_type);
    }

    //首页
    public function index() {
        $this->error('该功能已经下线，请使用自定义红包任务进行发送!');
        $model = M(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model);
        }
        $list = $this->get('list');

        $use_type = $this->use_type;
        foreach ($list as $k => &$item) {
            $item['use_type_name'] = $use_type[$item['use_type']];
            $item['tag_relation'] = $item['tag_relation'] == 0 ? '或者' : '并且';
        }
        $this->assign('list', $list);
        $this->display();
    }

    //插入
    public function add(){
        $this->assign('use_type', $this->use_type);
        $this->display();
    }

    //插入
    public function insert() {
        $form = M(MODULE_NAME);
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }

        $data['create_time'] = get_gmtime();
        $data['start_time'] = trim($data['start_time']) ? to_timespan($data['start_time']) : 0;
        $data['end_time'] = trim($data['end_time']) ? to_timespan($data['end_time']) : 0;

        if($data['start_time'] == 0 || $data['end_time'] == 0){
            $this->error('时间不能为空');
        }

        if($data['end_time'] <= $data['start_time']){
            $this->error('结束时间应大于开始时间');
        }

        //保存
        $GLOBALS['db']->startTrans();
        try {
            $id = $form->add($data);
            if (!$id) {
                throw new Exception('添加失败');
            }
            if(!$this->add_job($id)){
                throw new Exception('添加job队列失败');
            }
            //使用gearman队列发送
            //$event = new BonusBatchEvent($id);
            //$obj = new GTaskService();
            //$obj->doBackground($event, 1, TASK::PRIORITY_NORMAL, XDateTime::valueOfTime($data['start_time'] + 28800));
            $GLOBALS['db']->commit();
            $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
            $this->success(L("INSERT_SUCCESS"));
        } catch(Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage());
        }
    }

    //编辑
    public function update(){
        B('FilterString');
        $form = D(MODULE_NAME);

        // 字段校验
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }

        $data['update_time'] = get_gmtime();
        $data['end_time'] = trim($data['end_time']) ? to_timespan($data['end_time']) : 0;

        $job_info = M(MODULE_NAME)->find($data['id']);
        if($data['end_time'] <= $job_info['start_time']){
            $this->error('结束时间应大于开始时间');
        }

        //日志信息
        $log_info = "[" . $data[$this->pk_name] . "]";
        if (isset($data[$this->log_info_field])) {
            $log_info .= $data[$this->log_info_field];
        }
        $log_info .= "|";

        // 保存
        $result = $form->save($data);
        $this->pk_value = $data[$this->pk_name];
        if ($result !== false) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->display_success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"));
        }
    }

    //导出手机号
    public function mobile_csv(){
        $id = intval($_GET['id']);
        if($id <= 0){
            $this->error('error');
        }

        ini_set('memory_limit', '1024M');

        $bonusjobs_service = new \core\service\BonusJobsService();
        $list = $bonusjobs_service->getUserByJob($id);
        $job_info = M(MODULE_NAME)->find($id);

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename={$job_info['name']}.csv");
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        foreach ($list as $user) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, array($user['mobile']));
        }
    }

    //添加到任务
    private function add_job($id){
        $job_info = M(MODULE_NAME)->find($id);
        $function  = '\core\service\BonusJobsService::runSendJob';
        $add_count = 0;
        for($i=$job_info['start_time']; $i<$job_info['end_time']; $i+=86400){
            $res = JobsModel::instance()->addJob($function, array('id' => $id), $i);
            if($res){
                $add_count++;
            }
        }
        return $add_count;
    }

    /* private function add_job($id){

        $job_info = M(MODULE_NAME)->find($id);
        //job的个数,进1取整
        $job_count = ceil(bcdiv($job_info['end_time'] - $job_info['start_time'], 86400, 20));

        $start_time = $job_info['start_time'];
        $function  = str_replace('\\', '\\\\', '\core\service\BonusJobsService::runSendJob');
        $add_count = 0;
        for($i=0; $i<$job_count; $i++){
            $start_time += 86400;
            $res = JobsModel::instance()->addJob($function, array('id' => $id), $start_time);
            if($res){
                $add_count++;
            }
        }
        return $add_count == $job_count ? true : false;
    } */

    public function test_job(){
        $bonusjobs_service = new \core\service\BonusJobsService();
        $bonusjobs_service->runSendJob(array('id' => intval($_GET['id'])));
    }
}
?>
