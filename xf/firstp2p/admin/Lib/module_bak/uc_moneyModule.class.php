<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

//FP::import("app.uc");
//
//class uc_moneyModule extends SiteBaseModule
//{
//    private $creditsettings;
//    private $allow_exchange = false;
//    private  $credits_CFG = array(
//        '1' => array('title'=>'经验', 'unit'=>'' ,'field'=>'point'),
//        '2' => array('title'=>'积分', 'unit'=>'' ,'field'=>'score'),
//        '3' => array('title'=>'资金', 'unit'=>'' ,'field'=>'money'),
//        '4' => array('title'=>'额度', 'unit'=>'' ,'field'=>'quota'),
//        '5' => array('title'=>'冻结', 'unit'=>'' ,'field'=>'lock_money'),
//    );
//    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');
//    public static $message = array('code'=>'0000','message'=>'操作成功');
//
//    /**
//     * 筛选条件
//     * @var unknown
//     */
//    private $filter_arr = array('充值','提现申请','提现成功','提现失败','投标冻结','投资放款','取消投标','还本','付息',
//        '提前还款','提前还款补偿金','逾期罚息','邀请返利','投资返利','投资管理费','招标成功','偿还本息',
//        '提前还款申请','提前还款申请通过','提前还款申请拒绝','服务费和担保费','转账-账户转出资金','转账-账户转入资金',
//        '账户余额贴息', '投标贴息');
//
//    public function __construct()
//    {
//        if(file_exists(APP_ROOT_PATH."public/uc_config.php"))
//        {
//            require_once APP_ROOT_PATH."public/uc_config.php";
//        }
//        if(app_conf("INTEGRATE_CODE")=='Ucenter'&&UC_CONNECT=='mysql')
//        {
//            if(file_exists(APP_ROOT_PATH."public/uc_data/creditsettings.php"))
//            {
//                require_once APP_ROOT_PATH."public/uc_data/creditsettings.php";
//                $this->creditsettings = $_CACHE['creditsettings'];
//                if(count($this->creditsettings)>0)
//                {
//                    foreach($this->creditsettings as $k=>$v)
//                    {
//                        $this->creditsettings[$k]['srctitle'] = $this->credits_CFG[$v['creditsrc']]['title'];
//                    }
//                    $this->allow_exchange = true;
//                    $GLOBALS['tmpl']->assign("allow_exchange",$this->allow_exchange);
//                }
//            }
//        }
//        parent::__construct();
//    }
//
//
//    public function exchange()
//    {
//        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($GLOBALS['user_info']['id']));
//        $GLOBALS['tmpl']->assign("user_info",$user_info);
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_EXCHANGE']);
//        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_money_exchange.html");
//
//        $GLOBALS['tmpl']->assign("exchange_data",$this->creditsettings);
//        $GLOBALS['tmpl']->assign("exchange_json_data",json_encode($this->creditsettings));
//
//        $GLOBALS['tmpl']->display("page/uc.html");
//    }
//
//    public function doexchange()
//    {
//        if($this->allow_exchange)
//        {
//            $user_pwd = md5(addslashes(trim($_REQUEST['password'])));
//            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($GLOBALS['user_info']['id']));
//
//            if($user_info['user_pwd']=="")
//            {
//                //判断是否为初次整合
//                //载入会员整合
//                $integrate_code = trim(app_conf("INTEGRATE_CODE"));
//                if($integrate_code!='')
//                {
//                    $integrate_file = APP_ROOT_PATH."system/integrate/".$integrate_code."_integrate.php";
//                    if(file_exists($integrate_file))
//                    {
//                        require_once $integrate_file;
//                        $integrate_class = $integrate_code."_integrate";
//                        $integrate_obj = new $integrate_class;
//                    }
//                }
//                if($integrate_obj)
//                {
//                    $result = $integrate_obj->login($user_info['user_name'],$user_pwd);
//                    if($result['status'])
//                    {
//                        $GLOBALS['db']->query("update ".DB_PREFIX."user set user_pwd = '".$user_pwd."' where id = ".$user_info['id']);
//                        $user_info['user_pwd'] = $user_pwd;
//                    }
//                }
//            }
//            if($user_info['user_pwd']==$user_pwd)
//            {
//                $cfg = $this->creditsettings[addslashes(trim($_REQUEST['key']))];
//                if($cfg)
//                {
//                    $amount = floor($_REQUEST['amountdesc']);
//                    $use_amount = floor($amount*$cfg['ratio']); //消耗的本系统积分
//                    $field = $this->credits_CFG[$cfg['creditsrc']]['field'];
//
//                    if($user_info[$field]<$use_amount)
//                    {
//                        $data = array("status"=>false,"message"=>$cfg['srctitle']."不足，不能兑换");
//                        return ajax_return($data);
//                    }
//
//                    include_once(APP_ROOT_PATH . 'uc_client/client.php');
//                    $res = call_user_func_array("uc_credit_exchange_request", array(
//                        $user_info['integrate_id'],  //uid(整合的UID)
//                        $cfg['creditsrc'],  //原积分ID
//                        $cfg['creditdesc'],  //目标积分ID
//                        $cfg['appiddesc'],  //toappid目标应用ID
//                        $amount,  //amount额度(计算过的目标应用的额度)
//                    ));
//                    if($res)
//                    {
//                        //兑换成功
//                        $use_amount = 0 - $use_amount;
//                        $credit_data = array($field=>$use_amount);
//                        FP::import("libs.libs.user");
//                    // TODO finance 前台老系统 ucenter 兑换支出 | 不同步
//                        modify_account($credit_data,$user_info['id'],"兑换支出",0,"ucenter兑换支出");
//                        $data = array("status"=>true,"message"=>"兑换成功");
//                        return ajax_return($data);
//                    }
//                    else
//                    {
//                        $data = array("status"=>false,"message"=>"兑换失败");
//                        return ajax_return($data);
//                    }
//                }
//                else
//                {
//                    $data = array("status"=>false,"message"=>"非法的兑换请求");
//                    return ajax_return($data);
//                }
//            }
//            else
//            {
//                $data = array("status"=>false,"message"=>"登录密码不正确");
//                return ajax_return($data);
//            }
//        }
//        else
//        {
//            $data = array("status"=>false,"message"=>"未开启兑换功能");
//            return ajax_return($data);
//        }
//    }
//
//    public function index()
//    {
//        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($GLOBALS['user_info']['id']));
//        $level_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where id = ".intval($user_info['group_id']));
//        $point_level = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_level where id = ".intval($user_info['level_id']));
//        $user_info['user_level'] = $level_info['name'];
//        $user_info['point_level'] = $point_level['name'];
//        $user_info['discount'] = $level_info['discount']*10;
//        $GLOBALS['tmpl']->assign("user_data",$user_info);
//        $t = addslashes(htmlspecialchars(trim($_REQUEST['t'])));
//        $GLOBALS['tmpl']->assign("t",$t);
//        $page = intval($_REQUEST['p']);
//        if($page==0)
//        $page = 1;
//        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");
//        $result = get_user_log($limit,$GLOBALS['user_info']['id'],$t);
//
////         print_r($result['list']);exit;
//
//        $GLOBALS['tmpl']->assign("list",$result['list']);
//        $page = new Page($result['count'],app_conf("PAGE_SIZE"));   //初始化分页对象
//        $p  =  $page->show();
//        if($t){
//            //筛选条件
//            $GLOBALS['tmpl']->assign('filter',$this->filter_arr);
//            $GLOBALS['tmpl']->assign('pages',$p);
//        }
//
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_MONEY']);
//
//        if($t == 'money'){
//            $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_money_index_new.html");
//            $GLOBALS['tmpl']->assign('search_get', array('log_info' => getRequestString('log_info',''), 'start' => getRequestString('start',''), 'end' => getRequestString('end','')));
//            //$this->set_nav(array("我的P2P"=>url("index", "uc_center"), "资金记录"));
//            $this->display();
//        }else{
//            /* $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_money_index.html");
//            $GLOBALS['tmpl']->display("page/uc.html"); */
//            return app_redirect(APP_ROOT."/");
//        }
//
//    }
//
//
//    public function incharge_bak()
//    {
//
//        //获取用户银行卡信息
//        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//        // 如果用户没有银行卡信息或者信息没有确认保存过
//        if(!$bankcard_info || $bankcard_info['status'] != 1) {
//            $this->redircet_bank_info();
//        }
//
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_MONEY_INCHARGE']);
//
//        // 判断该用户是否在先锋支付的白名单
//        $xfjr_white_list  = 0;
//        $sql = "select * from ".DB_PREFIX."white_list where user_id = '{$GLOBALS['user_info']['id']}' and project = 'Xfjr' and type = 0 ";
//        $white_list = $GLOBALS['db']->getRow($sql);
//
//        // 判断时间
//        if($white_list)
//        {
//            $now = get_gmtime();
//
//            if($white_list['start_time'] == 0 || $white_list['start_time'] <= $now)
//            {
//                if($white_list['end_time'] == 0 || $white_list['end_time'] >= $now)
//                    $xfjr_white_list = 1;
//                else
//                    $xfjr_white_list = 0;
//            }
//            else
//                $xfjr_white_list = 0;
//        }
//
//        //输出支付方式
//        $payment_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."payment where is_effect = 1 and class_name <> 'Account' and class_name <> 'Voucher' and class_name <> 'tenpayc2c' and online_pay = 1 order by sort desc");
//        foreach($payment_list as $k=>$v)
//        {
//            // 如果不在白名单就屏蔽
//            if($v['class_name']=='Xfjr' && !$xfjr_white_list)
//            {
//                unset($payment_list[$k]);
//                continue;
//            }
//
//            $payment_list[$k]['max_fee'] = round($payment_list[$k]['max_fee'], 2);
//
//            if($v['class_name']=='Alipay')
//            {
//                $cfg = unserialize($v['config']);
//                if($cfg['alipay_service']!=2)
//                {
//                    unset($payment_list[$k]);
//                    continue;
//                }
//            }
//            $directory = APP_ROOT_PATH."system/payment/";
//            $file = $directory. '/' .$v['class_name']."_payment.php";
//            if(file_exists($file))
//            {
//                require_once($file);
//                $payment_class = $v['class_name']."_payment";
//                $payment_object = new $payment_class();
//                $payment_list[$k]['display_code'] = $payment_object->get_display_code();
//
//                if($payment_list[$k]['fee_type'] == 1)
//                    $payment_list[$k]['fee_amount'] = floatval($payment_list[$k]['fee_amount'])  * 100;
//                else
//                    $payment_list[$k]['fee_amount'] = floatval($payment_list[$k]['fee_amount']);
//            }
//            else
//            {
//                unset($payment_list[$k]);
//            }
//
//
//        }
//        $payment_list = array_values($payment_list); // 去除key解决前台显示js问题
//        $GLOBALS['tmpl']->assign("payment_list",$payment_list);
//
//
//
//        //输出充值订单
//        $page = intval($_REQUEST['p']);
//        if($page==0)
//        $page = 1;
//        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");
//
//        $result = get_user_incharge($limit,$GLOBALS['user_info']['id']);
//
//
//        $GLOBALS['tmpl']->assign("list",$result['list']);
//        $page = new Page($result['count'],app_conf("PAGE_SIZE"));   //初始化分页对象
//        $p  =  $page->show();
//        $GLOBALS['tmpl']->assign('pages',$p);
//
//        $GLOBALS['tmpl']->assign('handling_charge', $GLOBALS['dict']['HANDLING_CHARGE']);  // 充值手续费最高上限
//        #$GLOBALS['tmpl']->assign('handling_charge', $payment_list['max_fee']);
//        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_money_incharge_bak.html");
//        $GLOBALS['tmpl']->display("page/uc.html");
//    }
//
//    /**
//     * 支付银行列表页
//     */
//    function incharge() {
//        //获取用户银行卡信息
//        $bankcard = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "user_bankcard where user_id =" . $GLOBALS['user_info']['id'] . " limit 1");
//        // 如果用户没有银行卡信息或者信息没有确认保存过
//        if (!$bankcard || $bankcard['status'] != 1) {
//            $this->redircet_bank_info();
//        }
//
//        // 银行列表
//        require_once(APP_ROOT_PATH . "system/payment/Yeepay_payment.php");
//        $payment_object = new Yeepay_payment();
//        $bank_list = $payment_object->get_bank_list();
//
//        // 最近使用银行
//        $latest_order = get_user_incharge(1, $GLOBALS['user_info']['id']);
//        $latest_bank = false;
//        if (!empty($latest_order) && !empty($latest_order['list']) && !empty($latest_order['list'][0]['bank_id'])) {
//            $latest_order_bank_id = $latest_order['list'][0]['bank_id'];
//                foreach ($bank_list as $key=>$val){
//                    if($val['short_name'] == $latest_order_bank_id) {
//                        $latest_bank = $bank_list[$key];
//                        unset($bank_list[$key]);
//                    }
//                }
//        }
//        $bank_list_new  = $bank_list;
//        $first_bank     = array_shift($bank_list_new);
//        $first_bank_info= $this->AccessAuxiliary($first_bank['id']);
//
//     // print_r($bank_list);
//
//        $latest_list = $this->AccessAuxiliary($latest_bank['id']);
//        $GLOBALS['tmpl']->assign("latest_list",$latest_list);
//        $GLOBALS['tmpl']->assign("first_bank_info",$first_bank_info);
//        $GLOBALS['tmpl']->assign("latest_bank", $latest_bank);
//        $GLOBALS['tmpl']->assign("bank_list", $bank_list);
//        $GLOBALS['tmpl']->assign("inc_file", "inc/uc/uc_money_incharge.html");
//        $this->display();
//    }
//
//    //获取配置附属额度信息
//    private function AccessAuxiliary($charge_id) {
//        $list      = array('name'=>'','total'=>0,'list'=>array());
//        if(!empty($charge_id)) {
//            $sql            = 'SELECT NAME FROM '.DB_PREFIX.'bank_charge WHERE id = '.$charge_id;
//            $list['name']   = $GLOBALS['db']->getOne($sql);
//            $sql            = "SELECT id,charge_id,category,card_type,one_money,date_norm FROM ".DB_PREFIX."bank_charge_auxiliary WHERE STATUS =0 AND charge_id =".$charge_id;
//            $result         = $GLOBALS['db']->getAll($sql);
//            $list['total']  = count($result);
//            $list['list']   = $result;
//        }
//        return $list;
//    }
//
//    /**
//     * 获取银行配置附属额度信息
//     * @author  caolong
//     * @date    2014-2-14
//     */
//    public function getBankAuxiliary() {
//        $charge_id = intval($_POST['charge_id']);
//        $list      = array('name'=>'','total'=>0,'list'=>array());
//        if(!empty($charge_id)) {
//            $sql            = 'SELECT NAME FROM '.DB_PREFIX.'bank_charge WHERE id = '.$charge_id;
//            $list['name']   = $GLOBALS['db']->getOne($sql);
//            $sql            = "SELECT id,charge_id,category,card_type,one_money,date_norm FROM ".DB_PREFIX."bank_charge_auxiliary WHERE STATUS =0 AND charge_id =".$charge_id;
//            $result         = $GLOBALS['db']->getAll($sql);
//            $list['total']  = count($result);
//            $list['list']   = $result;
//            self::$message = array('code'=>'0000','message'=>$list);
//        }else{
//            self::$message = array('code'=>'4000','message'=>'参数错误');
//        }
//        echo json_encode(self::$message);
//    }
//
//
//    // 跟据支付接口获取相应的收费费率
//    function getFeeAmount($payment)
//    {
//            $payment_id = intval($payment);
//            $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where id = ".$payment_id);
//            echo $payment_info['fee_amount'];
//            exit;
//    }
//
//    public function incharge_done()
//    {
//        // 验证表单令牌
//        if(!check_token())
//        {
//            showErr($GLOBALS['lang']['TOKEN_ERR']);
//        }
//
//
//        $payment_id = intval($_REQUEST['payment']);
//        $money = floatval($_REQUEST['money']);
//        $pd_FrpId = str_replace("-", "_", $_REQUEST['pd_FrpId']);
//
//        if($money<0.01)
//        {
//            showErr($GLOBALS['lang']['PLEASE_INPUT_CORRECT_INCHARGE']);
//        }
//                $bankid=addslashes(htmlspecialchars(trim($_REQUEST['pd_FrpId'])));
//        $bankshort = explode('-',$bankid);
//
//                //是否使用-先锋支付
//                $use_xfjr=$GLOBALS['db']->getOne("select `value` from ".DB_PREFIX."conf where `name`='PAYMENT_USE_XFJR'");
//                if($use_xfjr==1)
//                {
//                    $xfjr_bank_id=$GLOBALS['db']->getOne("select `short_name` from ".DB_PREFIX."bank_charge where `value`='{$bankid}'");
//                    //关闭先锋支付IP限制
////                    $dic_id=$GLOBALS['db']->getOne("select `id` from ".DB_PREFIX."dictionary where `key`='PAYMENT_XFJR_IP'");
////                    if($dic_id)
////                    {
////                        $xfjr_ip=$GLOBALS['db']->getCol("select `value` from ".DB_PREFIX."dictionary_value where `key_id`=".$dic_id);
////                        if($xfjr_ip && in_array(get_client_ip(),$xfjr_ip)===true)
////                        {
//                            $xfjr_banklist=trim($GLOBALS['db']->getOne("select `value` from ".DB_PREFIX."conf where `name`='PAYMENT_XFJR_BANK'"));
//                            if(in_array($xfjr_bank_id,explode(',',trim($xfjr_banklist,','))))
//                            {
//                                $payment_id =4;//先锋支付ID
//                                $pd_FrpId=$xfjr_bank_id;
//                                $bankshort[0]=$xfjr_bank_id;
//                            }
////                        }
////                    }
//
//                }
//
//        $payment_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where id = ".$payment_id);
//        if(!$payment_info)
//        {
//            showErr($GLOBALS['lang']['PLEASE_SELECT_PAYMENT']);
//        }
//        //开始生成订单
//        $now = get_gmtime();
//        $order['type'] = 1; //充值单
//        $order['user_id'] = $GLOBALS['user_info']['id'];
//        $order['create_time'] = $now;
//
//        // 定额收取手续费
//        if($payment_info['fee_type'] == 0)
//            $order['total_price'] = $money + $payment_info['fee_amount'];
//        else
//        {
//            //$handling_charge = $GLOBALS['dict']['HANDLING_CHARGE'];
//            $handling_charge = $payment_info['max_fee'];
//
//               $fm = $money * $payment_info['fee_amount'] / ( 1 - $payment_info['fee_amount'] ); // 收取的手续费
//               $fm = round($fm, 2); // 取2位小数四舍五入
//
//               if($fm > $handling_charge)
//               {
//                   $fm = $handling_charge;
//                   $order['total_price'] = $money + $fm;
//               }
//               else
//                   $order['total_price'] = $money / (1 - $payment_info['fee_amount']); // 支付总额
//
//            $order['total_price'] = round($order['total_price'], 2);
//        }
//
//        $order['deal_total_price'] = $money;
//        $order['pay_amount'] = 0;
//        $order['pay_status'] = 0;
//        $order['delivery_status'] = 5;
//        $order['order_status'] = 0;
//        $order['payment_id'] = $payment_id;
//
//        $order['bank_id'] = $bankshort[0];
//
//        if($payment_info['fee_type'] == 0)
//            $order['payment_fee'] = $payment_info['fee_amount'];
//        else
//            $order['payment_fee'] = $fm;
//
//        //$order['bank_id'] = addslashes(htmlspecialchars(trim($_REQUEST['bank_id'])));
//
//        do
//        {
//            $order['order_sn'] = to_date(get_gmtime(),"YmdHis").str_pad(rand(1,999999), 6, '0', STR_PAD_LEFT);
//            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_order",$order,'INSERT','','SILENT');
//            $order_id = intval($GLOBALS['db']->insert_id());
//        }while($order_id==0);
//
//        FP::import("libs.libs.cart");
//        $payment_notice_id = make_payment_notice($order['total_price'],$order_id,$payment_info['id']);
//        //创建支付接口的付款单
//
//        $rs = order_paid($order_id);
//        if($rs)
//        {
//            return app_redirect(url("index","payment#incharge_done",array("id"=>$order_id))); //充值支付成功
//        }
//        else
//        {
//            return app_redirect(url("index","payment#pay",array("id"=>$payment_notice_id, 'pd_FrpId' =>$pd_FrpId )));
//        }
//    }
//
//    public function carry()
//    {
//        //echo base64_decode('MjIx576O6YeR');
//        //获取用户银行卡信息
//        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//        // 如果用户没有银行卡信息或者信息没有确认保存过
//        if(!$bankcard_info || $bankcard_info['status'] != 1) {
//            $this->redircet_bank_info();
//        }
//        // 检测身份证信息,没有认证就跳转填写
//        if ($GLOBALS['user_info']['idcardpassed'] != 1) {
//            //showErr('请先填写身份证信息',0,url("shop","uc_account"),0);
//            $GLOBALS['tmpl']->assign("page_title","成为投资者");
//            $GLOBALS['tmpl']->display("page/deal_mobilepaseed.html");
//            exit();
//        }
//
//        make_delivery_region_js();
//        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."bank ORDER BY is_rec DESC,sort DESC,id ASC");
//        $GLOBALS['tmpl']->assign("bank_list",$bank_list);
//
//        //todo 把状态等等的值都设计为常量
//        $carry_total_money = $GLOBALS['db']->getOne("SELECT sum(money) FROM ".DB_PREFIX."user_carry WHERE user_id=".intval($GLOBALS['user_info']['id'])." AND status=3");
//
//        $GLOBALS['tmpl']->assign("carry_total_money",$carry_total_money);
//
//        //地区列表
//        $region_lv1 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."delivery_region where region_level = 1");  //二级地址
//        $GLOBALS['tmpl']->assign("region_lv1",$region_lv1);
//
//        //用户银行卡信息
//        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//        if($bankcard_info){
//            $bankcard_info['hideCard'] = $this->getHideCard($bankcard_info['bankcard']);
//            foreach($bank_list as $k=>$v){
//                if($v['id'] == $bankcard_info['bank_id']){
//                    $bankcard_info['is_rec'] = $v['is_rec'];
//                    $bankcard_info['bankName'] = $v['name'];
//                    break;
//                }
//            }
//           // print_r($bankcard_info);
//            $GLOBALS['tmpl']->assign('bankcard_info',$bankcard_info);
//        }
//
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_CARRY']);
//        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_money_carry.html");
//        $this->display();
//        //$GLOBALS['tmpl']->display("page/uc.html");
//    }
//
//    private function getHideCard($card='') {
//        $str = '';
//        if(!empty($card)) {
//            $card = trim($card);
//            $start = substr($card, 0,4);
//            $middel= substr($card, 4,-4);
//            $end   = substr($card,(strlen($middel))+4,4);
//            for ($i=1;$i<=strlen($middel);$i++) {
//                $str.="*";
//            }
//            return $start.$str.$end;
//        }
//    }
//
//    public function savecarry(){
//
//        // 验证表单令牌
//        if(!check_token())
//        {
//            showErr($GLOBALS['lang']['TOKEN_ERR']);
//        }
//
//        if($GLOBALS['user_info']['id'] > 0){
//            $data['user_id'] = intval($GLOBALS['user_info']['id']);
//            $data['money'] = floatval($_REQUEST['amount']);
//
//            if($data['money'] <=0)
//            {
//                showErr($GLOBALS['lang']['CARRY_MONEY_NOT_TRUE']);
//            }
//
//            // 检查提现金额小数点不能超过2位
//            $r = explode('.', $data['money']);
//            if(isset($r[1]))
//            {
//                if(strlen($r[1]) > 2)
//                     showErr($GLOBALS['lang']['CARRY_MONEY_NOT_TRUE']);
//            }
//
//            $fee = 0;
//            if($data['money']>0&&$data['money'] < 20000){
//                $fee = 1;
//            }
//            if($data['money']>=20000&&$data['money'] < 50000){
//                $fee = 3;
//            }
//            if($data['money'] >= 50000){
//                $fee = 5;
//            }
//            $fee = 0;//不收手续费
//
//            if(($data['money'] + $fee) > floatval($GLOBALS['user_info']['money'])){
//                showErr($GLOBALS['lang']['CARRY_MONEY_NOT_ENOUGHT']);
//            }
//            $data['fee'] = $fee;
//
//            //获取用户银行卡信息
//            $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//            // 如果用户没有银行卡信息或者信息没有确认保存过
//            if(!$bankcard_info || $bankcard_info['status'] != 1)
//                showErr('请先填写银行卡信息',0,url("shop","uc_money#bank"),0);
//
//
//            $data['bank_id'] = $bankcard_info['bank_id'];
//            $data['real_name'] = $bankcard_info['card_name'];
//            $data['region_lv1'] = $bankcard_info['region_lv1'];
//            $data['region_lv2'] = $bankcard_info['region_lv2'];
//            $data['region_lv3'] = $bankcard_info['region_lv3'];
//            $data['region_lv4'] = $bankcard_info['region_lv4'];
//            $data['bankcard'] = $bankcard_info['bankcard'];
//            $data['bankzone'] = $bankcard_info['bankzone'];
//
//            /*
//            $data['bank_id'] = intval($_REQUEST['bank_id']);
//            if($data['bank_id'] == 0)
//            {
//                $data['bank_id'] = intval($_REQUEST['otherbank']);
//            }
//
//            if($data['bank_id'] == 0)
//            {
//                showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK']);
//            }
//
//            $data['real_name'] = trim($_REQUEST['real_name']);
//            if($data['real_name'] == ""){
//                showErr("请输入开户名");
//            }
//
//            $data['region_lv1'] = intval($_REQUEST['region_lv1']);
//            $data['region_lv2'] = intval($_REQUEST['region_lv2']);
//            $data['region_lv3'] = intval($_REQUEST['region_lv3']);
//            $data['region_lv4'] = intval($_REQUEST['region_lv4']);
//            if($data['region_lv4'] == 0){
//                showErr("请选择开户行所在地");
//            }
//
//            $data['bankzone'] = trim($_REQUEST['bankzone']);
//            */
//
//            /*
//            if($data['bankzone'] == ""){
//                showErr("请输入开户行网点");
//            }
//            */
//            /*
//            $data['bankcard'] = trim($_REQUEST['bankcard']);
//            if($data['bankcard'] == ""){
//                showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK_CODE']);
//            }
//            $bankcardlen = strlen($data['bankcard']);
//            if($bankcardlen != 12 && $bankcardlen != 16 && $bankcardlen != 18 && $bankcardlen != 19)
//            {
//                showErr($GLOBALS['lang']['CARRY_BANK_ERR']);
//            }
//            */
//
//            $data['create_time'] = get_gmtime();
//            $GLOBALS['db']->autoExecute(DB_PREFIX."user_carry",$data,"INSERT");
//
//            //更新会员账户信息
//            FP::import("libs.libs.user");
//            // TODO finance 前台老系统 提现申请 | 不同步
//            modify_account(array('money'=>0,'lock_money'=>$data['money']+$fee),$data['user_id'],"提现申请", true,"提现申请");
//
//            $content = "您于".to_date($data['create_time'],"Y年m月d日 H:i:s")."提交的".format_price($data['money'])."提现申请我们正在处理，如您填写的账户信息正确无误，您的资金将会于3个工作日内到达您的银行账户.";
//
//            send_user_msg("",$content,0,$data['user_id'],get_gmtime(),0,true,5);
//
//            return showSuccess($GLOBALS['lang']['CARRY_SUBMIT_SUCCESS']);
//        }else{
//            return app_redirect(url("index","user#login"));
//        }
//    }
//
//    /**
//     * 收款银行卡信息
//     */
//    public function bank(){
//        return app_redirect(url("index","account/addbank"));
//        //如果未绑定手机
//        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])==0){
//            $GLOBALS['tmpl']->assign("page_title","成为投资者");
//            $GLOBALS['tmpl']->display("page/deal_mobilepaseed.html");
//            exit();
//        }
//        make_delivery_region_js();
//        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
//        $GLOBALS['tmpl']->assign("bank_list",$bank_list);
//
//        //地区列表
//        $region_lv1 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."delivery_region where region_level = 1");  //二级地址
//        $GLOBALS['tmpl']->assign("region_lv1",$region_lv1);
//
//        //获取用户银行卡信息
//        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//        if($bankcard_info){
//            foreach($bank_list as $k=>$v){
//                if($v['id'] == $bankcard_info['bank_id']){
//                    $bankcard_info['is_rec'] = $v['is_rec'];
//                    break;
//                }
//            }
//        }
//
//        if(empty($bankcard_info['card_name'])){
//            $bankcard_info['card_name'] = $GLOBALS['user_info']['real_name'];//get_user("real_name", $uid);
//        }
//
//        //如果没有实名认证
//        if(!$GLOBALS['user_info']['real_name'] || $GLOBALS['user_info']['idcardpassed'] !=1){
//            $GLOBALS['tmpl']->assign("page_title","成为投资者");
//            $GLOBALS['tmpl']->display("page/deal_mobilepaseed.html");
//            exit();
//        }
//
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_BANK']);
//        $GLOBALS['tmpl']->assign('bankcard_info',$bankcard_info);
//        //$GLOBALS['tmpl']->assign('bankcard_info',$aa);
//        $GLOBALS['tmpl']->display("inc/uc/uc_money_bank.html");
//    }
//
//    /**
//     * 收款银行卡信息 保存
//     */
//    public function savebank() {
//        // 验证表单令牌
//        if(!check_token())
//        {
//            showErr($GLOBALS['lang']['TOKEN_ERR']);
//        }
//
//        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//        if($bankcard_info['status'])
//            showErr('不允许再次修改银行卡信息');
//
//        $data['bank_id'] = intval($_REQUEST['bank_id']);
//        if($data['bank_id'] == 0)
//        {
//            $data['bank_id'] = intval($_REQUEST['otherbank']);
//        }
//
//        if($data['bank_id'] == 0)
//        {
//            showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK']);
//        }
//
//        $data['card_name'] = trim($_REQUEST['real_name']);
//        if($data['card_name'] == ""){
//            showErr("请输入开户名");
//        }
//
//        $data['region_lv1'] = intval($_REQUEST['region_lv1']);
//        $data['region_lv2'] = intval($_REQUEST['region_lv2']);
//        $data['region_lv3'] = intval($_REQUEST['region_lv3']);
//        $data['region_lv4'] = intval($_REQUEST['region_lv4']);
//        if($data['region_lv4'] == 0){
//            showErr("请选择开户行所在地");
//        }
//
//        $data['bankzone'] = $this->filterJs(trim($_REQUEST['bankzone']));
//
//        $data['bankcard'] = $this->filterJs(trim($_REQUEST['bankcard']));
//
//        if( empty($data['bankcard'])){
//            showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK_CODE']);
//        }
//
//        if(!in_array(strlen($data['bankcard']), array(12,16,18,19))) {
//            showErr("银行卡号长度不正确");
//        }
//
//
//        $data['status'] = 1;  // 状态设置为1后前台不允许再修改
//
//        if($bankcard_info) {
//            $data['update_time'] = get_gmtime();
//            $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard",$data,"UPDATE", 'id = ' . $bankcard_info['id']);
//        } else {
//            $data['create_time'] = get_gmtime();
//            $data['user_id'] = $GLOBALS['user_info']['id'];
//            $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard",$data,"INSERT");
//        }
//
//        return showSuccess('银行卡信息修改成功！',0,'/uc_center');
//
//    }
//
//    //替换 js style 内容
//    private function filterJs($str='') {
//        if(!empty($str)) {
//            $pregfind = array(
//                    "/<script.*>.*<\/script>/siU",
//                    "/<style.*>.*<\/style>/siU",
//            );
//            $pregreplace = array(
//                    '',
//                    '',
//            );
//            $str = preg_replace($pregfind, $pregreplace, $str);    //filter script/style entirely
//        }
//       return $str;
//    }
//    // 如果用户银行卡信息为填写，则引导用户区填写银行卡信息，填写银行卡信息前首先验证用户身份是否验证过
//    private function redircet_bank_info() {
//        if (intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])==0) {
//            $msg = "请先填写身份证信息";
//        } else {
//            $msg = "请先填写银行卡信息";
//        }
//        showErr($msg,0,url("shop","uc_money#bank"),0,1);
//    }
//
//    /**
//     * 修改银行卡信息  add caolong 2014-1-24
//     */
//    public function editorBank() {
//        //如果未绑定手机
//        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])==0){
//            $GLOBALS['tmpl']->assign("page_title","成为投资者");
//            $GLOBALS['tmpl']->display("page/deal_mobilepaseed.html");
//            exit();
//        }
//        make_delivery_region_js();
//        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC");
//        $GLOBALS['tmpl']->assign("bank_list",$bank_list);
//
//        //地区列表
//        $region_lv1 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."delivery_region where region_level = 1");  //二级地址
//        $GLOBALS['tmpl']->assign("region_lv1",$region_lv1);
//
//        //获取用户银行卡信息
//        $bankcard_info = array();
//        $r = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard_audit where user_id =".$GLOBALS['user_info']['id']." ORDER BY id DESC  LIMIT 1 ");
//        if(empty($r)) {
//            $temp = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//            $user_bank_id = $temp['id'];
//        }else{
//            $user_bank_id = $r['user_bank_id'];
//          //  $bankcard_info = $r;
//        }
//
//       /*  if($bankcard_info){
//            foreach($bank_list as $k=>$v){
//                if($v['id'] == $bankcard_info['bank_id']){
//                    $bankcard_info['is_rec'] = $v['is_rec'];
//                    break;
//                }
//            }
//        } */
//
//        if(empty($bankcard_info['card_name'])){
//            $bankcard_info['card_name'] = $GLOBALS['user_info']['real_name'];//get_user("real_name", $uid);
//        }
//
//        //如果没有实名认证
//        if(!$GLOBALS['user_info']['real_name'] || $GLOBALS['user_info']['idcardpassed'] !=1){
//            $GLOBALS['tmpl']->assign("page_title","成为投资者");
//            $GLOBALS['tmpl']->display("page/deal_mobilepaseed.html");
//            exit();
//        }
//        $GLOBALS['tmpl']->assign("id",$r['id']);
//        $GLOBALS['tmpl']->assign("user_bank_id",$user_bank_id);
//        $GLOBALS['tmpl']->assign('idno',formatBankcard($GLOBALS['user_info']['idno']));
//        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_BANK']);
//        $GLOBALS['tmpl']->assign('bankcard_info',$bankcard_info);
//        $GLOBALS['tmpl']->display("inc/uc/new/uc_editor_bank.html");
//    }
//
//    /**
//     * 保存银行卡信息  add caolong 2014-1-24
//     */
//    public function saveBankInfo() {
//        // 验证表单令牌
//        if(!check_token()){
//            showErr($GLOBALS['lang']['TOKEN_ERR']);
//        }
//        $id                 = intval($_POST['id']);
//        $data['user_bank_id']= intval($_POST['user_bank_id']);
//        $data['bank_id']    = intval($_REQUEST['bank_id']);
//        $data['card_name']  = trim($_REQUEST['real_name']);
//        $data['region_lv1'] = intval($_REQUEST['region_lv1']);
//        $data['region_lv2'] = intval($_REQUEST['region_lv2']);
//        $data['region_lv3'] = intval($_REQUEST['region_lv3']);
//        $data['region_lv4'] = intval($_REQUEST['region_lv4']);
//        $data['bankzone']   = $this->filterJs(trim($_REQUEST['bankzone']));
//        $data['bankcard']   = $this->filterJs(trim($_REQUEST['bankcard']));
//        $data['create_time'] = get_gmtime();
//        $data['status']     = 1;    //审核中
//        $data['image_id']   = intval($_POST['image']);
//        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
//        //业务处理
//        if(empty($bankcard_info)) {
//            showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK'],0,'/uc_money-bank');
//        }
//        if(empty($data['bank_id'])) {
//            showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK']);
//        }
//        if(empty($data['card_name'])) {
//            showErr("请输入开户名");
//        }
//        if(empty($data['region_lv4'])){
//            showErr("请选择开户行所在地");
//        }
//        if(empty($data['bankcard'])) {
//            showErr($GLOBALS['lang']['PLASE_ENTER_CARRY_BANK_CODE']);
//        }
//
//        if(!in_array(strlen($data['bankcard']), array(12,16,18,19))) {
//            showErr("银行卡号长度不正确");
//        }
//
//
//        $data['user_id'] = $GLOBALS['user_info']['id'];
//        $GLOBALS['db']->autoExecute(DB_PREFIX."user_bankcard_audit",$data,"INSERT");
//        return showSuccess('网信理财将在3个工作日内完成信息审核。审核结果将以短信、站内信或电子邮件等方式通知您',0,'/uc_center');
//        //
//    }
//
//    //银行卡图片上传
//    public function bankinfoImage() {
//        $data   = array();
//        $file   = $_FILES['fileToUpload'];
//        $prefix = $this->getImagePostFix($file['tmp_name']);
//        $priv = isset($_GET['priv']) ? intval($_GET['priv']) : 0;
//        if(!empty($file) && in_array($prefix, $this->allowPostfix)) {
//            $result = uploadFile($file,1,1,'',$priv);
//            if(!empty($result['aid']) && $result['filename']) {
//                $data['image_id'] = $result['aid'];
//                $data['filename'] = $result['filename'];
//                self::$message['message'] = $data;
//            }else{
//                self::$message = array('code'=>'4001','message'=>'图片尺寸不能大于1.5M，请重新上传图片');
//            }
//        }else{
//            self::$message = array('code'=>'4000','message'=>'图片格式仅限JPG、PNG，请重新上传图片');
//        }
//        echo json_encode(self::$message);
//    }
//
//    //银行卡图片上传
//    public function bankinfoImageDel() {
//        $id   = intval($_POST['id']);
//        if(!empty($id)) {
//            if(!del_attr($id)) {
//                self::$message = array('code'=>'4000','message'=>'图片删除失败');
//            }
//        }else{
//            self::$message = array('code'=>'4001','message'=>'图片id不存在');
//        }
//        echo json_encode(self::$message);
//    }
//
//
//    //通过二进制流 读取文件后缀信息
//    private function getImagePostFix($filename) {
//        $file     = fopen($filename, "rb");
//        $bin      = fread($file, 2); //只读2字节
//        fclose($file);
//        $strinfo  = @unpack("c2chars", $bin);
//        $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
//        $filetype = "";
//        switch ($typecode) {
//            case 7790: $filetype = 'exe';break;
//            case 7784: $filetype = 'midi';break;
//            case 8297: $filetype = 'rar';break;
//            case 255216:$filetype = 'jpg';break;
//            case 7173: $filetype = 'gif';break;
//            case 6677: $filetype = 'bmp';break;
//            case 13780:$filetype = 'png';break;
//            default:   $filetype = 'unknown'.$typecode;
//        }
//        if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
//            return 'jpg';
//        }
//        if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
//            return 'png';
//        }
//        return $filetype;
//    }
//}
?>
