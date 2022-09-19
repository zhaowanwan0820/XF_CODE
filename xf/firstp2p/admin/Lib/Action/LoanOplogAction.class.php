<?php
// +----------------------------------------------------------------------
// | easethink 易想商城系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
FP::import("libs.libs.msgcenter");
FP::import("app.deal");
vendor("phpexcel.PHPExcel");
use core\service\user\BOFactory;
use core\service\DealService;
use core\dao\DealExtModel;
use core\dao\UserCarryModel;
use core\dao\LoanOplogModel;

class LoanOplogAction extends CommonAction{

    public static $opTypes = array('0' => '放款', '1' => '提交', '2' => '退回', '3' => '自动放款');
    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型
    //放款操作记录列表
    public function index()
    {
        if (isset($_REQUEST['ids'])) {
            if ($_REQUEST['ids'] <> '') {
                $ids = explode(',', $_REQUEST['ids']);
                if (is_array($ids)) {
                    $loan_oplog = new LoanOplogModel();

                    //放款批次号生成
                    $date = date('ymdHi');
                    $incrementId = \SiteApp::init()->dataCache->getRedisInstance()->incr('LOAN_OP_LOG_NO_'.$date);
                    $batchNo = 'FK'.$date.sprintf('%02d', $incrementId);

                    $batch_create_time = get_gmtime();
                    foreach ($ids as $v) {
                        $update_ids[] = intval($v);
                    }

                    $update_ids_str = implode(',',$update_ids);

                    $ret = $loan_oplog->updateBy(array('loan_batch_no' => $batchNo,'batch_create_time' => $batch_create_time),"id in (".$update_ids_str.") AND loan_batch_no = ''");

                    if(!$ret){
                        $this->error('更新失败');
                    }
                }
            } else {
                $this->error('未选择放款操作记录');
            }
        }

        $map = $this->_getOplogMap($_REQUEST);

        //默认显示100条
        if (empty($_REQUEST['listRows'])) {
            $_REQUEST['listRows'] = 100;
        }

        $name=$this->getActionName();
        $model = DI ($name);
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
        }

        if (!isset($_REQUEST['op_type'])) {
            $_REQUEST['op_type'] = '9999';
        }
        $this->assign('op_type_list', self::$opTypes);
        $this->assign('return_type_list', self::$returnTypes);
        $loan_types = UserCarryModel::$loantypeDesc;
        $this->assign('loan_types', $loan_types);
        $template = $this->is_cn ? 'index_cn':'index';
        $this->display($template);
        return;
    }

    public function form_index_list(&$list)
    {
        $userIds = array();
        $dealIds = array();
        foreach ($list as $key => $item) {
            $userIds[] = $item['borrow_user_id'];
            $dealIds[] = $item['deal_id'];
        }

        $result = M('User')->where('id IN ('.implode(',', $userIds).')')->findAll();
        $userInfo = array();
        foreach ($result as $item) {
            $userInfo[$item['id']] = $item;
        }

        $result = M('UserCarry')->where('deal_id IN ('.implode(',', $dealIds).')')->findAll();
        $userCarryInfo = array();
        foreach ($result as $item) {
            $userCarryInfo[$item['deal_id']] = $item;
        }

        $result = M('Deal')->where('id IN ('.implode(',', $dealIds).')')->findAll();
        $projectIds = array();
        foreach ($result as $item) {
            $projectIds[$item['id']] = $item['project_id'];
        }
        $result = M('DealProject')->where('id IN ('.implode(',', $projectIds).')')->findAll();
        $projectInfo = array();
        foreach ($result as $item) {
            $projectInfo[$item['id']] = $item;
        }

        foreach ($list as $key => $item) {
            $list[$key]['loan_money_type_name'] = $GLOBALS['dict']['LOAN_MONEY_TYPE'][$item['loan_money_type']];
            $list[$key]['user_name'] = $userInfo[$item['borrow_user_id']]['user_name'];
            $list[$key]['real_name'] = $userInfo[$item['borrow_user_id']]['real_name'];
            $list[$key]['user_carry_id'] = $userCarryInfo[$item['deal_id']]['id'];
            $list[$key]['user_carry_real_name'] = $item['loan_money_type'] == 3 ? $projectInfo[$projectIds[$item['deal_id']]]['card_name'] : $list[$key]['real_name'];
            $list[$key]['return_type'] = self::$returnTypes[$item['return_type']];
            $list[$key]['op_type'] = self::$opTypes[intval($item['op_type'])];
            $list[$key]['ext_loan_type'] = DealExtModel::instance()->getDealExtLoanType($item['deal_id']);
            $list[$key]['submit_user_name'] = $item['submit_uid'] >= 0 ? get_admin_name($item['submit_uid']) : '';
        }
    }

    /**
     * 导出订单 csv
     *
     * @Title: export
     * @Description: todo(这里用一句话描述这个方法的作用)
     * @param
     * @return return_type
     * @author Steven
     * @throws
     *
     */
    public function export_csv()
    {
        if($_REQUEST['id'] <> ''){
            $ids = explode(',',$_REQUEST['id']);
        }

        $map = $this->_getOplogMap($_REQUEST);

        $name=$this->getActionName();
        $model = DI ($name);
        if (! empty ( $model )) {
            $list = $this->_list ( $model, $map );
        }

        $content = iconv("utf-8","gbk","序号,放款批次号,标ID,项目名称,借款标题,借款金额,借款期限,放款方式,还款方式,借款人用户名,借款人姓名,放款金额,操作人员,操作时间");
        $content = $content . "\n";

        $oplog_value = array(
            'id'=>'""',
            'loan_batch_no'=>'""',
            'deal_id' =>'""',
            'project_name'=>'""',
            'deal_name'=>'""',
            'borrow_amount'=>'""',
            'repay_time'=>'""',
            'loan_money_type_name'=>'""',
            'loan_type'=>'""',
            'user_name'=>'""',
            'real_name'=>'""',
            'loan_money'=>'""',
            'admin_name'=>'""',
            'op_time'=>'""'
        );

        foreach($list as $k=>$v)
        {
            $oplog_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
            $oplog_value['loan_batch_no'] = '"' . iconv('utf-8','gbk',$v['loan_batch_no']) . '"';
            $oplog_value['deal_id'] = '"' . iconv('utf-8','gbk',$v['deal_id']) . '"';
            $project_info = DealService::getProjectInfoByDealId($v['deal_id']);
            $oplog_value['project_name'] = '"' . iconv('utf-8','gbk',$project_info['name']) . '"';
            $oplog_value['deal_name'] = '"' . iconv('utf-8','gbk',$v['deal_name']) . '"';
            $oplog_value['borrow_amount'] = '"' . iconv('utf-8','gbk',$v['borrow_amount']) . '"';


            if ($v['loan_type'] == 5) {
                $deal_repay_time = $v['repay_time'] . "天";
            } else {
                $deal_repay_time = $v['repay_time'] . "个月";
            }
            $oplog_value['repay_time'] = '"' . iconv('utf-8','gbk',$deal_repay_time) . '"';
            $deal_loantype = $this->is_cn ? $GLOBALS['dict']['LOAN_TYPE_CN'][$v['loan_type']] : $GLOBALS['dict']['LOAN_TYPE'][$v['loan_type']];
            $oplog_value['loan_money_type_name'] = '"' . iconv('utf-8','gbk',$v['loan_money_type_name']) . '"';
            $oplog_value['loan_type'] = '"' . iconv('utf-8','gbk',$deal_loantype) . '"';
            $oplog_value['user_name'] = '"' . iconv('utf-8','gbk',$v['user_name']) . '"';
            $oplog_value['real_name'] = '"' . iconv('utf-8','gbk',$v['real_name']) . '"';

            $oplog_value['loan_money'] = '"' . iconv('utf-8','gbk',$v['loan_money']) . '"';
            $adm_name = get_admin_name($v['op_user_id']);
            $oplog_value['admin_name'] = '"' . iconv('utf-8','gbk',$adm_name) . '"';
            $oplog_value['op_time'] = '"' . iconv('utf-8','gbk',date("Y-m-d H:i:s",$v['op_time']+8*3600)) . '"';

            if(is_array($ids) && count($ids) > 0){
                if(array_search($v['deal_id'],$ids) !== false){
                    $content .= implode(",", $oplog_value) . "\n";
                }
            }else{
                $content .= implode(",", $oplog_value) . "\n";
            }
        }

        $datatime = date("YmdHis",get_gmtime());
        header("Content-Disposition: attachment; filename={$datatime}_loan_op_log.csv");
        echo $content;
        return;

    }

    public function print_batch(){
        if(isset($_REQUEST['batch_no']) && ($_REQUEST['batch_no'] <> '')){
            $loan_oplog = new LoanOplogModel();
            $result = $loan_oplog->findAllViaSlave('loan_batch_no=":loan_batch_no"',true, '*', array(':loan_batch_no' => $_REQUEST['batch_no']));
            if(count($result) > 0){
                $batch_create_time = $result[0]['batch_create_time'];
                $loan_money_total = 0;
                foreach($result as $k=>$v){
                    $loan_money_total += $v['loan_money'];
                    $result[$k]['loan_money'] = $v['loan_money'];
                }
            }

            $this->form_index_list($result);

            $this->assign('loan_money_total',$loan_money_total);
            $this->assign('loan_batch_no',$_REQUEST['batch_no']);
            $this->assign('batch_create_time',$batch_create_time);
            $this->assign('result', $result);
            $this->display('print_batch');
        }
        return;
    }

    private function _getOplogMap($request){
        if(trim($request['loan_batch_no']) != ''){
            $map['loan_batch_no'] = array('like','%'.$request['loan_batch_no'].'%');
        }

        if(intval($request['id'])>0){
            $map['deal_id'] = intval($request['id']);
        }

        if(trim($request['deal_name'])!=''){
            $deal_name = addslashes(trim($request['deal_name']));
            $map['deal_name'] = array('like','%'.$deal_name.'%');
        }


        /*if(trim($request['loan_money_type'])!=''){
            $map['loan_money_type'] = array('eq', intval($request['loan_money_type']));
        }*/

        if ($this->is_cn) {
            $request['loan_money_type'] = empty($request['loan_money_type']) ? 1 : intval($request['loan_money_type']);
        }
        if(trim($request['loan_money_type'])!='') {
            $map['loan_money_type'] = array('eq', intval($request['loan_money_type']));
        }

        if(trim($request['admin_name'])!=''){
            $adm_name = get_admin_name($v['op_user_id']);
            $adminId = M('Admin')->where('adm_name="'.addslashes($request['admin_name']).'"')->getField('id');
            $map['op_user_id'] = array('eq', $adminId);
        }

        if(trim($request['user_name'])!='') {
            $sql  ="SELECT group_concat(id) FROM firstp2p_user WHERE user_name='".addslashes($request['user_name'])."'";
            $ids = $GLOBALS['db']->getOne($sql);
            $map['borrow_user_id'] = array("in",$ids);
        }

        if ($request['op_time_start']>0 && $request['op_time_end']>0) {
            $map['op_time'] = array("between", to_timespan($request['op_time_start']) . "," . to_timespan($request['op_time_end']));
        } elseif ($request['op_time_start']>0){
            $map['op_time'] = array("gt", to_timespan($request['op_time_start']));
        } elseif ($request['op_time_end']>0){
            $map['op_time'] = array("lt", to_timespan($request['op_time_end']));
        }

        if ($request['op_type'] != '9999' && isset($request['op_type'])) {
            $map['op_type'] = array('eq', $request['op_type']);
        }

        if ($request['return_type']) {
            $map['return_type'] = array('eq', $request['return_type']);
        }

        //机构管理后台
        $orgSql = $this->orgCondition(false);

        if (!empty($request['project_name'])) {
           if (!$this->is_cn) {
               $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE  `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` LIKE \'%' . trim($request['project_name']) .'%\')'.$orgSql.')';
           } else {
              $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0 AND `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` LIKE \'%' . trim($request['project_name']) .'%\')'.$orgSql.')';
           }
       } else {
           if ($this->is_cn) {
               $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `deal_type` = 0) ';
           } elseif($orgSql) {
               $map['_string'] = ' `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE 1 '.$orgSql.')';
           }
       }
       return $map;
    }
}

