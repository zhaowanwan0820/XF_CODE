<?php

FP::import("libs.libs.msgcenter");
FP::import("app.deal");

use core\dao\DealAgencyModel;
use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\UserModel;

use core\service\DealProjectService;
use core\service\DealService;

class DealProjectLoanAction extends CommonAction
{

    // 业务审核状态
    public static $auditStatus = array(
        '0'    => '放款待处理',
        '1'    => '放款待审核',
        '3'    => '放款已退回',
    );

    /**
     * 获取项目待放列表
     */
    public function index()
    {
        // 获取项目列表
        $project_list = $this->getListAndAssignCommon();
        $this->assign('list', $project_list);

        //最后访问的列表地址存起来，以便放款操作完返回
        $_SESSION['lastDealProjectLoanUrl'] = $_SERVER['REQUEST_URI'];

        $this->display();
    }

    /**
     * 为了共用方便，此方法完成两件事 1：生成列表信息；2：向模板传送变量
     * @return array $project_list 经过处理的项目列表
     */
    private function getListAndAssignCommon()
    {
        // a/b 审核
        $role = $this->getRole();
        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => ServiceAuditModel::SERVICE_TYPE_PROJECT_LOAN, 'status' =>
            array('in', implode(',', array(ServiceAuditModel::NOT_AUDIT, ServiceAuditModel::AUDIT_FAIL))));
        if ($role == 'b') {// B角审核筛选申请人
            $conds['status'] = ServiceAuditModel::NOT_AUDIT;
            if ($_REQUEST['admin_name'] != '') {
                $adminId = M('Admin')->where('adm_name="'.addslashes($_REQUEST['admin_name']).'"')->getField('id');
                $conds['submit_uid'] = $adminId;
            }
        } else {
            if ($_REQUEST['audit_status'] > 0 && $_REQUEST['audit_status'] != '9999') {
                $conds['status'] = intval($_REQUEST['audit_status']);
            }
        }
        $audit_project_list = D('ServiceAudit')->where($conds)->field('service_id,status,submit_uid,mark,update_time')->select();
        $service_ids = array();
        foreach ($audit_project_list as $item) {
            $admin_name = '';
            if ($item['submit_uid'] > 0) {
                $admin_name = get_admin_name($item['submit_uid']);
            }
            $service_ids[$item['service_id']] = array('submit_user_name' => $admin_name, 'status' => self::$auditStatus[$item['status']], 'mark' => $item['mark'], 'update_time' => $item['update_time']);
        }
        if (empty($service_ids)) {
            $service_ids[] = 0;
        }
        if ($role == 'b' && !isset($_GET['audit_status'])) { //审核角色展现待审核列表
            $map['id'] = array('in', implode(',', array_keys($service_ids)));
        } else {
            if (isset($_REQUEST['audit_status'])) {
                    if ($_REQUEST['audit_status'] == 0) {
                        $map['id'] = array('not in', implode(',', array_keys($service_ids)));
                    }
                    if ($_REQUEST['audit_status'] == 1 || $_REQUEST['audit_status'] == 3) {
                        $map['id'] = array('in', implode(',', array_keys($service_ids)));
                    }
            }
        }

        if (!isset($_REQUEST['audit_status'])) {
            $_REQUEST['audit_status'] = '9999';
        }
        $this->assign('audit_status_list', self::$auditStatus);
        $this->assign('role', $role);
        // 获取项目列表
        $map['fixed_value_date'] = array('gt', 0);
        $map['business_status'] = DealProjectModel::$PROJECT_BUSINESS_STATUS['transfer_loans_audit'];

        if (!empty($_REQUEST['project_id'])) {
            $map['id'] = intval($_REQUEST['project_id']);
        }

        if (!empty($_REQUEST['project_name'])) {
            $map['name'] = array('like', trim(addslashes($_REQUEST['project_name'])));
        }

        // 借款人搜索
        if(trim($_REQUEST['real_name'])!=''){
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where real_name like '%". trim(addslashes($_REQUEST['real_name'])) ."%'";
            $ids1 = $GLOBALS['db']->getOne($sql);
        }

        if(trim($_REQUEST['user_name']) != '') {
            $sql  ="select group_concat(id) from ".DB_PREFIX."user where user_name = '". trim(addslashes($_REQUEST['user_name'])) . "'";
            $ids2 = $GLOBALS['db']->getOne($sql);
        }

        if($ids1 && $ids2) {
            $map['user_id'] = array("in", $ids1 . "," . $ids2);
        } else if($ids1) {
            $map['user_id'] = array("in", $ids1);
        } else if($ids2) {
            $map['user_id'] = array("in", $ids2);
        }

        // 固定起息日
        if (!empty($_REQUEST['fixed_value_date_start']) && !empty($_REQUEST['fixed_value_date_end'])) {
            $map['fixed_value_date'] = array('BETWEEN', array(to_timespan($_REQUEST['fixed_value_date_start']), to_timespan($_REQUEST['fixed_value_date_end'])));
        } else if (!empty($_REQUEST['fixed_value_date_start'])) {
            $map['fixed_value_date'] = array('GT', to_timespan($_REQUEST['fixed_value_date_start']));
        } else if (!empty($_REQUEST['fixed_value_date_end'])) {
            $map['fixed_value_date'] = array('LT', to_timespan($_REQUEST['fixed_value_date_end']));
        }


        $model = DI ('DealProject');
        if (!empty($model)) {
            $list = $this->_list($model, $map);
        }

        // 处理项目信息
        return $this->handleProjectInfo($list, $service_ids);
    }

    /**
     * 收集项目相关的延伸信息
     * @param array $project_list
     * @param array $service_list key 为项目id
     * @return array 收集填充过的项目列表
     */
    private function handleProjectInfo($project_list, $service_list)
    {
        foreach ($project_list as $key => $project) {
            $deal = DealProjectModel::instance()->getFirstDealByProjectId($project['id']);
            $project['loantype'] = $deal['loantype'];
            $project['user_info'] = UserModel::instance()->findViaSlave($project['user_id']);
            $project['user_info']['user_name_url'] = (1 == $project['user_info']['user_type']) ? getUserFieldUrl($project['user_info'], 'company_name') : getUserFieldUrl($project['user_info'], 'real_name');
            $project['user_info']['user_mobile_url'] = getUserFieldUrl($project['user_info'], 'mobile');
            $project['success_time'] = DealProjectModel::instance()->getProjectSuccessTime($project['id']);
            $project['formated_success_time'] = empty($project['success_time']) ? '--' : to_date($project['success_time'], 'Y-m-d');

            // 标的信息
            $project['deal'] = $deal;
            $project['deal']['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
            $project['deal']['repay_period'] = $deal['repay_time'] . ($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY'] == $deal['loantype'] ? '天' : '月');
            $project['deal']['loan_fee_type'] = get_deal_ext_fee_type($deal['id']);
            $deal_agency_list = DealAgencyModel::instance()->getDealAgencyList(DealAgencyModel::TYPE_GUARANTEE);
            $project['deal']['agency_name'] = $deal_agency_list[$deal['agency_id']]['short_name'];

            // 审核信息
            $project['audit']['submit_user_name'] = isset($service_list[$project['id']]) ? $service_list[$project['id']]['submit_user_name'] : ''; // 申请人
            $project['audit']['mark'] = isset($service_list[$project['id']]) ? $service_list[$project['id']]['mark'] : ''; // 退回原因
            $project['audit']['audit_status'] = isset($service_list[$project['id']]) ? $service_list[$project['id']]['status'] : '放款待处理'; // 审核状态
            $project['audit']['update_time'] = isset($service_list[$project['id']]) ? $service_list[$project['id']]['update_time'] : ''; // 更新时间

            $project_list[$key] = $project;
        }

        return $project_list;
    }

    /**
     * 取消项目放款 流标
     */
    public function cancelLoan()
    {
        try {
            $project_id = intval($_REQUEST['project_id']);
            if (empty($project_id)) {
                throw new \Exception('项目信息错误', 1);
            }

            if ('b' != $this->getRole()) {
                throw new \Exception('审核角色信息错误', 2);
            }

            $pro_service = new DealProjectService();
            if (!$pro_service->failProject($project_id)) {
                throw new \Exception('项目流标任务添加失败', 3);
            }

            $result['status'] = 4;
            $result['error_msg'] = '项目流标任务添加成功';
        } catch (\Exception $e) {
            $result['error_msg'] = $e->getMessage();
        }

        return ajax_return($result);
    }

    /**
     * 导出 index csv
     */
    public function export_csv()
    {
        if($_REQUEST['id'] <> ''){
            $ids = explode(',',$_REQUEST['id']);
        }

        // 填充 查询条件
        $role = $this->getRole();
        $project_list = $this->getListAndAssignCommon();

        $content = iconv("utf-8","gbk","编号,项目名称,借款金额,借款期限,还款方式,项目满标时间,费用收取方式,放款方式,用户类型,借款人姓名,借款人用户名,担保公司名称,转让合同签署状态,处理状态,状态描述,完成时间");
        $content = $content . "\n";

        $order_value = array(
            'id'=>'""',
            'name'=>'""',
            'borrow_amount'=>'""',
            'repay_period' =>'""',
            'loantype'=>'""',
            'formated_success_time'=>'""',
            'loan_fee_type'=>'""',
            'loan_money_type'=>'""',
            'user_type_name' => '""',
            'user_real_name'=>'""',
            'user_user_name' => '""',
            'agency_name'=>'""',
            'dealing_status'=>'""',
            'status_desc'=>'""',
            'complete_time'=>'""',
        );

        foreach($project_list as $k=>$v)
        {
            $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
            $order_value['name'] = '"' . iconv('utf-8','gbk',$v['name']) . '"';
            $order_value['borrow_amount'] = '"' . iconv('utf-8','gbk',format_price($v['borrow_amount'], false)) . '"';
            $order_value['repay_period'] = '"' . iconv('utf-8','gbk',$v['deal']['repay_period']) . '"';
            $order_value['loantype'] = '"' . iconv('utf-8','gbk',get_loantype($v['loantype'])) . '"';
            $order_value['formated_success_time'] = '"' . iconv('utf-8','gbk',$v['formated_success_time']) . '"';
            $order_value['loan_fee_type'] = '"' . iconv('utf-8','gbk',$v['deal']['loan_fee_type']) . '"';
            $order_value['loan_money_type'] = '"' . iconv('utf-8','gbk',get_loan_money_type($v['loan_money_type'])) . '"';
            $order_value['user_type_name'] = '"' . iconv('utf-8','gbk',getUserTypeName($v['user_id'])) . '"';
            $order_value['user_real_name'] = '"' . iconv('utf-8','gbk',$v['user_info']['real_name']) . '"';
            $order_value['user_user_name'] = '"' . iconv('utf-8','gbk',$v['user_info']['user_name']) . '"';
            $order_value['agency_name'] = '"' . iconv('utf-8','gbk',$v['deal']['agency_name']) . '"';
            $order_value['dealing_status'] = 'b' == $role ? '"' . iconv('utf-8','gbk','放款待审核') . '"' : '"' . iconv('utf-8','gbk',$v['audit']['audit_status']) . '"';
            $order_value['status_desc'] = '"' . iconv('utf-8','gbk',$v['audit']['mark']) . '"';
            $order_value['complete_time'] = '"' . iconv('utf-8','gbk',to_date($v['audit']['update_time'])) . '"';

            if(is_array($ids) && count($ids) > 0){
                if(array_search($v['id'],$ids) !== false){
                    $content .= implode(",", $order_value) . "\n";
                }
            }else{
                $content .= implode(",", $order_value) . "\n";
            }
        }

        $datatime = date("YmdHis",get_gmtime());
        header("Content-Disposition: attachment; filename={$datatime}_deal_loan_list.csv");
        echo $content;
        return;
    }
}
