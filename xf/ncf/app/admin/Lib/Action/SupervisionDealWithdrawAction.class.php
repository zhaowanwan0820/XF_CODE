<?php
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use libs\utils\PaymentApi;
use libs\db\Db;
use core\enum\DealExtEnum;
use core\enum\SupervisionEnum;
use core\service\account\AccountService;
use core\service\user\BankService;
use core\service\user\UserService;
use core\service\supervision\SupervisionWithdrawService;
use core\dao\supervision\SupervisionWithdrawModel;
use core\dao\supervision\SupervisionWithdrawAuditModel;
use core\dao\deal\DealLoanTypeModel;
use libs\utils\DBDes;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class SupervisionDealWithdrawAction extends CommonAction{
    // 用户ID列表
    private $userIds = [];

    //提现申请列表
    public function index(){
        $_REQUEST['listRows'] = 100;
        // 过滤标的提现
        $map['bid'] = array('neq', 0);
        if(trim($_REQUEST['user_name'])!='')
        {
            $userInfo = UserService::getUserByName(trim($_REQUEST['user_name']));
            $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $map['user_id'] = array('in', $accountIds);
        }

        $user_num = trim($_GET['user_num']);
        if($user_num){
            $userId = de32Tonum($user_num);
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $map['user_id'] = array('in', $accountIds);
        }

        if (!empty($_REQUEST['deal_name']) || !empty($_REQUEST['deal_type_id']))
        {
            $deal_condition = " 1 ";
            if (!empty($_REQUEST['deal_name'])) {
                $dealName = addslashes(trim($_REQUEST['deal_name']));
                $deal_condition .= " AND name LIKE '%{$dealName}%'";
            }
            if (!empty($_REQUEST['deal_type_id'])) {
                $deal_type_id = intval($_REQUEST['deal_type_id']);
                $deal_condition .= " AND type_id = '{$deal_type_id}'";
            }
            $result =  DI('Deal')->where($deal_condition)->findAll();
            $dealIds = array();
            foreach ($result as $item) {
                $dealIds[] = $item['id'];
            }
            $map['bid'] = array('IN', $dealIds);
        }

        if($_REQUEST['out_order_id']){
            $map['out_order_id'] = $_REQUEST['out_order_id'];
        }
        // 筛选时间类型
        $timeType = trim(addslashes($_REQUEST['timeType']));
        $withdraw_time_start = $withdraw_time_end = 0;
        if (!empty($_REQUEST['withdraw_time_start'])) {
            $withdraw_time_start = strtotime($_REQUEST['withdraw_time_start']);
            $map[$timeType] = array('egt', $withdraw_time_start);
        }

        if (!empty($_REQUEST['withdraw_time_end'])) {
            $withdraw_time_end = strtotime($_REQUEST['withdraw_time_end']);
            $map[$timeType] = array('between', sprintf('%s,%s', $withdraw_time_start, $withdraw_time_end));
        }

        if($_REQUEST['status'] !='' && in_array($_REQUEST['status'], array(0,1,2,3,4)) )
        {
            $map['status'] = $_REQUEST['status'];
        }

        if (isset($_REQUEST['withdraw_status']) && $_REQUEST['withdraw_status'] !== '') {
            $map['withdraw_status'] = $_REQUEST['withdraw_status'];
        }

        // JIRA#3221 增加‘放款方式’ && ‘放款类型’ 搜索条件 <fanjingwen@ucfgroup.com>
        // 放款方式
        $subSqlDeal = '';
        if (isset($_REQUEST['loanway']) && '' != $_REQUEST['loanway']) {
            $loanway = addslashes($_REQUEST['loanway']);
            $sqlDealProject = "(SELECT `id` FROM `firstp2p_deal_project` WHERE `loan_money_type` = '{$loanway}')";
            $subSqlDeal = " AND (`bid` IN (SELECT `id` FROM `firstp2p_deal` WHERE `project_id` IN {$sqlDealProject}))";
        }
        // 放款类型
        $subSqlDealExt = '';
        if (isset($_REQUEST['loantype']) && '' != $_REQUEST['loantype']) {
            $loantype = addslashes($_REQUEST['loantype']);
            $subSqlDealExt = " AND `bid` IN (SELECT `deal_id` FROM `firstp2p_deal_ext` WHERE `loan_type` = '{$loantype}')";
        }

        // 组合字符串查询条件
        $map['_string'] = ' 1 ';
        if (!empty($subSqlDeal) || !empty($subSqlDealExt)) {
            $map['_string'] .= $subSqlDeal . $subSqlDealExt;
        }

        if (!empty($_REQUEST['project_name'])) {
            $map['_string'] .= ' AND `bid` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` = \'' . trim($_REQUEST['project_name']) .'\'))';
        }

        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        $this->assign("from_backup", $from_backup);

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        if($from_backup) {
            $model = DI("SupervisionWithdraw", '', 'firstp2p_moved', 'slave');
        } else {
            $model = DI("SupervisionWithdraw");
        }

        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        // 根据用户ID列表获取用户昵称、真实姓名
        $userNameList = $userBankList = [];
        if (!empty($this->userIds)) {
            // 批量获取用户信息
            $userNameList = UserService::getUserInfoByIds($this->userIds, false);
            // 批量获取用户银行卡信息
            $userBankList = BankService::getBankListByUserIds($this->userIds);
        }
        $this->assign('userNameList', $userNameList);
        $this->assign('userBankList', $userBankList);
        $this->assign("withdraw_status", SupervisionEnum::$withdrawDesc);
        $this->assign("rolltype", DealExtEnum::$rollDesc);
        if ($this->is_cn) {
            $loanMoneyTypeNameData  = $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'];
        } else {
            $loanMoneyTypeNameData  = $GLOBALS['dict']['LOAN_MONEY_TYPE'];
        }
        $loanMoneyTypeNameData[1] = '放款提现';
        $this->assign('loan_money_type', $loanMoneyTypeNameData); //放款方式

        // 产品类型
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->field('id,name')->findAll();
        $deal_type_tree = $this->is_cn ? $GLOBALS['dict']['DEAL_TYPE_ID_CN'] : $deal_type_tree;
        $this->assign("deal_type_tree", $deal_type_tree);

        // JIRA#3221 先计息后放款
        $loantype = $this->is_cn ? DealExtEnum::$loantypeDescCn : DealExtEnum::$loantypeDesc;
        $this->assign("loantype", $loantype);
        $this->assign("is_cn", $this->is_cn);
        $this->display ('index');
    }

    protected function form_index_list(&$list)
    {
        $existUserIds = [];
        $supWithdrawService = new SupervisionWithdrawService();
        foreach ($list as $key => $item) {
            // 账户ID需要转出用户ID
            $accountId = $item['user_id'];
            if (!isset($existUserIds[$accountId])) {
                $existUserIds[$accountId] = 1;
                // 把账户ID转换为用户ID
                $userId = AccountService::getUserId($accountId);
                if (!empty($userId)) {
                    $this->userIds[] = $userId;
                }
            }

            $list[$key]['loan_money_type'] = '';
            $list[$key]['loan_money_type_name'] = '';
            $list[$key]['deal_name'] = '';
            $list[$key]['project_id'] = 0;
            $list[$key]['can_redo_withdraw'] = false;
            //$list[$key]['withdraw_status'] = SupervisionEnum::$withdrawDesc[$item['withdraw_status']];

            // 根据账户ID获取账户金额
            $thirdBalanceInfo = AccountService::getAccountMoneyById($accountId);
            $svBalanceAmount = empty($thirdBalanceInfo) ? 0.00 : $thirdBalanceInfo['money'];
            $list[$key]['svBalanceFormat'] = format_price($svBalanceAmount);

            if ($item['bid'] > 0) {
                $dealInfo = M('deal')->where("id='{$item['bid']}'")->find();
                $projectInfo = M('deal_project')->where("id='{$dealInfo['project_id']}'")->find();
                $dealLoanType = DealLoanTypeModel::instance()->getLoanNameByTypeId($dealInfo['type_id']);

                $list[$key]['loan_money_type'] = $projectInfo['loan_money_type'];
                $list[$key]['loan_money_type_name'] = $GLOBALS['dict']['LOAN_MONEY_TYPE'][$projectInfo['loan_money_type']];
                $list[$key]['deal_name'] = $dealInfo['name'];
                $list[$key]['bid'] = $dealInfo['id'];
                $list[$key]['project_id'] = $projectInfo['id'];

                // JIRA#3221 增加放款类型 loan_type （直接放款、先计息后放款）  <fanjingwen@ucfgroup.com>
                $loantype = M('deal_ext')->where("deal_id='{$item['bid']}'")->getField('loan_type');
                $list[$key]['loan_type'] = $loantype == 1 ? '先计息后放款' : '直接放款';

                // 放款后提现失败可重新发起提现申请 JIRA#3606
                $list[$key]['deal_loan_type'] = $dealLoanType;
                $list[$key]['can_redo_withdraw'] = $supWithdrawService->canRedoWithdraw($item);
                $list[$key]['old_deal_name'] = getOldDealNameWithPrefix($item['bid'], $projectInfo['id']);
            }
        }
    }

    /**
     * 放款提现列表
     */
    public function dealloanList()
    {
        $_REQUEST['roll'] = 1;
        $this->assign('main_title', '放款提现列表');
        $this->index();
    }

    /**
     * 导出用户提现列表
     */
    public function get_carry_cvs()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '300M');
        $user = D("User");
        $condition = ' bid != 0 ';
        if (trim($_REQUEST['user_name']) != '') {
            $userInfo = UserService::getUserByName(trim($_REQUEST['user_name']));
            $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $condition .= sprintf(" AND user_id IN (%s)", join(',', $accountIds));
        }

        $user_num = trim($_GET['user_num']);
        if($user_num){
            $userId = de32Tonum($user_num);
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $condition .= sprintf(" AND user_id IN (%s)", join(',', $accountIds));
        }

        if (!empty($_REQUEST['deal_name']) || !empty($_REQUEST['deal_type_id']))
        {
            $deal_condition = " 1 ";
            if (!empty($_REQUEST['deal_name'])) {
                $dealName = addslashes(trim($_REQUEST['deal_name']));
                $deal_condition .= " AND name LIKE '%{$dealName}%'";
            }

            if (!empty($_REQUEST['deal_type_id'])) {
                $deal_type_id = intval($_REQUEST['deal_type_id']);
                $deal_condition .= " AND type_id = '{$deal_type_id}'";
            }

            $result =  DI('Deal')->where($deal_condition)->findAll();
            $dealIds = array();
            foreach ($result as $item) {
                $dealIds[] = $item['id'];
            }
            if (!empty($dealIds)) {
                $dealIds = implode(',', $dealIds);
                $condition .= " AND bid in ($dealIds)";
            }
        }

        if (isset($_REQUEST['withdraw_status']) && $_REQUEST['withdraw_status'] !== '') {
            $withdrawStatus = intval($_REQUEST['withdraw_status']);
            $condition .= " AND withdraw_status = '{$withdrawStatus}' ";
        }

        // 筛选时间类型
        $timeType = trim(addslashes($_REQUEST['timeType']));
        //添加搜索条件，编号区间
        if (!empty($_REQUEST['withdraw_time_start']) && trim($_REQUEST['withdraw_time_start']) != 'undefined') {
            $withdraw_time_start = strtotime($_REQUEST['withdraw_time_start']);
            $condition .= " AND {$timeType} >= " . $withdraw_time_start;
        }

        if (!empty($_REQUEST['withdraw_time_end']) && trim($_REQUEST['withdraw_time_end']) != 'undefined') {
            $withdraw_time_end = strtotime($_REQUEST['withdraw_time_end']);
            $condition .= " AND {$timeType} <= " . $withdraw_time_end;
        }
        // 放款方式
        if (isset($_REQUEST['loanway']) && '' != $_REQUEST['loanway'] && 2 != $roll) {
            $loanway = addslashes($_REQUEST['loanway']);
            $sqlDealProject = "(SELECT `id` FROM `firstp2p_deal_project` WHERE `loan_money_type` = '{$loanway}')";
            $condition .= " AND (`bid` IN (SELECT `id` FROM `firstp2p_deal` WHERE `project_id` IN {$sqlDealProject}))";
        }
        // 放款类型
        if (isset($_REQUEST['loantype']) && '' != $_REQUEST['loantype'] && 2 != $roll) {
            $loantype = addslashes($_REQUEST['loantype']);
            $condition .= " AND `bid` IN (SELECT `deal_id` FROM `firstp2p_deal_ext` WHERE `loan_type` = '{$loantype}')";
        }

        if (!empty($_REQUEST['out_order_id'])) {
            $condition .= ' AND out_order_id = ' .intval($_REQUEST['out_order_id']);
        }

        if (!empty($_REQUEST['project_name'])) {
            $condition .= ' AND `bid` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` = \'' . trim($_REQUEST['project_name']) .'\'))';
        }

        $tableName = SupervisionWithdrawModel::instance()->tableName();
        $sql = "SELECT * FROM `{$tableName}` WHERE $condition ORDER BY `id` DESC";
        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        if ($from_backup) {
            $res = Db::getInstance('firstp2p_moved', 'slave')->query($sql);
        } else {
            $res = Db::getInstance('firstp2p', 'slave')->query($sql);
        }
        if ($res === false) {
            $this->error('查询错误');
        }

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => $sql
                )
        );

        $withdraw_status = SupervisionEnum::$withdrawDesc;

        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename={$datatime}.csv");

        $title = array(
                '编号','外部订单号',
                '借款标题', '放款方式', '放款类型',
                '用户ID', '会员名称', '用户姓名', '提现金额',
                '申请时间', '产品类别','备注',
                '支付状态', '支付时间',
                '开户名', '银行卡号', '提现失败操作'
        );

        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gbk//IGNORE", $v);
        }

        $count = 1;
        $limit = 10000;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);


        while($v = $GLOBALS['db']->fetchRow($res)) {
            $user_bank = get_user_bank_info($v['user_id']);

            $dealName = '';
            $loanMoneyTypeName = '';
            $loanTypeName = '';

            $cardName = $user_bank['card_name'];
            $cardNo = $user_bank['bankcard'];
            // 提现失败显示内容
            $withdrawFailedMsg = '';

            if ($v['bid'] > 0) {
                $dealInfo = MI('Deal')->where("id='{$v['bid']}'")->find();
                $loanType = MI('deal_ext')->where("deal_id ='{$v['bid']}'")->getField('loan_type');
                $projectInfo = MI('DealProject')->where("id='{$dealInfo['project_id']}'")->find();

                $dealName = $dealInfo['name'];
                $loanMoneyTypeName = $GLOBALS['dict']['LOAN_MONEY_TYPE'][$projectInfo['loan_money_type']];
                if ($projectInfo['loan_money_type'] == 1)
                {
                    $loanMoneyTypeName = '放款提现';
                }
                else if ($projectInfo['loan_money_type'] == 2)
                {
                    $loanMoneyTypeName = '放款';
                }
                $loanTypeName = $loanType == 0 ? '直接放款' : '先计息后放款';

                if ($projectInfo['loan_money_type'] == 3) {
                    $cardName = $projectInfo['card_name'];
                    $cardNo = DBDes::decryptOneValue($projectInfo['bankcard']);
                }
                // 提现失败显示内容
                if ($v['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_FAILED) {
                    $withdrawFailedMsg = !empty($v['update_time_finance']) ? '已操作' : '未操作';
                }
                $dealTypeName = MI('DealLoanType')->where("id = '{$dealInfo['type_id']}'")->getField('name');
            }

            $arr = array();

            $arr[] = $v['id'];
            $arr[] = $v['out_order_id']."\t";
            //借款标题、放款方式、放款类型
            $arr[] = $dealName;
            $arr[] = $loanMoneyTypeName;
            $arr[] = $loanTypeName;
            // 获取用户信息
            $userInfo = UserService::getUserById($v['user_id']);
            $arr[] = $v['user_id'];
            $arr[] = $userInfo['user_name'];
            $arr[] = $userInfo['real_name'];
            $arr[] = bcdiv($v['amount'], 100,2);
            $arr[] = date('Y-m-d H:i:s', $v['create_time']);
            $arr[] = $dealTypeName;
            $arr[] = str_replace(array("</p>","<p>"),array('',''),$v['desc']);
            $withdarwStatus = $withdraw_status[$v['withdraw_status']];
            // 非实际放款失败改失败状态
            if ($v['withdraw_status'] == 2)
            {
                $withdarwStatus = $projectInfo['loan_money_type']== 2 ? '提现还款' : $withdarwStatus;
            }
            $arr[] = $withdarwStatus;
            if ($v['withdraw_status'] != 0) {
                $arr[] = date('Y-m-d H:i:s', $v['update_time']);
            } else {
                $arr[] = '';
            }
            $arr[] = $cardName;
            $arr[] = $cardNo."\t ";
            $arr[] = $withdrawFailedMsg;
            $arr[] = "\t";

            foreach ($arr as $k => $v){
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", strip_tags($v));
            }

            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, $arr);
        }
        exit;
    }

    /**
     * 重新发起借款人失败的提现申请
     */
    public function redoWithdraw() {
        $orderId = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';
        if (empty($orderId))
        {
            $this->error('操作失败');
            return;
        }
        $withdrawService = new SupervisionWithdrawService;
        $rs = $withdrawService->redoWithdraw($orderId);
        if ($rs == true) {
            save_log("编号为".$orderId."的提现申请".L("UPDATE_SUCCESS") ,1);
            $this->success("操作成功");
        } else {
            $this->error("操作失败");
        }
    }


    public function edit() {
        $id = intval($_GET['id']);
        $condition['id'] = $id;
        $isView = intval($_GET['isView']);
        $vo = SupervisionWithdrawModel::instance()->find($id);
        // 修复页面字段显示错误的问题
        $user_bankcard = BankService::getNewCardByUserId($vo['user_id']);
        $region_lv1 = !empty($vo['region_lv1']) ? $vo['region_lv1'] : $user_bankcard['region_lv1'];
        $region_lv2 = !empty($vo['region_lv2']) ? $vo['region_lv2'] : $user_bankcard['region_lv2'];
        $region_lv3 = !empty($vo['region_lv3']) ? $vo['region_lv3'] : $user_bankcard['region_lv3'];
        $region_lv4 = !empty($vo['region_lv4']) ? $vo['region_lv4'] : $user_bankcard['region_lv4'];

        if($vo['type'] == 1){
            $vo['region_lv1_name'] = M("DeliveryRegion")->where("id=".$region_lv1)->getField("name");
            $vo['region_lv2_name'] = M("DeliveryRegion")->where("id=".$region_lv2)->getField("name");
            $vo['region_lv3_name'] = M("DeliveryRegion")->where("id=".$region_lv3)->getField("name");
            $vo['region_lv4_name'] = M("DeliveryRegion")->where("id=".$region_lv4)->getField("name");
        }
        $vo['card_type'] = !empty($user_bankcard['card_type']) ? (int)$user_bankcard['card_type'] : 0;
        $vo['bankzone'] = !empty($user_bankcard['bankzone']) ? $user_bankcard['bankzone'] : '';
        $vo['bankcard'] = $user_bankcard['bankcard'];
        $vo['real_name'] = $user_bankcard['card_name'];
        $vo['bank_id'] = $user_bankcard['bank_id'];
        $trusteePay = false;
        if ($vo['bid'] > 0) {
            $dealInfo = M('deal')->where("id={$vo['bid']}")->find();
            $projectInfo = M('deal_project')->where("id={$dealInfo['project_id']}")->find();
            //如果是受托支付
            if ($projectInfo['loan_money_type'] == 3) {
                $trusteePay = true;

                $vo['bankcard'] = DBDes::decryptOneValue($projectInfo['bankcard']);
                $vo['bankzone'] = $projectInfo['bankzone'];
                $vo['real_name'] = $projectInfo['card_name'];
                $vo['bank_id'] = $projectInfo['bank_id'];
                $vo['card_type'] = $projectInfo['card_type'];

                $vo['region_lv1_name'] = '';
                $vo['region_lv2_name'] = '';
                $vo['region_lv3_name'] = '';
                $vo['region_lv4_name'] = '';

                $this->assign('projectId', $projectInfo['id']);
            }
        }

        // 获取银行基本信息
        $bankData = BankService::getBankInfoByBankId($vo['bank_id']);
        $vo['bank_name'] = !empty($bankData['name']) ? $bankData['name'] : '';

        $this->assign("vo",$vo);
        $this->assign("trusteePay", $trusteePay);
        $this->display ();
    }

    // 提现审核列表
    public function audit_index(){
        $auth_action = [];
        $is_auth = $this->is_have_action_auth(MODULE_NAME, 'doFirstAudit');
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : -1;
        if ($is_auth && in_array($status, [-1, 0, 3])) {
            $auth_action[] = array('a' => 'doFirstAudit', 'p' => SupervisionEnum::STATUS_A_PASS, 'r'=>'', 'n' => 'A角色');
        }
        $is_auth_final = $this->is_have_action_auth(MODULE_NAME, 'doFinalAudit');
        if ($is_auth_final && in_array($status, [-1, 1])) {
            $auth_action[] = array('a' => 'doFinalAudit', 'p' => SupervisionEnum::STATUS_B_PASS, 'r' => SupervisionEnum::STATUS_B_REFUND, 'n' => 'B角色');
        }
        $this->assign('auth_action', $auth_action);

        $_REQUEST['listRows'] = 100;
        // 过滤标的提现
        $map['bid'] = array('neq', 0);
        // 状态（不包含B角色审核通过的数据）
        $map['status'] = array('neq', SupervisionEnum::STATUS_B_PASS);

        // 申请时间
        $time_start = $time_end = 0;
        if (!empty($_REQUEST['time_start'])) {
            $time_start = strtotime($_REQUEST['time_start']);
            $map['create_time'] = array('egt', $time_start);
        }
        if (!empty($_REQUEST['time_end'])) {
            $time_end = strtotime($_REQUEST['time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $time_start, $time_end));
        }

        // 会员名称
        if(trim($_REQUEST['user_name'])!='')
        {
            $userInfo = UserService::getUserByName(trim($_REQUEST['user_name']));
            $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $map['user_id'] = array('in', $accountIds);
        }

        // 会员编号
        $user_num = trim($_GET['user_num']);
        if($user_num){
            $userId = de32Tonum($user_num);
            // 根据用户ID获取所有的账户ID列表
            $accountIds = AccountService::getAccountIdsByUserId($userId);
            empty($accountIds) && $accountIds = [0];
            $map['user_id'] = array('in', $accountIds);
        }

        // 借款标题、产品类别
        if (!empty($_REQUEST['deal_name']) || !empty($_REQUEST['deal_type_id']))
        {
            $dealCondition = " 1 ";
            // 借款标题
            if (!empty($_REQUEST['deal_name'])) {
                $dealName = addslashes(trim($_REQUEST['deal_name']));
                $dealCondition .= " AND `name` LIKE '%{$dealName}%'";
            }
            // 产品类别
            if (!empty($_REQUEST['deal_type_id'])) {
                $deal_type_id = intval($_REQUEST['deal_type_id']);
                $dealCondition .= " AND `type_id` = '{$deal_type_id}'";
            }
            $result =  DI('Deal')->where($dealCondition)->findAll();
            $dealIds = array();
            foreach ($result as $item) {
                $dealIds[] = $item['id'];
            }
            $map['bid'] = array('IN', $dealIds);
        }

        // 状态
        if($_REQUEST['status'] !='' && isset(SupervisionEnum::$auditStatusDesc[$_REQUEST['status']])) {
            $map['status'] = $_REQUEST['status'];
        }

        // 放款方式
        $subSqlDeal = '';
        if (isset($_REQUEST['loanway']) && '' != $_REQUEST['loanway']) {
            $loanway = addslashes($_REQUEST['loanway']);
            $sqlDealProject = "(SELECT `id` FROM `firstp2p_deal_project` WHERE `loan_money_type` = '{$loanway}')";
            $subSqlDeal = " AND (`bid` IN (SELECT `id` FROM `firstp2p_deal` WHERE `project_id` IN {$sqlDealProject}))";
        }
        // 放款类型
        $subSqlDealExt = '';
        if (isset($_REQUEST['loantype']) && '' != $_REQUEST['loantype']) {
            $loantype = addslashes($_REQUEST['loantype']);
            $subSqlDealExt = " AND `bid` IN (SELECT `deal_id` FROM `firstp2p_deal_ext` WHERE `loan_type` = '{$loantype}')";
        }
        // 组合字符串查询条件
        $map['_string'] = ' 1 ';
        if (!empty($subSqlDeal) || !empty($subSqlDealExt)) {
            $map['_string'] .= $subSqlDeal . $subSqlDealExt;
        }
        // 项目名称
        if (!empty($_REQUEST['project_name'])) {
            $map['_string'] .= ' AND `bid` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` = \'' . trim($_REQUEST['project_name']) .'\'))';
        }

        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        $this->assign("from_backup", $from_backup);

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        if($from_backup) {
            $model = DI("SupervisionWithdrawAudit", '', 'firstp2p_moved', 'slave');
        } else {
            $model = DI("SupervisionWithdrawAudit");
        }
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        // 根据用户ID列表获取用户昵称、真实姓名
        $userNameList = $userBankList = [];
        if (!empty($this->userIds)) {
            // 批量获取用户信息
            $userNameList = UserService::getUserInfoByIds($this->userIds, false);
            // 批量获取用户银行卡信息
            $userBankList = BankService::getBankListByUserIds($this->userIds);
        }
        $this->assign('userNameList', $userNameList);
        $this->assign('userBankList', $userBankList);
        unset(SupervisionEnum::$auditStatusDesc[SupervisionEnum::STATUS_B_PASS]);
        $this->assign("auditstatus_config", SupervisionEnum::$auditStatusDesc);
        $this->assign("rolltype", DealExtEnum::$rollDesc);
        // 放款方式
        if ($this->is_cn) {
            $loanMoneyTypeNameData  = $GLOBALS['dict']['LOAN_MONEY_TYPE_CN'];
        } else {
            $loanMoneyTypeNameData  = $GLOBALS['dict']['LOAN_MONEY_TYPE'];
        }
        $loanMoneyTypeNameData[1] = '放款提现';
        $this->assign('loan_money_type', $loanMoneyTypeNameData);

        // 产品类型
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->field('id,name')->findAll();
        $deal_type_tree = $this->is_cn ? $GLOBALS['dict']['DEAL_TYPE_ID_CN'] : $deal_type_tree;
        $this->assign("deal_type_tree", $deal_type_tree);

        // JIRA#3221 先计息后放款
        $loantype = $this->is_cn ? DealExtEnum::$loantypeDescCn : DealExtEnum::$loantypeDesc;
        $this->assign("loantype", $loantype);
        $this->assign("is_cn", $this->is_cn);
        $this->display ('audit_index');
    }

    /**
     * 审核记录-查看
     */
    public function audit_view() {
        $id = intval($_GET['id']);
        $isView = intval($_GET['isView']);
        $vo = SupervisionWithdrawAuditModel::instance()->find($id);
        // 修复页面字段显示错误的问题
        $user_bankcard = BankService::getNewCardByUserId($vo['user_id']);
        $region_lv1 = !empty($vo['region_lv1']) ? $vo['region_lv1'] : $user_bankcard['region_lv1'];
        $region_lv2 = !empty($vo['region_lv2']) ? $vo['region_lv2'] : $user_bankcard['region_lv2'];
        $region_lv3 = !empty($vo['region_lv3']) ? $vo['region_lv3'] : $user_bankcard['region_lv3'];
        $region_lv4 = !empty($vo['region_lv4']) ? $vo['region_lv4'] : $user_bankcard['region_lv4'];

        // 开户行所在地
        $vo['region_lv1_name'] = M("DeliveryRegion")->where("id=".$region_lv1)->getField("name");
        $vo['region_lv2_name'] = M("DeliveryRegion")->where("id=".$region_lv2)->getField("name");
        $vo['region_lv3_name'] = M("DeliveryRegion")->where("id=".$region_lv3)->getField("name");
        $vo['region_lv4_name'] = M("DeliveryRegion")->where("id=".$region_lv4)->getField("name");

        // 银行卡类型
        $vo['card_type'] = !empty($user_bankcard['card_type']) ? (int)$user_bankcard['card_type'] : 0;
        $vo['bankzone'] = !empty($user_bankcard['bankzone']) ? $user_bankcard['bankzone'] : '';
        $vo['bankcard'] = $user_bankcard['bankcard'];
        $vo['real_name'] = $user_bankcard['card_name'];
        $vo['bank_id'] = $user_bankcard['bank_id'];
        $trusteePay = false;
        if (!empty($vo['bid'])) {
            $dealInfo = M('deal')->where("id={$vo['bid']}")->find();
            $projectInfo = M('deal_project')->where("id={$dealInfo['project_id']}")->find();
            // 如果是受托支付
            if ($projectInfo['loan_money_type'] == 3) {
                $trusteePay = true;

                $vo['bankcard'] = DBDes::decryptOneValue($projectInfo['bankcard']);
                $vo['bankzone'] = $projectInfo['bankzone'];
                $vo['real_name'] = $projectInfo['card_name'];
                $vo['bank_id'] = $projectInfo['bank_id'];
                $vo['card_type'] = $projectInfo['card_type'];
                $vo['region_lv1_name'] = '';
                $vo['region_lv2_name'] = '';
                $vo['region_lv3_name'] = '';
                $vo['region_lv4_name'] = '';
            }
        }

        // 获取银行基本信息
        $bankData = BankService::getBankInfoByBankId($vo['bank_id']);
        $vo['bank_name'] = !empty($bankData['name']) ? $bankData['name'] : '';

        $this->assign("vo", $vo);
        $this->assign("trusteePay", $trusteePay);
        $this->display('edit');
    }

    /**
     * A角色审核通过-批量
     */
    public function doFirstAudit() {
        $ret = ['failMsg'=>''];
        // 获取ID数组
        $ids = $this->get_id_list();
        $isBatch = intval($_REQUEST['is_batch']);
        foreach ($ids as $id) {
            $this->_doFirstAuditOne($id, $isBatch, $ret);
        }
        if ($isBatch == 1) {
            $auditMsg = sprintf('一共执行%d笔，%d笔成功，%d笔失败或已审核。', count($ids), count($ret['success']), count($ret['fail']));
            ajax_return(['status'=>'OK', 'msg'=>$auditMsg, 'error'=>join(',', $ret['failMsg'])]);
        }
    }

    /**
     * A角色审核通过-单个
     * @param int $id
     * @param int $isBatch
     * @param array $ret
     * @throws WXException
     */
    private function _doFirstAuditOne($id, $isBatch = 0, &$ret = []) {
        $result = ['id'=>$id, 'status' => -1, 'msg' => '操作失败'];
        try {
            if (empty($id) || empty($_REQUEST['status'])) {
                throw new WXException('ERR_PARAM');
            }
            $id = intval($id);
            $ajax = intval($_REQUEST['ajax']);
            $status = intval($_REQUEST['status']);

            $supervisionWithdrawAuditObj = new SupervisionWithdrawAuditModel();
            $auditResult = $supervisionWithdrawAuditObj->doFirstAudit($id, $status, true);
            if ($auditResult['respCode'] != 0) {
                throw new \Exception($auditResult['respMsg']);
            }

            // 记录admin日志
            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            save_log('网贷账户放款提现审核列表-A角色审核通过，管理员id['.$adminInfo['adm_id'].']，管理员名称[' . $adminInfo['adm_name'] . ']，记录id[' . $id . ']' . L('UPDATE_SUCCESS'), 1, ['status'=>0], ['status'=>$status]);
            $result['status'] = 'OK';
            $result['msg'] = '操作成功';
            $ret['success'][] = $result;
        } catch(\Exception $e) {
            $result['msg'] = $e->getMessage();
            $result['status'] = $e->getCode();
            $ret['fail'][] = $result;
            $ret['failMsg'][] = $id . '-' . $result['msg'];
        }
        if ($isBatch != 1) {
            ajax_return($result);
            exit;
        }else{
            return $ret;
        }
    }

    /**
     * B角色审核-批量
     */
    public function doFinalAudit() {
        $ret = ['failMsg'=>''];
        // 获取ID数组
        $ids = $this->get_id_list();
        $isBatch = intval($_REQUEST['is_batch']);
        foreach ($ids as $id) {
            $this->_doFinalAuditOne($id, $isBatch, $ret);
        }
        if ($isBatch == 1) {
            $auditMsg = sprintf('一共执行%d笔，%d笔成功，%d笔失败或已审核。', count($ids), count($ret['success']), count($ret['fail']));
            ajax_return(['status'=>'OK', 'msg'=>$auditMsg, 'error'=>join(',', $ret['failMsg'])]);
        }
    }

    /**
     * B角色审核-单个
     * @param int $id
     * @param int $isBatch
     * @param array $ret
     * @throws WXException
     */
    private function _doFinalAuditOne($id, $isBatch = 0, &$ret = []) {
        $result = array('id'=>$id, 'status' => -1, 'msg' => '操作失败');
        $id = intval($id);
        if (empty($id)) {
            $result['msg'] = '参数错误';
            if ($isBatch != 1) {
                ajax_return($result);
            }else{
                $ret['fail'][] = $result;
                $ret['failMsg'][] = $id . '-' . $result['msg'];
                return $ret;
            }
        }

        try {
            if (empty($id) || empty($_REQUEST['status'])) {
                throw new WXException('ERR_PARAM');
            }
            $status = intval($_REQUEST['status']);

            $supervisionWithdrawAuditObj = new SupervisionWithdrawAuditModel();
            $auditResult = $supervisionWithdrawAuditObj->doFinalAudit($id, $status, true);
            if ($auditResult['respCode'] != 0) {
                throw new \Exception($auditResult['respMsg']);
            }

            // 记录admin日志
            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            $logTips = $status == SupervisionEnum::STATUS_B_PASS ? 'B角色审核通过' : 'B角色审核拒绝';
            save_log('网贷账户放款提现审核列表-' . $logTips . '，管理员id['.$adminInfo['adm_id'].']，管理员名称[' . $adminInfo['adm_name'] . ']，记录id[' . $id . ']' . L('UPDATE_SUCCESS'), 1, ['status'=>1], ['status'=>$status]);
            $result['status'] = 'OK';
            $result['msg'] = '操作成功';
            $ret['success'][] = $result;
        } catch(\Exception $e) {
            $result['status'] = $e->getCode();
            $result['msg'] = $e->getMessage();
            $ret['fail'][] = $result;
            $ret['failMsg'][] = $id . '-' . $result['msg'];
        }
        if ($isBatch != 1) {
            ajax_return($result);
        }else{
            return $ret;
        }
    }


    public function queryOrder() {
        if (empty($_REQUEST['bid'])) {
            $this->display("queryOrder");
            return;
        }
        $_REQUEST['listRows'] = 50;
        // 加载数据
        $this->assign('bid', $_REQUEST['bid']);
        // 拼装sql
        $bidListString = preg_replace("/\r\n/",',', $_REQUEST['bid']);
        $rows = $this->getData($bidListString);
        $this->assign('list', $rows);
        $this->display('queryOrder');

    }

    public function export_query_order() {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $bidListString = addslashes(trim($_REQUEST['bid']));
        $rows = $this->getData($bidListString);
        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename={$datatime}.csv");

        $title = array(
                '编号','外部订单号', '用户ID', '提现金额',
                '申请时间','支付状态', '支付时间',
        );

        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gbk//IGNORE", $v);
        }

        $withdraw_status = SupervisionEnum::$withdrawDesc;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);
        foreach ($rows as $v) {
            $arr = [];
            $arr[] = $v['id'];
            $arr[] = $v['out_order_id']."\t";
            // 获取用户信息
            $arr[] = $v['user_id'];
            $arr[] = bcdiv($v['amount'], 100,2);
            $arr[] = date('Y-m-d H:i:s', $v['create_time']);
            $arr[] = $withdraw_status[$v['withdraw_status']];
            if ($v['withdraw_status'] != 0) {
                $arr[] = date('Y-m-d H:i:s', $v['update_time']);
            } else {
                $arr[] = '';
            }

            foreach ($arr as $k => $v){
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", strip_tags($v));
            }
            fputcsv($fp, $arr);
        }


    }

    public function getData($bidListString) {
        // 拼装sql
        $sql = "SELECT * FROM firstp2p_supervision_withdraw WHERE bid IN ({$bidListString})";

        $db = Db::getInstance('firstp2p', 'master');
        $rows = $db->getAll($sql);

        //ncfphmoved
        $dbm = Db::getInstance('firstp2p_moved', 'master');
        $rows2 = $dbm->getAll($sql);

        foreach ($rows2 as $v) {
            $rows[] = $v;
        }
        return $rows;
    }
}
