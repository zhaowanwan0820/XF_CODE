<?php
/**
 * 项目还款相关
 */

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\UserModel;
use core\dao\DealExtModel;
use core\dao\DealRepayModel;
use core\dao\DealAgencyModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealPrepayModel;
use core\dao\JobsModel;
use core\dao\ProjectRepayListModel;

use core\service\DealService;
use core\service\DealPrepayService;
use core\service\DealRepayService;
use core\service\DealProjectPrepayService;
use core\service\DealProjectRepayYjService;

use libs\utils\Logger;
use libs\utils\Finance;

class DealProjectRepayAction extends CommonAction
{

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型

    // 还款业务审核状态
    public static $audit_status_map = array(
        1 => '还款待审核',
        2 => '还款已通过',
        3 => '还款已退回',
        4 => '还款待处理',
    );
    public static $audit_service_type_map = array(
        7  => '正常还款',
        8  => '提前还款',
    );

    public function index()
    {
        unset($_REQUEST['m'], $_REQUEST['a']);
        $this->assign("main_title",L("DEAL_YUQI"));
        $this->assign("role", $this->getRole());
        // 记录查询参数并复原
        if (!empty($_REQUEST['ref'])) {
            $_REQUEST = \es_session::get('seKeyDealProjectRepay');
            // 记录分页参数
            if (isset($_GET['p'])) {
                $_REQUEST['p'] = (int)$_GET['p'];
                \es_session::set('seKeyDealProjectRepay', $_REQUEST);
            }else{
                $_GET['p'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
            }
        } else if (isset($_REQUEST['repay_time_begin'])) {
            \es_session::set('seKeyDealProjectRepay', $_REQUEST);
        } else if (isset($_REQUEST['repay_time_end'])) {
            \es_session::set('seKeyDealProjectRepay', $_REQUEST);
        }else{
            \es_session::delete('seKeyDealProjectRepay');
        }

        // 获取项目列表
        $project_repay_list = $this->getListAndAssignCommon();
        $this->assign('list', $project_repay_list);

        \es_session::set('project_repay_index_page_query', http_build_query($_GET));
        $this->assign('querystring', http_build_query($_GET));
        $this->display();
    }

    /**
     * 为了共用方便，此方法完成两件事 1：生成列表信息；2：向模板传送变量
     * @return array $project_list 经过处理的项目列表
     */
    private function getListAndAssignCommon()
    {
        if (empty($_REQUEST['business_status']) || '999' == $_REQUEST['business_status']) {
            // 默认查询条件
            $_string = sprintf('`project_id` IN (SELECT `id` FROM %s WHERE `business_status` IN (%d, %d))', DealProjectModel::instance()->tableName(), DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying'], DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay']);
        } else {
            $_string = sprintf('`project_id` IN (SELECT `id` FROM %s WHERE `business_status` = %d)', DealProjectModel::instance()->tableName(), intval($_REQUEST['business_status']));
        }
        $map['_string'] = $_string;

        if(!empty($_REQUEST['id'])) {
            $map['id'][] = intval($_REQUEST['id']);
        }

        if(!empty($_REQUEST['project_id'])) {
            $map['project_id'][] = intval($_REQUEST['project_id']);
        }

        if(!empty($_REQUEST['project_name'])) {
            $pro_id_arr = DealProjectModel::instance()->getProjectIdsByName(addslashes(trim($_REQUEST['project_name'])));
            if (!empty($pro_id_arr)) {
                $map['project_id'][]  = array('exp'," IN (".implode(",",$pro_id_arr).") ");
            }
        }

        //去掉盈嘉线下还款的项目
        $repayYjService = new DealProjectRepayYjService();
        $yjProjectIds = $repayYjService->getYjProjectIds();
        if (!empty($yjProjectIds)) {
            $map['project_id'][]  = array('exp'," NOT IN (".implode(",",$yjProjectIds).") ");
        }

        if(!empty($_REQUEST['user_name'])) {
            $user_info = UserModel::instance()->getUserinfoByUsername(addslashes(trim($_REQUEST['user_name'])));
            $user_id = empty($user_info) ? 0 : $user_info['id'];
            $map['user_id'] = $user_id;
        }
        if($_REQUEST['repay_time_begin']) {
            $requestRepayTimeBegin = to_timespan($_REQUEST['repay_time_begin'] . " 0:0:0");
            $map['repay_time'][] = array('EGT', $requestRepayTimeBegin);
        }

        if($_REQUEST['repay_time_end']) {
            $requestRepayTimeEnd = to_timespan($_REQUEST['repay_time_end'] . " 23:59:59");
            $map['repay_time'][] = array('ELT', $requestRepayTimeEnd);
        }
        $map['status'] = array('EQ', DealRepayModel::STATUS_WAITING);

        // 审核信息过滤
        if (4 == $_REQUEST['audit_status']) { // 待处理
            $audit_status = 0; // 搜索出所有的
            $audit_status_cond = 'NOT IN';
        } elseif ('b' === $this->getRole() || !empty($_REQUEST['audit_status'])) {
            $audit_status = intval($_REQUEST['audit_status']); // 搜索出所有的
            $audit_status_cond = 'IN';
        }
        $audit_info = $this->getAuditInfo($audit_status);
        if (isset($audit_status_cond)) {
            $map['id'][]  = array('exp'," {$audit_status_cond} (".implode(",",array_keys($audit_info)).") ");
        }
        $project_repay_list = $this->_list(DI('ProjectRepayList'), $map, 'repay_time');
        return $this->handleProjectInfo($project_repay_list, $audit_info);
    }

    /**
     * 处理项目信息
     * @param array $project_repay_list
     * @param array $audit_info
     * @return array $project_repay_list [... 'project_info', 'user_info', 'audit_info']
     */
    private function handleProjectInfo($project_repay_list, $audit_info)
    {
        $deal_agency = MI('DealAgency')->where('is_effect = 1 and type=2')->getField('id,name');
        $project_info_arr = array();
        foreach ($project_repay_list as $key => $one_project_repay) {
            $project_id = $one_project_repay['project_id'];
            if (empty($project_info_arr[$project_id])) {
                // 基本信息
                $project_info_arr[$project_id] = $project = DealProjectModel::instance()->findViaSlave($project_id);
                $project_info_arr[$project_id]['is_repay'] = (DealProjectModel::$PROJECT_BUSINESS_STATUS['repaid'] == $project['business_status']);
                $project_info_arr[$project_id]['first_deal'] = $deal = DealProjectModel::instance()->getFirstDealByProjectId($project_id);
                $project_info_arr[$project_id]['first_deal_ext'] = $deal_ext = DealExtModel::instance()->getDealExtByDealId($project_info_arr[$project_id]['first_deal']['id']);
                $project_info_arr[$project_id]['repay_period'] = $deal['repay_time'] . ($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY'] == $deal['loantype'] ? '天' : '月');
                $project_info_arr[$project_id]['repay_start_time'] = to_date($deal['repay_start_time'], 'Y-m-d');
                $project_info_arr[$project_id]['fee_rate_type_name'] = get_deal_ext_fee_type($deal['id']);
                $project_info_arr[$project_id]['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
                $project_info_arr[$project_id]['agency'] = ($deal['advisory_id'] && $deal_agency[$deal['advisory_id']]) ? $deal_agency[$deal['advisory_id']] : '-'; // 资产管理方

                // 中文名映射
                $name_map['loan_fee_rate_type_name'] = DealExtModel::$fee_rate_type_name_map[$deal_ext['loan_fee_rate_type']];
                $name_map['consult_fee_rate_type_name'] = DealExtModel::$fee_rate_type_name_map[$deal_ext['consult_fee_rate_type']];
                $name_map['guarantee_fee_rate_type_name'] = DealExtModel::$fee_rate_type_name_map[$deal_ext['guarantee_fee_rate_type']];
                $name_map['pay_fee_rate_type_name'] = DealExtModel::$fee_rate_type_name_map[$deal_ext['pay_fee_rate_type']];
                $project_info_arr[$project_id]['name_map'] = $name_map;
            }
            $one_project_repay['project_info'] = $project_info_arr[$project_id];

            // 借款人信息
            $user_info = UserModel::instance()->findViaSlave($project_info_arr[$project_id]['user_id']);
            $user_info['insufficient'] = (bccomp($user_info['money'], $one_project_repay['repay_money'], 2) == -1) ? 1 : 0; // 标识余额不足
            $one_project_repay['user_info'] = $user_info;

            // 审核信息
            if (isset($audit_info[$one_project_repay['id']])) {
                $audit = $audit_info[$one_project_repay['id']];
                $audit['status_name'] = self::$audit_status_map[$audit['status']];
                $audit['service_type_name'] = self::$audit_service_type_map[$audit['service_type']];
                $audit['submit_user_name'] = $audit['submit_uid'] ? get_admin_name($audit['submit_uid']) : '';
                $audit['is_prepay'] = (ServiceAuditModel::SERVICE_TYPE_PROJECT_PREPAY == $audit['service_type']) ? true : false; // 标识提前还款
            } else {
                $audit['status'] = 4;
                $audit['status_name'] = self::$audit_status_map[$audit['status']];
                $audit['service_type_name'] = '';
            }
            $one_project_repay['audit_info'] = $audit;
            $one_project_repay['can_op'] = (to_date($one_project_repay['repay_time'], 'Ymd') <= date('Ymd')); // 正常还款是否可以操作

            $project_repay_list[$key] = $one_project_repay;
        }

        return $project_repay_list;
    }

    /**
     * 获取审核信息
     * @param int $audit_status 审核状态 0:代表所有
     * @return array $audit_list key 为 project_repay_list_id
     */
    private function getAuditInfo($audit_status = 0)
    {

        // 根据角色获取[提前]还款项目审核信息
        $cond_type = array(ServiceAuditModel::SERVICE_TYPE_PROJECT_REPAY, ServiceAuditModel::SERVICE_TYPE_PROJECT_PREPAY);
        if ('b' == $this->getRole()) {
            $cond_status = array(ServiceAuditModel::NOT_AUDIT);
        } else {
            $cond_status = empty($audit_status) ? array() : array($audit_status);
        }

        $service_audit_obj = D('ServiceAudit');
        $audit_info = $service_audit_obj->getAuditListByTypeAndStatus($cond_type, $cond_status);

        return empty($audit_info) ? array(0) : $audit_info;
    }

    /**
     * 项目正常还款
     */
    public function repay(){
        $role = $this->getRole();
        $this->assign('role', $role);
        $this->assign('return_type_list', self::$returnTypes);

        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $this->assign('project_repay_id', $project_repay_id);

        // 获取项目及其首标的信息
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        $project['can_repay'] = (false != DealRepayModel::instance()->getProjectDealRepay($project['id']));
        if(empty($project)){
            $this->error("参数错误");
        }
        $this->assign("project", $project);
        $deal = DealProjectModel::getFirstDealByProjectId($project['id']);
        $this->assign("deal", $deal);

        // 项目下标的信息汇总
        $loan_list = $this->collectDealsRepayInfo($project['id']);
        $this->assign('loan_list', $loan_list);
        $this->assign('today', to_date(time(), 'Y-m-d'));

        $this->assignRepayUser($deal, $role);

        // 取出 a 角提交审核时，存在 redis 里的变量值
        if ($role == 'b') {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis) {
                $this->assign('chk_ids', explode(',', $redis->get(sprintf('admin_cache_service_audit_force_repay_project_chk_value_%d', $deal['id']))));
                $this->assign('ignore_impose_money', intval($redis->get(sprintf('admin_cache_service_audit_force_repay_project_ignore_ignore_impose_money_%d', $deal['id']))));
                $this->assign('repay_user_type', $redis->get(sprintf('admin_cache_service_audit_force_repay_project_user_type_%d', $deal['id'])));
            }
        }

        $this->assign('querystring', \es_session::get('project_repay_index_page_query'));
        $this->display();
    }

    private function collectDealsRepayInfo($project_id)
    {
        $deal_list = DealModel::instance()->getDealByProId($project_id,array(4));
        $sum_collection = array(
            "loan_fee" => 0,
            "consult_fee" => 0,
            "guarantee_fee" => 0,
            "pay_fee" => 0,
            "canal_fee" => 0,
        );
        $loan_collection = array();
        foreach ($deal_list as $deal) {
            $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

            // 因为计算过程相同，所以这里用闭包的形式计算各项平台手续费 并与之前值进行累加
            $func_get_fee = function($fee_ext, $fee_rate, $old_fee) use ($deal, $deal_ext) {
                if (empty($fee_ext)) {
                    // 年化收 还是 固定比例收
                    if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND, DealExtModel::FEE_RATE_TYPE_FIXED_PERIOD))) {
                        $fee_rate_period = $fee_rate;
                    } else {
                        $fee_rate_period = Finance::convertToPeriodRate($deal['loantype'], $fee_rate, $deal['repay_time'], false);
                    }
                    $fee = DealModel::instance()->floorfix($deal['borrow_amount'] * $fee_rate_period / 100.0);
                } else {
                    $fee_ext_arr = json_decode($fee_ext, true);
                    $fee = $fee_ext_arr[0];
                }
                return Finance::addition(array($old_fee, $fee));
            };

            $sum_collection['loan_fee'] = call_user_func($func_get_fee, $deal_ext['loan_fee_ext'], $deal['loan_fee_rate'], $sum_collection['loan_fee']);
            $sum_collection['consult_fee'] = call_user_func($func_get_fee, $deal_ext['consult_fee_ext'], $deal['consult_fee_rate'], $sum_collection['consult_fee']);
            $sum_collection['guarantee_fee'] = call_user_func($func_get_fee, $deal_ext['guarantee_fee_ext'], $deal['guarantee_fee_rate'], $sum_collection['guarantee_fee']);
            $sum_collection['pay_fee'] = call_user_func($func_get_fee, $deal_ext['pay_fee_ext'], $deal['pay_fee_rate'], $sum_collection['pay_fee']);
            $sum_collection['canal_fee'] = call_user_func($func_get_fee, $deal_ext['canal_fee_ext'], $deal['canal_fee_rate'], $sum_collection['canal_fee']);


            // 每期汇总 (同一项目下的不同标期限相同)
            $loan_list = DealRepayModel::instance()->findAll(sprintf('deal_id = %d order by id asc', $deal['id']));

            for ($i = 0; $i < count($loan_list); ++$i) {
                // 只记录每期的第一个标的的回款信息
                if (!isset($loan_collection[$i])) {
                    $loan_collection[$i] = $loan_list[$i];
                    $loan_collection[$i]['repay_day'] = to_date($loan_list[$i]['repay_time'], 'Y-m-d');
                    $loan_collection[$i]['status_text'] = $this->getLoanStatus($loan_list[$i]['status']);
                } else {
                    $loan_collection[$i]['loan_fee'] = bcadd($loan_list[$i]['loan_fee'], $loan_collection[$i]['loan_fee'], 2);
                    $loan_collection[$i]['consult_fee'] = bcadd($loan_list[$i]['consult_fee'], $loan_collection[$i]['consult_fee'], 2);
                    $loan_collection[$i]['guarantee_fee'] = bcadd($loan_list[$i]['guarantee_fee'], $loan_collection[$i]['guarantee_fee'], 2);
                    $loan_collection[$i]['pay_fee'] = bcadd($loan_list[$i]['pay_fee'], $loan_collection[$i]['pay_fee'], 2);
                    $loan_collection[$i]['canal_fee'] = bcadd($loan_list[$i]['canal_fee'], $loan_collection[$i]['canal_fee'], 2);

                }

                // money
                if (DealRepayModel::STATUS_WAITING == $loan_list[$i]['status']) {
                    $loan_collection[$i]['month_need_all_repay_money'] = Finance::addition(array($loan_list[$i]['repay_money'], $loan_collection[$i]['month_need_all_repay_money']));
                } else {
                    $loan_collection[$i]['month_has_repay_money_all'] = Finance::addition(array($loan_list[$i]['repay_money'], $loan_collection[$i]['month_has_repay_money_all']));
                }
                $loan_collection[$i]['month_repay_money'] = Finance::addition(array($loan_list[$i]['principal'], $loan_list[$i]['interest'], $loan_collection[$i]['month_repay_money']));
                $loan_collection[$i]['impose_money'] = Finance::addition(array($loan_list[$i]->feeOfOverdue(), $loan_collection[$i]['impose_money']));
            }
        }

        // 汇总项目的还款金额信息，页面中置为首行，只作为参考行，不能被选中提交
        $deal = DealProjectModel::getFirstDealByProjectId($project_id);
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

        $temp_list = array(
            "repay_day" => to_date($deal['repay_start_time'], 'Y-m-d'),
            "month_has_repay_money_all" => Finance::addition(array($sum_collection['loan_fee'], $sum_collection['consult_fee'], $sum_collection['guarantee_fee'], $sum_collection['pay_fee'], $sum_collection['canal_fee'])),
            "month_need_all_repay_money" => 0,
            "month_repay_money" => 0,
            "status_text" => $this->getLoanStatus(1),
            "status" => 1,
            "impose_money" => 0,
        );

        //如果费用为前收,则将各项手续费展示
        if($deal_ext['loan_fee_rate_type'] == 1){
            $temp_list['loan_fee'] = $sum_collection['loan_fee'];
        }

        if($deal_ext['consult_fee_rate_type'] == 1){
            $temp_list['consult_fee'] = $sum_collection['consult_fee'];
        }

        if($deal_ext['guarantee_fee_rate_type'] == 1){
            $temp_list['guarantee_fee'] = $sum_collection['guarantee_fee'];
        }

        if($deal_ext['pay_fee_rate_type'] == 1){
            $temp_list['pay_fee'] = $sum_collection['pay_fee'];
        }

        if($deal_ext['canal_fee_rate_type'] == 1){
            $temp_list['canal_fee'] = $sum_collection['canal_fee'];
        }

        $loan_collection[-1] = $temp_list;
        sort($loan_collection);

        return $loan_collection;
    }

    private function getLoanStatus($status_id)
    {
        $status = array(
            0 => '待还',
            1 => '准时还款',
            2 => '逾期还款',
            3 => '严重逾期',
            4 => '提前还款'
        );
        return $status[$status_id];
    }

    /**
     * 渲染还款方的相关信息
     * @param array $deal 项目的首标信息
     * @param string $role 当前用户的角色
     */
    private function assignRepayUser($deal, $role)
    {
        // 获取还款用户信息
        $borrowUser = UserModel::instance()->find($deal['user_id']);
        $repayUser[] = array('userName' => $borrowUser['real_name'],'type'=> 0);

        $repayMode = 0;
        //代垫机构
        if($deal['advance_agency_id'] > 0){
            $advance_agency = DealAgencyModel::instance()->find($deal['advance_agency_id']);
            $repayUser[] = array('userName' => $advance_agency['short_name'],'type'=> 1);
            $deal_type = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);
            if(( $deal_type == DealLoanTypeModel::TYPE_XFFQ)||($deal_type == DealLoanTypeModel::TYPE_XFD) || ($deal_type == DealLoanTypeModel::TYPE_ZHANGZHONG)){
                //消费分期的标的使用代垫机构还款
                $repayMode = 1;
            }
        }
        //代偿机构
        if($deal['agency_id'] > 0){//担保机构代偿
            $agency = DealAgencyModel::instance()->find($deal['agency_id']);
            $repayUser[] = array('userName' => $agency['short_name'],'type'=> 2);
        }
        $this->assign("repay_mode",$repayMode);
        $this->assign("repay_user",$repayUser);

        $repayUserType = 0;
        if ($role == 'b') {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $repayUserType = $redis->get(sprintf('admin_cache_service_audit_force_repay_project_user_type_%d', $deal['id']));
        }

        $dealService = new DealService();
        $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'], 1);
        $advanceUserInfo = UserModel::instance()->find(intval($advanceAgencyUserId));
        $this->assign('advance_money',$advanceUserInfo['money']);
        $agencyUserId = $dealService->getRepayUserAccount($deal['id'], 2);
        $agencyUserInfo = UserModel::instance()->find(intval($agencyUserId));
        $this->assign('agency_money',$agencyUserInfo['money']);
        $this->assign('user_money',$borrowUser['money']);
    }

    /**
     * 提交审核信息
     */
    public function submitAudit()
    {
        try {
            $audit_type = intval($_REQUEST['audit_type']);
            if (!in_array($audit_type, array(ServiceAuditModel::SERVICE_TYPE_PROJECT_REPAY, ServiceAuditModel::SERVICE_TYPE_PROJECT_PREPAY))) {
                throw new \Exception("提交审核类型错误", 1);
            }

            $role = $this->getRole();
            $project_repay_id = intval($_REQUEST['project_repay_id']);
            $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
            if(empty($project_repay_info)){
                throw new \Exception(sprintf("提交项目还款信息错误, project_repay_id:%d", $project_repay_id), 2);
            }

            $first_deal = DealProjectModel::getFirstDealByProjectId($project_repay_info['project_id']);
            if (empty($_REQUEST['deal_repay_id']) && $_REQUEST['audit_type'] == ServiceAuditModel::SERVICE_TYPE_PROJECT_PREPAY) {
                $sql = sprintf('select * from %s where deal_id= %d and status =0', DealPrepayModel::instance()->tableName(), $first_deal['id']);
                $res = $GLOBALS['db']->getRow($sql);
                $_REQUEST['deal_repay_id'] = $res['id'];
            }

            if (!is_array(explode(',', $_REQUEST['deal_repay_id']))) {
                throw new \Exception("请选择还款", 3);
            }

            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis) {
                if ($_REQUEST['ignore_impose_money'] == 'true') {
                    $_REQUEST['ignore_impose_money'] = 1;
                } else {
                    $_REQUEST['ignore_impose_money'] = 0;
                }
                $redis->setex(sprintf('admin_cache_service_audit_force_repay_project_chk_value_%d', $first_deal['id']), 2592000, $_REQUEST['deal_repay_id']); //有效期30天
                $redis->setex(sprintf('admin_cache_service_audit_force_repay_project_ignore_ignore_impose_money_%d', $first_deal['id']), 2592000, $_REQUEST['ignore_impose_money']); //有效期30天
                $redis->setex(sprintf('admin_cache_service_audit_force_repay_project_user_type_%d', $first_deal['id']), 2592000, $_REQUEST['repay_user_type']); //有效期30天
            }

            $service_audit_obj = D('ServiceAudit');
            $audit = $service_audit_obj->where(array('service_type' => $audit_type, 'service_id' => $project_repay_info['id']))->find();
            if ($role != 'b' && in_array($audit['service_type'], array(ServiceAuditModel::SERVICE_TYPE_PROJECT_REPAY, ServiceAuditModel::SERVICE_TYPE_PROJECT_PREPAY)) && $audit['status'] == ServiceAuditModel::NOT_AUDIT) {
                throw new \Exception("该项目已经在审核中，请审核后再提交!", 4);
            }

            if (!$service_audit_obj->audit($project_repay_info, $role, $audit, $audit_type, $_REQUEST['agree'], $_REQUEST['return_reason'])) {
                throw new \Exception("审核状态变更出错!", 5);
            }

            $result['errCode'] = 0;
            $result['errMsg'] = "提交审核成功";
        } catch (\Exception $e) {
            $result['errCode'] = $e->getCode();
            $result['errMsg'] = $e->getMessage();
        }

        ajax_return($result);
        return;
    }

    /**
     * 项目提前还款
     */
    public function prepay()
    {
        try{
            $type = intval($_GET['type']);
            $role = $this->getRole();
            $project_repay_id = intval($_REQUEST['project_repay_id']);
            if(empty($project_repay_id) || empty($type)) {
                throw new \Exception("参数错误");
            }
            $this->assign('project_repay_id', $project_repay_id);

            // 获取项目及其首标的信息
            $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
            $project_id = $project_repay_info['project_id'];

            $deal_pro_prepay_service = new DealProjectPrepayService();
            $deal_pro_prepay_service->prepayCheckByProjectId($project_id);
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        //检查是否已经存在提前还款计划事务
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if($redis){
            $exist_transaction = $redis->get('admin_cache_action_deal_project_repay_save_prepay_start_transaction_' . $project_id);
            $this->assign('exist_transaction', $exist_transaction);
        }

        $this->assign('type',$type);
        $this->assign('role', $role);
        $this->assign('return_type_list', self::$returnTypes);
        $this->assign ('not_ab', $_GET['not_ab']);

        $project = DealProjectModel::instance()->find($project_id);
        $this->assign("project", $project);

        // 标的相关处理
        $deal = DealProjectModel::instance()->getFirstDealByProjectId($project_id,4);
        $this->assign("deal", $deal);

        // 以首标信息为准
        $deal_repay_service = new DealRepayService();
        $interest_time =  $deal_repay_service->getMaxRepayTimeByDealId($deal); // 计息时间(利息开始日期)
        $this->assign('interest_time',$interest_time);

        $repay = $deal_repay_service->getExpectRepayStat($deal['id']);
        $end_day = to_date($repay['last_repay_time'],'Y-m-d'); // 到期日期
        $expect_interest_days = ($repay['last_repay_time'] - $repay['repay_start_time'])/86400; // 到期利息天数
        $this->assign('expect_interest_days',$expect_interest_days);
        $this->assign('end_day', $end_day);


        // 提前还款信息
        $prepay_info = $this->collectDealsPrepayInfo($project_id);
        $this->assign('has_calc',to_date($prepay_info['prepay_collection']['prepay_time'],'Y-m-d'));
        $this->assign('has_saved', empty($prepay_info['prepay_collection']) ? 0 : 1);
        $this->assign('data',$prepay_info['repay_collection']);
        $this->assign('prepay', $prepay_info['prepay_collection']);
        $this->assign("repay_user",$prepay_info['repay_user_collection']);
        $this->assign("repay_mode",$prepay_info['repay_mode']);

        // 还款方信息
        $this->assignRepayUser($deal, $role);
        $this->assign('querystring', \es_session::get('project_repay_index_page_query'));
        $this->display();
    }

    /**
     * 汇总项目下标的提前还款信息
     */
    private function collectDealsPrepayInfo($project_id)
    {
        // 提前还款明细
        $deal_prepay_service = new DealPrepayService();
        $prepay_collection = $deal_prepay_service->getProjectPrepayInfo($project_id);

        // 到期还款明细
        $deal_repay_service = new DealRepayService();
        $repay_collection = $deal_repay_service->getProjectRepayInfo($project_id);

        return array('repay_collection' => $repay_collection, 'prepay_collection' => $prepay_collection);
    }

    /**
     * 计算提前还款各项费用
     * 1：计息结束日期是否大于计息日期
     * 2：利息天数小于提前还款锁定期天数
     */
    public function calc_project_prepay()
    {
        $project_id = intval($_GET['project_id']);
        $end_day = trim($_GET['day']);
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>'');

        try{
            $deal_pro_prepay_service = new DealProjectPrepayService();
            $result['data'] = $deal_pro_prepay_service->prepayCalcProject($project_id, $end_day);
        }catch (\Exception $ex) {
            $result['errCode'] = $ex->getCode();
            $result['errMsg'] = $ex->getMessage();
        }
        ajax_return($result);
    }

    /**
     * 保存提前还款
     */
    public function save_prepay()
    {
        $project_id = intval($_GET['project_id']);
        $repay_type = trim($_GET['repay_user_type']);

        $end_day = trim($_GET['day']);
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>true);
        //检查是否已经存在提前还款计划事务
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if($redis){
            $exist_transaction = $redis->get('admin_cache_action_deal_project_repay_save_prepay_start_transaction_' . $project_id);
            if(!empty($exist_transaction)){
                $result = array('errCode'=>2, 'errMsg'=>'正在处理中，请稍后重试...','data'=> '');
                Logger::info(__FILE__." | ".__LINE__." | redis key exists. project_id:".$project_id);
                ajax_return($result);
                return false;
            }
        }

        // 保存提前还款数据
        $deal_pro_prepay_service = new DealProjectPrepayService();
        if (false === $deal_pro_prepay_service->saveProjectPrepayInfo($project_id, $end_day, $repay_type)) {
            $result = array('errCode'=> 1,'errMsg'=> '项目提前还款信息保存失败！','data'=> '');
            Logger::info(sprintf('项目提前还款保存失败，项目id：%d，计息结束日期：%s', $project_id, $end_day));
        }

        ajax_return($result);
    }


    /**
     * 专享项目提前还款
     * @param $id 项目ID
     * @return json 返回结果
     * @author 王鉴通 <wangjiantong@ucfgroup.com>
     **/
    public function prepayProject()
    {
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>true);

        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        $projectId = $project_repay_info['project_id'];
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>true);
        $saveLogFile = C('SAVE_LOG_FILE');
        $authKey =conf ("AUTH_KEY");
        $admInfo = \es_session::get(md5($authKey));

        try{
            $GLOBALS['db']->startTrans();

            // 操作提前还款
            $deal_pro_prepay_service = new DealProjectPrepayService();
            $deal_pro_prepay_service->prepayPipelineProject($projectId, $admInfo);

            // AB 角
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_PROJECT_PREPAY, 'service_id' => intval($project_repay_id)))->find();
            if (!empty($audit) && $audit['status'] == 1) {
                $auditRes= M("ServiceAudit")->where("id=" . $audit['id'])->save(array('status' => ServiceAuditModel::AUDIT_SUCC, 'audit_uid' => $admInfo['adm_id']));
                if (!$auditRes) {
                    throw new \Exception("更新审核状态失败");
                }
            }

            save_log('提前还款'.' project_id:' . $projectId, C('SUCCESS'), '', '', $saveLogFile);
            $GLOBALS['db']->commit();

        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            save_log('提前还款'.' project:' . $projectId, C('FAILED'), '', '', $saveLogFile);
            $result = array('errCode'=>$e->getCode(),'errMsg'=>$e->getMessage(),'data'=>false);
        }

        ajax_return($result);
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
        $project_repay_list = $this->getListAndAssignCommon();

        $content = iconv("utf-8","gbk","编号,项目id,项目名称,借款金额,年化借款利率,借款期限,放款日期,费用收取方式,还款方式,资产管理方,用户类型,借款人姓名,借款人用户名,借款人id,借款人账户余额,最近一期还款日,本期还款金额,项目状态,审核状态,借款手续费收取方式,借款咨询费收取方式,借款担保费收取方式,支付服务费收取方式");
        $content = $content . "\n";

        $order_value = array(
            'id'=>'""',
            'project_id'=>'""',
            'name'=>'""',
            'borrow_amount'=>'""',
            'rate' =>'""',
            'repay_period'=>'""',
            'repay_start_time'=>'""',
            'fee_rate_type_name'=>'""',
            'loantype'=>'""',
            'agency'=>'""',
            'user_type_name' => '""',
            'user_real_name'=>'""',
            'user_user_name' => '""',
            'user_id' => '""',
            'user_money'=>'""',
            'repay_time'=>'""',
            'repay_money'=>'""',
            'business_status'=>'""',
            'audit_status'=>'""',
            'loan_fee_rate_type_name'=>'""',
            'consult_fee_rate_type_name'=>'""',
            'guarantee_fee_rate_type_name'=>'""',
            'pay_fee_rate_type_name'=>'""',
        );

        foreach($project_repay_list as $k=>$v)
        {
            $order_value['id'] = '"' . iconv('utf-8','gbk',$v['id']) . '"';
            $order_value['project_id'] = '"' . iconv('utf-8','gbk',$v['project_id']) . '"';
            $order_value['name'] = '"' . iconv('utf-8','gbk',$v['project_info']['name']) . '"';
            $order_value['borrow_amount'] = '"' . iconv('utf-8','gbk',format_price($v['project_info']['borrow_amount'], false)) . '"';
            $order_value['rate'] = '"' . iconv('utf-8','gbk',$v['project_info']['rate']) . '%"';
            $order_value['repay_period'] = '"' . iconv('utf-8','gbk',$v['project_info']['repay_period']) . '"';
            $order_value['repay_start_time'] = '"' . iconv('utf-8','gbk',$v['project_info']['repay_start_time']) . '"';
            $order_value['fee_rate_type_name'] = '"' . iconv('utf-8','gbk',$v['project_info']['fee_rate_type_name']) . '"';
            $order_value['loantype'] = '"' . iconv('utf-8','gbk',$v['project_info']['loantype']) . '"';
            $order_value['agency'] = '"' . iconv('utf-8','gbk',$v['project_info']['agency']) . '"';
            $order_value['user_type_name'] = '"' . iconv('utf-8','gbk',getUserTypeName($v['project_info']['user_id'])) . '"';
            $order_value['user_real_name'] = '"' . iconv('utf-8','gbk',$v['user_info']['real_name']) . '"';
            $order_value['user_user_name'] = '"' . iconv('utf-8','gbk',$v['user_info']['user_name']) . '"';
            $order_value['user_id'] = '"' . iconv('utf-8','gbk',$v['user_info']['id']) . '"';
            $order_value['user_money'] = '"' . iconv('utf-8','gbk',$v['user_info']['money']) . '"';
            $order_value['repay_time'] = '"' . iconv('utf-8','gbk',to_date($v['repay_time'], 'Y-m-d')) . '"';
            $order_value['repay_money'] = '"' . iconv('utf-8','gbk',format_price($v['repay_money'], false)) . '"';
            $order_value['business_status'] = '"' . iconv('utf-8','gbk',getProjectBusinessStatusNameByValue($v['project_info']['business_status'])) . '"';
            $order_value['audit_status'] = '"' . iconv('utf-8','gbk',$v['audit_info']['status_name']) . '"';
            $order_value['loan_fee_rate_type_name'] = iconv("utf-8", "gbk", $v['project_info']['name_map']['loan_fee_rate_type_name']);
            $order_value['consult_fee_rate_type_name'] = iconv("utf-8", "gbk", $v['project_info']['name_map']['consult_fee_rate_type_name']);
            $order_value['guarantee_fee_rate_type_name'] = iconv("utf-8", "gbk", $v['project_info']['name_map']['guarantee_fee_rate_type_name']);
            $order_value['pay_fee_rate_type_name'] = iconv("utf-8", "gbk", $v['project_info']['name_map']['pay_fee_rate_type_name']);

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
