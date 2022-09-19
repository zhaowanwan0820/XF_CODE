<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use app\models\dao\DealAgency;
class IndexAction extends AuthAction{
    //首页
    public function index(){
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $template = empty($adm_session['org_id']) ? 'index' : 'index_org';
        $this->display($template);
    }


    //框架头
    public function top()
    {
        $navs = M("RoleNav")->where("is_effect=1 and is_delete=0")->order("sort asc")->findAll();

        if($this->is_cn && app_conf('CN_ADMIN_SHOW_MENU')) {
            $allow_menus = explode("::",app_conf('CN_ADMIN_SHOW_MENU'));
            foreach($navs as $k=>$v) {
                if(!in_array($v['name'],$allow_menus)) {
                    unset($navs[$k]);
                }
            }
        }

        $adm_session = es_session::get(md5(conf("AUTH_KEY")));

        $this->assign("navs",$navs);
        $this->assign("adm_data",$adm_session);
        $this->display();
    }
    //框架左侧
    public function left()
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_id = intval($adm_session['adm_id']);

        if ($_REQUEST['id'] == 14) {
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_HONGBAO'));
        }else if($_REQUEST['id'] == 7){
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_YONGHU'));
        }else if($_REQUEST['id'] == 1 || $_REQUEST['id'] == 16 || $_REQUEST['id'] == 20 || $_REQUEST['id'] == 21){ 
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_TOUZIQUAN'));
        }else if($_REQUEST['id'] == 3){
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_XITONGQUANXIAN'));
        }else if($_REQUEST['id'] == 10){
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_HETONG'));
        }else if($_REQUEST['id'] == 18){
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_ZHIDUOXING'));
        }else if($_REQUEST['id'] == 8){
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_ORDER'));
        }else{
            $cn_left_menus = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU'));
        }
        $allow_menus = json_decode($cn_left_menus,true);
        $allow_menus = is_array($allow_menus) ? $allow_menus : array();
        $cn_left_menus_node_name_map = str_replace('`',"\"",app_conf('CN_ADMIIN_LEFT_MENU_NODE_NAME_MAP')); //菜单名称映射表
        $node_name_map = json_decode($cn_left_menus_node_name_map, true);

        $nav_id = intval($_REQUEST['id']);
        $nav_group = M("RoleGroup")->where("nav_id=".$nav_id." and is_effect = 1 and is_delete = 0")->order("sort asc")->findAll();
        foreach($nav_group as $k=>$v)
        {

            if($this->is_cn && !in_array($v['name'],array_keys($allow_menus[$nav_id]))) {
                unset($nav_group[$k]);
                continue;
            }

            $sql = "select role_node.`action` as a,role_module.`module` as m,role_node.id as nid,role_node.name as name from ".conf("DB_PREFIX")."role_node as role_node left join ".
                   conf("DB_PREFIX")."role_module as role_module on role_module.id = role_node.module_id ".
                   "where role_node.is_effect = 1 and role_node.is_delete = 0 and role_module.is_effect = 1 and role_module.is_delete = 0 and role_node.group_id = ".$v['id']." order by role_node.id asc";

            $nodes = M()->query($sql);

            $nav_group[$k]['nodes'] = $nodes;
        }
        $this->assign("menus",$nav_group);
        $this->display();
    }
    //默认框架主区域
    public function main()
    {
        //会员数
        //$total_user = MI("User")->count();
        //$total_verify_user = M("User")->where("is_effect=1")->count();
        //$total_verify_user = MI("User")->where("idcardpassed=1")->count();
        //$this->assign("total_user",$total_user);
        //$this->assign("total_verify_user",$total_verify_user);
        //满标的借款
        $suc_deal_count = MI("Deal")->where("publish_wait = 0 AND is_delete = 0 AND deal_status = 2")->count();
        //待审核的借款
        $wait_deal_count = MI("Deal")->where("publish_wait = 1 AND is_delete = 0 ")->count();
        /* $where_wait_user_arr = array('idcardpassed=3','creditpassed=3','workpassed=3','incomepassed=3',
                'housepassed=3','carpassed=3','marrypassed=3','edupassed=3',
                'skillpassed=3','videopassed=3','mobiletruepassed=3','residencepassed=3'
        ); */
        $where_wait_user_arr = 'idcardpassed=3';

        $where_wait_user = "is_delete=0 AND is_effect=1 AND {$where_wait_user_arr}";

        //待审核用户
        $wait_user_count = MI("User")->where($where_wait_user)->count();
        //等待用户确认的贷款
        $wait_confirm_deal_count =     count($this->wait_confirm_factory_data());
        //客户已确认的借款
        $confirm_deal_count = count($this->confirm_factory_data());

        //待审核提现申请
        $carry_count_0 = DI("UserCarry")->where("status = 0")->count();
        //待批准处理提现申请
        $carry_count_1 = DI("UserCarry")->where("status = 1")->count();
        //三日要还款的借款
        $threeday_repay_count = MI("Deal")->where("publish_wait = 0 AND is_delete = 0 AND deal_status = 4 AND (next_repay_time - ".get_gmtime().")/24/3600 between 0 AND 3 ")->count(); //24小时即将流标
        $twenty_four_count = MI("Deal")->where("publish_wait = 0 AND is_delete = 0 AND deal_status = 1 AND (start_time + enddate*24*3600 - ".get_gmtime()." ) < 24 * 3600")->count();
        //逾期未还款的
        //$yq_repay_count = M("Deal")->where("publish_wait = 0 AND is_delete = 0 AND parent_id !=0 AND deal_status = 4 AND (".get_gmtime()." - next_repay_time)/24/3600 > 0 ")->count();
        $deal_repay_service = new \core\service\DealRepayService();
        $yq_repay_count = $deal_repay_service->getDelayRepayCount();
        //未处理举报
        $reportguy_count = MI("Reportguy")->where("status = 0")->count();
        //未审核的充值申请
        $moneyapply_count = MI("MoneyApply")->where("status =0 AND parent_id =0")->count();
        //待审核更换银行卡用户
        $updateBank_count = MI("UserBankcardAudit")->where('status = 1')->count();
        $this->assign("wait_confirm_deal_count", $wait_confirm_deal_count);
        $this->assign("confirm_deal_count", $confirm_deal_count);
        $this->assign("twenty_four_count",$twenty_four_count);
        $this->assign("suc_deal_count",$suc_deal_count);
        $this->assign("wait_deal_count",$wait_deal_count);
        $this->assign("wait_user_count",$wait_user_count);
        $this->assign("info_deal_count",$info_deal_count);
        $this->assign("carry_count_0",$carry_count_0);
        $this->assign("carry_count_1",$carry_count_1);
        $this->assign("threeday_repay_count",$threeday_repay_count);
        $this->assign("yq_repay_count",$yq_repay_count);
        $this->assign("reportguy_count",$reportguy_count);
        $this->assign("moneyapply_count",$moneyapply_count);
        $this->assign("updateBank_count",$updateBank_count);

        $topic_count = MI("Topic")->where("is_effect = 1 and fav_id = 0")->count();
        $msg_count = MI("Message")->where("is_buy = 0")->count();
        $buy_msg_count = MI("Message")->count();



        $this->assign("topic_count",$topic_count);
        $this->assign("msg_count",$msg_count);
        $this->assign("buy_msg_count",$buy_msg_count);

        //订单数
        /* 暂不统计，如果有需求，找群强。
        $order_count = MI("DealOrder")->where("type = 0")->count();
        $this->assign("order_count",$order_count);

        $order_buy_count = MI("DealOrder")->where("pay_status=2 and type = 0")->count();
        $this->assign("order_buy_count",$order_buy_count);
        */

        //充值单数
        /*
        $incharge_order_buy_count = MI("DealOrder")->where("pay_status=2 and type = 1")->count();
        $this->assign("incharge_order_buy_count",$incharge_order_buy_count);


        $income_amount = MI("DealOrder")->sum("pay_amount");
        $refund_amount = MI("DealOrder")->sum("refund_money");
        $this->assign("income_amount",$income_amount);
        $this->assign("refund_amount",$refund_amount);
        */

        //统计数据  tm库查出来没有东西。MySQL returned an empty result set (i.e. zero rows).
        //dbo查询后，线上这个表纪录为0, 故，干掉之
        /*
        $reminder = MI("RemindCount")->find();
        if ($reminder) {
            $reminder['topic_count'] = intval(MI("Topic")->where("is_effect = 1 and relay_id = 0 and fav_id = 0 and create_time >".$reminder['topic_count_time'])->count());
            $reminder['msg_count'] = intval(MI("Message")->where("create_time >".$reminder['msg_count_time'])->count());
            $reminder['buy_msg_count'] = intval(MI("Message")->where("create_time >".$reminder['buy_msg_count_time'])->count());
            $reminder['order_count'] = intval(MI("DealOrder")->where("is_delete = 0 and type = 0 and pay_status = 2 and create_time >".$reminder['order_count_time'])->count());
            $reminder['refund_count'] = intval(MI("DealOrder")->where("is_delete = 0 and refund_status = 1 and create_time >".$reminder['refund_count_time'])->count());
            $reminder['retake_count'] = intval(MI("DealOrder")->where("is_delete = 0 and retake_status = 1 and create_time >".$reminder['retake_count_time'])->count());
            $reminder['incharge_count'] = intval(MI("DealOrder")->where("is_delete = 0 and type = 1 and pay_status = 2 and create_time >".$reminder['incharge_count_time'])->count());

            M("RemindCount")->save($reminder);
        }
        $this->assign("reminder",$reminder);
        */
        /* $money_count = M("user")->where("is_delete=0")->sum("money");
        $this->assign("money_count",$money_count); */

        //平台关联账户余额
        // 超级慢sql
        /*
        $agency_userid[] = app_conf('DEAL_CONSULT_FEE_USER_ID');
        $agency_list = DealAgency::instance()->findAll('is_effect = 1');

        if($agency_list){
            foreach ($agency_list as $agency_one){
                $agency_userid[] = $agency_one['user_id'];
            }
        }
        $agency_userids = implode(',', array_unique($agency_userid));



        $platformMoney = MI("user")->where("id IN ($agency_userids)")->sum("money");

        //用户余额（正）
        $userMoneyPlus = MI("user")->where("is_delete=0 AND money > 0 AND id NOT IN ($agency_userids)")->sum("money");

        //用户余额（负）
        $userMoneyMinus = MI("user")->where("is_delete=0 AND money < 0 AND id NOT IN ($agency_userids)")->sum("money");

        $this->assign("platformMoney",$platformMoney);
        $this->assign("userMoneyPlus",$userMoneyPlus);
        $this->assign("userMoneyMinus",$userMoneyMinus);
        */
        $this->display();
    }
    //底部
    public function footer()
    {
        $this->display();
    }

    //修改管理员密码
    public function change_password()
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $this->assign("adm_data",$adm_session);
        $this->assign("force", $_REQUEST['force']);
        $this->display();
    }
    public function do_change_password()
    {
        $adm_id = intval($_REQUEST['adm_id']);
        if(!check_empty($_REQUEST['adm_password']))
        {
            $this->error(L("ADM_PASSWORD_EMPTY_TIP"));
        }
        if(!check_empty($_REQUEST['adm_new_password']))
        {
            $this->error(L("ADM_NEW_PASSWORD_EMPTY_TIP"));
        }
         if($_REQUEST['adm_password'] == $_REQUEST['adm_new_password'])
        {
            $this->error(L("ADM_NEW_PASSWORD_CAN_NOT_USE_LAST_ONE"));
        }
       if($_REQUEST['adm_confirm_password']!=$_REQUEST['adm_new_password'])
        {
            $this->error(L("ADM_NEW_PASSWORD_NOT_MATCH_TIP"));
        }
        if(M("Admin")->where("id=".$adm_id)->getField("adm_password")!=md5($_REQUEST['adm_password']))
        {
            $this->error(L("ADM_PASSWORD_ERROR"));
        }
        vendor('Psecio.Pwdcheck.Password');
        $pwdObj = new \Psecio\Pwdcheck\Password();
        $pwdObj->evaluate($_REQUEST['adm_new_password']);
        if($pwdObj->getScore()<80){
            $this->error('密码强度不够');
        }
        M("Admin")->where("id=".$adm_id)->setField( "adm_password", md5($_REQUEST['adm_new_password']));
        M("Admin")->where("id=".$adm_id)->setField( "force_change_pwd", 1);
        M("Admin")->where("id=".$adm_id)->setField( "password_update_time", time());
        save_log(M("Admin")->where("id=".$adm_id)->getField("adm_name").L("CHANGE_SUCCESS"),1);
        //$this->success(L("CHANGE_SUCCESS"));

        $this->redirect(u("Public/do_loginout"));

    }

    public function reset_sending()
    {
        $field = trim($_REQUEST['field']);
        if($field=='DEAL_MSG_LOCK'||$field=='PROMOTE_MSG_LOCK'||$field=='APNS_MSG_LOCK')
        {
            M("Conf")->where("name='".$field."'")->setField("value",'0');
            $this->success(L("RESET_SUCCESS"),1);
        }
        else
        {
            $this->error(L("INVALID_OPERATION"),1);
        }
    }

    public function welcome(){
        if ($this->is_cn) {
           $name = '普惠';
        } else {
           $name = '理财';
        }
        echo "<html><head><meta charset='utf-8'></head><body style='margin:100px 0;text-align:center'>
        <h1 style='color:#666;font-size:50px;'>网信".$name."后台管理系统</h1><h3 style='color:#999'>您的所有操作行为将会被记录，请谨慎操作！</h3></body></html>";
    }

}
?>
