<?php
/**
 * 盈嘉项目还款相关
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
use core\service\DealProjectRepayYjService;
use core\service\DealCompoundService;

use libs\utils\Logger;
use libs\utils\Finance;

class DealProjectYjRepayAction extends CommonAction
{

    static $offlneStatus = array(
        '1'=>'未充值',
        '2'=>'已充值',
        '3'=>'未确认代发',
        '4'=>'已确认代发',
        '5'=>'已确认代发',
        );

    public function index()
    {
        unset($_REQUEST['m'], $_REQUEST['a']);
        // 记录查询参数并复原
        if (!empty($_REQUEST['ref'])) {
            $_REQUEST = \es_session::get('seKeyDealProjectYjRepay');
            // 记录分页参数
            if (isset($_GET['p'])) {
                $_REQUEST['p'] = (int)$_GET['p'];
                \es_session::set('seKeyDealProjectYjRepay', $_REQUEST);
            }else{
                $_GET['p'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
            }
        } else if (isset($_REQUEST['repay_time_begin'])) {
            \es_session::set('seKeyDealProjectYjRepay', $_REQUEST);
        } else if (isset($_REQUEST['repay_time_end'])) {
            \es_session::set('seKeyDealProjectYjRepay', $_REQUEST);
        }else{
            \es_session::delete('seKeyDealProjectYjRepay');
        }

        // 获取项目列表
        $project_repay_list = $this->getListAndAssignCommon();
        $this->assign('list', $project_repay_list);

        \es_session::set('project_repay_yj_index_page_query', http_build_query($_GET));
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
            $map['id'] = intval($_REQUEST['id']);
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

        //只筛选盈嘉线下还款的项目
        $repayYjService = new DealProjectRepayYjService();
        $yjProjectIds = $repayYjService->getYjProjectIds();
        if (!empty($yjProjectIds)) {
            $map['project_id'][]  = array('exp'," IN (".implode(",",$yjProjectIds).") ");
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

        if(!empty($_REQUEST['offline_status'])) {
            if(intval($_REQUEST['offline_status']) == 4) {
                $map['offline_status'][]  = array('exp'," IN (4,5) ");
            } else {
                $map['offline_status'] = array('EQ', $_REQUEST['offline_status']);
            }
        }

        //盈嘉线下还款取几日内还款的项目，默认3天
        $limitDays = intval(app_conf('PROJECT_YJ_REPAY_TIME_LIMIT'));
        if ($limitDays <= 0) {
            $limitDays = 3;
        }
        $nextRepayTimeMax = $this->_getAfterWorkingDaysTime($limitDays);
        $map['repay_time'][] = array('LT', $nextRepayTimeMax);

        $project_repay_list = $this->_list(DI('ProjectRepayList'), $map, 'repay_time');
        return $this->handleProjectInfo($project_repay_list);
    }

    /**
     * 获取几个工作日后的时间
     * @param int $days
     * @return int
     */
    private function _getAfterWorkingDaysTime($days=3) {
        $dcService = new DealCompoundService();
        $nextWorkTimeStamp = strtotime(date('Y-m-d'));
        for ($i=0;$i<$days;$i++) {
            $nextWorkTimeStamp += 86400;
            while (($dcService->checkIsHoliday(date('Y-m-d', $nextWorkTimeStamp)))) {
                $nextWorkTimeStamp += 86400;
            }
        }
        return $nextWorkTimeStamp;
    }

    /**
     * 处理项目信息
     * @param array $project_repay_list
     * @return array $project_repay_list [... 'project_info', 'user_info', 'audit_info']
     */
    private function handleProjectInfo($project_repay_list)
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
            $one_project_repay['repay_remain_days'] = ceil(($one_project_repay['repay_time']-strtotime(date('Y-m-d 00:00:00')))/86400); // 还款剩余天数
            $project_repay_list[$key] = $one_project_repay;
        }
        return $project_repay_list;
    }

    /**
     * 项目充值完成
     */
    public function charge(){
        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $this->assign('project_repay_id', $project_repay_id);

        // 获取项目及其首标的信息
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)){
            $this->error("查询项目还款信息失败");
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            $this->error("参数错误");
        }
        //判断状态
        if($project_repay_info['offline_status'] != DealProjectRepayYjService::OFFLINE_STATUS_UNCHARGE){
            $this->error("当前状态不可执行该操作！");
        }

        //更改状态为已充值
        $repayYjService = new DealProjectRepayYjService();
        $res = $repayYjService->charge($project_repay_id);
        if($res) {
            $this->success("确认完成充值成功！");
        }else {
            $this->error("确认完成充值失败！");
        }
    }

    /**
     * 项目还款计算
     */
    public function repay_calc(){
        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $this->assign('project_repay_id', $project_repay_id);

        // 获取项目及其首标的信息
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)){
            $this->error("查询项目还款信息失败");
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            $this->error("参数错误");
        }
        //判断状态
        if($project_repay_info['offline_status'] != DealProjectRepayYjService::OFFLINE_STATUS_CHARGED){
            $this->error("当前状态不可执行该操作！");
        }

        //更改状态为已还款计算完成
        $repayYjService = new DealProjectRepayYjService();
        try{
            $res = $repayYjService->repayCalc($project_repay_id);
        }catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
        $this->success("当期线下还款成功！");
    }

    /**
     * 项目还款
     */
    public function repay(){
        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $this->assign('project_repay_id', $project_repay_id);

        // 获取项目及其首标的信息
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)){
            $this->error("查询项目还款信息失败",1);
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            $this->error("参数错误",1);
        }
        //判断状态
        if($project_repay_info['offline_status'] != DealProjectRepayYjService::OFFLINE_STATUS_REPAY_CALC){
            $this->error("当前状态不可执行该操作！",1);
        }

        //更改状态为已还款完成
        $repayYjService = new DealProjectRepayYjService();
        try{
            $res = $repayYjService->repay($project_repay_id);
        }catch (\Exception $ex) {
            $this->error($ex->getMessage(),1);
        }
        $this->success("更改状态为已还款成功！",1);
    }


    /**
     * 盈嘉项目查看代发金额
     */
    public function repay_info(){
        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $this->assign('project_repay_id', $project_repay_id);

        // 获取项目及其首标的信息
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)){
            $this->error("查询项目还款信息失败");
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            $this->error("参数错误");
        }
        //判断状态
        if($project_repay_info['offline_status'] != DealProjectRepayYjService::OFFLINE_STATUS_REPAY_CALC){
            $this->error("当前状态不可执行该操作！");
        }

        //获取还款信息
        $repayYjService = new DealProjectRepayYjService();
        $repayInfo = $repayYjService->checkRepayInfo($project_repay_id);
        $canCheck = 1;//是否可以查看
        if(empty($repayInfo)) {
            $canCheck = 0;
        }
        //返回还款信息
        $this->assign('project_repay_id', $project_repay_id);
        $this->assign('repay_info', $repayInfo);
        $this->assign('can_check', $canCheck);
        $this->display();

    }
    /**
     * 盈嘉项目更改还款状态
     */
    public function change_repay_status(){
        $project_repay_id = intval($_REQUEST['project_repay_id']);
        $this->assign('project_repay_id', $project_repay_id);

        // 获取项目及其首标的信息
        $project_repay_info = ProjectRepayListModel::instance()->find($project_repay_id);
        if(empty($project_repay_info)){
            $this->error("查询项目还款信息失败");
        }
        $project = DealProjectModel::instance()->find(intval($project_repay_info['project_id']));
        if(empty($project)){
            $this->error("参数错误");
        }
        //判断状态
        if($project_repay_info['offline_status'] != DealProjectRepayYjService::OFFLINE_STATUS_REPAYED){
            $this->error("当前状态不可执行该操作！");
        }

        //更改还款状态
        $repayYjService = new DealProjectRepayYjService();
        $res = $repayYjService->changeRepayStatus($project_repay_id);
        if($res) {
            $this->success("更改还款状态成功！");
        }else {
            $this->success("更改还款状态失败！");
        }
    }
    /**
     * 盈嘉项目查看还款计划
     */
    public function repay_list(){
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

        $this->assign('querystring', \es_session::get('project_repay_yj_index_page_query'));
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
     * 导出 index csv
     */
    public function export_csv()
    {
        if($_REQUEST['id'] <> ''){
            $ids = explode(',',$_REQUEST['id']);
        }

        $project_repay_list = $this->getListAndAssignCommon();

        $content = iconv("utf-8","gbk","编号,项目id,项目名称,借款金额,年化借款利率,借款期限,放款日期,费用收取方式,还款方式,资产管理方,用户类型,借款人姓名,借款人用户名,借款人id,借款人账户余额,最近一期还款日,本期还款金额,距离还款日剩余天数,项目状态,代发状态");
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
            'repay_remain_days'=>'""',
            'business_status'=>'""',
            'offline_status'=>'""',
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
            $order_value['repay_remain_days'] = ceil(($v['repay_time']-strtotime(date('Y-m-d 00:00:00')))/86400); // 还款剩余天数
            $order_value['business_status'] = '"' . iconv('utf-8','gbk',getProjectBusinessStatusNameByValue($v['project_info']['business_status'])) . '"';
            $order_value['offline_status'] = '"' . iconv('utf-8','gbk',self::$offlneStatus[$v['offline_status']]) . '"';

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
