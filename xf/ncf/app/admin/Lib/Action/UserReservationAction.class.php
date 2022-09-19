<?php
/**
 * Created by PhpStorm.
 * User: weiwei12@ucfgroup.com
 * Date: 16/11/04
 * Time: 上午11:15
 */
use core\service\reserve\UserReservationService;
use core\service\reserve\ReservationCardService;
use core\dao\reserve\ReservationConfModel;
use core\dao\reserve\UserReservationModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\reserve\ReservationDealLoadModel;
use core\dao\reserve\ReservationEntraModel;
use core\service\reserve\ReservationMatchService;
use core\dao\reserve\ReservationMatchModel;
use core\dao\o2o\OtoTriggerRuleModel;
use core\dao\reserve\ReservationCardModel;
use core\dao\reserve\ReservationMoneyAssignRatioModel;
use core\dao\ConfModel;
use core\service\deal\DealTypeGradeService;
use core\service\reserve\ReservationConfService;
use core\service\reserve\ReservationEntraService;
use core\service\risk\RiskAssessmentService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionAccountService;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\service\contract\CategoryService;
use core\service\contract\ContractDtService;
use core\service\contract\ContractPreService;
use core\service\contract\ContractService;
use core\service\contract\ContractInvokerService;

use libs\tcpdf\Mkpdf;

use core\enum\UserAccountEnum;
use core\enum\ReserveEnum;
use core\enum\ReserveCardEnum;
use core\enum\ReserveConfEnum;
use core\enum\ReserveMatchEnum;
use core\enum\ReserveEntraEnum;
use core\enum\O2oEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\contract\ContractCategoryEnum;
use core\enum\contract\ContractServiceEnum;

class UserReservationAction extends CommonAction{

    protected static $deal_types = array('0' => '网贷');

    /**
     * 根据站点获取借款类型
     */
    private function getDealTypes() {
        return self::$deal_types;
    }

    /**
     * 批量获取用户信息
     */
    private function getUserListByArr($arr) {
        $accountMap = [];
        foreach ($arr as $key => $value) {
            $accountMap[$value['user_id']] = AccountService::getUserId($value['user_id']);
        }
        $userIds = array_filter(array_values($accountMap));
        $userList = UserService::getUserInfoByIds(implode(',', $userIds));
        $result = [];
        foreach ($accountMap as $accountId => $userId) {
            if (isset($userList[$userId])) {
                $result[$accountId] = $userList[$userId];
            }
        }
        return $result;
    }

    /**
     * 获取入口名称
     */
    private function getEntraName($dealType, $investLine, $investUnit, $investRate, $loantype) {
        if (empty($investLine) || empty($investUnit)) {
            return '';
        }
        $dealTypes = $this->getDealTypes();
        $loanTypeMap = $GLOBALS['dict']['LOAN_TYPE_CN'];
        return $investLine . ($investUnit == ReserveEnum::INVEST_DEADLINE_UNIT_DAY ? '' :'个') . ReserveEnum::$investDeadLineUnitConfig[$investUnit] . '-'
            . $investRate . '%' . '-'
            . (isset($loanTypeMap[$loantype]) ? $loanTypeMap[$loantype] : '全部');
    }

    /**
     * 获取投资利率+期限列表
     */
    private function getInvestList() {
        $lineList = $rateList = [];
        $entraList = ReservationEntraModel::instance()->getReserveEntraList(-1);
        foreach ($entraList as $entra) {
            if (!empty($entra['invest_line']) && !empty($entra['invest_unit'])) {
                $lineList[$entra['invest_line'].'_'.$entra['invest_unit']] = [
                    'deadline'              => $entra['invest_line'],
                    'deadline_unit'         => $entra['invest_unit'],
                    'deadline_unit_format'  => ReserveEnum::$investDeadLineUnitConfig[$entra['invest_unit']],
                    'deadline_format'       => $entra['invest_line'] . ReserveEnum::$investDeadLineUnitConfig[$entra['invest_unit']],
                ];
            }

            if ($entra['invest_rate'] > 0) {
                $rateList[$entra['invest_rate']] = $entra['invest_rate'];
            }
        }
        return [
            'line_list' => array_values($lineList),
            'rate_list' => array_values($rateList),
        ];
    }

    /**
     * 预约列表
     */
    public function index()
    {
        $map = array();
        $map['deal_type'] = 0;
        if (!empty($_REQUEST['id'])) {
            $map['id'] = intval($_REQUEST['id']);
        }
        $_REQUEST['reserve_status'] = isset($_REQUEST['reserve_status']) ? $_REQUEST['reserve_status'] : -1;
        if (intval($_REQUEST['reserve_status']) >= 0) {
            $map['reserve_status'] = intval($_REQUEST['reserve_status']);
        }
        if (!empty($_REQUEST['invest_deadline_opt'])) {
            $arr = explode('|', $_REQUEST['invest_deadline_opt']);
            $map['invest_deadline'] = $arr[0];
            $map['invest_deadline_unit'] = $arr[1];
        }

        if (!empty($_REQUEST['reserve_src'])) {
            $map['reserve_referer'] = intval($_REQUEST['reserve_src']);
        }

        if(!empty($_REQUEST['real_name'])){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $ids = UserService::getUserIdByRealName($real_name);
            if (!empty($ids)) {
                $accountIds = [];
                foreach ($ids as $id) {
                    $accountIds = array_merge($accountIds, AccountService::getAccountIdsByUserId($id));
                }
                !empty($accountIds) && $map['user_id'] = array('in', $accountIds);
            }
        }
        if(!empty($_REQUEST['mobile'])){
            $user = UserService::getUserByMobile(intval($_REQUEST['mobile']));
            if (!empty($user['id'])) {
                $accountIds = AccountService::getAccountIdsByUserId($user['id']);
                !empty($accountIds) && $map['user_id'] = array('in', $accountIds);
            }
        }
        if (!empty($_REQUEST['user_id'])) {
            $accountIds = AccountService::getAccountIdsByUserId($_REQUEST['user_id']);
            !empty($accountIds) && $map['user_id'] = array('in', $accountIds);
        }
        // 预约提交时间
        if (!empty($_REQUEST['start_from'])) {
            $map['start_time'] = array('egt', strtotime($_REQUEST['start_from']));
        }
        if (!empty($_REQUEST['start_to'])) {
            $map['start_time'] = array('elt', strtotime($_REQUEST['start_to']));
        }
        if (!empty($_REQUEST['start_from']) && !empty($_REQUEST['start_to'])) {
            $map['start_time'] = array('between', array(strtotime($_REQUEST['start_from']), strtotime($_REQUEST['start_to'])));
        }
        // 预约结束时间
        if (!empty($_REQUEST['end_from'])) {
            $map['end_time'] = array('egt', strtotime($_REQUEST['end_from']));
        }
        if (!empty($_REQUEST['end_to'])) {
            $map['end_time'] = array('elt', strtotime($_REQUEST['end_to']));
        }
        if (!empty($_REQUEST['end_from']) && !empty($_REQUEST['end_to'])) {
            $map['end_time'] = array('between', array(strtotime($_REQUEST['end_from']), strtotime($_REQUEST['end_to'])));
        }
        if (!empty($_REQUEST['loantype'])) {
            $map['loantype'] = intval($_REQUEST['loantype']);
        }
        if (!empty($_REQUEST['invest_rate'])) {
            $map['invest_rate'] = $_REQUEST['invest_rate'];
        }
        //预约金额
        if (!empty($_REQUEST['start_reserve_amount'])) {
            $map['reserve_amount'] = array('egt', bcmul($_REQUEST['start_reserve_amount'], 100));
        }
        if (!empty($_REQUEST['end_reserve_amount'])) {
            $map['reserve_amount'] = array('elt', bcmul($_REQUEST['end_reserve_amount'], 100));
        }
        if (!empty($_REQUEST['start_reserve_amount']) && !empty($_REQUEST['end_reserve_amount'])) {
            $map['reserve_amount'] = array('between', array(bcmul($_REQUEST['start_reserve_amount'], 100), bcmul($_REQUEST['end_reserve_amount'], 100)));
        }

        // 获取预约列表
        $list = $this->_getReserveList($map, 'end_time', false);

        // 获取预约来源
        $srcTmpList = [];
        foreach (ReserveEnum::$reserveRefererConfig as $key => $value) {
            $srcTmpList[] = ['srcId' => $key, 'srcName'=>$value];
        }
        $data['reserve_src_list'] = $srcTmpList;

        // 期限配置
        $investList = $this->getInvestList();
        $data['deadlineConf'] = $investList['line_list'];
        $data['rateList'] = $investList['rate_list'];

        // 预约协议
        foreach($list as $key => $value){
            $value['id'];
            $number = ContractService::createDtNumber($value['id'],0);
            $contract = ContractService::getContractByNumber($value['id'],$number,ContractServiceEnum::SOURCE_TYPE_RESERVATION);
            $list[$key]['contract_id'] = ( $contract[0]['status'] == 1) ? $contract[0]['id'] : 0;
        }

        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式
        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign('summary', $summary);
        $this->display();
    }

    /**
     * 导出预约列表
     */
    public function export_index() {
        set_time_limit(0);
        @ini_set('memory_limit', '2048M');
        $where = " 1 = 1 ";
        $where .= sprintf(" AND `deal_type` = 0 ");

        if (!empty($_REQUEST['id'])) {
            $where .= " AND `id` = " . intval($_REQUEST['id']);
        }
        //预约状态
        $_REQUEST['reserve_status'] = isset($_REQUEST['reserve_status']) ? $_REQUEST['reserve_status'] : -1;
        if (intval($_REQUEST['reserve_status']) >= 0) {
            $where .= " AND `reserve_status` = " . intval($_REQUEST['reserve_status']);
        }
        if (!empty($_REQUEST['invest_deadline_opt'])) {
            $arr = explode('|', $_REQUEST['invest_deadline_opt']);
            $invest_deadline = $arr[0];
            $invest_deadline_unit = $arr[1];
            $where .= " AND `invest_deadline` = " . $invest_deadline;
            $where .= " AND `invest_deadline_unit` = " . $invest_deadline_unit;
        }
        if (!empty($_REQUEST['reserve_src'])) {
            $where .= " AND `reserve_referer` =" . intval($_REQUEST['reserve_src']);
        }

        if(!empty($_REQUEST['real_name'])){
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $ids = UserService::getUserIdByRealName($real_name);
            if (!empty($ids)) {
                $accountIds = [];
                foreach ($ids as $id) {
                    $accountIds = array_merge($accountIds, AccountService::getAccountIdsByUserId($id));
                }
                !empty($accountIds) && $where .= sprintf(' AND `user_id` IN (%s)', implode(',', $accountIds));
            }
        }
        if(!empty($_REQUEST['mobile'])){
            $user = UserService::getUserByMobile(intval($_REQUEST['mobile']));
            if (!empty($user['id'])) {
                $accountIds = AccountService::getAccountIdsByUserId($user['id']);
                !empty($accountIds) && $where .= sprintf(' AND `user_id` IN (%s)', implode(',', $accountIds));
            }

        }
        if (!empty($_REQUEST['user_id'])) {
            $accountIds = AccountService::getAccountIdsByUserId($_REQUEST['user_id']);
            !empty($accountIds) && $where .= sprintf(' AND `user_id` IN (%s)', implode(',', $accountIds));
        }
        // 预约提交时间
        if (!empty($_REQUEST['start_from'])) {
            $start_from = strtotime($_REQUEST['start_from']);
            $where .= " AND `start_time` >= " . $start_from;
        }
        if (!empty($_REQUEST['start_to'])) {
            $start_to = strtotime($_REQUEST['start_to']);
            $where .= " AND `start_time` <= " . $start_to;
        }
        // 预约结束时间
        if (!empty($_REQUEST['end_from'])) {
            $end_from = strtotime($_REQUEST['end_from']);
            $where .= " AND `end_time` >= " . $end_from;
        }
        if (!empty($_REQUEST['end_to'])) {
            $end_to = strtotime($_REQUEST['end_to']);
            $where .= " AND `end_time` <= " . $end_to;
        }
        //预约金额
        if (!empty($_REQUEST['start_reserve_amount'])) {
            $where .= " AND `reserve_amount` >= " . bcmul($_REQUEST['start_reserve_amount'], 100);
        }
        if (!empty($_REQUEST['end_reserve_amount'])) {
            $where .= " AND `reserve_amount` <= " . bcmul($_REQUEST['end_reserve_amount'], 100);
        }
        if (!empty($_REQUEST['loantype'])) {
            $where .= " AND `loantype` = " . intval($_REQUEST['loantype']);
        }
        if (!empty($_REQUEST['invest_rate'])) {
            $where .= " AND `invest_rate` = " . $_REQUEST['invest_rate'];
        }
        $sort = 'desc';
        $sql = "SELECT *
            FROM " . DB_PREFIX . "user_reservation" .
            " WHERE " . $where .
            " ORDER BY end_time {$sort} ";
        $res = \libs\db\Db::getInstance('firstp2p', 'slave')->query($sql);

        $datatime = date("YmdHis", get_gmtime());
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename=user_index_{$datatime}.csv");

        $title = array('预约ID','预约结束时间','预约提交时间','预约来源','账户ID','用户名','手机号','预约状态','预约金额','已出借金额','出借笔数','剩余出借金额','预约出借期限','年化借款利率','还款方式','优惠券ID','优惠券状态');
        foreach ($title as $k => $v) {
                $title[$k] = iconv("utf-8", "gbk", $v);
        }

        $count = 1;
        $limit = 10000;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);

        $loanTypeMap = $GLOBALS['dict']['LOAN_TYPE_CN'];
        while (true) {
            //分批次
            $reserveList = [];
            $i = 1;
            while ($val = $GLOBALS['db']->fetchRow($res)) {
                $reserveList[] = $val;
                if ($i >= 100) {
                    break;
                }
                $i ++;
            }
            if (empty($reserveList)) {
                break;
            }
            //批量取用户信息
            $userList = $this->getUserListByArr($reserveList);
            foreach ($reserveList as $v) {
                $user = $userList[$v['user_id']];
                $arr = array();
                $arr[] = $v['id'];
                $arr[] = format_date($v['end_time']);
                $arr[] = format_date($v['start_time']);
                $arr[] = ReserveEnum::$reserveRefererConfig[(int)$v['reserve_referer']];
                $arr[] = $v['user_id'];
                $arr[] = $user['real_name'];
                $arr[] = $user['mobile'];
                $arr[] = $v['reserve_status']? '预约结束' : '预约中';
                $arr[] = bcdiv($v['reserve_amount'], 100, 2) . '元';
                $arr[] = bcdiv($v['invest_amount'], 100, 2) . '元';
                $arr[] = $v['invest_count'];
                $arr[] = bcdiv($v['reserve_amount'] - $v['invest_amount'], 100, 2) . '元';
                $arr[] = $v['invest_deadline'] . ReserveEnum::$investDeadLineUnitConfig[$v['invest_deadline_unit']];
                $arr[] = $v['invest_rate'] . '%';
                $arr[] = isset($loanTypeMap[$v['loantype']]) ? $loanTypeMap[$v['loantype']] : '全部';
                $arr[] = $v['discount_id'];
                $arr[] = ReserveEnum::$discountStatusMap[$v['discount_status']];
                $arr[] = "\t";

                foreach ($arr as $k => $v){
                    $arr[$k] = iconv("utf-8", "gbk", strip_tags($v));
                }

                $count++;
                if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $count = 0;
                }
                fputcsv($fp, $arr);
            }
        }
        exit();
    }

    /**
     * 交易列表
     */
    public function trans()
    {
        $where = " `deal_type` = 0 ";
        $aliasTransTable = "t1";
        $aliasReserveTable = "t2";
        if (!empty($_REQUEST['id'])) {
            $where .= " AND {$aliasTransTable}.`id` = " . intval($_REQUEST['id']);
        }
        if (!empty($_REQUEST['reserve_id'])) {
            $where .= " AND {$aliasTransTable}.`reserve_id` = " . intval($_REQUEST['reserve_id']);
        }
        if (!empty($_REQUEST['invest_deadline_opt'])) {
            $arr = explode('|', $_REQUEST['invest_deadline_opt']);
            $invest_deadline = $arr[0];
            $invest_deadline_unit = $arr[1];
            $where .= " AND {$aliasReserveTable}.`invest_deadline` = " . $invest_deadline;
            $where .= " AND {$aliasReserveTable}.`invest_deadline_unit` = " . $invest_deadline_unit;
        }
        //投资交易时间
        if (!empty($_REQUEST['invest_date_from'])) {
            $deal_time_from = strtotime($_REQUEST['invest_date_from']);
            $where .= " AND {$aliasTransTable}.`create_time` >= " . $deal_time_from;
        }
        if (!empty($_REQUEST['invest_date_to'])) {
            $deal_time_to = strtotime($_REQUEST['invest_date_to']);
            $where .= " AND {$aliasTransTable}.`create_time` <= " . $deal_time_to;
        }
        //投资标的
        if(!empty($_REQUEST['deal_name'])){
            $deal_name = addslashes(trim($_REQUEST['deal_name']));
            $sql  = "select id from ".DB_PREFIX."deal where name like '%" . $deal_name . "%'";
            $ids = $GLOBALS['db']->get_slave()->getAll($sql);
            if (!empty($ids)) {
                foreach ($ids as $key => $value) {
                    $set[] = $value['id'];
                }
                $id_arr = implode(",", $set);
                $where .= " AND {$aliasTransTable}.`deal_id` in " . "($id_arr)";
            } else {
                $where .= ' AND 1 <> 1'; //标的不存在
            }
        }

        // 期限配置
        $data = [];
        $entraService = new ReservationEntraService();
        $investList = $this->getInvestList();
        $data['deadlineConf'] = $investList['line_list'];

        $sort = 'desc';
        $tplSql = "SELECT %s
            FROM " . DB_PREFIX . "reservation_deal_load {$aliasTransTable} " . " LEFT JOIN " . DB_PREFIX . "user_reservation {$aliasReserveTable} " .
            " ON {$aliasTransTable}.`reserve_id` = {$aliasReserveTable}.`id` " .
            " WHERE " . $where .
            " ORDER BY  {$aliasTransTable}.`create_time` {$sort} ";
        //总数
        //$count = $GLOBALS['db']->get_slave()->getOne(sprintf($tplSql, "count(1)"));

        $p = new Page (CommonAction::LIST_WITHOUT_PAGE_MAX);
        $limit = " limit {$p->firstRow}, {$p->listRows}";
        $tplSql .= $limit;
        $voList = $GLOBALS['db']->get_slave()->getAll(sprintf($tplSql, "{$aliasTransTable}.*, {$aliasReserveTable}.`user_id`, {$aliasReserveTable}.`invest_deadline`, {$aliasReserveTable}.`invest_deadline_unit`"), array(), true);

        //分页显示 不显示总数
        $page = $p->show(false, count($voList));
        //列表排序显示
        $sortImg = $sort; //排序图标
        $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT"); //排序提示
        $sort = $sort == 'desc' ? 1 : 0; //排序方式

        //批量取用户信息
        $userList = $this->getUserListByArr($voList);

        $dealLoadModel = DealLoadModel::instance();
        foreach ($voList as $key => $value) {
            $user = $userList[$value['user_id']];
            $voList[$key]['user_id'] = $value['user_id'];
            $voList[$key]['real_name'] = $user['real_name'];
            $voList[$key]['mobile'] = $user['mobile'];
            $deal = $dealLoadModel->getDealInfoByLoadId($value['load_id']);
            $voList[$key]['deal_id'] = $deal['deal_id'];
            $voList[$key]['deal_name'] = $deal['name'];
            $voList[$key]['money_format'] = $deal['money'] . '元';
            $voList[$key]['invest_deadline_format'] = $value['invest_deadline'] . ReserveEnum::$investDeadLineUnitConfig[$value['invest_deadline_unit']];
        }

        $this->assign('list', $voList);
        $this->assign('data', $data);
        $this->assign('sort', $sort);
        $this->assign('sortImg', $sortImg);
        $this->assign('sortType', $sortAlt);
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);

        $this->display();
    }

    /**
     * 导出交易列表
     */
    public function export_trans()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '2048M');
        $where = " 1 = 1 ";
        $where .= sprintf(" AND `deal_type` = 0 ");
        $aliasTransTable = "t1";
        $aliasReserveTable = "t2";

        if (!empty($_REQUEST['id'])) {
            $where .= " AND {$aliasTransTable}.`id` = " . intval($_REQUEST['id']);
        }
        if (!empty($_REQUEST['reserve_id'])) {
            $where .= " AND {$aliasTransTable}.`reserve_id` = " . intval($_REQUEST['reserve_id']);
        }
        if (!empty($_REQUEST['invest_deadline_opt'])) {
            $arr = explode('|', $_REQUEST['invest_deadline_opt']);
            $invest_deadline = $arr[0];
            $invest_deadline_unit = $arr[1];
            $where .= " AND {$aliasReserveTable}.`invest_deadline` = " . $invest_deadline;
            $where .= " AND {$aliasReserveTable}.`invest_deadline_unit` = " . $invest_deadline_unit;
        }
        if (!empty($_REQUEST['invest_date_from'])) {
            $deal_time_from = strtotime($_REQUEST['invest_date_from']);
            $where .= " AND {$aliasTransTable}.`create_time` >= " . $deal_time_from;
        }
        if (!empty($_REQUEST['invest_date_to'])) {
            $deal_time_to = strtotime($_REQUEST['invest_date_to']);
            $where .= " AND {$aliasTransTable}.`create_time` <= " . $deal_time_to;
        }
        if (!empty($_REQUEST['invest_date_from']) && !empty($_REQUEST['invest_date_to'])) {
            $deal_time_from = strtotime($_REQUEST['invest_date_from']);
            $deal_time_to = strtotime($_REQUEST['invest_date_to']);
            $where .= " AND {$aliasTransTable}.`create_time` >= " . $deal_time_from;
            $where .= " AND {$aliasTransTable}.`create_time` <= " . $deal_time_to;
        }
        if(!empty($_REQUEST['deal_name'])){
            $deal_name = addslashes(trim($_REQUEST['deal_name']));
            $sql  = "select id from ".DB_PREFIX."deal where name like '%" . $deal_name . "%'";
            $ids = $GLOBALS['db']->get_slave()->getAll($sql);
            if (!empty($ids)) {
                foreach ($ids as $key => $value) {
                    $set[] = $value['id'];
                }
                $id_arr = implode(",", $set);
                $where .= " AND {$aliasTransTable}.`deal_id` in " . "($id_arr)";
            } else {
                $where .= ' AND 1 <> 1'; //标的不存在
            }
        }

        $sort = 'desc';
        $sql = "SELECT {$aliasTransTable}.*, {$aliasReserveTable}.`user_id`, {$aliasReserveTable}.`invest_deadline`, {$aliasReserveTable}.`invest_deadline_unit`
            FROM " . DB_PREFIX . "reservation_deal_load {$aliasTransTable} " . " LEFT JOIN " . DB_PREFIX . "user_reservation {$aliasReserveTable} " .
            " ON {$aliasTransTable}.`reserve_id` = {$aliasReserveTable}.`id` " .
            " WHERE " . $where .
            " ORDER BY  {$aliasTransTable}.`create_time` {$sort} ";
        $res = \libs\db\Db::getInstance('firstp2p', 'slave')->query($sql);

        $datatime = date("YmdHis", get_gmtime());
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename=user_trans_{$datatime}.csv");

        $title = array('预约ID','交易ID','交易时间','账户ID','用户名','手机号','交易状态','出借标的','出借金额','预约出借期限');
        foreach ($title as $k => $v) {
                $title[$k] = iconv("utf-8", "gbk", $v);
        }

        $count = 1;
        $limit = 10000;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);
        $reservation_deal_load = M("reservation_deal_load");

        $dealLoadModel = new DealLoadModel();
        while (true) {
            //分批次
            $reserveList = [];
            $i = 1;
            while ($val = $GLOBALS['db']->fetchRow($res)) {
                $reserveList[] = $val;
                if ($i >= 100) {
                    break;
                }
                $i ++;
            }
            if (empty($reserveList)) {
                break;
            }
            //批量取用户信息
            $userList = $this->getUserListByArr($reserveList);
            foreach ($reserveList as $v) {
                $user = $userList[$v['user_id']];
                $deal = $dealLoadModel->getDealInfoByLoadId($v['load_id']);
                $arr = array();
                $arr[] = $v['reserve_id'];
                $arr[] = $v['id'];
                $arr[] = format_date($v['create_time']);
                $arr[] = $v['user_id'];
                $arr[] = $user['real_name'];
                $arr[] = $user['mobile'];
                $arr[] = "交易成功";
                $arr[] = $deal['name'];
                $arr[] = $deal['money'] . '元';
                $arr[] = $v['invest_deadline'] . ReserveEnum::$investDeadLineUnitConfig[$v['invest_deadline_unit']];
                $arr[] = "\t";

                foreach ($arr as $k => $v){
                    $arr[$k] = iconv("utf-8", "gbk", strip_tags($v));
                }

                $count++;
                if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $count = 0;
                }
                fputcsv($fp, $arr);
            }
        }
        exit();
    }

    /**
     * 预告发布
     */
    public function notice()
    {
        $reservationConfModel = new ReservationConfModel;
        $confModel = ConfModel::instance();
        $type = ReserveConfEnum::TYPE_NOTICE_P2P;
        $data = $reservationConfModel->getReserveInfoByType($type);

        // 预约期限范围配置
        $expireUnitConfig = array();
        foreach (ReserveEnum::$expireUnitConfig as $expireKey => $expireValue) {
            $expireUnitConfig[] = array('expireNum'=>$expireKey, 'expireUnit'=>$expireValue);
        }

        //预约协议模版
        $protocolTplList = CategoryService::getCategorys(ContractCategoryEnum::CATEGORY_IS_DLETE_NO, ContractCategoryEnum::BUSINESS_TYPE_DEAL, ContractServiceEnum::SOURCE_TYPE_RESERVATION);
        $confRet = $confModel->get('RESERVE_PROTOCOL_TPL');
        $originProtocolTpl = !empty($confRet['value']) ? (int) $confRet['value'] : 0;

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $banner = isset($_POST['banner']) ? $_POST['banner'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            // 预约规则
            $reserveRule = isset($_POST['reserve_rule']) ? $_POST['reserve_rule'] : '';
            $expire = isset($_POST['expire']) ? $_POST['expire'] : array();
            $expireUnit = isset($_POST['expire_unit']) ? $_POST['expire_unit'] : array();
            $protocolTpl = isset($_POST['protocol_tpl']) ? (int) $_POST['protocol_tpl'] : 0; //预约协议模版
            if (empty($banner) || empty($description) || empty($reserveRule) || empty($expire) || empty($expireUnit)) {
                $this->error('缺少参数');
            }
            $reserveConf = [];
            foreach ($expire as $key => $value) {
                // 预约期限单位
                $expireUnitValue = isset($expireUnit[$key]) ? intval($expireUnit[$key]) : 0;
                if (empty($expireUnitValue)) {
                    continue;
                }
                // 检查预约期限单位是否合法
                if (empty(ReserveEnum::$expireUnitConfig[$expireUnitValue])) {
                    continue;
                }
                $reserveConf[$key]['expire'] = $value;
                $reserveConf[$key]['expire_unit'] = $expireUnitValue;
            }

            if (empty($data)) {
                $result = $reservationConfModel->createReserveInfo($type, $description, $banner, 0, 0, array(), $reserveConf, $reserveRule);
            } else {
                $result = $reservationConfModel->updateReserveInfo($type, $description, $banner, 0, 0, array(), $reserveConf, $reserveRule);
            }
            //更新预约协议模版
            if (!empty($protocolTpl) && $originProtocolTpl != $protocolTpl) {
                $setResult = CategoryService::setDealCId(ContractServiceEnum::RESERVATION_PROJECT_ID, $protocolTpl, ContractServiceEnum::TYPE_RESERVATION, ContractServiceEnum::SOURCE_TYPE_RESERVATION);
                if (!$setResult) {
                    $this->error('更新预约协议模版失败');
                }
                //更新系统配置
                $confModel->set('RESERVE_PROTOCOL_TPL', $protocolTpl);
            }

            if (!$result) {
                $this->error('保存预告失败');
            }
            $this->success(L("SAVE_SUCCESS"));
        }
        $this->assign('data', $data);
        $this->assign('expireUnitConfig', $expireUnitConfig);
        $this->assign('protocolTplList', $protocolTplList);
        $this->assign('originProtocolTpl', $originProtocolTpl);
        $this->assign('product_name', UserReservationService::PRODUCT_NAME);
        $this->display();
    }

    /**
     * 预约入口
     */
    public function reservecard()
    {
        $entraService = new ReservationEntraService();
        $list = $entraService->getReserveEntraList(-1);
        $dealTypes = $this->getDealTypes();
        $loanTypeMap = $GLOBALS['dict']['LOAN_TYPE_CN'];
        foreach ($list as $key => $val) {
            $list[$key]['deal_type_name'] = $dealTypes[$val['deal_type']];
            $list[$key]['loantype_name'] = isset($loanTypeMap[$val['loantype']]) ? $loanTypeMap[$val['loantype']] : '全部';
            $list[$key]['invest_line_name'] = $val['invest_line'] . ReserveEnum::$investDeadLineUnitConfig[$val['invest_unit']];
            $list[$key]['min_amount_yuan'] = bcdiv($val['min_amount'], 100, 2);
            $list[$key]['max_amount_yuan'] = $val['max_amount'] > 0 ? bcdiv($val['max_amount'], 100, 2) : '无限制';
            $list[$key]['status_name'] = ReserveEntraEnum::$statusName[$val['status']];
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加预约入口
     */
    public function reservecard_add()
    {
        $dealTypeList = $this->getDealTypes();

        //产品名称分类列表
        $p2pName = 'P2P';
        $firstLayerGradeList = [['name'=>$p2pName]];
        $secondLayerGradeList = DealTypeGradeService::getAllSecondLayersByName($p2pName);
        $thirdLayerGradeList = DealTypeGradeService::getAllThirdLayersByName($p2pName);

        $entraService = new ReservationEntraService();
        $rebateRateMap = $entraService->getRebateRateMap();

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = [];
            $params['dealType'] = isset($_POST['deal_type']) ? (int) $_POST['deal_type'] : 2;
            $params['investRate'] = isset($_POST['invest_rate']) ? $_POST['invest_rate'] : 0; //年化借款利率
            $params['loantype'] = isset($_POST['loantype']) ? (int) $_POST['loantype'] : 0; //还款方式
            $params['investLine'] = isset($_POST['repay_time']) ? (int) $_POST['repay_time'] : 0; //投资时间
            $params['investUnit'] = isset($_POST['invest_unit']) ? (int) $_POST['invest_unit'] : 0; //投资期限单位 1天 2月
            $params['minAmount'] = isset($_POST['min_amount']) ? bcmul($_POST['min_amount'], 100) : 10000;//默认100元
            $params['maxAmount'] = isset($_POST['max_amount']) ? bcmul($_POST['max_amount'], 100) : 0;//默认无限额
            $params['investInterest'] = isset($_POST['invest_interest']) ? bcmul($_POST['invest_interest'], 100) : 0;//每万元投资利息
            $params['rateFactor'] = isset($_POST['rate_factor']) ? $_POST['rate_factor'] : 1;//年化利息折算系数
            $params['visiableGroupIds'] = isset($_POST['visiable_group_ids']) ? $_POST['visiable_group_ids'] : '';//可见组配置
            $firstGradeNames = isset($_POST['first_grade_name']) ? array_filter(array_unique($_POST['first_grade_name'])) : [];
            $secondGradeNames = isset($_POST['second_grade_name'])? array_filter(array_unique($_POST['second_grade_name'])) : [];
            $thirdGradeNames = isset($_POST['third_grade_name']) ? array_filter(array_unique($_POST['third_grade_name'])) : [];
            $params['labelBefore'] = isset($_POST['label_before']) ? $_POST['label_before'] : '';//前标签
            $params['labelAfter'] = isset($_POST['label_after']) ? $_POST['label_after'] : '';//后标签
            $displayTotal = isset($_POST['display_total']) ? (int) $_POST['display_total'] : 0;//显示预约总人数/金额
            $params['description'] = isset($_POST['description']) ? $_POST['description'] : '';//产品详情
            $params['status'] = isset($_POST['status']) ? $_POST['status'] : 0;//预约入口状态 1有效 0无效

            //检查参数
            if (empty($params['minAmount']) || empty($params['investRate']) || empty($params['investLine']) || empty($params['investUnit'])
                || empty($params['rateFactor']) || empty($params['description']) || !isset($params['status']) || empty($params['investInterest'])
                || (empty($firstGradeNames) && empty($secondGradeNames) && empty($thirdGradeNames))) {
                $this->error('缺少参数');
            }

            switch ($displayTotal) {
                case 1:
                    $params['displayMoney'] = 1;
                    $params['displayPeople'] = 0;
                    break;
                case 2:
                    $params['displayMoney'] = 0;
                    $params['displayPeople'] = 1;
                    break;
                default:
                    $params['displayMoney'] = 0;
                    $params['displayPeople'] = 0;
            }

            //添加入口
            $params['productGradeConf'] = [
                'firstGradeName' => $firstGradeNames,
                'secondGradeName' => $secondGradeNames,
                'thirdGradeName' => $thirdGradeNames,
            ];
            $result = $entraService->saveReserveEntra($params);
            if ('00' !== $result['errorCode']) {
                $this->error($result['errorMsg']);
            }
            $this->success('提交成功');
        }

        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式
        $this->assign('dealTypeList', $dealTypeList);
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->assign('firstLayerGradeList', $firstLayerGradeList);
        $this->assign('secondLayerGradeList', $secondLayerGradeList);
        $this->assign('thirdLayerGradeList', $thirdLayerGradeList);
        $this->assign('entra', $entra);
        $this->assign('rebateRateMap', $rebateRateMap);
        $this->display();

    }

    /**
     * 编辑预约入口
     */
    public function reservecard_edit()
    {
        //入口id
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if (empty($id)) {
            $this->error('参数错误');
        }

        $entraService = new ReservationEntraService();
        $dealTypeList = $this->getDealTypes();

        $entra = $entraService->getReserveEntraById($id);
        if (empty($entra)) {
            $this->error('入口不存在');
        }
        $entra['product_grade_conf'] = json_decode($entra['product_grade_conf'], true);
        $entra['min_amount_yuan'] = bcdiv($entra['min_amount'], 100, 2);
        $entra['max_amount_yuan'] = bcdiv($entra['max_amount'], 100, 2);
        $entra['invest_interest_yuan'] = bcdiv($entra['invest_interest'], 100, 2);
        $entra['display_total'] = 0;
        if ($entra['display_money'] == 1) {
            $entra['display_total'] = 1;
        } else if ($entra['display_people'] == 1) {
            $entra['display_total'] = 2;
        }

        //产品名称分类列表
        $p2pName = 'P2P';
        $firstLayerGradeList = [['name'=>$p2pName]];
        $secondLayerGradeList = DealTypeGradeService::getAllSecondLayersByName($p2pName);
        $thirdLayerGradeList = DealTypeGradeService::getAllThirdLayersByName($p2pName);

        $rebateRateMap = $entraService->getRebateRateMap();

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = [];
            $params['id'] = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $params['dealType'] = isset($_POST['deal_type']) ? (int) $_POST['deal_type'] : 2;
            $params['investRate'] = isset($_POST['invest_rate']) ? $_POST['invest_rate'] : 0; //年化借款利率
            $params['loantype'] = isset($_POST['loantype']) ? (int) $_POST['loantype'] : 0; //还款方式
            $params['investLine'] = isset($_POST['repay_time']) ? (int) $_POST['repay_time'] : 0; //投资时间
            $params['investUnit'] = isset($_POST['invest_unit']) ? (int) $_POST['invest_unit'] : 0; //投资期限单位 1天 2月
            $params['minAmount'] = isset($_POST['min_amount']) ? bcmul($_POST['min_amount'], 100) : 10000;//默认100元
            $params['maxAmount'] = isset($_POST['max_amount']) ? bcmul($_POST['max_amount'], 100) : 0;//默认无限额
            $params['investInterest'] = isset($_POST['invest_interest']) ? bcmul($_POST['invest_interest'], 100) : 0;//每万元投资利息
            $params['rateFactor'] = isset($_POST['rate_factor']) ? $_POST['rate_factor'] : 1;//年化利息折算系数
            $params['visiableGroupIds'] = isset($_POST['visiable_group_ids']) ? $_POST['visiable_group_ids'] : '';//可见组配置
            $firstGradeNames = isset($_POST['first_grade_name']) ? array_filter(array_unique($_POST['first_grade_name'])) : [];
            $secondGradeNames = isset($_POST['second_grade_name'])? array_filter(array_unique($_POST['second_grade_name'])) : [];
            $thirdGradeNames = isset($_POST['third_grade_name']) ? array_filter(array_unique($_POST['third_grade_name'])) : [];
            $params['labelBefore'] = isset($_POST['label_before']) ? $_POST['label_before'] : '';//前标签
            $params['labelAfter'] = isset($_POST['label_after']) ? $_POST['label_after'] : '';//后标签
            $displayTotal = isset($_POST['display_total']) ? (int) $_POST['display_total'] : 0;//显示预约总人数/金额
            $params['description'] = isset($_POST['description']) ? $_POST['description'] : '';//产品详情
            $params['status'] = isset($_POST['status']) ? $_POST['status'] : 0;//预约入口状态 1有效 0无效

            //检查参数
            if (empty($params['minAmount']) || empty($params['investRate']) || empty($params['investLine']) || empty($params['investUnit'])
                || empty($params['rateFactor']) || empty($params['description']) || !isset($params['status']) || empty($params['investInterest'])
                || (empty($firstGradeNames) && empty($secondGradeNames) && empty($thirdGradeNames))) {
                $this->error('缺少参数');
            }

            switch ($displayTotal) {
                case 1:
                    $params['displayMoney'] = 1;
                    $params['displayPeople'] = 0;
                    break;
                case 2:
                    $params['displayMoney'] = 0;
                    $params['displayPeople'] = 1;
                    break;
                default:
                    $params['displayMoney'] = 0;
                    $params['displayPeople'] = 0;
            }

            $params['productGradeConf'] = [
                'firstGradeName' => $firstGradeNames,
                'secondGradeName' => $secondGradeNames,
                'thirdGradeName' => $thirdGradeNames,
            ];
            $entraService = new ReservationEntraService();
            $result = $entraService->saveReserveEntra($params);
            if ('00' !== $result['errorCode']) {
                $this->error($result['errorMsg']);
            }
            $this->success('提交成功');
        }


        $this->assign('loan_type', $GLOBALS['dict']['LOAN_TYPE_CN']);        //还款方式
        $this->assign('dealTypeList', $dealTypeList);
        $this->assign('repay_time', get_repay_time_month(3, 36, 3));    //还款期限
        $this->assign('repay_time_month', get_repay_time_month());
        $this->assign('firstLayerGradeList', $firstLayerGradeList);
        $this->assign('secondLayerGradeList', $secondLayerGradeList);
        $this->assign('thirdLayerGradeList', $thirdLayerGradeList);
        $this->assign('entra', $entra);
        $this->assign('rebateRateMap', $rebateRateMap);

        $this->display();
    }

    /**
     * 预约排价
     * 废弃
     */
    public function config()
    {
        $reservationConfModel = ReservationConfModel::instance();
        $reservationConfService = new ReservationConfService();
        $confModel = ConfModel::instance();
        $dealTypeList = array_keys($this->getDealTypes());
        $data = $reservationConfService->getReserveInfoByType(ReserveConfEnum::TYPE_CONF, $dealTypeList);
        if (!empty($data)) {
            $data['min_amount_yuan'] = bcdiv($data['min_amount'], 100, 0);
            $data['max_amount_yuan'] = bcdiv($data['max_amount'], 100, 0);
            $investConf = $reserveConf = $amountConf = array();
            foreach ($data['invest_conf'] as $key => $value) {
                $investConf[$key] = $value;
                $investConf[$key]['rate_format'] = $value['rate'] . '%';
                $investConf[$key]['deadline_unit_format'] = ReserveEnum::$investDeadLineUnitConfig[$value['deadline_unit']];
                $investConf[$key]['rate_factor'] = isset($value['rate_factor']) ? $value['rate_factor'] : 1; //年化收益折算系数
            }
            $data['invest_conf'] = $investConf;
            $data['invest_conf_cnt'] = count($investConf);
            foreach ($data['reserve_conf'] as $key => $value) {
                $reserveConf[$key] = $value;
                $reserveConf[$key]['expire_unit_format'] = ReserveEnum::$expireUnitConfig[$value['expire_unit']];
            }
            $data['reserve_conf'] = $reserveConf;
            //预约金额配置，区分借款类型
            foreach ($data['amount_conf'] as $key => $val) {
                $amountConf[$key] = $val;
                $amountConf[$key]['min_amount_yuan'] = bcdiv($val['min_amount'], 100, 0);
                $amountConf[$key]['max_amount_yuan'] = bcdiv($val['max_amount'], 100, 0);
            }
            $data['amount_conf'] = $amountConf;
        }

        //产品名称分类列表
        $p2pName = 'P2P';
        $firstLayerGradeList = [['name'=>$p2pName]];
        $secondLayerGradeList = DealTypeGradeService::getAllSecondLayersByName($p2pName);
        $thirdLayerGradeList = DealTypeGradeService::getAllThirdLayersByName($p2pName);

        // 投资期限范围配置
        $investUnitConfig = array();
        foreach (ReserveEnum::$investDeadLineUnitConfig as $unitKey => $unitValue) {
            $investUnitConfig[] = array('investNum'=>$unitKey, 'investUnit'=>$unitValue);
        }
        // 预约期限范围配置
        $expireUnitConfig = array();
        foreach (ReserveEnum::$expireUnitConfig as $expireKey => $expireValue) {
            $expireUnitConfig[] = array('expireNum'=>$expireKey, 'expireUnit'=>$expireValue);
        }

        // 期限配置
        $investList = $this->getInvestList();
        $data['deadlineConf'] = $investList['line_list'];

        //借款类型
        $dealTypeList = $this->getDealTypes();

        //预约协议模版
        $protocolTplList = CategoryService::getCategorys(ContractCategoryEnum::CATEGORY_IS_DLETE_NO, ContractCategoryEnum::BUSINESS_TYPE_DEAL, ContractServiceEnum::SOURCE_TYPE_RESERVATION);
        $confRet = $confModel->get('RESERVE_PROTOCOL_TPL');
        $originProtocolTpl = !empty($confRet['value']) ? (int) $confRet['value'] : 0;

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $minAmount = isset($_POST['min_amount']) ? bcmul($_POST['min_amount'], 100) : 10000;//默认100元 旧
            $maxAmount = isset($_POST['max_amount']) ? bcmul($_POST['max_amount'], 100) : 0;//默认无限额 旧
            $deadlineJoin = isset($_POST['deadline_join']) ? $_POST['deadline_join'] : array();
            $rate = isset($_POST['rate']) ? $_POST['rate'] : array();
            $rateFactor = isset($_POST['rate_factor']) ? $_POST['rate_factor'] : array();
            $expire = isset($_POST['expire']) ? $_POST['expire'] : array();
            $expireUnit = isset($_POST['expire_unit']) ? $_POST['expire_unit'] : array();
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $firstGradeNames = isset($_POST['first_grade_name'])?array_map("array_unique", $_POST['first_grade_name']):array();
            $secondGradeNames = isset($_POST['second_grade_name'])?array_map("array_unique", $_POST['second_grade_name']):array();
            $thirdGradeNames = isset($_POST['third_grade_name'])?array_map("array_unique", $_POST['third_grade_name']):array();
            $visiableGroupIds = isset($_POST['visiableGroupIds']) ? $_POST['visiableGroupIds'] : array();
            $investDealTypes = isset($_POST['invest_deal_type']) ? $_POST['invest_deal_type'] : []; //投资期限借款类型
            $protocolTpl = isset($_POST['protocol_tpl']) ? (int) $_POST['protocol_tpl'] : 0; //预约协议模版

            //预约金额配置
            $amountDealTypes = isset($_POST['amount_deal_type']) ? $_POST['amount_deal_type'] : []; //预约金额的借款类型
            $minAmountArr = isset($_POST['min_amount_arr']) ? $_POST['min_amount_arr'] : [];
            $maxAmountArr = isset($_POST['max_amount_arr']) ? $_POST['max_amount_arr'] : [];
            if (empty($minAmount) || empty($deadlineJoin) || empty($rate) || empty($rateFactor) || empty($expire) || empty($expireUnit) 
                || empty($amountDealTypes) || empty($investDealTypes) || empty($minAmountArr) || empty($maxAmountArr) 
                || (empty($firstGradeNames) && empty($secondGradeNames) && empty($thirdGradeNames)) && empty($protocolTpl)) {
                $this->error('缺少参数');
            }

            // CheckVisiableGroupIds
            foreach ($visiableGroupIds as $key => $groupIds) {
                $visiableGroupIds[$key] = trim($groupIds);
                if (empty($visiableGroupIds[$key])) {
                    continue;
                }

                $groupIds = explode(',', $groupIds);
                $validateGroupIds = [];
                foreach ($groupIds as $groupId) {
                    $groupId = intval(trim($groupId));
                    if ($groupId == 0) {
                        continue;
                    }

                    $validateGroupIds[] = $groupId;
                }
                $visiableGroupIds[$key] = implode(',', $validateGroupIds);
            }

            $amountConf = $investConf = $reserveConf = $investTmp = $reserveTmp = $ReservationCardIds = array();
            //预约金额配置
            foreach ($amountDealTypes as $key => $value) {
                $amountConf[] = [
                    'deal_type' => $value,
                    'min_amount' => bcmul($minAmountArr[$key], 100),
                    'max_amount' => bcmul($maxAmountArr[$key], 100),
                ];
            }

            $loop = 0;
            foreach ($deadlineJoin as $key => $value) {
                $valueArr = explode('-', $value);
                // 投资期限
                $deadlineValue = isset($valueArr[0]) ? intval($valueArr[0]) : 0;
                if (empty($deadlineValue)) {
                    continue;
                }
                // 投资期限单位
                $deadlineUnitValue = isset($valueArr[1]) ? intval($valueArr[1]) : 0;
                if (empty($deadlineUnitValue)) {
                    continue;
                }
                // 借款类型
                $dealType = isset($investDealTypes[$key]) ? $investDealTypes[$key] : 0;

                // 一级产品名称
                $firstGradeName = isset($firstGradeNames[$key]) ? $firstGradeNames[$key] : "";
                // 二级产品名称
                $secondGradeName = isset($secondGradeNames[$key]) ? $secondGradeNames[$key] : "";
                // 三级产品名称
                $thirdGradeName = isset($thirdGradeNames[$key]) ? $thirdGradeNames[$key] : "";
                //不能都为空
                if (empty($firstGradeName[0]) && empty($secondGradeName[0]) && empty($thirdGradeName[0])) {
                    $this->error('产品名称为空');
                }
                // 检查投资期限单位是否合法
                if (empty(ReserveEnum::$investDeadLineUnitConfig[$deadlineUnitValue])) {
                    continue;
                }
                $uniqKey = sprintf('%d_%d_%d', $dealType, $deadlineValue, $deadlineUnitValue);
                if (!empty($investTmp[$uniqKey])) {
                    continue;
                }
                $investTmp[$uniqKey] = 1;
                $investConf[$loop]['deadline'] = $deadlineValue;
                $investConf[$loop]['deadline_unit'] = $deadlineUnitValue;
                $investConf[$loop]['deal_type'] = $dealType;
                $investConf[$loop]['rate'] = $rate[$key];
                $investConf[$loop]['rate_factor'] = $rateFactor[$key];
                $investConf[$loop]['firstGradeName'] = !empty($firstGradeName[0]) ? $firstGradeName : [];
                $investConf[$loop]['secondGradeName'] = !empty($secondGradeName[0]) ? $secondGradeName : [];
                $investConf[$loop]['thirdGradeName'] = !empty($thirdGradeName[0]) ? $thirdGradeName : [];
                $investConf[$loop]['visiableGroupIds'] = $visiableGroupIds[$key];
                ++$loop;
                //清除产品名称缓存
                $siteId = \libs\utils\Site::getId();
                \SiteApp::init()->dataCache->removeOne(new ReservationConfService(), 'getThirdGradeByDeadLine', [$deadlineValue, $deadlineUnitValue], $siteId);
            }
            foreach ($expire as $key => $value) {
                // 预约期限单位
                $expireUnitValue = isset($expireUnit[$key]) ? intval($expireUnit[$key]) : 0;
                if (empty($expireUnitValue)) {
                    continue;
                }
                // 检查预约期限单位是否合法
                if (empty(ReserveEnum::$expireUnitConfig[$expireUnitValue])) {
                    continue;
                }
                /*$uniqKey = sprintf('%d_%d', $value, $expireUnitValue);
                if (!empty($reserveTmp[$uniqKey])) {
                    $this->error('预约期限不能重复');
                    continue;
                }
                $reserveTmp[$uniqKey] = 1;*/
                $reserveConf[$key]['expire'] = $value;
                $reserveConf[$key]['expire_unit'] = $expireUnitValue;
            }

            if (empty($data)) {
                $result = $reservationConfModel->createReserveInfo(ReserveConfEnum::TYPE_CONF, $description, '', $minAmount, $maxAmount, $investConf, $reserveConf, '', $amountConf);
            } else {
                // 比较新旧投资周期，更新预约卡片的状态
                $investConfOld = $data['invest_conf'];
                if (!empty($investConfOld)) {
                    foreach ($investConfOld as $oldKey => $investOldItem) {
                        foreach ($investConf as $investNewItem) {
                            if ($investOldItem['deadline'] == $investNewItem['deadline'] && $investOldItem['deadline_unit'] == $investNewItem['deadline_unit']) {
                                unset($investConfOld[$oldKey]);
                            }
                        }
                    }
                    // 过滤出已经被删掉的投资期限
                    if (!empty($investConfOld)) {
                        foreach ($investConfOld as $investItem) {
                            ReservationCardModel::instance()->cancelReserveCardByInvestLine($investItem['deadline'], $investItem['deadline_unit']);
                        }
                    }
                }
                $result = $reservationConfModel->updateReserveInfo(ReserveConfEnum::TYPE_CONF, $description, '', $minAmount, $maxAmount, $investConf, $reserveConf, '', $amountConf, $isP2p);
            }
            if (!$result) {
                $this->error('保存预约排价失败');
            }

            //更新预约协议模版
            if (!empty($protocolTpl) && $originProtocolTpl != $protocolTpl) {
                $setResult = CategoryService::setDealCId(ContractServiceEnum::RESERVATION_PROJECT_ID, $protocolTpl, ContractServiceEnum::TYPE_RESERVATION, ContractServiceEnum::SOURCE_TYPE_RESERVATION);
                if (!$setResult) {
                    $this->error('更新预约协议模版失败');
                }
                //更新系统配置
                $confModel->set('RESERVE_PROTOCOL_TPL', $protocolTpl);
            }

            $this->success(L("SAVE_SUCCESS"));
        }
        $this->assign('data', $data);
        $this->assign('investUnitConfig', $investUnitConfig);
        $this->assign('expireUnitConfig', $expireUnitConfig);
        $this->assign('firstLayerGradeList', $firstLayerGradeList);
        $this->assign('secondLayerGradeList', $secondLayerGradeList);
        $this->assign('thirdLayerGradeList', $thirdLayerGradeList);
        $this->assign('dealTypeList', $dealTypeList);
        $this->assign('protocolTplList', $protocolTplList);
        $this->assign('originProtocolTpl', $originProtocolTpl);
        $this->display();
    }

    /**
     * 投资期限配置管理
     * 废弃
     */
    public function deadline() {
        $reservationConfModel = new ReservationConfModel;
        $data = $reservationConfModel->getReserveInfoByType(ReserveConfEnum::TYPE_DEADLINE);
        $data['invest_conf_cnt'] = 1;
        if (!empty($data)) {
            $data['invest_conf_cnt'] = count($data['invest_conf']);
        }
        // 投资期限范围配置
        $investUnitConfig = array();
        foreach (ReserveEnum::$investDeadLineUnitConfig as $unitKey => $unitValue) {
            $investUnitConfig[] = array('investNum'=>$unitKey, 'investUnit'=>$unitValue);
        }

        //提交更新
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $deadline = isset($_POST['deadline']) ? $_POST['deadline'] : array();
            $deadlineUnit = isset($_POST['deadline_unit']) ? $_POST['deadline_unit'] : array();
            if (empty($deadline) || empty($deadlineUnit)) {
                $this->error('缺少参数');
            }
            $investConf = $investTmp = [];
            $loop = 0;
            foreach ($deadline as $key => $value) {
                // 投资期限单位
                $deadlineUnitValue = isset($deadlineUnit[$key]) ? intval($deadlineUnit[$key]) : 0;
                if (empty($deadlineUnitValue)) {
                    continue;
                }

                // 检查投资期限单位是否合法
                if (empty(ReserveEnum::$investDeadLineUnitConfig[$deadlineUnitValue])) {
                    continue;
                }
                $uniqKey = sprintf('%d_%d', $value, $deadlineUnitValue);
                if (!empty($investTmp[$uniqKey])) {
                    continue;
                }

                $investTmp[$uniqKey] = 1;
                $investConf[$loop]['deadline'] = $value;
                $investConf[$loop]['deadline_unit'] = $deadlineUnitValue;
                ++$loop;
            }
            $result = $reservationConfModel->updateReserveInfo(ReserveConfEnum::TYPE_DEADLINE, '', '', 0, 0, $investConf, []);
            if (!$result) {
                $this->error('保存投资期限失败');
            }
            $this->success(L("SAVE_SUCCESS"));

        }
        $this->assign('data', $data);
        $this->assign('investUnitConfig', $investUnitConfig);
        $this->display();
    }

    /**
     * 预约匹配首页
     */
    public function reservematch() {
        $dealTypes = $this->getDealTypes();
        // 获取有效的预约匹配列表
        $reservationMatch = new ReservationMatchService();
        $list = $reservationMatch->getReserveMatchListByTypeId(0, -1, 0, 0, '`is_effect` DESC, `id` DESC');
        $entraService = new ReservationEntraService();
        if (!empty($list)) {
            // 产品类别
            $dealTypeMap = array();
            $dealTypeTree = MI('DealLoanType')->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->findAll();
            if (!empty($dealTypeTree)) {
                foreach ($dealTypeTree as $item) {
                    $dealTypeMap[$item['id']] = array('id'=>$item['id'], 'name'=>$item['name']);
                }
            }
            //借款类型
            $dealTypeList = $this->getDealTypes();
            foreach ($list as $key => &$item) {
                // 预约启动类型名称
                $item['reserve_type_name'] = !empty(ReserveMatchEnum::$reserveTypeConfig[$item['reserve_type']]) ? ReserveMatchEnum::$reserveTypeConfig[$item['reserve_type']] : '未知类型';
                $item['reserve_type_name'] = str_replace('投资', '出借', $item['reserve_type_name']);
                // 产品类型
                $item['type_name'] = !empty($dealTypeMap[$item['type_id']]['name']) ? $dealTypeMap[$item['type_id']]['name'] : '未知类别';
                $item['entra_name'] = '';
                if ( !empty($item['entra_id']) ) {
                    $entra = $entraService->getReserveEntraById($item['entra_id']);
                    $item['entra_name'] = $this->getEntraName($entra['deal_type'], $entra['invest_line'], $entra['invest_unit'], $entra['invest_rate'], $entra['loantype']);
                }

                // 备注
                $item['remark'] = htmlspecialchars_decode($item['remark']);
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 新增预约匹配
     */
    public function reservematch_add() {
        // 产品类别
        $data = array('dealTypeId'=>-1);
        $dealTypeTree = MI('DealLoanType')->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->findAll();
        if (!empty($dealTypeTree)) {
            foreach ($dealTypeTree as $item) {
                // 记录“资产管理计划”
                if (DealLoanTypeEnum::TYPE_GLJH == $item['type_tag']) {
                    $data['dealTypeId'] = $item['id'];
                    $data['dealTypeTips'] = sprintf('业务需求【%s】应该选择【人工直接 投资+预约投资】，当前不符合业务需求，是否继续操作?', $item['name']);
                }
                $data['dealTypeMap'][] = array('id'=>$item['id'], 'name'=>$item['name'], 'typeTag'=>$item['type_tag']);
            }
        }
        $entraService = new ReservationEntraService();
        $entraList = $entraService->getReserveEntraList(-1);
        // 预约入口配置
        $data['entra_conf'] = array();
        foreach ($entraList as $item) {
            $data['entra_conf'][$item['id']]
                = $this->getEntraName($item['deal_type'], $item['invest_line'], $item['invest_unit'], $item['invest_rate'], $item['loantype']);
        }

        //借款类型
        $dealTypeList = $this->getDealTypes();

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post')
        {
            $params = array();
            // 预约服务启动类型
            $params['reserveType'] = isset($_POST['reserve_type']) ? intval($_POST['reserve_type']) : ReserveMatchEnum::RESERVE_TYPE_DEFAULT_RESERVING;
            // 产品类型
            $typeIdString = isset($_POST['type_id']) ? $_POST['type_id'] : '';
            $typeIdArray = !empty($typeIdString) ? explode('_', $typeIdString) : array();
            $params['typeId'] = isset($typeIdArray[0]) ? intval($typeIdArray[0]) : 0;
            $params['typeTag'] = isset($typeIdArray[1]) ? htmlspecialchars($typeIdArray[1]) : '';
            //预约入口
            $entraId = isset($_POST['entra_id']) ? $_POST['entra_id'] : 0;
            $entra = $entraService->getReserveEntraById($entraId); //查询预约入口
            if (empty($entra)) {
                $this->error('预约入口不存在');
            }
            $params['entraId'] = $entraId;

            // 状态
            $params['isEffect'] = isset($_POST['is_effect']) ? intval($_POST['is_effect']) : ReserveMatchEnum::IS_EFFECT_INVALID;
            // 备注
            $params['remark'] = !empty($_POST['remark']) ? $_POST['remark'] : '';
            if (empty($params['reserveType']) || !is_numeric($params['reserveType'])) {
                $this->error('预约服务启动类型不能为空或不合法');
            }
            if (empty($params['typeId']) || !is_numeric($params['typeId']) || empty($params['typeTag'])) {
                $this->error('产品类型不能为空或不合法');
            }
            //废弃字段
            $params['investConf'] = [];
            $params['advisoryId'] = 0;
            $params['projectIdsArray'] = [];

            // TAG名称
            $params['tagName'] = $params['reserveType'] == ReserveMatchEnum::RESERVE_TYPE_DEFAULT_RESERVING ? ReserveMatchEnum::TAGNAME_RESERVATION_1 : ReserveMatchEnum::TAGNAME_RESERVATION_2;
            $reservationMatch = new ReservationMatchService();
            $createRet = $reservationMatch->createReserveMatch($params);
            if ('00' !== $createRet['respCode']) {
                $respMsg = '04' == $createRet['respCode'] ? sprintf('当前规则与规则编号%d存在冲突，请验证', $createRet['data']['id']) : $createRet['respMsg'];
                $this->error($respMsg);
            }
            $this->success('提交成功');
        }
        $this->assign('data', $data);
        $this->assign('dealTypeList', $dealTypeList);
        $this->display();
    }

    /**
     * 编辑预约匹配
     */
    public function reservematch_edit() {
        // 配置ID
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        // 产品类别
        $data = array('dealTypeId'=>-1);
        $dealTypeTree = MI('DealLoanType')->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->findAll();
        if (!empty($dealTypeTree)) {
            foreach ($dealTypeTree as $item) {
                // 记录“资产管理计划”
                if (DealLoanTypeEnum::TYPE_GLJH == $item['type_tag']) {
                    $data['dealTypeId'] = $item['id'];
                    $data['dealTypeTips'] = sprintf('业务需求【%s】应该选择【人工直接 投资+预约投资】，当前不符合业务需求，是否继续操作?', $item['name']);
                }
                $data['dealTypeMap'][] = array('id'=>$item['id'], 'name'=>$item['name'], 'typeTag'=>$item['type_tag']);
            }
        }
        // 获取该配置数据
        $reserveMatchData = ReservationMatchModel::instance()->getReserveMatchById($_REQUEST['id']);
        if (empty($reserveMatchData)) {
            $this->error('该配置不存在');
        }
        $reserveMatchData['remark'] = htmlspecialchars_decode($reserveMatchData['remark']);

        $entraService = new ReservationEntraService();
        $entraList = $entraService->getReserveEntraList(-1);
        // 预约入口配置
        $data['entra_conf'] = array();
        foreach ($entraList as $item) {
            $data['entra_conf'][$item['id']]
                = $this->getEntraName($item['deal_type'], $item['invest_line'], $item['invest_unit'], $item['invest_rate'], $item['loantype']);
        }

        //借款类型
        $dealTypeList = $this->getDealTypes();

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = array();
            // 配置ID
            $params['id'] = isset($_POST['id']) ? intval($_POST['id']) : 0;
            // 预约服务启动类型
            $params['reserveType'] = isset($_POST['reserve_type']) ? intval($_POST['reserve_type']) : ReserveMatchEnum::RESERVE_TYPE_DEFAULT_RESERVING;
            // 产品类型
            $typeIdString = isset($_POST['type_id']) ? $_POST['type_id'] : '';
            $typeIdArray = !empty($typeIdString) ? explode('_', $typeIdString) : array();
            $params['typeId'] = isset($typeIdArray[0]) ? intval($typeIdArray[0]) : 0;
            $params['typeTag'] = isset($typeIdArray[1]) ? htmlspecialchars($typeIdArray[1]) : '';

            //预约入口
            $entraId = isset($_POST['entra_id']) ? $_POST['entra_id'] : 0;
            $entra = $entraService->getReserveEntraById($entraId); //查询预约入口
            if (empty($entra)) {
                $this->error('预约入口不存在');
            }
            $params['entraId'] = $entraId;
            // 状态
            $params['isEffect'] = isset($_POST['is_effect']) ? intval($_POST['is_effect']) : ReserveMatchEnum::IS_EFFECT_INVALID;
            // 备注
            $params['remark'] = !empty($_POST['remark']) ? $_POST['remark'] : '';
            if (empty($params['id'])) {
                $this->error('配置ID不合法');
            }
            if (empty($params['reserveType']) || !is_numeric($params['reserveType'])) {
                $this->error('预约服务启动类型不能为空或不合法');
            }
            if (empty($params['typeId']) || !is_numeric($params['typeId']) || empty($params['typeTag'])) {
                $this->error('产品类型不能为空或不合法');
            }
            //废弃字段
            $params['investConf'] = [];
            $params['advisoryId'] = 0;
            $params['projectIdsArray'] = [];

            // TAG名称
            $params['tagName'] = $params['reserveType'] == ReserveMatchEnum::RESERVE_TYPE_DEFAULT_RESERVING ? ReserveMatchEnum::TAGNAME_RESERVATION_1 : ReserveMatchEnum::TAGNAME_RESERVATION_2;
            $reservationMatch = new ReservationMatchService();
            $createRet = $reservationMatch->updateReserveMatch($params);
            if ('00' !== $createRet['respCode']) {
                $respMsg = '04' == $createRet['respCode'] ? sprintf('当前规则与规则编号%d存在冲突，请验证', $createRet['data']['id']) : $createRet['respMsg'];
                $this->error($respMsg);
            }
            $this->success('更新成功');
        }
        $this->assign('data', $data);
        $this->assign('matchData', $reserveMatchData);
        $this->assign('dealTypeList', $dealTypeList);
        $this->display();
    }

    /**
     * 企业用户预约-首页
     */
    public function enterprise_index()
    {
        // 获取预约列表
        $map = [];
        $map['deal_type'] = 0;
        // 预约状态
        $_REQUEST['reserve_status'] = isset($_REQUEST['reserve_status']) ? $_REQUEST['reserve_status'] : 0;
        if (intval($_REQUEST['reserve_status']) >= 0) {
            $map['reserve_status'] = intval($_REQUEST['reserve_status']);
        }
        // 用户姓名
        if(!empty($_REQUEST['real_name'])) {
            $real_name = addslashes(trim($_REQUEST['real_name']));
            $ids = UserService::getUserIdByRealName($real_name);
            $map['user_id'] = array('in', $ids);
        }
        // 用户手机号
        if(!empty($_REQUEST['mobile'])) {
            $user = UserService::getUserByMobile(floatval($_REQUEST['mobile']));
            if (!empty($user['id'])) {
                $map['user_id'] = $user['id'];
            }
        }
        // 用户UID
        if (!empty($_REQUEST['user_id'])) {
            $accountIds = AccountService::getAccountIdsByUserId($_REQUEST['user_id']);
            !empty($accountIds) && $map['user_id'] = array('in', $accountIds);
        }
        // 预约来源
        $map['reserve_referer'] = ReserveEnum::RESERVE_REFERER_ADMIN;
        $list = $this->_getReserveList($map, 'end_time', false);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 企业用户预约-提交页面
     */
    public function enterprise_reserve()
    {
        $type = ReserveConfEnum::TYPE_NOTICE_P2P;
        // 获取后台配置的预约标配置信息
        $dealTypes = $this->getDealTypes();

        $entraService = new ReservationEntraService();
        $entraList = $entraService->getReserveEntraList();
        // 预约入口配置
        $data['entra_conf'] = array();
        foreach ($entraList as $item) {
            $data['entra_conf'][$item['id']]
                = $this->getEntraName($item['deal_type'], $item['invest_line'], $item['invest_unit'], $item['invest_rate'], $item['loantype']);
        }

        $reservationConfModel = new ReservationConfModel;
        $reserveConf = $reservationConfModel->getReserveInfoByType($type);
        // 获取预约期限
        $data['reserve_conf'] = array();
        if (!empty($reserveConf['reserve_conf'])) {
            foreach ($reserveConf['reserve_conf'] as $key => $item) {
                if (!empty(ReserveEnum::$expireUnitConfig[$item['expire_unit']])) {
                    $expireDisplayName = $item['expire'] . ReserveEnum::$expireUnitConfig[$item['expire_unit']];
                } else {
                    $expireDisplayName = $item['expire'] . ReserveEnum::$expireUnitConfig[ReserveEnum::EXPIRE_UNIT_HOUR];
                }
                $data['reserve_conf'][$item['expire'].'_'.$item['expire_unit']] = array('key'=>$key, 'expire'=>$item['expire'], 'expire_unit'=>$item['expire_unit'], 'expire_display_name'=>$expireDisplayName);
            }
        }

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            // 企业用户ID
            $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
            // 预约授权金额
            $reserveAmountCent = isset($_POST['amount']) ? intval(bcmul($_POST['amount'], 100)) : 0;
            // 预约入口
            $entraId = !empty($_POST['entra_id']) ? addslashes($_POST['entra_id']) : 0;
            // 预约期限
            $expire = !empty($_POST['expire']) ? htmlspecialchars($_POST['expire']) : '';
            if (empty($uid) || !is_numeric($uid)) {
                $this->error('用户ID不能为空或不合法');
            }
            if (empty($reserveAmountCent) || !is_numeric($reserveAmountCent)) {
                $this->error('预约授权金额不能为空或不合法');
            }
            if (empty($entraId)) {
                $this->error('预约入口不能为空');
            }
            if (empty($expire)) {
                $this->error('预约期限不能为空');
            }
            // 获取用户信息，否则白名单获取不到
            $GLOBALS['user_info'] = UserService::getUserById($uid);
            if (empty($GLOBALS['user_info'])) {
                $this->error('用户不存在');
            }
            // 检查存管的预约开关
            if ((int)app_conf('SUPERVISION_RESERVE_SWITCH') === 1) {
                //存管降级
                if (SupervisionService::isServiceDown()) {
                    $this->error(SupervisionService::maintainMessage());
                }
                $supervisionAccountObj = new SupervisionAccountService();
                $accountId = AccountService::getUserAccountId($uid, UserAccountEnum::ACCOUNT_INVESTMENT);
                if (empty($accountId)) {
                    $this->error('用户未开通投资账户');
                }
                $isOpenAccount = $supervisionAccountObj->isSupervisionUser($accountId);
                // 检查用户是否开通快捷投资服务
                $grantInfo = AccountAuthService::checkAccountAuth($accountId);
                if ($isOpenAccount === 0 && !empty($grantInfo)) {
                    $this->error('该用户网贷 P2P账户和快捷投资服务未开通，请联系用户开通否则无法进行预约。');
                } else if ($isOpenAccount === 0) {
                    $this->error('该用户网贷 P2P账户未开通，请联系用户开通否则无法进行预约。');
                } else if (!empty($grantInfo)) {
                    $this->error('该用户快捷投资服务未开通，请联系用户开通否则无法进行预约。');
                }
            }

            $entra = $entraService->getReserveEntraById($entraId); //查询预约入口
            if (empty($entra['status'])) {
                $this->error('预约入口不存在');
            }
            $investDead = $entra['invest_line'];
            $investDeadUnit = $entra['invest_unit'];
            $dealType = $entra['deal_type'];
            $investRate = $entra['invest_rate'];
            $loantype = $entra['loantype'];

            // 最低预约金额,单位分
            $minAmountConf = !empty($entra['min_amount']) ? $entra['min_amount'] : 1;
            // 最高预约金额,单位分
            $maxAmountConf = !empty($entra['max_amount']) ? $entra['max_amount'] : 9999999900;
            if ($reserveAmountCent < $minAmountConf || $reserveAmountCent < 10000) {
                $this->error(sprintf('授权金额不能低于%s元，不能低于最低预约授权金额', bcdiv($minAmountConf, 100, 2)));
            }
            if (!empty($maxAmountConf) && $reserveAmountCent > $maxAmountConf) {
                $this->error(sprintf('授权金额不能高于%s元，不能高于最高预约授权金额', bcdiv($maxAmountConf, 100, 2)));
            }
            // 预约期限
            list($expireValue, $expireUnit) = explode('-', $expire, 2);
            if (empty($data['reserve_conf'][$expireValue.'_'.$expireUnit])) {
                $this->error('预约期限不合法');
            }

             if(!UserService::isEnterprise($uid)) {
                 // 不是企业用户检查风险承受
                 $dealProjectService = new DealProjectRiskAssessmentService();
                 $reservationConfService = new ReservationConfService();
                 $rissAssessemntService = new RiskAssessmentService();
                 $riskData = $rissAssessemntService->getUserRiskAssessmentData($uid);
                 if ($riskData['needForceAssess'] == 1){
                     $this->error('请完成风险评估');
                 }
                 $projectScoure = $reservationConfService->getScoreByDeadLine($investDead,$investDeadUnit, $dealType, $investRate, $loantype);

                 if ($projectScoure == false){
                     $this->error( '当前您的风险承受能力为“'.$riskData['last_level_name'].'” 暂不能预约此项目');

                 }
                 $dealProjectRiskRet = $dealProjectService->checkReservationRisk($uid,$projectScoure,false,$riskData);
                 if ($dealProjectRiskRet['result'] == false){
                     $this->error('当前您的风险承受能力为“'.$riskData['last_level_name'].'” 暂不能预约此项目');
                 }
             }
             $accountId = AccountService::getUserAccountId($uid, UserAccountEnum::ACCOUNT_INVESTMENT);

             //检查账户用途
             if (empty($accountId)) {
                 $this->error('用户未开通投资账户');
             }

             // 检查授权
             $supervisionService = new SupervisionService();
             // 检查用户是否开通快捷投资服务
             $grantInfo = AccountAuthService::checkAccountAuth($accountId);
             if (!empty($grantInfo)) {
                 $this->error('您未授权免密投资，暂无法预约');
             }

            // 创建用户预约投标记录
            $userReservationService = new UserReservationService();
            $createRet = $userReservationService->createUserReserve($accountId, $reserveAmountCent, $investDead, $expireValue, '', $investDeadUnit, $expireUnit, $reserveConf, ReserveEnum::RESERVE_REFERER_ADMIN, 100, [], 0, $dealType, $loantype, $investRate);
            if (false == $createRet['ret']) {
                $this->error($createRet['errorMsg']);
            }
            $this->success('预约成功', 0, u('UserReservation/enterprise_index?reserve_status=0&user_id=' . $uid));
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 取消预约
     */
    public function reserve_cancel() {
        $id = (int)$_REQUEST['id'];
        $userId = (int)$_REQUEST['uid'];
        if (!is_numeric($id) || !is_numeric($userId)) {
            $this->error('参数错误');
        }
        // 查询预约记录是否存在
        $reserveInfo = UserReservationModel::instance()->getUserReserveById($id, $userId);
        if (empty($reserveInfo)) {
            $this->error('该预约记录不存在');
        }
        if ($reserveInfo['reserve_status'] == ReserveEnum::RESERVE_STATUS_END) {
            $this->error('该预约记录已经取消');
        }
        $userReservationObj = new UserReservationService();
        $ret = $userReservationObj->cancelUserReserve($id, $userId);
        $retMsg = '随心约预约列表-取消预约，预约id['.$id.']，会员id['.$userId.']';
        if (false === $ret) {
            save_log($retMsg . '操作失败', 0);
            $this->error('取消预约失败');
        }
        save_log($retMsg . '操作成功', 1);
        $this->success('取消预约成功');
    }

    /**
     * 预约卡片首页
     * 废弃
     */
    public function old_reservecard() {
        // 获取预约卡片列表
        $reservationCardService = new ReservationCardService();
        $list = $reservationCardService->getReserveCardListByAdmin(-1);
        $dealTypes = $this->getDealTypes();
        if (!empty($list)) {
            foreach ($list as $key => &$item) {
                //普惠后台只显示网贷
                if ($item['deal_type'] != 0) {
                    unset($list[$key]);
                    continue;
                }
                // 投资期限+单位
                $item['invest_line_unit'] = $item['invest_line'] . ReserveEnum::$investDeadLineUnitConfig[$item['invest_unit']];
                // app里显示的标签名称
                $item['label_name'] = sprintf('%s %s', $item['label_before'], $item['label_after']);
                $item['deal_type_name'] =  $dealTypes[$item['deal_type']];
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 新增预约卡片
     * 废弃
     */
    public function old_reservecard_add() {
        $data = array('investConf'=>array());
        // 获取“预约排价表”的投资期限
        // 获取预约配置
        $reserveInfo = ReservationConfModel::instance()->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
        if (!empty($reserveInfo['invest_conf'])) {
            $data['investConf'] = $reserveInfo['invest_conf'];
            foreach ($reserveInfo['invest_conf'] as $key => $item) {
                $data['investConf'][$key]['invest_line_name'] = $item['deadline'] . ReserveEnum::$investDeadLineUnitConfig[$item['deadline_unit']];
            }
        }

        // 期限配置
        $investList = $this->getInvestList();
        $data['deadlineConf'] = $investList['line_list'];

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = array();
            // 投资期限
            $investLineUnit = isset($_POST['invest_line_unit']) ? addslashes($_POST['invest_line_unit']) : '';
            $investLineUnitArray = !empty($investLineUnit) ? explode('_', $investLineUnit) : array();
            $params['investLine'] = isset($investLineUnitArray[0]) ? intval($investLineUnitArray[0]) : 0;
            $params['investUnit'] = isset($investLineUnitArray[1]) ? intval($investLineUnitArray[1]) : '';
            // 按钮
            $params['buttonName'] = isset($_POST['button_name']) ? addslashes($_POST['button_name']) : '';
            // 前标签
            $params['labelBefore'] = isset($_POST['label_before']) ? addslashes($_POST['label_before']) : '';
            //贷款类型
            $params['dealType'] = isset($_POST['dealType']) ? intval($_POST['dealType']) : '0';
            // 后标签
            $params['labelAfter'] = isset($_POST['label_after']) ? addslashes($_POST['label_after']) : '';
            // 是否启用今天预约人数
            $params['displayPeople'] = isset($_POST['display_people']) ? intval($_POST['display_people']) : 0;
            // 是否启用累积金额
            $params['displayMoney'] = isset($_POST['display_money']) ? intval($_POST['display_money']) : 0;
            // 产品详情
            $params['description'] =  isset($_POST['description']) ? $_POST['description'] : '';

            // 状态
            $params['status'] = isset($_POST['status']) ? intval($_POST['status']) : ReserveCardEnum::STATUS_UNVALID;
            if (empty($params['investLine']) || !is_numeric($params['investLine']) || empty($params['investUnit'])) {
                $this->error('投资期限不能为空');
            }
            if (!empty($reserveInfo['invest_conf'])) {
                $investRet = false;
                foreach ($reserveInfo['invest_conf'] as $item) {
                    if (intval($item['deadline']) == intval($params['investLine']) && intval($item['deadline_unit']) == intval($params['investUnit'])) {
                        //检查贷款类型
                        $item['deal_type'] = isset($item['deal_type']) ? $item['deal_type'] : 0;
                        if ($item['deal_type'] == $params['dealType']) {
                            $investRet = true;
                            break;
                        }
                    }
                }
                if (!$investRet) {
                    $this->error('投资期限、贷款类型不合法');
                }
            }
            if (empty($params['buttonName'])) {
                $this->error('按钮名称不能为空');
            }
            if (empty($params['description'])) {
                $this->error('产品详情不能为空');
            }
            $reservationCard = new ReservationCardService();
            $editRet = $reservationCard->editReserveCard($params);
            if ('00' !== $editRet['errorCode']) {
                $this->error($editRet['errorMsg']);
            }
            $this->success('提交成功');
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 编辑预约卡片
     * 废弃
     */
    public function old_reservecard_edit() {
        // 卡片ID
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $data = array('investConf'=>array());
        // 获取“预约排价表”的投资期限
        // 获取预约配置
        $reserveInfo = ReservationConfModel::instance()->getReserveInfoByType(ReserveConfEnum::TYPE_CONF);
        if (!empty($reserveInfo['invest_conf'])) {
            $data['investConf'] = $reserveInfo['invest_conf'];
            foreach ($reserveInfo['invest_conf'] as $key => $item) {
                $data['investConf'][$key]['invest_line_name'] = $item['deadline'] . ReserveEnum::$investDeadLineUnitConfig[$item['deadline_unit']];
            }
        }
        // 期限配置
        $entraService = new ReservationEntraService();
        $investList = $this->getInvestList();
        $data['deadlineConf'] = $investList['line_list'];

        // 获取卡片信息
        $reservationCardService = new ReservationCardService();
        $cardInfo = $reservationCardService->getReserveInfoById($id);
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = array();
            // 自增ID
            $params['id'] = isset($_POST['id']) ? intval($_POST['id']) : 0;
            // 投资期限
            $investLineUnit = isset($_POST['invest_line_unit']) ? addslashes($_POST['invest_line_unit']) : '';
            $investLineUnitArray = !empty($investLineUnit) ? explode('_', $investLineUnit) : array();
            $params['investLine'] = isset($investLineUnitArray[0]) ? intval($investLineUnitArray[0]) : 0;
            $params['investUnit'] = isset($investLineUnitArray[1]) ? intval($investLineUnitArray[1]) : '';
            // 按钮
            $params['buttonName'] = isset($_POST['button_name']) ? addslashes($_POST['button_name']) : '';
            // 前标签
            $params['labelBefore'] = isset($_POST['label_before']) ? addslashes($_POST['label_before']) : '';
            //贷款类型
            $params['dealType'] = isset($_POST['dealType']) ? intval($_POST['dealType']) : '0';
            // 后标签
            $params['labelAfter'] = isset($_POST['label_after']) ? addslashes($_POST['label_after']) : '';
            // 是否启用今天预约人数
            $params['displayPeople'] = isset($_POST['display_people']) ? intval($_POST['display_people']) : 0;
            // 是否启用累积金额
            $params['displayMoney'] = isset($_POST['display_money']) ? intval($_POST['display_money']) : 0;
            // 产品详情
            $params['description'] =  isset($_POST['description']) ? $_POST['description'] : '';
            // 状态
            $params['status'] = isset($_POST['status']) ? intval($_POST['status']) : ReserveCardEnum::STATUS_UNVALID;
            if (empty($params['investLine']) || !is_numeric($params['investLine']) || empty($params['investUnit'])) {
                $this->error('投资期限不能为空');
            }
            if (!empty($reserveInfo['invest_conf'])) {
                $investRet = false;
                foreach ($reserveInfo['invest_conf'] as $item) {
                    if (intval($item['deadline']) == intval($params['investLine']) && intval($item['deadline_unit']) == intval($params['investUnit'])) {
                        //检查贷款类型
                        $item['deal_type'] = isset($item['deal_type']) ? $item['deal_type'] : 0;
                        if ($item['deal_type'] == $params['dealType']) {
                            $investRet = true;
                            break;
                        }
                    }
                }
                if (!$investRet) {
                    $this->error('投资期限、贷款类型不合法');
                }
            }
            if (empty($params['buttonName'])) {
                $this->error('按钮名称不能为空');
            }
            if (empty($params['description'])) {
                $this->error('产品详情不能为空');
            }
            $reservationCard = new ReservationCardService();
            $editRet = $reservationCard->editReserveCard($params);
            if ('00' !== $editRet['errorCode']) {
                $this->error($editRet['errorMsg']);
            }
            $this->success('编辑成功');
        }
        $this->assign('data', $data);
        $this->assign('cardInfo', $cardInfo);
        $this->display();
    }

    /**
     * 触发规则管理列表首页
     */
    public function rule() {
        // 获取触发规则管理列表
        $list = OtoTriggerRuleModel::instance()->getRuleList();
        $entraService = new ReservationEntraService();
        if (!empty($list)) {
            foreach ($list as $key => $item) {
                if ( !empty($item['entra_id']) ) {
                    $entra = $entraService->getReserveEntraById($item['entra_id']);
                    $list[$key]['entra_name'] = $this->getEntraName($entra['deal_type'], $entra['invest_line'], $entra['invest_unit'], $entra['invest_rate'], $entra['loantype']);
                }

                //咨询机构
                if (empty($item['company'])) {
                    $list[$key]['company_name'] = '';
                } else {
                    $dealAdvisoryMap = array();
                    $dealAdvisory = MI('DealAgency')->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
                    if (!empty($dealAdvisory)) {
                        foreach ($dealAdvisory as $daItem) {
                            $dealAdvisoryName = !empty($daItem['short_name']) ? $daItem['short_name'] : $daItem['name'];
                            $dealAdvisoryMap[$daItem['id']] = $dealAdvisoryName;
                        }
                    }
                    $list[$key]['company_name'] = $dealAdvisoryMap[$item['company']];
                }

                // 触发内容
                $triggerTmp = array();
                $triggerInfo = json_decode($item['trigger_info'], true);
                if (!empty($triggerInfo)) {
                    foreach ($triggerInfo as $trigger) {
                        $triggerTmp[] = sprintf('累计%s-%s元，%s，%s， 红包比例%s%%', $trigger['down_amount'], $trigger['up_amount'], O2oEnum::$giftTypeConfig[$trigger['award_type']], $trigger['award_id'], $trigger['rate']);
                    }
                }
                $list[$key]['trigger_name'] = join('<br />', $triggerTmp);
                $list[$key]['use_date'] = sprintf('%s—%s', date('Y-m-d', $item['use_start_time']), date('Y-m-d', $item['use_end_time']));
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 触发规则管理新增
     */
    public function rule_add() {
        $data = array();
        $entraService = new ReservationEntraService();
        $entraList = $entraService->getReserveEntraList(-1);
        // 预约入口配置
        $data['entra_conf'] = array();
        foreach ($entraList as $item) {
            $data['entra_conf'][$item['id']]
                = $this->getEntraName($item['deal_type'], $item['invest_line'], $item['invest_unit'], $item['invest_rate'], $item['loantype']);
        }

        //咨询机构
        $dealAdvisoryMap = array();
        $dealAdvisory = MI('DealAgency')->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        if (!empty($dealAdvisory)) {
            foreach ($dealAdvisory as $item) {
                $dealAdvisoryName = !empty($item['short_name']) ? $item['short_name'] : $item['name'];
                $data['dealAdvisoryMap'][] = array('id'=>$item['id'], 'name'=>$dealAdvisoryName);
            }
        }

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = $paramsRule = array();
            $params['type'] = O2oEnum::TYPE_ACCUMULATE;
            // 咨询机构
            $params['company'] = isset($_POST['advisory_id']) ? intval($_POST['advisory_id']) : 0;
            // 触发规则-累计金额起始值
            $paramsRule['downAmount'] = !empty($_POST['down_amount']) ? $_POST['down_amount'] : array();
            // 触发规则-累计金额结束值
            $paramsRule['upAmount'] = !empty($_POST['up_amount']) ? $_POST['up_amount'] : array();
            // 触发规则-礼品类型
            $paramsRule['awardType'] = !empty($_POST['award_type']) ? $_POST['award_type'] : array();
            // 触发规则-券ID
            $paramsRule['awardId'] = !empty($_POST['award_id']) ? $_POST['award_id'] : array();
            // 触发规则-年化投资额返红包比例
            $paramsRule['rate'] = !empty($_POST['rate']) ? $_POST['rate'] : array();
            // 有效开始时间
            $params['use_start_time'] = isset($_POST['use_start_time']) ? strtotime(addslashes($_POST['use_start_time'] . ' 00:00:00')) : '';
            // 有效结束时间
            $params['use_end_time'] = isset($_POST['use_end_time']) ? strtotime(addslashes($_POST['use_end_time'] . ' 23:59:59')) : '';
            // 状态
            $params['status'] = isset($_POST['status']) ? intval($_POST['status']) : O2oEnum::STATUS_VALID;
            if ($params['use_end_time'] <= $params['use_start_time']) {
                $this->error('请输入正确的起止时间');
            }

            // 预约入口
            $entraId = isset($_POST['entra_id']) ? intval($_POST['entra_id']) : 0;
            $entra = $entraService->getReserveEntraById($entraId); //查询预约入口
            if (empty($entra)) {
                $this->error('预约入口不存在');
            }
            $params['entra_id'] = $entraId;

            if (empty($paramsRule['downAmount'])) {
                $this->error('累计投资金额不能为空');
            }
            $ruleConf = array();
            foreach ($paramsRule['downAmount'] as $key => $value) {
                if (empty($value) || !is_numeric($value) || empty($paramsRule['upAmount'][$key]) || !is_numeric($paramsRule['upAmount'][$key])) {
                    $this->error('累计投资金额都不能为空');
                }
                if (empty($paramsRule['awardType'][$key]) || !is_numeric($paramsRule['awardType'][$key])) {
                    $this->error('券ID不能为空');
                }
                if (empty($paramsRule['awardId'][$key])) {
                    $this->error('券ID不能为空');
                }
                // 奖品类型==礼券时，年化投资返红包比例不能为空
                if ($paramsRule['awardType'][$key] == O2oEnum::GIFT_TYPE_COUPON && (empty($paramsRule['rate'][$key]) || !is_numeric($paramsRule['rate'][$key]))) {
                    $this->error('年化投资额返红包比例不能为零或空');
                }
                $upAmount = intval($paramsRule['upAmount'][$key]);
                if ($value >= $upAmount) {
                    $this->error('累计投资成功金额,下限必须小于上限');
                }
                // 券ID
                $awardIdTmp = explode(',', trim($paramsRule['awardId'][$key]));
                $awardIdString = array_filter(array_unique(array_map('intval', $awardIdTmp)), 'strlen');
                foreach ($awardIdString as $awardKey => $awardValue) {
                    if (empty($awardValue) || !is_numeric($awardValue)) {
                        unset($awardIdString[$awardKey]);
                    }
                }
                if (empty($awardIdString)) {
                    $this->error('券ID不合法');
                }

                $ruleConf[] = array(
                        'down_amount' => intval($value),
                        'up_amount' => $upAmount,
                        'award_type' => intval($paramsRule['awardType'][$key]),
                        'award_id' => join(',', $awardIdString),
                        'rate' => addslashes($paramsRule['rate'][$key]),
                );
            }
            $params['trigger_info'] = json_encode($ruleConf);
            if ($params['status'] == O2oEnum::STATUS_VALID) {
                $checkRule = $this->checkUniqueRule($params['entra_id'], $params['company']);
                if ($checkRule) {
                    $this->error("规则提交失败，与已有规则{$checkRule}重复");
                }
            }
            $params['bid_time_limit'] = $params['bid_time_type'] = 0; //废弃字段

            $insertRet = OtoTriggerRuleModel::instance()->addRecord($params);
            if (false === $insertRet) {
                $this->error('规则提交失败，请稍后重试');
            }
            $this->success('提交成功');
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 触发规则管理编辑
     */
    public function rule_edit() {
        // 规则ID
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $data = array();
        $entraService = new ReservationEntraService();
        $entraList = $entraService->getReserveEntraList(-1);
        // 预约入口配置
        $data['entra_conf'] = array();
        foreach ($entraList as $item) {
            $data['entra_conf'][$item['id']]
                = $this->getEntraName($item['deal_type'], $item['invest_line'], $item['invest_unit'], $item['invest_rate'], $item['loantype']);
        }

        //咨询机构
        $dealAdvisoryMap = array();
        $dealAdvisory = MI('DealAgency')->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        if (!empty($dealAdvisory)) {
            foreach ($dealAdvisory as $item) {
                $dealAdvisoryName = !empty($item['short_name']) ? $item['short_name'] : $item['name'];
                $data['dealAdvisoryMap'][] = array('id'=>$item['id'], 'name'=>$dealAdvisoryName);
            }
        }
        // 获取触发规则
        $info = OtoTriggerRuleModel::instance()->getTriggerRuleOneById($id);
        if (!empty($info)) {
            $info['use_start_date'] = date('Y-m-d', $info['use_start_time']);
            $info['use_end_date'] = date('Y-m-d', $info['use_end_time']);
            $info['trigger_list'] = json_decode($info['trigger_info'], true);
        }

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $params = $paramsRule = array();
            // 规则ID
            $params['id'] = intval($_POST['id']);
            $params['type'] = O2oEnum::TYPE_ACCUMULATE;
            // 咨询机构
            $params['company'] = isset($_POST['advisory_id']) ? intval($_POST['advisory_id']) : 0;
            // 触发规则-累计金额起始值
            $paramsRule['downAmount'] = !empty($_POST['down_amount']) ? $_POST['down_amount'] : array();
            // 触发规则-累计金额结束值
            $paramsRule['upAmount'] = !empty($_POST['up_amount']) ? $_POST['up_amount'] : array();
            // 触发规则-礼品类型
            $paramsRule['awardType'] = !empty($_POST['award_type']) ? $_POST['award_type'] : array();
            // 触发规则-券ID
            $paramsRule['awardId'] = !empty($_POST['award_id']) ? $_POST['award_id'] : array();
            // 触发规则-年化投资额返红包比例
            $paramsRule['rate'] = !empty($_POST['rate']) ? $_POST['rate'] : array();
            // 有效开始时间
            $params['use_start_time'] = isset($_POST['use_start_time']) ? strtotime(addslashes($_POST['use_start_time'] . ' 00:00:00')) : '';
            // 有效结束时间
            $params['use_end_time'] = isset($_POST['use_end_time']) ? strtotime(addslashes($_POST['use_end_time'] . ' 23:59:59')) : '';
            // 状态
            $params['status'] = isset($_POST['status']) ? intval($_POST['status']) : O2oEnum::STATUS_VALID;
            if ($params['use_end_time'] <= $params['use_start_time']) {
                $this->error('请输入正确的起止时间');
            }

            // 预约入口
            $entraId = isset($_POST['entra_id']) ? intval($_POST['entra_id']) : 0;
            $entra = $entraService->getReserveEntraById($entraId); //查询预约入口
            if (empty($entra)) {
                $this->error('预约入口不存在');
            }
            $params['entra_id'] = $entraId;

            if (empty($paramsRule['downAmount'])) {
                $this->error('累计投资金额不能为空');
            }
            $ruleConf = array();
            foreach ($paramsRule['downAmount'] as $key => $value) {
                if (empty($value) || !is_numeric($value) || empty($paramsRule['upAmount'][$key]) || !is_numeric($paramsRule['upAmount'][$key])) {
                    $this->error('累计投资金额都不能为空');
                }
                if (empty($paramsRule['awardType'][$key]) || !is_numeric($paramsRule['awardType'][$key])) {
                    $this->error('券ID不能为空');
                }
                if (empty($paramsRule['awardId'][$key])) {
                    $this->error('券ID不能为空');
                }
                // 奖品类型==礼券时，年化投资返红包比例不能为空
                if ($paramsRule['awardType'][$key] == O2oEnum::GIFT_TYPE_COUPON && (empty($paramsRule['rate'][$key]) || !is_numeric($paramsRule['rate'][$key]))) {
                    $this->error('年化投资额返红包比例不能为零或空');
                }
                $upAmount = intval($paramsRule['upAmount'][$key]);
                if ($value >= $upAmount) {
                    $this->error('累计投资成功金额,下限必须小于上限');
                }
                // 券ID
                $awardIdTmp = explode(',', trim($paramsRule['awardId'][$key]));
                $awardIdString = array_filter(array_unique(array_map('intval', $awardIdTmp)), 'strlen');
                foreach ($awardIdString as $awardKey => $awardValue) {
                    if (empty($awardValue) || !is_numeric($awardValue)) {
                        unset($awardIdString[$awardKey]);
                    }
                }
                if (empty($awardIdString)) {
                    $this->error('券ID不合法');
                }

                $ruleConf[] = array(
                    'down_amount' => intval($value),
                    'up_amount' => $upAmount,
                    'award_type' => intval($paramsRule['awardType'][$key]),
                    'award_id' => join(',', $awardIdString),
                    'rate' => addslashes($paramsRule['rate'][$key]),
                );
            }
            $params['trigger_info'] = json_encode($ruleConf);
            if ($params['status'] == O2oEnum::STATUS_VALID) {
                $checkRule = $this->checkUniqueRule($params['entra_id'], $params['company'], $params['id']);
                if ($checkRule) {
                    $this->error("规则提交失败，与已有规则{$checkRule}重复");
                }
            }
            $params['bid_time_limit'] = $params['bid_time_type'] = 0; //废弃字段

            $updateRet = OtoTriggerRuleModel::instance()->editRecord($params);
            if (false === $updateRet) {
                $this->error('规则编辑失败，请稍后重试');
            }
            $this->success('编辑成功');
        }
        $this->assign('data', $data);
        $this->assign('info', $info);
        $this->display();
    }

    private function checkUniqueRule($entraId, $company, $id = 0) {
        $condition = 'entra_id=:entra_id AND status=1';
        $params = array(':entra_id' => $entraId);
        $existRules = OtoTriggerRuleModel::instance()->findAll($condition, true, "*",$params);
        if (empty($existRules)) {
            return 0;
        } else {
            foreach($existRules as $rule) {
                if($id && ($id == $rule['id'])) {
                    continue;
                }
                if ($rule['company'] == $company) {
                    return $rule['id'];
                }
                if ($rule['company'] == 0 || ($company == 0)) {
                    return $rule['id'];
                }
            }
        }
    }

    /**
     * 获取预约列表
     * @param array $map
     * @param string $order
     * @param string $asc
     */
    private function _getReserveList($map, $order = 'end_time', $asc = false) {
        $model = DI($this->getActionName());
        $list = array();
        if (!empty($model)) {
            $this->_setPageEnable(false);
            $list = $this->_list($model, $map, $order, $asc);
        }
        $now = time();
        $dealTypes = $this->getDealTypes();

        //批量取用户信息
        $userList = $this->getUserListByArr($list);
        $loanTypeMap = $GLOBALS['dict']['LOAN_TYPE_CN'];
        foreach ($list as $key=>$value) {
            //$need_time = $value['end_time'] >= $now && $value['reserve_status'] != ReserveEnum::RESERVE_STATUS_END ? $value['end_time'] - $now : 0;
            //$need_hours = sprintf('%02d', floor($need_time / 3600));
            //$need_minutes = sprintf('%02d', floor(($need_time % 3600) / 60));
            //$need_seconds = sprintf('%02d', floor($need_time % 60));
            //$list[$key]['need_date'] = $need_hours . ':' . $need_minutes . ':' . $need_seconds;
            $list[$key]['is_cancel'] = $value['reserve_status'] == ReserveEnum::RESERVE_STATUS_END ? 0 : 1;
            $list[$key]['reserve_status_format'] = $value['reserve_status'] == ReserveEnum::RESERVE_STATUS_END ? '预约结束' : '预约中';
            $list[$key]['reserve_amount_format'] = bcdiv($value['reserve_amount'], 100, 2) . '元';
            $list[$key]['invest_amount_format'] = bcdiv($value['invest_amount'], 100, 2) . '元';
            $list[$key]['need_amount_format'] = bcdiv($value['reserve_amount'] - $value['invest_amount'], 100, 2) . '元';
            $list[$key]['invest_deadline_format'] = $value['invest_deadline'] . ReserveEnum::$investDeadLineUnitConfig[$value['invest_deadline_unit']];
            $user = $userList[$value['user_id']];
            $list[$key]['real_name'] = $user['real_name'];
            $list[$key]['mobile'] = $user['mobile'];
            $list[$key]['referer'] = ReserveEnum::$reserveRefererConfig[(int)$value['reserve_referer']];
            $list[$key]['deal_type_desc'] = $dealTypes[$value['deal_type']];
            $list[$key]['discount_status_desc'] = ReserveEnum::$discountStatusMap[$value['discount_status']];
            $list[$key]['loantype_name'] = isset($loanTypeMap[$value['loantype']]) ? $loanTypeMap[$value['loantype']] : '全部';
        }
        return $list;
    }

    /**
     * 资金分配比例
     */
    public function money_assign_ratio() {
       $list = ReservationMoneyAssignRatioModel::instance()->getMoneyAssignRatioList();
       foreach ($list as $key=>$value) {
           $list[$key]['type_name'] = DealLoanTypeModel::instance()->getLoanNameByTypeId($value['type_id']);
           $list[$key]['money_ratio_format'] = bcmul($value['money_ratio'], 100, 2) . '%';
           $list[$key]['money_limit_format'] = bccomp($value['money_limit'], 0, 2) === 0 ? '无限制' : $value['money_limit'] . '元';
           $list[$key]['invest_deadline_format'] = $value['invest_deadline'] . ReserveEnum::$investDeadLineUnitConfig[$value['invest_deadline_unit']];
       }
       $this->assign('list', $list);
       $this->display();
    }

    /**
     * 添加资金分配比例
     */
    public function money_assign_ratio_edit() {
        $list = ReservationMoneyAssignRatioModel::instance()->getMoneyAssignRatioList();
        foreach ($list as $key=>$value) {
           $list[$key]['type_name'] = DealLoanTypeModel::instance()->getLoanNameByTypeId($value['type_id']);
           $list[$key]['money_ratio_percent'] = bcmul($value['money_ratio'], 100, 2);
           $list[$key]['invest_deadline_format'] = $value['invest_deadline'] . '|' . $value['invest_deadline_unit'];
        }

        //资产类型
        $dealTypeMap = [];
        $dealTypeTree = MI('DealLoanType')->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->findAll();
        if (!empty($dealTypeTree)) {
            foreach ($dealTypeTree as $item) {
                $dealTypeMap[] = array('id'=>$item['id'], 'name'=>$item['name'], 'typeTag'=>$item['type_tag']);
            }
        }

        // 期限配置
        $investList = $this->getInvestList();
        $data['deadlineConf'] = $investList['line_list'];

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $idArr = isset($_POST['id']) ? $_POST['id'] : [];
            $typeIdArr = isset($_POST['type_id']) ? $_POST['type_id'] : [];
            $investDeadlineOptArr = isset($_POST['invest_deadline_opt']) ? $_POST['invest_deadline_opt'] : [];
            $moneyRatioArr = isset($_POST['money_ratio']) ? $_POST['money_ratio'] : [];
            $moneyLimitArr = isset($_POST['money_limit']) ? $_POST['money_limit'] : [];
            $isEffectArr = isset($_POST['is_effect']) ? $_POST['is_effect'] : [];
            $remarkArr = isset($_POST['remark']) ? $_POST['remark'] : [];
            if (empty($idArr) || empty($typeIdArr) || empty($investDeadlineOptArr) || empty($moneyRatioArr) || empty($moneyLimitArr) || empty($isEffectArr) || empty($remarkArr)) {
                $this->error('参数错误');
            }

            if (count($typeIdArr) > 2) {
                $this->error('最多添加两条配置');
            }

            $limitCount = 0;
            foreach($moneyLimitArr as $moneyLimit) {
                if (bccomp($moneyLimit, 0, 2) === 1) $limitCount ++;
            }
            if ($limitCount > 1) {
                $this->error('最多配置一项当日可匹配总金额');
            }

            //校验比例
            $tmp = [];
            foreach ($investDeadlineOptArr as $key=>$investDeadlineOpt) {
                if (!isset($tmp[$investDeadlineOpt])) $tmp[$investDeadlineOpt] = 0;
                $tmp[$investDeadlineOpt] = bcadd($tmp[$investDeadlineOpt], bcdiv($moneyRatioArr[$key], 100, 4), 2);
            }
            foreach ($tmp as $val) {
                if (bccomp($val, 1, 2) !== 0) {
                    $this->error('所有产品类型的比例加和需等于100%');
                }
            }

            $newIdArr = [];
            foreach ($idArr as $index => $id) {
                $newIdArr[] = $id;
                $investDeadlineOpt = $investDeadlineOptArr[$index];
                $arr = explode('|', $investDeadlineOpt);
                $investDeadline = $arr[0];
                $investDeadlineUnit = $arr[1];
                $params = [
                    'typeId' => $typeIdArr[$index],
                    'deadline' => $investDeadline,
                    'deadlineUnit' => $investDeadlineUnit,
                    'moneyRatio' => bcdiv($moneyRatioArr[$index], 100, 4),
                    'moneyLimit' => $moneyLimitArr[$index],
                    'isEffect' => $isEffectArr[$index],
                    'remark' => $remarkArr[$index],
                ];
                if (!empty($id)) {
                    $ret = ReservationMoneyAssignRatioModel::instance()->updateMoneyAssignRatio($id, $params);
                } else {
                    $ret = ReservationMoneyAssignRatioModel::instance()->addMoneyAssignRatio($params);
                }
                if (!$ret) {
                    $this->error('编辑失败');
                }
            }

            //清理资金分配比例
            foreach ($list as $key => $value) {
                if (!in_array($value['id'], $newIdArr)) {
                    $ret = ReservationMoneyAssignRatioModel::instance()->deleteMoneyAssignRatio($value['id']);
                }
                if (!$ret) {
                    $this->error('编辑失败');
                }
            }

            $this->success('编辑成功');
        }

        $this->assign('dealTypeMap', $dealTypeMap);
        $this->assign('deadlineConf', $data['deadlineConf']);
        $this->assign('list', $list);
        $this->assign('cnt', count($list));
        $this->display();
    }

    /**
     * 删除资金分配比例
     */
    public function money_assign_ratio_del() {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        if (empty($id)) {
            $this->error('参数错误');
        }
        $ret = ReservationMoneyAssignRatioModel::instance()->deleteMoneyAssignRatio($id);
        if (!$ret) {
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }

    /**
     * 获取已匹配金额
     */
    public function get_invest_amount_by_type() {
        $typeId = isset($_REQUEST['type_id']) ? $_REQUEST['type_id'] : 0;
        $deadline = isset($_REQUEST['deadline']) ? $_REQUEST['deadline'] : 0;
        $deadlineUnit = isset($_REQUEST['deadline_unit']) ? $_REQUEST['deadline_unit'] : 0;
        if (empty($deadline) || empty($deadlineUnit)) {
            $this->error('参数错误');
        }
        $userReservationService = new UserReservationService();
        $date = date('Y-m-d');
        $amountGroup = $userReservationService->getInvestAmountGroupByDate($date);
        $groupKey = $typeId . '_' . $deadline . '_' . $deadlineUnit;
        $amount = isset($amountGroup[$groupKey]) ? $amountGroup[$groupKey] : 0;
        $this->ajaxReturn($amount);
    }

    /**
     * 获取总剩余预约金额
     */
    public function get_total_reserve_amount() {
        $deadline = isset($_REQUEST['deadline']) ? $_REQUEST['deadline'] : 0;
        $deadlineUnit = isset($_REQUEST['deadline_unit']) ? $_REQUEST['deadline_unit'] : 0;
        if (empty($deadline) || empty($deadlineUnit)) {
            $this->error('参数错误');
        }
        $userReservationService = new UserReservationService();
        $amount = $userReservationService->getTotalEffectReserveAmount($deadline, $deadlineUnit);
        $this->ajaxReturn($amount);
    }

    /**
     * 获取分配的预约金额
     */
    public function get_reserve_amount_by_type() {
        $typeId = isset($_REQUEST['type_id']) ? $_REQUEST['type_id'] : 0;
        $deadline = isset($_REQUEST['deadline']) ? $_REQUEST['deadline'] : 0;
        $deadlineUnit = isset($_REQUEST['deadline_unit']) ? $_REQUEST['deadline_unit'] : 0;
        if (empty($typeId) || empty($deadline) || empty($deadlineUnit)) {
            $this->error('参数错误');
        }
        $userReservationService = new UserReservationService();
        $amount = $userReservationService->getEffectReserveAmountByTypeId($typeId, $deadline, $deadlineUnit);
        $this->ajaxReturn($amount);
    }

    /**
     * 查看合同文本内容
     */
    public function openContract(){
        $result = $this->_getContractContent();
        echo hide_message($result['content']);
    }

    /**
     * 下载合同pdf
     */
    public function download(){
        $result = $this->_getContractContent();
        $number = $result['number'];
        $file_name = $number.".pdf";
        $file_path = APP_ROOT_PATH.'../runtime/'.$file_name;
        $mkpdf = new Mkpdf ();
        $mkpdf->mk($file_path, $result['content']);
        header ( "Content-type: application/pdf");
        header ( 'Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header ( "Content-Length: " . filesize($file_path));
        readfile($file_path);
        @unlink($file_path);
    }

    /**
     * 根据预约id获取渲染后的合同文本
     */
    private function _getContractContent(){
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        if(empty($id)){
            $this->error('参数错误');
        }

        $contractParams = ContractDtService::getReservationContractParams($id);
        if(empty($contractParams['user_id'])){
            $this->error('预约id不存在');
        }
        $number = ContractService::createDtNumber($id,0);
        $contractContent = ContractPreService::getReservationContract($contractParams['user_id'], $contractParams['money'],
            $contractParams['invest_deadline'], $contractParams['invest_deadline_unit'], $contractParams['invest_rate'],
            $contractParams['start_time'],$number);
        return array(
            'content' => $contractContent,
            'number' => $number,
        );
    }

    /**
     * 下载打戳合同文件
     */
    public function downloadTsa(){
        $contractId = isset($_REQUEST['contract_id']) ? $_REQUEST['contract_id'] : 0;
        $reservationId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        if(empty($reservationId) || empty($contractId)){
            $this->error('参数错误');
        }
        ContractInvokerService::downloadTsa('dt',$contractId,$reservationId,ContractServiceEnum::SOURCE_TYPE_RESERVATION);
    }

}
