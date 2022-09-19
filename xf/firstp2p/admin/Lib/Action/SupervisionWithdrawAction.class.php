<?php
use libs\utils\Finance;
use libs\utils\Curl;
use core\service\UserCarryService;
use libs\utils\PaymentApi;
use core\dao\FinanceQueueModel;
use core\dao\UserCarryModel;
use core\dao\UserModel;
use core\dao\PaymentNoticeModel;
use core\service\MsgBoxService;
use core\dao\SupervisionWithdrawModel;

class SupervisionWithdrawAction extends CommonAction{

    //提现申请列表
    public function index(){
        $_REQUEST['listRows'] = 100;
        $roll = 0;
        if (isset($_REQUEST['roll'])) {
            $roll = intval($_REQUEST['roll']);
            if ($roll == 1) {
                $map['deal_id'] = array('neq', 0);

            }
            else if ($roll == 2){
                $map['deal_id'] = array('eq', 0);
            }
        }

       $map['bid'] = 0;

        if(trim($_REQUEST['user_name'])!='')
        {
            $map['user_id'] = DI("User")->where("user_name='".trim($_REQUEST['user_name'])."'")->getField('id');
        }

        $user_num = trim($_GET['user_num']);
        if($user_num){
           $map['user_id'] = de32Tonum($user_num);
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
        if (isset($_REQUEST['loanway']) && '' != $_REQUEST['loanway'] && 2 != $roll) {
            $loanway = addslashes($_REQUEST['loanway']);
            $sqlDealProject = "(SELECT `id` FROM `firstp2p_deal_project` WHERE `loan_money_type` = '{$loanway}')";
            $subSqlDeal = " AND (`deal_id` IN (SELECT `id` FROM `firstp2p_deal` WHERE `project_id` IN {$sqlDealProject}))";
        }
        // 放款类型
        $subSqlDealExt = '';
        if (isset($_REQUEST['loantype']) && '' != $_REQUEST['loantype'] && 2 != $roll) {
            $loantype = addslashes($_REQUEST['loantype']);
            $subSqlDealExt = " AND `deal_id` IN (SELECT `deal_id` FROM `firstp2p_deal_ext` WHERE `loan_type` = '{$loantype}')";
        }

        // 组合字符串查询条件
        $map['_string'] = ' 1 ';
        if (!empty($subSqlDeal) || !empty($subSqlDealExt)) {
            $map['_string'] .= $subSqlDeal . $subSqlDealExt;
        }

        if (!empty($_REQUEST['project_name'])) {
            $map['_string'] .= ' AND `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` = \'' . trim($_REQUEST['project_name']) .'\'))';
        }

        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        $this->assign('withdraw_status', SupervisionWithdrawModel::$withdrawDesc);
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

        // JIRA#3221 先计息后放款
        $this->assign("loantype", core\dao\UserCarryModel::$loantypeDesc);
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display ($template);
    }

    protected function form_index_list(&$list)
    {
        foreach ($list as $key => $item) {
            $cardInfo = MI('UserBankcard')->where(" user_id = '{$item['user_id']}'")->order('id DESC')->find();
            if (empty($cardInfo)) {
                $cardInfo = ['cardName' => '',];
            }
            $list[$key]['cardName'] = $cardInfo['card_name'];
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
        $condition = ' bid = 0 ';
        if (trim($_REQUEST['user_name']) != '') {
            $userId = DI("User")->where("user_name='".trim($_REQUEST['user_name'])."'")->getField('id');
            $condition .= " AND user_id = '$userId'";
        }

        $user_num = trim($_GET['user_num']);
        if($user_num){
            $userId = de32Tonum($user_num);
            $condition .= " AND user_id = '$userId'";
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

        if (!empty($_REQUEST['out_order_id'])) {
            $condition .= ' AND out_order_id = ' .intval($_REQUEST['out_order_id']);
        }

        if (isset($_REQUEST['withdraw_status']) && $_REQUEST['withdraw_status'] !== '') {
            $withdrawStatus = intval($_REQUEST['withdraw_status']);
            $condition .= " AND withdraw_status = '{$withdrawStatus}' ";
        }

        $sql = "SELECT * FROM " .DB_PREFIX. "supervision_withdraw WHERE $condition ORDER BY id DESC";
        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        if ($from_backup) {
            $res = \libs\db\Db::getInstance('firstp2p_moved', 'slave')->query($sql);
        } else {
            $res = \libs\db\Db::getInstance('firstp2p', 'slave')->query($sql);
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

        $withdraw_status = SupervisionWithdrawModel::$withdrawDesc;

        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename={$datatime}.csv");

        $title = array(
                '编号','外部订单号',
                '用户ID', '会员名称', '用户姓名', '提现金额',
                '手续费', '申请时间', '备注',
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
                // 提现失败显示内容
                if ($v['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED) {
                    $withdrawFailedMsg = !empty($v['update_time_finance']) ? '已操作' : '未操作';
                }
            }
            $arr = array();

            $arr[] = $v['id'];
            $arr[] = $v['out_order_id']. "\t \t";
            $arr[] = $v['user_id'];
            $arr[] = $user->where("id=".$v['user_id'])->getField("user_name");
            $arr[] = $v['real_name'];
            $arr[] = bcdiv($v['amount'], 100, 2);
            $arr[] = $v['fee'];
            $arr[] = date('Y-m-d H:i:s', $v['create_time']);
            $arr[] = str_replace(array("</p>","<p>"),array('',''),$v['remark']);
            $withdarwStatus = $withdraw_status[$v['withdraw_status']];
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

}
