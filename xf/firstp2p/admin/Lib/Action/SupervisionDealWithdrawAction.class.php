<?php
use libs\utils\Finance;
use libs\utils\Curl;
use core\service\UserCarryService;
use core\service\SupervisionWithdrawService;
use libs\utils\PaymentApi;
use core\dao\FinanceQueueModel;
use core\dao\UserCarryModel;
use core\dao\UserModel;
use core\dao\PaymentNoticeModel;
use core\service\MsgBoxService;
use core\dao\SupervisionWithdrawModel;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use core\service\P2pDealGrantService;
use core\service\SupervisionService;
use core\dao\UserBankcardModel;


error_reporting(E_ALL);
ini_set('display_errors', 1);

class SupervisionDealWithdrawAction extends CommonAction{

    //提现申请列表
    public function index(){
        $_REQUEST['listRows'] = 100;
        // 过滤标的提现
        $map['bid'] = array('neq', 0);
        if(trim($_REQUEST['user_name'])!='')
        {
            $map['user_id'] = DI("User")->where("user_name='".trim($_REQUEST['user_name'])."'")->getField('id');
        }

        $user_num = trim($_GET['user_num']);
        if($user_num){
           $map['user_id'] = de32Tonum($user_num);
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

        $this->assign("withdraw_status", SupervisionWithdrawModel::$withdrawDesc);
        $this->assign("rolltype", core\dao\UserCarryModel::$rollDesc);
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
        $loantype = $this->is_cn ? core\dao\UserCarryModel::$loantypeDescCn : core\dao\UserCarryModel::$loantypeDesc;
        $this->assign("loantype", $loantype);
        $this->assign("is_cn", $this->is_cn);
        $this->display ('index');
    }

    protected function form_index_list(&$list)
    {
        $supWithdrawService = new SupervisionWithdrawService();
        foreach ($list as $key => $item) {
            $list[$key]['loan_money_type'] = '';
            $list[$key]['loan_money_type_name'] = '';
            $list[$key]['deal_name'] = '';
            $list[$key]['project_id'] = 0;
            $list[$key]['can_redo_withdraw'] = false;
            //$list[$key]['withdraw_status'] = SupervisionWithdrawModel::$withdrawDesc[$item['withdraw_status']];
            $list[$key]['bankcard_name'] = $GLOBALS['db']->getOne("SELECT card_name FROM firstp2p_user_bankcard WHERE user_id = '{$item['user_id']}'");

            $thirdBalanceInfo = MI('UserThirdBalance')->where(" user_id = '{$item['user_id']}'")->find();
            if (empty($thirdBalanceInfo)) {
                $thirdBalanceInfo = ['supervision_balance' => 0.00];
            }
            $list[$key]['svBalanceFormat'] = format_price($thirdBalanceInfo['supervision_balance']);

            if ($item['bid'] > 0) {
                $dealInfo = M('deal')->where("id='{$item['bid']}'")->find();
                $projectInfo = M('deal_project')->where("id='{$dealInfo['project_id']}'")->find();
                $dealLoanType = \core\dao\DealLoanTypeModel::instance()->getLoanNameByTypeId($dealInfo['type_id']);

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
            $userId = DI("User")->where("user_name='".trim($_REQUEST['user_name'])."'")->getField('id');
            $condition .= " AND user_id = '$userId'";
        }

        $user_num = trim($_GET['user_num']);
        if($user_num){
            $userId = de32Tonum($user_num);
            $condition .= " AND user_id = '$userId'";
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
                    $cardNo = $projectInfo['bankcard'];
                }
                // 提现失败显示内容
                if ($v['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED) {
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
            $userInfo = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT user_name,real_name FROM firstp2p_user WHERE id = '{$v['user_id']}'");
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
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $id= intval($_REQUEST['id']);
        $withdraw = SupervisionWithdrawModel::instance()->find($id);
        if (empty($withdraw)) {
            $this->error('非法操作','',0);
        }

        // 检查是否可重新申请提现
        $withdrawService = new SupervisionWithdrawService();
        $canRedo = $withdrawService->canRedoWithdraw($withdraw);
        if (empty($canRedo)) {
            $this->error("非法操作");
        }

        try {
            $gtm = new GlobalTransactionManager();
            $gtm->setName('AdminRedoGrantWithdraw');

            // {{{ 复制原申请记录
            $withdrawNewData = $withdraw->getRow();
            $withdrawNewData['out_order_id'] = Idworker::instance()->getId();
            unset($withdrawNewData['withdraw_status']);
            unset($withdrawNewData['update_time']);
            unset($withdrawNewData['id']);
            unset($withdrawNewData['create_time']);
            unset($withdrawNewData['user_id']);
            unset($withdrawNewData['amount']);

            // 如果是标放款提现
            if ($withdraw['bid'] != '') {
                // 审批状态
                $deal = \core\dao\DealModel::instance()->find($withdraw['bid']);
                if (empty($deal)) {
                    $this->error("非法操作");
                }
                //$loan_type_info = M("Deal_loan_type")->where("id = ".intval($deal['type_id']))->find();
                //if ($loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_XFFQ ){
                //    $withdrawNewData['withdarw_status'] = SupervisionWithdrawModel::WITHDRAW_STATUS_INQUEUE; // 消费分期,进入自动审批
                //}
            }
            // {{{ 请求存管行提现
            $params = [];
            // 放款提现处理
            $params['orderId'] =  $withdrawNewData['out_order_id'];
            $params['dealId'] = $withdraw['bid'];
            $params['grantMoney'] = bcdiv($withdraw['amount'], 100, 2);
            $withdrawEvent = new EventMaker([
                'commit' => [(new P2pDealGrantService()), 'afterGrantWithdraw', $params],
            ]);
            $gtm->addEvent($withdrawEvent);
            // }}}

            // 更新提现记录-新
            $withdrawUpdateEvent = new EventMaker([
                'commit' => [(new SupervisionService()), 'updateRedoWithdraw', [$withdrawNewData]],
            ]);
            $gtm->addEvent($withdrawUpdateEvent);

            // 更新提现记录-旧
            $withdrawOldData = ['out_order_id'=>$withdraw['out_order_id'], 'update_time_finance'=>time()];
            $withdrawUpdateOldEvent = new EventMaker([
                'commit' => [(new SupervisionService()), 'updateRedoWithdraw', [$withdrawOldData]],
            ]);
            $gtm->addEvent($withdrawUpdateOldEvent);
            // }}}
            $rs = $gtm->execute();

        } catch( \Exception $e) {
            \libs\utils\PaymentApi::log('redoWithdraw fail, outOrderId:'.$withdraw['out_order_id'].',msg:'.$e->getMessage());
            $rs = false;
        }
        if ($rs == true) {
            save_log("编号为".$withdraw['out_order_id']."(新编号为{$withdrawNewData['out_order_id']})的提现申请".L("UPDATE_SUCCESS") ,1);
            $this->success("操作成功");
        } else {
            $this->error("操作失败");
        }
    }


    public function edit() {
        $id = intval($_GET['id']);
        $condition['id'] = $id;
        $isView = intval($_GET['isView']);
        $vo = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT * FROM firstp2p_supervision_withdraw WHERE id = '{$id}'");
        // 修复页面字段显示错误的问题
        $user_bankcard = UserBankcardModel::instance()->getNewCardByUserId($vo['user_id'], '*', false);
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

                $vo['bankcard'] = $projectInfo['bankcard'];
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

        $vo['bank_name'] =  MI("bank")->where("id=".$vo['bank_id'])->getField("name");

        $this->assign("vo",$vo);
        $this->assign("trusteePay", $trusteePay);
        $this->display ();
    }
}
