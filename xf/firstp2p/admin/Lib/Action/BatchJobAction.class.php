<?php
/**
 * 批作业控制台
 * @author jinhaidong@ucfgroup.com
 * @date 2015-12-29 11:29:30
 */

use core\service\ContractNewService;
use core\service\DealService;
use core\service\DealProjectService;
use core\service\DealRepayAccountService;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\DealRepayModel;
use libs\utils\Logger;


class BatchJobAction extends CommonAction{

    /**
     * 批作业类型
     * @var array
     */
    public $jobType = array(
        1 => '还款',
        2 => '时间戳',
        3 => '放款',
        4 => '专享项目还款',
        5 => '代扣',
    );

    /**
     * 批作业类型普惠
     * @var array
     */
    public $jobType_cn = array(
        1 => '还款',
        2 => '时间戳',
        5 => '代扣',
    );
    public function __construct() {
        parent::__construct();
    }
    // 批作业列表
    public function index() {
        $id = intval($_REQUEST['id']);
        $job_name = trim($_REQUEST['job_name']);

        if($id) {
            $options['id'] = $id;
        }
        if(!empty($job_name)) {
            $options['job_name'] = array('like','%'.$job_name.'%');
        }
        
        if ($this->is_cn) {
            $show_cn = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];
            $allow = $job =[];
            if (!empty($show_cn)) {
                foreach ($show_cn as $key=>$value) {
                    $allow[] = $value['id'];
                }
                $ids = '0,'.implode(',', $allow); //增加deal_type是0的类型
            }
            $options['deal_type']  = array('in',$ids);

            $job_type = $this->jobType_cn;
            if (!empty($job_type)) {
                foreach ($job_type as $k=>$v) {
                    $job[] = $k;
                }

            }
            $options['job_type']  = array('in', implode(',', $job));
        }
        $name=$this->getActionName();
        $model = D ($name);
        $res = $this->_list ($model,$options,'id');

        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
        $this->assign('deal_loan_type', $this->is_cn ? $GLOBALS['dict']['DEAL_TYPE_ID_CN'] : $dealLoanType);
        //$this->assign('yesterday_ts', strtotime(date('Y-m-d'))-86400);
        $this->assign('yesterday_ts', to_timespan(date('Y-m-d'),'Y-m-d') - 86400);
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }
    /*新增*/
    public function add() {
        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
        if ($this->is_cn) {
            $dealLoanType = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];
        }
        $this->assign('deal_loan_type',$dealLoanType);
        $this->assign('job_type',$this->is_cn ? $this->jobType_cn :$this->jobType);
        $this->display();
    }
    /*新增*/
    public function doAdd() {
        if(!empty($_FILES['job_file']["tmp_name"])){
            $data['job_ids'] = $this->process($_FILES["job_file"]);
        }
        $data['job_name'] = $_POST['job_name'];
        $data['job_type'] = $_POST['job_type'];
        $data['repay_mode'] = $_POST['repay_mode'];
        if($data['job_type'] == 3 || $data['job_type'] == 5){
            if(empty($_POST['type_id']) && $_POST['type_id'] == ''){
                $this->error("未指定产品类别!");
            }
            if($data['job_type'] == 3 && empty($_POST['full_status_time']) && $_POST['full_status_time'] == ''){
                $this->error("未指定满标截止时间!");
            }
            $data['full_status_time'] = $_POST['full_status_time'];
        }
        $data['is_right_now'] =  $_POST['is_right_now'];
        if((empty($_POST['job_run_time']) ||  $_POST['job_run_time'] == '') && $data['is_right_now'] != 1){
            $this->error("执行时间不能为空!");
        }
        if($data['is_right_now'] == 1){
            $_POST['job_run_time'] = '';
        }
        if(!empty( $data['job_ids']) && $data['is_right_now'] != 1){
            $this->error("导入标的必须立即执行!");
        }
        if(!empty( $data['job_ids']) && $data['type_id'] != 0){
            $this->error("导入标的不能指定产品类别!");
        }
        $data['deal_type'] = $_POST['type_id'];
        if(!isset($this->jobType[$data['job_type']])) {
            $this->error('业务类型不正确');
        }
        $data['job_interval_start'] = strtotime($_POST['job_interval_start']);
        $data['job_interval_end'] = strtotime($_POST['job_interval_end']);
        if($data['job_interval_end'] <= $data['job_interval_start']) {
            $this->error('有效期结束时间不能小于开始时间');
        }
        $data['job_run_time'] = $_POST['job_run_time'];
        $data['job_status'] = $_POST['job_status'];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['next_repay_time'] = !empty($_POST['next_repay_time']) ? strtotime($_POST['next_repay_time']) : 0;
        $result = M(MODULE_NAME)->add($data);
        if(!$result){
            $dbErr = M()->getDbError();
            $this->error(L("INSERT_FAILED").$dbErr);
        }
        if( $data['is_right_now'] == 1 &&  $data['job_status'] ==1 && $data['job_interval_start'] <= time()  && $data['job_interval_end'] >= time()  && $data['job_type'] ==1  ) {//立即执行
            $ret= $this->add_bath_to_Job($result);
            if(!$ret){
                $this->error('立即执行错误');
            }
        }
        save_log($data['job_name'],1);
        $this->success(L("INSERT_SUCCESS"),0,'m.php?m=BatchJob&a=index');
    }
    /*编辑*/
    public function edit() {
        $id = intval($_REQUEST['id']);
        if($id <=0) {
            $this->error("操作错误");
        }
        $dealLoanTypeModel = new DealLoanTypeModel();
        $dealLoanType = $dealLoanTypeModel->findAll("is_effect = 1",true,"id,name");
        $data = M('BatchJob')->where("id=".$id)->find();

        if ($this->is_cn) {
            $this->assign('job_type',$this->jobType_cn);
            $dealLoanType = $GLOBALS['dict']['DEAL_TYPE_ID_CN'];
        } else {
            $this->assign('job_type',$this->jobType);
        }
        $this->assign('deal_loan_type',$dealLoanType);
        $this->assign('data', $data);
        $this->display();
    }
    /*编辑*/
    public function doEdit() {
        if(!empty($_FILES['job_file']["tmp_name"])){
            $data['job_ids'] = $this->process($_FILES["job_file"]);
        }
        $data['id'] = $_POST['id'];
        $data['job_name'] = $_POST['job_name'];
        $data['repay_mode'] = $_POST['repay_mode'];
        $data['job_type'] = $_POST['job_type'];
        if(!isset($this->jobType[$data['job_type']])) {
            $this->error('业务类型不正确');
        }
        if($data['job_type'] == 3){
            if(empty($_POST['type_id']) && $_POST['type_id'] == ''){
                $this->error("未指定产品类别!");
            }
            if(empty($_POST['full_status_time']) && $_POST['full_status_time'] == ''){
                $this->error("未指定满标截止时间!");
            }
            $data['deal_type'] = $_POST['type_id'];
            $data['full_status_time'] = $_POST['full_status_time'];
        }
        $data['is_right_now'] =  $_POST['is_right_now'];
        if((empty($_POST['job_run_time']) ||  $_POST['job_run_time'] == '') && $data['is_right_now'] != 1){
            $this->error("执行时间不能为空!");
        }

        if($data['is_right_now'] == 1){
            $_POST['job_run_time'] = '';
        }
        if(!empty( $data['job_ids']) && $data['is_right_now'] != 1){
            $this->error("导入标的必须立即执行!");
        }
        if(!empty( $data['job_ids']) && $data['type_id'] != 0){
            $this->error("导入标的不能指定产品类别!");
        }
        $data['deal_type'] = $_POST['type_id'];
        $data['job_interval_start'] = strtotime($_POST['job_interval_start']);
        $data['job_interval_end'] = strtotime($_POST['job_interval_end']);
        if($data['job_interval_end'] <= $data['job_interval_start']) {
            $this->error('有效期结束时间不能小于开始时间');
        }
        $data['job_run_time'] = $_POST['job_run_time'];
        $data['job_status'] = $_POST['job_status'];
        $data['update_time'] = time();
        $data['next_repay_time'] = !empty($_POST['next_repay_time']) ? strtotime($_POST['next_repay_time']) : 0;
        $result = M(MODULE_NAME)->save($data);
        if(!$result){
            $dbErr = M()->getDbError();
            save_log($data['job_name'].L("INSERT_FAILED").$dbErr,0);
            $this->error(L("UPDATE_FAILED").$dbErr);
        }
        if( $data['is_right_now'] == 1 &&  $data['job_status'] ==1 && $data['job_interval_start'] <= time()  && $data['job_interval_end'] >= time() && $data['job_type'] ==1 ) {//立即执行
            $ret= $this->add_bath_to_Job( $data['id']);
            if(!$ret){
                $this->error('立即执行错误');
            }
        }
        $this->success(L("UPDATE_SUCCESS"));
    }
    /**
     * 删除批任务
     */
    public function delete() {
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $ids = explode(',', $id);
        if(empty($ids)) {
            $this->error ('参数错误',$ajax);
        }
        $condition = implode(',',$ids);
        $res = M(MODULE_NAME)->delete($condition);
        if($res) {
            save_log($ids.l("DELETE_SUCCESS"),1);
            $this->success (l("DELETE_SUCCESS"),$ajax);
        }
    }
    /**
     * 导入DealIds
     */
    public function process($file_data) {
        $this->tag_item = array($this->tags_name_approval, $this->tags_name_settlement);
        $file_name_suffix = strrpos($file_data['name'], '.');
        if(($suffix = substr($file_data['name'],$file_name_suffix+1) !== 'csv')){
            $this->error('上传的文件不是csv格式');
        }
        $row = 1;
        $handle = fopen($file_data['tmp_name'],'r');
        $fileline = file($file_data['tmp_name']);
        $file_line_num = count($fileline);
        if($file_line_num > 2001){
            $this->error('上传的数据不能超过2000行');
        }
        $k = 0;
        while(($res = fgetcsv( $handle)) !== FALSE){
            for($i=0; $i<4; $i++){
                $csv_list[$k][$i] = ($i !== 0) ? (string)trim(mb_convert_encoding($res[$i], 'utf-8', 'gbk')) : $res[$i];
            }
            $k++;
        }
        unset($csv_list[0]);
        /*数据校验*/
        $data_list = $tags_list = array();
        foreach($csv_list as $csv_item){
            $deal_id     = $csv_item[0];
            (empty($deal_id)) && $this->error('导入文件存在空行');
            (!is_numeric($deal_id)) && $this->error('Deal ID不能为非阿拉伯数字');
            $data_list[] = $deal_id;
        }
        $ids = implode(',', $data_list);
        return $ids;
    }
    /**
     * 展示时间戳任务详情
     */
    public function showDetailTs() {
        $type = $this->jobType[2];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();

        $list = $redis->hKeys('tsa_deal_'.date('Y-m-d'));
        foreach($list as $id){
            $deals[$id] = $id;
        }

        $this->assign("list", $deals);
        $this->display();
    }
    /*生成csv文件*/
    function exportCsv($title_arr = array(), $file_name = 'demo.csv', $header_data = array())
    {
        if(count($title_arr)){
            $nums = count($title_arr);
            for($i=0; $i<$nums-1; ++$i) {
                $csv_data .= '"' . $title_arr[$i] . '",';
            }
        }

        ($nums>0) && $csv_data .= '"' . $title_arr[$nums - 1] ."\"\r\n";

        if(count($header_data)){
            $nums = count($header_data);
            foreach ($header_data as $k => $row) {
                for ($i = 0; $i < 3; ++$i) {
                    $row[$i] = str_replace("\"", "\"\"", $row[$i]);
                    $csv_data .= '"' . $row[$i] . '",';
                }
                $csv_data .= '"' . $row[3] . "\"\r\n";
                unset($data[$k]);
            }
        }

        if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")){
            $file_name = urlencode($file_name);
            $file_name = str_replace('+', '%20', $file_name);
        }
        $csv_data = mb_convert_encoding($csv_data, 'cp936', 'UTF-8');
        $file_name = $file_name;
        header('Content-type:text/csv;');
        header('Content-Disposition:attachment;filename=' . $file_name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $csv_data;
    }
    /*输出模板csv文件*/
    public function demoCsv()
    {
        $file_name = 'demo.csv';
        $fields = array('0' => '标的ID');
        $this->exportCsv($fields, $file_name) ;
    }
    /**
     * 立即执行还款
     */
    public function add_bath_to_Job($bath_id) {
        $admInfo = array(
            'adm_name' => 'system',
            'adm_id' => 0,
        );
        $data = M('BatchJob')->where("id=".$bath_id)->find();
        if(empty($data)){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"没找到该还款任务","bath_id:".$bath_id)));
            return false;
        }
        if($data['next_repay_time']) {
            $startTime = 0;
            $endTime  =  to_timespan(date('Y-m-d',$data['next_repay_time']));
        }else{
            $startTime = to_timespan(date("Y-m-d") . "00:00:00");
            $endTime  =  to_timespan(date("Y-m-d") . " 23:59:59");
        }
        try{
            $GLOBALS['db']->startTrans();
            $param = array( 'bath_id' => $bath_id,'startTime' => $startTime, 'endTime' => $endTime, 'deal_type' => $data['deal_type'],'job_ids' => $data['job_ids'],'repay_mode'=>$data['repay_mode'], 'admInfo' => $admInfo);
            $job_model = new JobsModel();
            // 异步处理还款
            $function = '\core\service\BatchJobService::addBathToJob';
            $job_model->priority = JobsModel::PRIORITY_ADD_BATH_RIGHT;
            $res = $job_model->addJob($function, $param);
            if ($res === false) {
                throw new \Exception("加入jobs失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"bath_id:".$bath_id)));
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"成功插入jobs并更改了还款状态","bath_id:".$bath_id)));
        return true;

    }

}
