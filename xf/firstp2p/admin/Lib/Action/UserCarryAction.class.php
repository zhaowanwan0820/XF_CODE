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
use core\dao\UserBankcardModel;
use core\dao\ConfModel;

class UserCarryAction extends CommonAction{

    //获取格式化的状态信息
    private function  get_carry_status($status, $id){
        $str = l("CARRY_STATUS_".$status);
        return $str;
    }

    /*
     * 平台代缴提现手续费
     * @actionlock
     * @lockAuthor daiyuxin
     */
    public function edit_fee(){
        $new_fee = floatval($_REQUEST['new_fee']);
        $old_fee = floatval($_REQUEST['old_fee']);
        $id = intval($_REQUEST['id']);

        if(empty($id)){
            $this->ajaxReturn('非法操作','',0);
        }

        if(M(MODULE_NAME)->where("id = $id")->save(array('fee' => $new_fee))){

            //修改平台关联账户的余额
            FP::import("libs.libs.user");
            $user_id = app_conf('DEAL_CONSULT_FEE_USER_ID');

            //提现信息
            $user_carry = M(MODULE_NAME)->where("id = $id")->find();
            $user_info = M('User')->where("id = ".$user_carry['user_id'])->find();

            if($old_fee){
        // TODO finance? 后台 平台代缴提现手续费
                modify_account(array('money' => $old_fee), $user_id, "平台代缴提现手续费", true,'会员名称 '.$user_info['user_name'].'，提现金额 '.$user_carry['money'].'元');
            }

            if($new_fee){
        // TODO finance? 后台 平台代缴提现手续费
                modify_account(array('money' => -$new_fee), $user_id, "平台代缴提现手续费", true,'会员名称 '.$user_info['user_name'].'，提现金额 '.$user_carry['money'].'元');
            }

            $this->ajaxReturn('','',1);
        }else{
            $this->ajaxReturn('修改失败，请刷新页面重试','',0);
        }
    }

    /*
     * 处理待审列表审核拒绝
     */
    private function _waitPass($ids, $status)
    {
        $data['msg'] = trim($_REQUEST['msg']);
        $successIds = array();
        //$failedIds = array();

        foreach ($ids as $id) {
            $vo = M(MODULE_NAME)->where("id = $id")->find();
            if($vo['status'] == 0)
            {
                try {
                    $data['status'] = $status;
                    $data['update_time_step1'] = $data['update_time'] = get_gmtime();

                    $se = \es_session::get(md5(conf("AUTH_KEY")));
                    $data['desc'] = $vo['desc'] . '<p>运营：' . $se['adm_name'] . '</p>';

                    // 开启事务
                    $GLOBALS['db']->startTrans();
                    $redb = $GLOBALS['db']->autoExecute(DB_PREFIX . "user_carry", $data, 'UPDATE', "id = $id");
                    if(false == $redb)
                    {
                        throw new \Exception("编号为".$vo['id']."的提现申请".L("UPDATE_FAILED"));
                    }

                    // 如果是拒绝
                    if($status == 2)
                    {
                        require_once APP_ROOT_PATH."/system/libs/user.php";
                        $user_id = $vo['user_id'];
                        $content = "您于".to_date($vo['create_time'],"Y年m月d日 H:i:s")."提交的".format_price($vo['money'])."提现申请被我们驳回。";
                        // TODO finance? 后台 提现失败，修改冻结资金
                        $result = modify_account(array("lock_money"=>-($vo['money']+$vo['fee'])),$vo['user_id'],"提现失败", true);
                        if (!$result) {
                            throw new \Exception('用户资金回滚失败');
                        }
                        //$content = "您于".to_date($vo['create_time'],"Y年m月d日 H:i:s")."提交的".format_price($vo['money'])."提现申请被我们驳回，驳回原因\"".$data['msg']."\"";

                        $group_arr = array(0,$user_id);
                        $group_arr[] =  7;
                        sort($group_arr);

                        $msg_data['content'] = $content;
                        $msg_data['to_user_id'] = $user_id;
                        $msg_data['create_time'] = get_gmtime();
                        $msg_data['type'] = 0;
                        $msg_data['group_key'] = implode("_",$group_arr);
                        $msg_data['is_notice'] = 7;

                        $msgBoxService = new MsgBoxService();
                        $msgBoxService->create($msg_data['to_user_id'], $msg_data['is_notice'], "", $msg_data['content'] ); //这里修改了逻辑，需要认真检查。
                        /*
                        $result = $GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg_data);
                        if (!$result) {
                            throw new \Exception("提现申请{$id}消息插入失败");
                        }
                        */

                    }
                    $GLOBALS['db']->commit();
                    save_log("编号为".$id."的提现申请".L("UPDATE_SUCCESS") ,1);
                    $successIds[] = $id;
                } catch (\Exception $e) {
                    $GLOBALS['db']->rollback();
                    //$failedIds[] = $id;
                    save_log("编号为".$id."的提现申请".L("UPDATE_FAILED") . $e->getMessage() ,0);
                    //$errMesage .= $e->getMessage();
                }
            }
        }

        if (count($successIds) == 0 ) {
            return array("function" => "error", "msg" => L("UPDATE_FAILED"));
        } else {
            return array("function" => "success", "msg" => L("UPDATE_SUCCESS"));
        }

    }
    /**
     * 提现加速
     */
    public function accelerate() {
        $id = getRequestInt('id');
        if (!$id) {
            $this->error('参数错误');
        }
        $userCarryService = new \core\service\UserCarryService();
        $result = $userCarryService->accelerate($id);
        if (isset($result['ret']) && true === $result['ret']) {
            $this->success($result['message']);
        }
        else {
            $this->error($result['message']);
        }
    }


    /** 待审核页面添加功能 */
    public function add()
    {
        $uid = getRequestInt("uid");
        if(!$uid){
            $this->success("参数错误！");
            exit;
        }

        $this->assign('uid', $uid);
        // 获取用户信息
        $uinfo = M("User")->where("id = ".$uid)->find();
        $this->assign('userinfo', $uinfo);
        $this->display ();
    }

    /*
     * 添加提现申请
     * @actionlock
     * @lockAuthor daiyuxin
     */

    public function insert()
    {
        if(is_numeric($_POST['money']))
            $moeny = round($_POST['money'], 2);
        else
        {
            $this->success(L("UPDATE_FAILED"));
            exit;
        }

        //后台提现申请的相关修改 - 去掉会员列表-提现申请面板中的手续费编辑框；用户提现时，手续费由平台（东方联合）缴纳。20140425
        $fee = 0;
//        if(is_numeric($_POST['fee']))
//            $fee = round($_POST['fee'], 2);
//        else
//        {
//            $this->success(L("UPDATE_FAILED"));
//            exit;
//        }


        // 金额不能为负数
        if($moeny  <= 0 || $fee < 0)
        {
            $this->success(L("UPDATE_FAILED"));
            exit;
        }

        $status = 1;
        $user_id = getRequestInt("user_id");
        // 获取用户信息
        $uinfo = M("User")->where("id = ".$user_id)->find();

        if(!$user_id)
        {
            $this->success(L("UPDATE_FAILED"));
            exit;
        }
        if(!$uinfo){
            $this->success(L("UPDATE_FAILED"));
            exit;
        }

        // 判断用户金额
        if($moeny + $fee > $uinfo['money'])
        {
            $this->success("提取金额和手续费超过用户余额");
            exit;
        }

        // 获取用户银行卡信息
        $bankinfo = M("UserBankcard")->where("user_id = $user_id")->find();
        if($bankinfo['status'] == 1)
        {
            $data['user_id'] = $user_id;
            $data['money'] = $moeny;
            $data['fee'] = $fee;
            $data['bank_id'] = $bankinfo['bank_id'];
            $data['bankcard'] = $bankinfo['bankcard'];
            $data['create_time'] = get_gmtime();
            $data['status'] = $status;
            $data['real_name'] = $bankinfo['card_name'];
            $data['region_lv1'] = $bankinfo['region_lv1'];
            $data['region_lv2'] = $bankinfo['region_lv2'];
            $data['region_lv3'] = $bankinfo['region_lv3'];
            $data['region_lv4'] = $bankinfo['region_lv4'];
            $data['bankzone'] = $bankinfo['bankzone'];
            $data['platform'] = PaymentNoticeModel:: PLATFORM_ADMIN;

            // 保存后台录入的描述信息,如果没有填写备注则记录操作人员名称
            $data['desc'] = getRequestString('desc');
            if (empty($data['desc']))
            {
                $se = es_session::get(md5(conf("AUTH_KEY")));
                $data['desc'] .= '<p>运营：' . $se['adm_name']  . '</p>';;
                $data['update_time'] = get_gmtime();
            }

            M(MODULE_NAME)->add($data);

            //更新会员账户信息
            FP::import("libs.libs.user");
        // TODO finance? 后台 提现申请，冻结用户提现金额和手续费
            modify_account(array('money'=>0,'lock_money'=>$moeny+$fee),$user_id,"提现申请", true,'系统发起');

            $this->success(L("UPDATE_SUCCESS"));
        }
        else
            $this->success(L("UPDATE_FAILED") . ",请确认用户银行卡信息。");
    }

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
            $map['deal_id'] = array('IN', $dealIds);
        }

        if($_REQUEST['id']){
            $map['id'] = $_REQUEST['id'];
        }
        $withdraw_time_start = $withdraw_time_end = 0;
        if (!empty($_REQUEST['withdraw_time_start'])) {
            $withdraw_time_start = to_timespan($_REQUEST['withdraw_time_start']);
            $map[$_REQUEST['timeType']] = array('egt', $withdraw_time_start);
        }

        if (!empty($_REQUEST['withdraw_time_end'])) {
            $withdraw_time_end = to_timespan($_REQUEST['withdraw_time_end']);
            $map[$_REQUEST['timeType']] = array('between', sprintf('%s,%s', $withdraw_time_start, $withdraw_time_end));
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
        $this->assign("from_backup", $from_backup);

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        if($from_backup) {
            $model = DI("UserCarry", '', 'firstp2p_moved', 'slave');
        } else {
            $model = DI("UserCarry");
        }

        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        $this->assign("withdraw_status", core\dao\UserCarryModel::$withdrawDesc);
        $this->assign("rolltype", core\dao\UserCarryModel::$rollDesc);
        $loanMoneyTypeNameData  = $GLOBALS['dict']['LOAN_MONEY_TYPE'];
        $loanMoneyTypeNameData[1] = '放款提现';
        $loanMoneyTypeNameData[2] = '放款';
        $this->assign('loan_money_type', $loanMoneyTypeNameData); //放款方式

        // 产品类型
        $deal_type_tree = M("DealLoanType")->where("`is_effect`='1' AND `is_delete`='0'")->order('sort desc')->field('id,name')->findAll();
        $this->assign("deal_type_tree", $deal_type_tree);

        // JIRA#3221 先计息后放款
        $this->assign("loantype", UserCarryModel::$loantypeDesc);
        $this->assign("user_name", trim($_REQUEST['user_name']));
        $this->display ('index');
    }

    protected function form_index_list(&$list)
    {
        $userCarryService = new UserCarryService();
        foreach ($list as $key => $item) {
            $list[$key]['loan_money_type'] = '';
            $list[$key]['loan_money_type_name'] = '';
            $list[$key]['deal_name'] = '';
            $list[$key]['project_id'] = 0;
            $list[$key]['can_redo_withdraw'] = false;

            if ($item['deal_id'] > 0) {
                $dealInfo = M('deal')->where("id='{$item['deal_id']}'")->find();
                $projectInfo = M('deal_project')->where("id='{$dealInfo['project_id']}'")->find();
                $dealLoanType = \core\dao\DealLoanTypeModel::instance()->getLoanNameByTypeId($dealInfo['type_id']);

                $list[$key]['loan_money_type'] = $projectInfo['loan_money_type'];
                $list[$key]['loan_money_type_name'] = $GLOBALS['dict']['LOAN_MONEY_TYPE'][$projectInfo['loan_money_type']];
                $list[$key]['deal_name'] = $dealInfo['name'];
                $list[$key]['deal_id'] = $dealInfo['id'];
                $list[$key]['project_id'] = $projectInfo['id'];
                $list[$key]['project_name'] = $projectInfo['name'];

                // JIRA#3221 增加放款类型 loan_type （直接放款、先计息后放款）  <fanjingwen@ucfgroup.com>
                $loantype = M('deal_ext')->where("deal_id='{$item['deal_id']}'")->getField('loan_type');
                $list[$key]['loan_type'] = $loantype;
                $list[$key]['loan_type_name'] = UserCarryModel::$loantypeDesc[$loantype];

                // 放款后提现失败可重新发起提现申请 JIRA#3606
                $list[$key]['deal_loan_type'] = $dealLoanType;
                $list[$key]['can_redo_withdraw'] = $userCarryService->canRedoWithdraw($item);
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

    //提现申请列表
    public function edit()
    {
        $id = intval($_GET['id']);
        $condition['id'] = $id;
        $isView = intval($_GET['isView']);
        $vo = M(MODULE_NAME)->where($condition)->find();
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
        $trusteePay = false;
        if ($vo['deal_id'] > 0) {
            $dealInfo = M('deal')->where("id={$vo['deal_id']}")->find();
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

        $vo['bank_name'] =  M("bank")->where("id=".$vo['bank_id'])->getField("name");

        $this->assign("vo",$vo);
        $this->assign("trusteePay", $trusteePay);
        $this->assign("isView", $isView);
        $this->assign("querystring", "?".$_SERVER['QUERY_STRING']);
        $this->display ();
    }

    /**
     * 提现申请通过
     */
    private function _update($ids, $status, $ajax){

        require_once APP_ROOT_PATH."/system/libs/user.php";
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        $data['status'] = $status;
        //$data['status'] = intval($_REQUEST['status']);
        $num = 0; // 已处理记录计数器
        $gs_Message = ''; // 记录金额不足ID提示信息
        foreach ($ids as $k => $id) {
            $data['id'] = $id;
            if(intval($data['status'])==0)
            {
                $this->success(L("UPDATE_SUCCESS") . "\n" . $gs_Message);
            }
            $data['update_time_step2'] = $data['update_time'] = get_gmtime();
            $se = es_session::get(md5(conf("AUTH_KEY")));
            //事务开始
            $GLOBALS['db']->startTrans();
            $withdraw_data = array();
            try{
                $info = UserCarryModel::instance()->find($data['id']);
                if(($info['status'] == 3 || $info['status'] == 4) && ($data['status'] == 1 || $data['status'] == 3 || $data['status'] == 4)){
                    throw new \Exception('数据已被处理', 0);
                }
                $data['desc'] = $info['desc'] . '<p>财务：' . $se['adm_name'] .'</p>';
                // 更新数据，考虑并发，增加乐观锁
                $upResult = $GLOBALS['db']->autoExecute('firstp2p_user_carry', $data, 'UPDATE', ' id  = ' . $id . ' AND status NOT IN (3,4)');
                $affectedResult = $GLOBALS['db']->affected_rows();
                if ($affectedResult <= 0) {
                    throw new \Exception('操作失败', 1);
                }
                //成功提示

                // 现在进入update的type只有提现的了，现在这块就不用管了
                //if($info['type'] != 1){//不是用户提现的 暂时不做处理
                    //$this->success(L("UPDATE_SUCCESS") . "\n" . $gs_Message);
                //}
                $user_id = $info['user_id'];
                if($data['status']==3){
                    //提现
                    $userCarryService = new UserCarryService();
                    $result = $userCarryService->doPass($info);

                }
                elseif($data['status']==4){
                    //驳回
                    $content = "您于".to_date($info['create_time'],"Y年m月d日 H:i:s")."提交的".format_price($info['money'])."提现申请被我们驳回，驳回原因\"".$data['msg']."\"";
            // TODO finance? 后台 提现失败
                    modify_account(array("lock_money"=>-($info['money']+$info['fee'])),$info['user_id'],"提现失败", true);
                    $group_arr = array(0,$user_id);
                    sort($group_arr);
                    $group_arr[] =  7;
                    $msg_data['content'] = $content;
                    $msg_data['to_user_id'] = $user_id;
                    $msg_data['create_time'] = get_gmtime();
                    $msg_data['type'] = 0;
                    $msg_data['group_key'] = implode("_",$group_arr);
                    $msg_data['is_notice'] = 7;

                    $msgBoxService = new MsgBoxService();
                    $msgBoxService->create( $msg_data['to_user_id'],$msg_data['is_notice'], "",  $msg_data['content']);
                    /*
                    $result = $GLOBALS['db']->autoExecute(DB_PREFIX."msg_box",$msg_data);
                    if (!$result) {
                        throw new \Exception("提现申请{$id}消息插入失败");
                    }
                    */
                    //短信通知
                    if(app_conf("SMS_ON") == 1){
                        $userDao = new UserModel();
                        $userObj = $userDao->find($user_id);
                        $params = array(
                            'money' => format_price($info['money']),
                        );
                        $this->_sendSms($userObj, $params, 'TPL_SMS_ACCOUNT_CASHOUT_FAIL', '提现失败');
                    }
                }

                $GLOBALS['db']->commit();
                save_log("编号为".$data['id']."的提现申请".L("UPDATE_SUCCESS") ,1);
                if (!$ajax) {
                    return array('function' => 'success', 'msg' => L("UPDATE_SUCCESS") . "\n" . $gs_Message);
                }
            }
            catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                \libs\utils\Alarm::push('payment', 'towithdrawal', $data['id'] . $e->getMessage());
                if ($ajax) {
                    $num ++;
                    continue;
                }
                else {
                    $_msg = $e->getMessage();
                    if ($e->getCode() == 2) {
                        $_msg = '支付接口异常:' . $e->getMessage() ;
                    }
                    return array('function' => 'error', "msg" => '提现操作失败，请稍后重试或联系技术人员！<br/>' . preg_replace('/\n/', '<br/>', $gs_Message) .'<br/><span style="color:gray;">'. $_msg . '</span>');
                }
            }
        }

        if ($ajax && $num == count($ids)) {
            return array('function' => 'error', 'msg' => "批量处理失败！\n" . $gs_Message);
        } else {
            return array('function' => 'success', 'msg' => L("UPDATE_SUCCESS") .  "\n" .$gs_Message);
        }
    }

    protected function _sendSms($userObj, $params, $tpl, $mark='')
    {
        // SMSSend 提现失败短信
        \libs\sms\SmsServer::instance()->send($userObj['mobile'], $tpl, $params, $userObj['id']);
    }

    /*
     * 待审核页面删除
     * @actionlock
     * @lockAuthor daiyuxin
     */
    function delwait()
    {
        $idarr = explode(",", $_GET['id']);
        $se = es_session::get(md5(conf("AUTH_KEY")));

        foreach($idarr as $id)
        {
            $vo = M(MODULE_NAME)->where("id=". $id)->find();

            // 只删除已被拒绝的
            if($vo['status'] == 2)
            {
                $vo = M(MODULE_NAME)->where("id=". $id)->delete();
                save_log("编号为".$id."的提现申请删除",1);
            }
        }

        $this->success(L("UPDATE_SUCCESS"), 1);
    }


    /*
     *申请列表页面删除
     * @actionlock
     * @lockAuthor daiyuxin
    */
    function del()
    {
        $idarr = explode(",", $_GET['id']);
        $se = es_session::get(md5(conf("AUTH_KEY")));

        foreach($idarr as $id)
        {
            $vo = M(MODULE_NAME)->where("id=". $id)->find();

            // 只删除已被拒绝的
            if($vo['status'] == 4 || $vo['status'] == 2)
            {
                $vo = M(MODULE_NAME)->where("id=". $id)->delete();
                save_log("编号为".$id."的提现申请删除",1);
            }
        }

        $this->success(L("UPDATE_SUCCESS"), 1);
    }

    /* 下载支付导入文件
     * 支付导入文件格式为：
     * 批次号;订单号;银行帐户名;银行帐号;开户银行;开户银行所在省;开户银行所在市;结算金额;打款原因;支行信息;手机号
     * 批次号 、订单号自定义，批次号可以重复。 订单号不可以。打款原因可以不填。支行信息不填有的银行可以， 有的银行不行。
     * */
    public function getYbCsv()
    {
        $idarr = explode(",", $_GET['id']);

        $content = '批次号,订单号,银行账户名,银行账号,开户银行,开户所在省,开户所在市,结算金额（提现金额+手续费）,打款原因,支行信息,用户ID';
        $content = iconv("utf-8", "gbk//IGNORE", $content) . "\n";

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => $idarr
                )
        );

        foreach($idarr as $id)
        {
            $id = intval($id);
            $info = M("UserCarry")->where("id=". $id)->find();

            // 只导出财务通过的
            if($info['status'] == 3)
            {
                $info['region_lv1_name'] = M("DeliveryRegion")->where("id=".$info['region_lv1'])->getField("name");
                $info['region_lv2_name'] = M("DeliveryRegion")->where("id=".$info['region_lv2'])->getField("name");
                $info['region_lv3_name'] = M("DeliveryRegion")->where("id=".$info['region_lv3'])->getField("name");
                $info['region_lv4_name'] = M("DeliveryRegion")->where("id=".$info['region_lv4'])->getField("name");
                //$info['mobile'] = M("user")->where("id=".$info['user_id'])->getField("mobile");

                // 生成数据
                /*
                $out = array();
                $out[] = "\"\t" . $id .  "\""; // 批次号
                $out[] = "\"\t" . $id .  "\""; // 订单号
                $out[] = iconv('utf-8','gbk',$info['real_name']); // 银行帐户名
                $out[] = "\"\t" . $info['bankcard'] .  "\""; // 银行帐号
                $out[] = iconv('utf-8','gbk',$info['bank_name']); // 开户银行
                $out[] = iconv('utf-8','gbk',$info['region_lv2_name']); // 开户银行所在省
                $out[] = iconv('utf-8','gbk',$info['region_lv3_name']); // 开户银行所在市
                $out[] = "\"\t" . ($info['money'] + $info['fee']) . "\""; // 结算金额 ， 提现金额+手续费
                $out[] = iconv('utf-8','gbk',$info['msg']); // 打款原因
                $out[] = iconv('utf-8','gbk', $info['bankzone']); // 支行信息
                $out[] = "\"\t" . $info['mobile'] . "\""; // 手机号
                */

                $bankId = $info['bank_id'];

                //受托支付银行信息显示逻辑
                if ($info['deal_id'] > 0) {
                    $dealInfo = M('Deal')->where("id='{$info['deal_id']}'")->find();
                    $projectInfo = M('DealProject')->where("id='{$dealInfo['project_id']}'")->find();
                    if ($projectInfo['loan_money_type'] == 3) {
                        $info['real_name'] = $projectInfo['card_name'];
                        $info['bankcard'] = $projectInfo['bankcard'];
                        $info['bankzone'] = $projectInfo['bankzone'];
                        $bankId = $projectInfo['bank_id'];

                        $info['region_lv1_name'] = '';
                        $info['region_lv2_name'] = '';
                        $info['region_lv3_name'] = '';
                        $info['region_lv4_name'] = '';
                    }
                }

                $info['bank_name'] =  M("bank")->where("id='{$bankId}'")->getField("name");

                $out = array();
                $out[] = $id; // 批次号
                $out[] = $id; // 订单号

                $out[] = iconv('utf-8','gbk',$info['real_name']); // 银行帐户名

                $out[] = iconv('utf-8', 'gbk', $info['bankcard']."\t　"); // 银行帐号
                $out[] = iconv('utf-8','gbk',$info['bank_name']); // 开户银行
                $out[] = iconv('utf-8','gbk',$info['region_lv2_name']); // 开户银行所在省
                $out[] = iconv('utf-8','gbk',$info['region_lv3_name']); // 开户银行所在市
                $out[] = ($info['money'] + $info['fee']); // 结算金额 ， 提现金额+手续费
                $out[] = iconv('utf-8','gbk',$info['msg']); // 打款原因
                $out[] = iconv('utf-8','gbk', $info['bankzone']); // 支行信息
                //$out[] = $info['mobile']; // 手机号
                $out[] = $info['user_id']; // 用户id


                $content .= implode(",", $out) . "\n";
            }
        }

        header("Content-Disposition: attachment; filename=" . time() .".csv");
        echo $content;
    }

    /**
     * 导出用户提现列表
     */
    public function get_carry_cvs()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '300M');
        $user = D("User");
        $condition = '1=1';
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
                $condition .= " AND deal_id in ($dealIds)";
            }
        }

        $timeType = trim(addslashes($_REQUEST['timeType']));
        //添加搜索条件，编号区间
        if (!empty($_REQUEST['withdraw_time_start'])) {
            $withdraw_time_start = to_timespan($_REQUEST['withdraw_time_start']);
            $condition .= " AND  {$timeType} >= " . $withdraw_time_start;
        }

        if (!empty($_REQUEST['withdraw_time_end'])) {
            $withdraw_time_end = to_timespan($_REQUEST['withdraw_time_end']);
            $condition .= " AND  {$timeType} <= " . $withdraw_time_end;
        }

        if (!empty($_REQUEST['id'])) {
            $condition .= ' AND id = ' .intval($_REQUEST['id']);
        }

        if ($_REQUEST['status'] !== '' && in_array($_REQUEST['status'], array(0,1,2,3,4))) {
            $condition .= ' AND status = ' .$_REQUEST['status'];
        } else {
            $condition .= ' AND status in (0,1,2,3,4)';
        }

        if ($_REQUEST['type'] !== '' && in_array($_REQUEST['type'], array(1,2,3))) {
            $condition .= ' AND type = ' .$_REQUEST['type'];
        }

        if (isset($_REQUEST['withdraw_status']) && $_REQUEST['withdraw_status'] !== '') {
            $condition .= ' AND withdraw_status = ' .$_REQUEST['withdraw_status'];
            //$map['withdraw_status'] = $_REQUEST['withdraw_status'];
        }
        $roll = isset($_REQUEST['roll']) ? intval($_REQUEST['roll']) : 0;
        if ($roll === 1) {
            $condition .= ' AND deal_id>0';
        }

        // 放款方式
        if (isset($_REQUEST['loanway']) && '' != $_REQUEST['loanway'] && 2 != $roll) {
            $loanway = addslashes($_REQUEST['loanway']);
            $sqlDealProject = "(SELECT `id` FROM `firstp2p_deal_project` WHERE `loan_money_type` = '{$loanway}')";
            $condition .= " AND (`deal_id` IN (SELECT `id` FROM `firstp2p_deal` WHERE `project_id` IN {$sqlDealProject}))";
        }
        // 放款类型
        if (isset($_REQUEST['loantype']) && '' != $_REQUEST['loantype'] && 2 != $roll) {
            $loantype = addslashes($_REQUEST['loantype']);
            $condition .= " AND `deal_id` IN (SELECT `deal_id` FROM `firstp2p_deal_ext` WHERE `loan_type` = '{$loantype}')";
        }

        if (!empty($_REQUEST['project_name'])) {
            $condition .= ' AND `deal_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal` WHERE `project_id` IN (SELECT `id` FROM `' . DB_PREFIX . 'deal_project` WHERE `name` = \'' . trim($_REQUEST['project_name']) .'\'))';
        }

        $sql = "SELECT * FROM " .DB_PREFIX. "user_carry WHERE $condition ORDER BY id DESC";
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

        $withdraw_status = core\dao\UserCarryModel::$withdrawDesc;
        $types = array('1' => '用户提现', '2' => '咨询服务费', '3' => '担保费', '4' => '咨询费');

        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename={$datatime}.csv");

        $title = array(
                '编号',
                '借款标题','项目名称', '放款方式', '放款类型',
                '用户ID', '会员名称', '用户姓名', '提现金额',
                '手续费', '申请时间', '状态', '类型', '备注', '异常款项备注',
                '处理时间', '支付状态', '支付时间',
                '开户名', '银行卡号', '开户行名称','支行名称','提现失败操作'
        );

        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gbk//IGNORE", $v);
        }

        $count = 1;
        $limit = 10000;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);

        $userCarryService = new UserCarryService();
        while($v = $GLOBALS['db']->fetchRow($res)) {
            $user_bank = get_user_bank_info($v['user_id']);

            $dealName = '';
            $loanMoneyTypeName = '';
            $loanTypeName = '';

            $cardName = $user_bank['card_name'];
            $cardNo = $user_bank['bankcard'];
            $bankName = $user_bank['bankName'];
            $bankZoneName = $user_bank['bankzone'];
            // 提现失败显示内容
            $withdrawFailedMsg = '';

            if ($v['deal_id'] > 0) {
                $dealInfo = MI('Deal')->where("id='{$v['deal_id']}'")->find();
                $loanType = MI('deal_ext')->where("deal_id='{$v['deal_id']}'")->getField('loan_type');
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
                $loanTypeName = UserCarryModel::$loantypeDesc[$loanType];

                if ($projectInfo['loan_money_type'] == 3) {
                    $cardName = $projectInfo['card_name'];
                    $cardNo = $projectInfo['bankcard'];
                    $bankId = intval($projectInfo['bank_id']);
                    $bankName = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT name FROM firstp2p_bank WHERE id = '{$bankId}'");
                    $bankZoneName = $projectInfo['bankzone'];
                }
                // 提现失败显示内容
                if ($v['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_FAILED) {
                    $withdrawFailedMsg = $userCarryService->canRedoWithdraw($v) ? '未操作' : '已操作';
                }
            }

            $arr = array();

            $arr[] = $v['id'];

            //借款标题、项目名称 放款方式、放款类型
            $arr[] = $dealName;
            $arr[] = $projectInfo['name'];
            $arr[] = $loanMoneyTypeName;
            $arr[] = $loanTypeName;

            $arr[] = $v['user_id'];
            $arr[] = $user->where("id=".$v['user_id'])->getField("user_name");
            $arr[] = $v['real_name'];
            $arr[] = $v['money'];
            $arr[] = $v['fee'];
            $arr[] = to_date($v['create_time']);
            $arr[] = L("CARRY_STATUS_" . $v['status']);
            $arr[] = $types[$v['type']];
            $arr[] = str_replace(array("</p>","<p>"),array('',''),$v['desc']);
            $arr[] = getWarningInfo($v['warning_stat'], "，", $v['money_limit']);
            // 处理时间更换
            $dealTime = "";
            if ($v['update_time_step1']) {
                $dealTime .= "运营：" . to_date($v['update_time_step1']) . "，";
            }

            if ($v['update_time_step2']) {
                $dealTime .= "财务：" . to_date($v['update_time_step2']) . "，";
            }
            $dealTime .= '系统自动处理：' . to_date($v['update_time']);
            $arr[] = $dealTime;
            $withdarwStatus = $withdraw_status[$v['withdraw_status']];
            // 非实际放款失败改失败状态
            if ($v['withdraw_status'] == 2)
            {
                $withdarwStatus = $projectInfo['loan_money_type']== 2 ? '提现还款' : $withdarwStatus;
            }
            $arr[] = $withdarwStatus;
            $arr[] = to_date($v['withdraw_time']);
            $arr[] = $cardName;
            $arr[] = $cardNo."\t ";
            $arr[] = $bankName."\t ";
            $arr[] = $bankZoneName."\t ";
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
     * 导入RDM
     */
    function import_rdm() {
        $carry_id = getRequestInt('id');
        if (!$carry_id) {
            exit;
        }

        $info = M("UserCarry")->where(array("id"=>$carry_id))->find();
        if ($info['deal_id']) {
            $issue_info = $this->_gene_deal_issue($info);
        } else {
            $issue_info = $this->_gene_user_issue($info);
        }
        $bank = M("Bank")->where(array("id"=>$info['bank_id']))->find();
        $user = M("User")->where(array("id"=>$info['user_id']))->find();
        $bank_info = M("Banklist")->where(array("name"=>$info['bankzone']))->find();
        $bank_id = $bank_info ? $bank_info['bank_id'] : "";
        $user_bankcard = UserBankcardModel::instance()->getNewCardByUserId($info['user_id'], '*', false);

        $region = new \core\dao\RegionConfModel();
        $province = $region->getRegionName($user_bankcard['region_lv2']);
        $city = $region->getRegionName($user_bankcard['region_lv3']);

        $bank_name = $bank['name'] . "," . $province . $city . "," . $info['bankzone'];

        $custom_fields = array(
            array("id"=>201, "value"=>$info['real_name']),
            array("id"=>202, "value"=>$bank_name),
            array("id"=>127, "value"=>$info['real_name'] . "款项"),
            array("id"=>258, "value"=>$carry_id),
            array("id"=>259, "value"=>$info['money']),
            array("id"=>260, "value"=>$user['user_name']),
            array("id"=>262, "value"=>$bank_id),
            array("id"=>247, "value"=>$info['bankcard']),
            array("id"=>250, "value"=>$user['idno']),
        );
        $issue_info['custom_fields'] = $custom_fields;

        $issue_id = $this->_insert_rdm($issue_info);
        if ($issue_id) {
            $this->success("操作成功", 1);
        } else {
            $this->error("操作失败", 1);
        }
    }

    private function _gene_deal_issue($info) {
        $deal = M("Deal")->where(array("id"=>$info['deal_id']))->find();
        $desc = $this->print_carry($info['deal_id']);
        $issue_info = array(
            "project_id" => "firstp2p_yuying",
            "tracker_id" => 8,
            "subject" => ("{$info['real_name']} {$deal['name']} （{$deal['id']}） " . number_format($deal['borrow_amount'], 2) . "元 {$deal['repay_time']}") . ($deal['loantype']==5 ? "天" : "个月"),
            "description" => $desc,
            "status_id" => 1,
            "priority_id" => 2,
        );
        return $issue_info;
    }

    private function _gene_user_issue($info) {
        $desc = $this->print_user($info);
        $issue_info = array(
            "project_id" => "firstp2p_yuying",
            "tracker_id" => 9,
            "subject" => $info['real_name'] . " " . number_format($info['money'], 2),
            "description" => $desc,
            "status_id" => 1,
            "priority_id" => 2,
        );
        return $issue_info;
    }

    private function _insert_rdm($issue_info) {
        $params = array("issue" => $issue_info);
        $str_params = json_encode($params);
        $str_params = str_replace("<br>", "\\r\\n", $str_params);

        $rdm_url = "http://pm.ncfgroup.com/issues.json";

        $http_header = array();
        $http_header[] = 'Content-Type: application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rdm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, 'p2psy:p2psy123');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str_params);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        //curl_setopt($ch, CURLOPT_PORT, 8080); 天津机房迁移变更
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        if ($result === false) {
            self::$code = curl_errno($ch);
        }
        curl_close($ch);

        $arr = json_decode($result, true);
        return $arr['issue']['id'];
    }

    /**
     * 打印用户提现
     */
    function print_carry($id = false){
        FP::import("app.deal");
        if ($id === false) {
            $deal_id = getRequestInt('dealid');
        } else {
            $deal_id = $id;
        }
        $list = array();
        if($deal_id){
            // 费用处理 update caolong 2014-3-3
            $deal_data = M('Deal')->where(array('is_delete' => 0, 'id' => $deal_id))->find();
            $loan_fee_rate = Finance::convertToPeriodRate($deal_data['loantype'], $deal_data['loan_fee_rate'], $deal_data['repay_time'], false);
            $consult_fee_rate = Finance::convertToPeriodRate($deal_data['loantype'], $deal_data['consult_fee_rate'], $deal_data['repay_time'], false);
            $guarantee_fee_rate = Finance::convertToPeriodRate($deal_data['loantype'], $deal_data['guarantee_fee_rate'], $deal_data['repay_time'], false);
            $loan_fee = $deal_data['borrow_amount'] * $loan_fee_rate / 100.0;
            $consult_fee = $deal_data['borrow_amount'] * $consult_fee_rate / 100.0;
            $guarantee_fee = $deal_data['borrow_amount'] * $guarantee_fee_rate / 100.0;
            $cost = array('loan_fee'=>$loan_fee,'consult_fee'=>$consult_fee,'guarantee_fee'=>$guarantee_fee);
            //
            $deal_load = M("DealLoad")->where('deal_id = '.$deal_id)->findAll();
            $deal = M("Deal")->where(array("id"=>$deal_id))->find();
            $user = get_user_info($deal['user_id'],true);
            $bank = M("UserBankcard")->where(array("user_id"=>$deal['user_id']))->find();
            $bank_name =  M("bank")->where("id=".$bank['bank_id'])->getField("name");
            $bank['bankzone'] = $bank_name.$bank['bankzone'];

            if($deal['loantype'] == 5){//按天
                $deal['text'] = $deal['repay_time'].'天';
            }else{
                $deal['text'] = $deal['repay_time'].'个月';
            }
            $usercarry = M('UserCarry')->where('deal_id = '.$deal_id)->findAll();
        }
        if($deal['agency_id']){
            //担保机构
            $agency = M("DealAgency")->where(array("id"=>$deal['agency_id']))->find();
        }
        if($deal['advisory_id']){
            //咨询机构
            $advisory = M("DealAgency")->where(array("id"=>$deal['advisory_id']))->find();
        }
        $this->assign('deal_load', $deal_load);
        $this->assign('agency', $agency);
        $this->assign('advisory', $advisory);
        $this->assign('deal', $deal);
        $this->assign('user', $user);
        $this->assign('bank', $bank);
        $this->assign('usercarry', $usercarry);
        $this->assign('cost',$cost);
        if ($id === false) {
            $this->display();
        } else {
            return $this->fetch("desc_carry");
        }
    }

    //打印用户明细信息 update caolong 2014-1-6
    public function print_user($carry_info=false) {
        if ($carry_info === false) {
            $user_id   = intval($_REQUEST['user_id']);
            $id        = intval($_REQUEST['id']);
        } else {
            $id = intval($carry_info['id']);
            $user_id = intval($carry_info['user_id']);
        }
        $user_info = M("User")->getById($user_id);
        $tips      = '';
        $result= $r= array();
        $time      = intval($_REQUEST['create_time']);
        $r = M ("UserLog")->where('user_id ='.$user_id)->select();
        $alikes = $this->getRepeatData($user_id);
        //$userCarry_service  = new UserCarryService();
        //$alikes = $userCarry_service->getAlikes($user_id,'提现申请');
        if($alikes) {
            $tips = '<span style="color:red;">24小时多次提现，连续相同资金记录</span>';
        }
        //用户提现
        $bank = M("UserBankcard")->where(array("user_id"=>$user_id))->find();

        if(!empty($bank)) {
            $bank['bankName'] = M("Bank")->where('id='.intval($bank['bank_id']))->getField('name');
        }
        FP::import("app.deal");
        if($id){
            $usercarry = M('UserCarry')->where('id = '.$id)->find();
        }
        $this->assign('bank', $bank);
        $this->assign('usercarry', $usercarry);
        $this->assign('user_info', $user_info);
        $this->assign('dealLog', $r);
        $this->assign('tips',$tips);
        if ($carry_info === false) {
            $this->display();
        } else {
            return $this->fetch("desc_user");
        }
    }

    /**
     * 获取重复提现数据
     */
    public function getRepeatData($userId,$info='提现申请') {
        $sql = "SELECT log_time FROM firstp2p_user_log WHERE user_id=".intval($userId)." AND log_info = '".$info."'  ORDER BY log_time DESC LIMIT 1 ";
        $rtime = $GLOBALS['db']->getOne($sql);
        if(empty($rtime)){
            return false;
        }
        $tmp = $rtime-86400;
        $sql = "SELECT * FROM firstp2p_user_log WHERE log_info = '".$info."'
                AND log_time <=".$rtime." and log_time>={$tmp} AND user_id=".intval($userId)."
               ";
        $r = $GLOBALS['db']->getAll($sql);
        return count($r) > 1 ? true : false;
    /*     foreach ($r as $k=>$v) {
            $r[$k]['date_time'] = date('Y-m-d H:i:s',$v['log_time']);
        }
        print_r($r); */
    }

    function _getSearchCondition() {

        if (trim($_REQUEST['user_name']) != '') {
            $map['user_id'] = D("User")->where("user_name='".trim($_REQUEST['user_name'])."'")->getField('id');
        }

        //添加搜索条件，编号区间
        $map['id'] = array('egt', intval($_REQUEST['id_start']));
        if ($_REQUEST['id_end']) {
            $map['id'] = array('between', sprintf("%s,%s", intval($_REQUEST['id_start']), intval($_REQUEST['id_end'])));
        }

        if ($_REQUEST['id']) {
            $map['id'] = $_REQUEST['id'];
        }

        if ($_REQUEST['status'] != '' && in_array($_REQUEST['status'], array(0,1,2,3,4))) {
            $map['status'] = $_REQUEST['status'];
        } else {
            $map['status'] = array('in',  '0, 1, 2, 3, 4');  //0 为未处理 1为运营已通过，2为运营拒绝 3为财务通过，4为财务拒绝
        }

        if ($_REQUEST['type'] != '' && in_array($_REQUEST['type'], array(1,2,3))) {
            $map['type'] = $_REQUEST['type'];
        }

        if (isset($_REQUEST['withdraw_status']) && $_REQUEST['withdraw_status'] !== '') {
            $map['withdraw_status'] = $_REQUEST['withdraw_status'];
        }
        return $map;
    }

    /**
     * 审核统一入口
     * doAudit
     * @actionLock
     * @lockAuthor luzhengshuai
     */
    public function doAudit() {
        // 获取参数
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        $audit = trim($_REQUEST['audit']);
        if (!in_array($audit, array('pass', 'refuse'))) {
            $this->error("无效的操作", $ajax);
        }

        if(!$ajax){
            //开始验证有效性
            parse_str($_SERVER['QUERY_STRING'], $query_arr);
            if (!$query_arr['search_id']) {
                unset($query_arr['id']);
            }
            unset($query_arr['m'], $query_arr['a'], $query_arr['search_id']);
            $this->assign("jumpUrl",u(MODULE_NAME."/index?".http_build_query($query_arr)));
        }

        // 判断权限
        $auditRole = 0;
        // 运营审核权限
        if ($this->is_have_action_auth(MODULE_NAME, "waitPass")) {
            $auditRole += 1;
        }

        // 财务审核权限
        if ($this->is_have_action_auth(MODULE_NAME, "update")) {
            $auditRole += 2;
        }

        if ($auditRole == 0) {
            $this->error("权限不足", $ajax);
        }

        // 记录更新时的错误消息
        $updateErrorMsg = '';
        // 分组
        foreach ($ids as $id) {
            // 是否放入更新数组里面
            $isPushArray = true;
            // 获取提现记录
            $vo = M(MODULE_NAME)->where("id = $id")->find();
            // 如果已经发送给支付，则禁止拒绝处理 Add By guofeng At 20160701 13:17
            if ($audit === 'refuse' && !empty($vo['withdraw_status'])
                && $vo['withdraw_status'] == UserCarryModel::WITHDRAW_STATUS_PROCESS)
            {
                $this->error("提现ID[{$id}]处于支付处理中，拒绝失败", $ajax);
            }

            if(!empty($vo['deal_id'])) {
                $deal = \core\dao\DealModel::instance()->find($vo['deal_id']);
                if (empty($deal)) {
                    $this->error('非法操作', $ajax);
                }
                $loan_type_info = M("Deal_loan_type")->where("id = ".intval($deal['type_id']))->find();
                if ($loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_XFFQ) {//信分期不允许操作
                    $this->error("您不能处理此类业务", $ajax);
                }
                // FIRSTPTOP-4093(点击批准按钮+财务待处理(status=1)+非实际放款(loan_money_type=2))
                if ($audit === 'pass' && $vo['status'] == 1 && !empty($deal['project_id'])) {
                    $projectInfo = M('deal_project')->where("id='{$deal['project_id']}'")->find();
                    // 确定是[非实际放款]
                    if (!empty($projectInfo['loan_money_type']) && $projectInfo['loan_money_type'] == 2) {
                        // 单个批准/批量批准
                        $updateErrorMsg = "“放款方式”为“非实际放款”的所选数据，未能执行，请查验";
                        $isPushArray = false;
                    }
                }
            }
            if ($vo['status'] == 0) {
                $waitPassIds[] = $id;
            } else if ($vo['status'] == 1) {
                $isPushArray && $updateIds[] = $id;
            }
        }
        if (!$waitPassIds && !$updateIds) {
            $this->error((!empty($updateErrorMsg) ? $updateErrorMsg : "没有需要处理的数据!"), $ajax);
        }

        // step1 待运营审核数据处理
        if ($waitPassIds) {
            if ($auditRole == 1 || $auditRole == 3) {
                $status = ($audit == "pass") ? 1 : 2;
                $waitPassResult = $this->_waitPass($waitPassIds, $status);
            }
        }

        // step2 待财务审核数据处理
        if ($updateIds) {
            if ($auditRole == 2 || $auditRole == 3) {
                $status = ($audit == "pass") ? 3 : 4;
                $updateResult = $this->_update($updateIds, $status, $ajax);
            }
        }

        if (!$updateResult && !$waitPassResult) {
            $this->error("权限不足", $ajax);
        }

        // 处理返回
        if ($waitPassResult['function'] == 'success' || $updateResult['function'] == 'success') {
            if (!empty($updateErrorMsg)) {
                $msg = $updateErrorMsg;
            }else{
                $msg = ($waitPassResult['function'] == 'success') ? $waitPassResult['msg'] : $updateResult['msg'];
            }
            $this->success($msg, $ajax);
        } else {
            // 如果批量批准失败的话，优先提示报错消息
            if (!empty($waitPassResult['msg'])) {
                $msg = $waitPassResult['msg'];
            } else if (!empty($updateResult['msg'])) {
                $msg = $updateResult['msg'];
            } else if (!empty($updateErrorMsg)) {
                $msg = $updateErrorMsg;
            }
            $this->error($msg, $ajax);
        }
    }

    /**
     * 提现取消入口(尚未发送给支付接口+2小时之前的提现记录，可以取消)
     * doCancel
     * @actionLock
     */
    public function doCancel() {
        // 获取参数
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $adminSession = es_session::get(md5(conf('AUTH_KEY')));

        try {
            if (empty($id)) {
                throw new \Exception('参数错误');
            }

            $is_auth = $this->is_have_action_auth(MODULE_NAME, 'doCancel');
            if ( ! $is_auth) {
                throw new \Exception('您还没有操作权限，请联系管理员');
            }

            // 两小时前的时间戳
            $withdrawTime = time() - 28800 - (3600 * 2);

            // 获取提现记录
            $condition = sprintf('`id`=\'%d\' AND `status` = \'%d\' AND `withdraw_status`=\'%d\'', $id, UserCarryModel::STATUS_ACCOUNTANT_PASS, UserCarryModel::WITHDRAW_STATUS_CREATE);
            $withdrawData = M(MODULE_NAME)->where($condition)->find();
            if (empty($withdrawData)) {
                throw new \Exception("提现ID[{$id}]记录不存在或状态已更新，取消失败");
            }
            // 尚未超过提现取消时效
            if ($withdrawData['create_time'] > $withdrawTime) {
                throw new \Exception("提现ID[{$id}]提现处理中，取消失败");
            }

            // 超过指定时效的记录，提现取消
            $userCarryService = new UserCarryService();
            $userCarryService->doRefuse($withdrawData['id'], 2);

            $log = $adminSession['adm_name'].'操作提现取消成功，用户ID：'.$withdrawData['user_id'].'，提现金额：' . format_price($withdrawData['money']);
            save_log($log, 1);
            PaymentApi::log(sprintf('%s, adminName:%s, msg:后台提现取消操作成功, withdrawId:%s, userId:%d, money:%s', __METHOD__, $adminSession['adm_name'], $id, $withdrawData['user_id'], format_price($withdrawData['money'])));
            $this->success('提现取消成功');
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s, msg:后台提现取消操作失败, adminName:%s, withdrawId:%s, errMsg:%s', __METHOD__, $adminSession['adm_name'], $id, $e->getMessage()));
            $this->error($e->getMessage());
        }
    }

    /**
     * 批量导入提现申请
     */
    public function import()
    {
        $this->display();
    }

    /**
     * 处理导入提现申请
     */
    public function doimport()
    {
        $adminSession = es_session::get(md5(conf('AUTH_KEY')));

        //参数检查
        $filename = isset($_FILES['upfile']['name']) ? trim($_FILES['upfile']['name']) : '';
        if (empty($filename)) {
            $this->error('请选择要上传的文件');
        }

        if (strtolower(substr($filename, -4)) !== '.csv') {
            $this->error('只支持上传csv格式的文件');
        }

        $content = file_get_contents($_FILES['upfile']['tmp_name']);
        $content = trim($content);
        if (iconv('gbk', 'utf-8', $content) !== false) {
            $content = iconv('gbk', 'utf-8', $content);
        }

        //解析文件
        $contentArray = explode("\n", $content);
        if (count($contentArray) < 2) {
            $this->error('上传的文件没有内容');
        }

        array_shift($contentArray);

        //检查数据
        $userData = array();
        $error = array();

        if (count($contentArray)>1000)
        {
            $this->error('导入的数据超过1000条');
        }
        $processedUserIds = [];

        foreach ($contentArray as $key => $item) {
            $line = $key + 2;
            $row = explode(',', $item);
            if (count($row) !== 2) {
                $error['格式检查'][] = "第{$line}行：数据格式错误";
                continue;
            }
            $userId = trim($row[0]);
            $money = trim($row[1]);
            if (in_array($userId, $processedUserIds))
            {
                $error['格式检查'][] = "第{$line}行: 用户ID{$userId}在同一个批次中重复出现";
                continue;
            }
            $processedUserIds[] = $userId;
            if (bccomp($money, '0.00', 2) < 0 || !preg_match('/^\-?\d{1,10}(\.\d{1,2})?$/', $money)) {
                $error['格式检查'][] = "第{$line}行: 金额{$money}有误";
                continue;
            }

            $userInfo = M('User')->where(array('id' => $userId))->find();
            // 用户是否存在, 如果用户id不存在， 停止后续所有条件校验
            if (empty($userInfo)) {
                $error[$userId][] = "提现会员ID{$userId}不存在";
                continue;
            }

            // 用户是否有效
            if (!empty($userInfo) && $userInfo['is_effect'] == 1) {
                $error[$userId][] = "提现会员ID{$userId}状态为有效";
            }

            // 用户是否绑卡
            $userService = new \core\service\UserService($userId);
            $userCheck = $userService->isBindBankCard();
            if ($userCheck['ret'] == false && $userCheck['respCode'] == \core\service\UserService::STATUS_BINDCARD_UNBIND) {
                $error[$userId][] = "提现会员ID{$userId}未绑卡";
            }

            //提现账户余额小于等于提现后剩余金额
            if (bccomp($userInfo['money'], $money, 2) <= 0)
            {
                $error[$userId][] = "提现会员ID{$userId}账户余额小于或等于提现后剩余金额";
            }

            $withdrawAmount = bcsub($userInfo['money'], $money, 2);

            if (bccomp($withdrawAmount, 1000.00, 2) >= 0)
            {
                $error[$userId][] = "提现会员ID{$userId}提现金额超过1000元";
            }
            if (!empty($error[$userId]))
            {
                continue;
            }
            // 获取用户银行卡信息
            $bankinfo = M("UserBankcard")->where("user_id = $userId")->find();
            $data['user_id'] = $userId;
            $data['money'] = $withdrawAmount;
            $data['fee'] = '0.00';
            $data['bank_id'] = $bankinfo['bank_id'];
            $data['bankcard'] = $bankinfo['bankcard'];
            $data['create_time'] = get_gmtime();
            $data['status'] = 3;
            $data['real_name'] = $bankinfo['card_name'];
            $data['region_lv1'] = $bankinfo['region_lv1'];
            $data['region_lv2'] = $bankinfo['region_lv2'];
            $data['region_lv3'] = $bankinfo['region_lv3'];
            $data['region_lv4'] = $bankinfo['region_lv4'];
            $data['bankzone'] = $bankinfo['bankzone'];
            $data['platform'] = PaymentNoticeModel:: PLATFORM_ADMIN;

            // 保存后台录入的描述信息,如果没有填写备注则记录操作人员名称
            $se = es_session::get(md5(conf("AUTH_KEY")));
            $data['desc'] = '后台批量提现 '.$se['adm_name'].'<p>运营：自动审批</p><p>财务：自动审批</p>';
            $data['update_time_step1'] = $data['update_time_step2'] = get_gmtime();
            $userData[] = $data;
        }

        if (!empty($error)) {
            $__msg = [];
            foreach ($error as $uid => $msgs)
            {
                $__msg[] = implode(',', $msgs);
            }
            $this->assign('waitSecond', 600);
            $this->error(implode('<br />', $__msg));
        }

        //更新会员账户信息
        \FP::import("libs.libs.user");
        //保存到数据库
        foreach ($userData as $item) {
            $GLOBALS['db']->startTrans();
            try {
                $fields = array_keys($item);
                $_fields = [];
                $_values = [];
                foreach ($fields as $f)
                {
                    $_fields[] = '`'.$f.'`';
                }
                $values= array_values($item);
                foreach ($values as $v)
                {
                    $_values[] = "'".addslashes($v)."'";
                }
                $fieldString = implode(',', $_fields);
                $valueString = implode(',', $_values);
                $sql = "INSERT INTO firstp2p_user_carry({$fieldString}) VALUES({$valueString})";
                $GLOBALS['db']->query($sql);
                $affectRows = $GLOBALS['db']->affected_rows();
                if ($affectRows <= 0) {
                    throw new \Exception('添加记录失败');
                }
                // 提现冻结
                $res = modify_account(array('money' => 0, 'lock_money' => $item['money'] + $item['fee']), $item['user_id'], "提现申请", true, "提现申请", false);
                if (!$res) {
                    throw new \Exception('修改用户账户金额失败');
                }

                $log = $adminSession['adm_name'].'通过批量导入申请修改'.$item['user_id'].'账户余额'.format_price($item['money']);
                save_log($log, 1);
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->error($e->getMessage(), 0, $url);
            }
        }


        $this->success('批量导入成功', 0, u('UserCarry/index'));
    }

    /**
     * 重新发起借款人失败的提现申请
     */
    public function redoWithdraw() {
        $id = intval($_REQUEST['id']);
        $user_carry = UserCarryModel::instance()->find($id);
        if (empty($user_carry)) {
            $this->ajaxReturn('非法操作','',0);
        }

        // 检查是否可重新申请提现
        $userCarryService = new UserCarryService();
        $can_redo = $userCarryService->canRedoWithdraw($user_carry);
        if (empty($can_redo)) {
            $this->error("非法操作");
        }

        // 复制原申请记录
        $user_carry_data = $user_carry->getRow();
        $user_carry_data['create_time'] = get_gmtime();
        $user_carry_data['update_time'] = get_gmtime();
        $user_carry_data['update_time_step1'] = get_gmtime();
        unset($user_carry_data['id']);
        unset($user_carry_data['desc']);
        unset($user_carry_data['withdraw_status']);
        unset($user_carry_data['withdraw_time']);
        unset($user_carry_data['withdraw_msg']);

        // 审批状态
        $deal = \core\dao\DealModel::instance()->find($user_carry['deal_id']);
        if (empty($deal)) {
            $this->error("非法操作");
        }
        $loan_type_info = M("Deal_loan_type")->where("id = ".intval($deal['type_id']))->find();
        if ($loan_type_info['type_tag'] == \core\dao\DealLoanTypeModel::TYPE_XFFQ ){
            $user_carry_data['update_time_step2'] = get_gmtime();
            $user_carry_data['status'] = 3; // 消费分期,进入自动审批
        } else {
            $user_carry_data['status'] = 1; // 其它进入财务审批
        }

        // 保存
        $user_carry_new = new UserCarryModel();
        $user_carry_new->setRow($user_carry_data);
        $rs = $user_carry_new->insert();
        if (!empty($rs)) {
            save_log("编号为".$id."的提现申请".L("UPDATE_SUCCESS") ,1);
            $this->success("操作成功");
        } else {
            $this->error("操作失败");
        }
    }

    /**
     * 自动提现配置
     */
    public function autoWithdraw() {
        $confModel = ConfModel::instance();
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : '';
            $hour = isset($_POST['hour']) ? $_POST['hour'] : '';
            $minute = isset($_POST['minute']) ? $_POST['minute'] : '';
            $emails = isset($_POST['emails']) ? $_POST['emails'] : '';

            if (empty($userIds) || empty($hour) || empty($minute) || empty($emails)) {
                return $this->error('缺少参数');
            }
            if ($hour > 23 || $hour < 0) {
                return $this->error('小时格式不正确');
            }
            if ($minute > 59 || $minute < 0) {
                return $this->error('分钟格式不正确');
            }
            $time = sprintf("%02d", $hour) . ':' . sprintf("%02d", $minute);

            $confModel->set('AUTO_WITHDRAW_USER_IDS', $userIds);
            $confModel->set('AUTO_WITHDRAW_TIME', $time);
            $confModel->set('AUTO_WITHDRAW_EMAILS', $emails);

            return $this->success("保存成功，10分钟后生效");
        }

        $userIds = $confModel->getValue('AUTO_WITHDRAW_USER_IDS'); //用户id
        $time = $confModel->getValue('AUTO_WITHDRAW_TIME'); //自动提现时间
        list($hour, $minute) = explode(':', $time);
        $emails = $confModel->getValue('AUTO_WITHDRAW_EMAILS'); //告警地址

        $this->assign('userIds', $userIds);
        $this->assign('hour', $hour);
        $this->assign('minute', $minute);
        $this->assign('emails', $emails);
        $this->display ();
    }

}
