<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
FP::import("app.deal");
FP::import("libs.libs.msgcenter");
FP::import("libs.libs.send_contract");

use app\models\dao\Bank;
use app\models\dao\User;
use app\models\dao\DealLoanRepay;
use app\models\dao\Deal;
use app\models\dao\DealRepay;
use app\models\dao\DealLoad;
use app\models\service\Earning;
use app\models\service\LoanType;

class dealModule extends SiteBaseModule
{

    public function index(){
        $id = intval($_REQUEST['id']);
        
        $deal = get_deal($id);

        //机构名义贷款类型
         $company = get_deal_borrow_info($deal);
        $company['company_description'] = str_replace("\n", "<br/>", $company['company_description']);
         $GLOBALS['tmpl']->assign('company',$company);
        //贷款担保人数据
            $guarantor = $this->_guarantor($id);
        
        //如果此处为不可见,并且不是已经登录用户的单子，则转向到首页。
        // 贷款保证人也可见
        if( !$guarantor  &&  empty($deal['is_visible']) && $deal['user_id'] != $GLOBALS['user_info']['id']){
            return app_redirect(url("index")); 
        }
        if(!$deal)
            return app_redirect(url("index")); 
        
        //借款列表
        $load_list = DealLoad::instance()->getDealLoanList($id);
        
        if($load_list){
            foreach($load_list as $lkey => &$lval){
                $lval['user_deal_name'] = get_deal_username($lval['user_id']) ? get_deal_username($lval['user_id']) : $lval['user_deal_name'];
            }            
        }
        
        $u_info = get_user("*",$deal['user_id']);
        //可用额度
        $can_use_quota=get_can_use_quota($deal['user_id']);
        $GLOBALS['tmpl']->assign('can_use_quota',$can_use_quota);
        
        $credit_file = get_user_credit_file($deal['user_id']);
        $deal['is_faved'] = 0;
        if($GLOBALS['user_info']){
            $deal['is_faved'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_collect WHERE deal_id = ".$id." AND user_id=".intval($GLOBALS['user_info']['id']));

            if($deal['deal_status'] >=4){
                //还款列表
                $loan_repay_list = get_deal_load_list($deal);
                $GLOBALS['tmpl']->assign("loan_repay_list",$loan_repay_list);

                $deal_loan_repay_model = new DealLoanRepay();
                foreach($load_list as $k=>$v){
                    $load_list[$k]['remain_money'] = $v['money'] - $deal_loan_repay_model->getTotalPrincipalMoneyByUserid($id, $v['user_id']);
                    if($load_list[$k]['remain_money'] <=0){
                        $load_list[$k]['remain_money'] = 0;
                        $load_list[$k]['status'] = 1;
                    }
                }
            }
            
            $GLOBALS['tmpl']->assign("user_info_id",intval($GLOBALS['user_info']['id']));
            
            $focus_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_focus where focus_user_id = ".intval($GLOBALS['user_info']['id'])." and focused_user_id = ".$deal['user_id']);
            if($focus_data){
                $GLOBALS['tmpl']->assign("is_focus",1);
            }
        }
        $user_statics = sys_user_status($deal['user_id'],true);
        $GLOBALS['tmpl']->assign("user_statics",$user_statics);
        
        $GLOBALS['tmpl']->assign("load_list",$load_list);    
        $GLOBALS['tmpl']->assign("credit_file",$credit_file);

        $GLOBALS['tmpl']->assign("u_info",$u_info);
        
        //工作认证是否过期
        $time = get_gmtime();
        $expire_time = 6*30*24*3600;
        if($u_info['workpassed']==1){
            if(($time - $u_info['workpassed_time']) > $expire_time){
                $expire['workpassed_expire'] = 1;
            }
        }
        if($u_info['incomepassed']==1){
            if(($time - $u_info['incomepassed_time']) > $expire_time){
                $expire['incomepassed_expire'] = 1;
            }
        }
        if($u_info['creditpassed']==1){
            if(($time - $u_info['creditpassed_time']) > $expire_time){
                $expire['creditpassed_expire'] = 1;
            }
        }
        if($u_info['residencepassed']==1){
            if(($time - $u_info['residencepassed_time']) > $expire_time){
                $expire['residencepassed_expire'] = 1;
            }
        }
        
        $GLOBALS['tmpl']->assign('expire',$expire);
        if($deal['type_match_row'])
            $seo_title = $deal['seo_title']!=''?$deal['seo_title']:$deal['type_match_row'] . " - " . $deal['name'];
        else
            $seo_title = $deal['seo_title']!=''?$deal['seo_title']: $deal['name'];
            
        $GLOBALS['tmpl']->assign("page_title",$seo_title);
        $seo_keyword = $deal['seo_keyword']!=''?$deal['seo_keyword']:$deal['type_match_row'].",".$deal['name'];
        $GLOBALS['tmpl']->assign("page_keyword",$seo_keyword.",");
        $seo_description = $deal['seo_description']!=''?$deal['seo_description']:$deal['name'];
        
        //留言
        FP::import("app.message");
        FP::import("app.page");
        $rel_table = 'deal';
        $message_type = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."message_type where type_name='".$rel_table."'");
        $condition = "rel_table = '".$rel_table."' and rel_id = ".$id;
    
        if(app_conf("USER_MESSAGE_AUTO_EFFECT")==0)
        {
            $condition.= " and user_id = ".intval($GLOBALS['user_info']['id']);
        }
        else 
        {
            if($message_type['is_effect']==0)
            {
                $condition.= " and user_id = ".intval($GLOBALS['user_info']['id']);
            }
        }
        
        //message_form 变量输出
        $GLOBALS['tmpl']->assign('rel_id',$id);
        $GLOBALS['tmpl']->assign('rel_table',"deal");
        
        //分页
        $page = intval($_REQUEST['p']);
        if($page==0)
        $page = 1;
        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");
        $msg_condition = $condition." AND is_effect = 1 ";
        $message = get_message_list($limit,$msg_condition);
        
        $page = new Page($message['count'],app_conf("PAGE_SIZE"));   //初始化分页对象         
        $p  =  $page->show();
        $GLOBALS['tmpl']->assign('pages',$p);
        
        foreach($message['list'] as $k=>$v){
            $msg_sub = get_message_list("","pid=".$v['id'],false);
            $message['list'][$k]["sub"] = $msg_sub["list"];
        }
        
        $GLOBALS['tmpl']->assign("message_list",$message['list']);
        if(!$GLOBALS['user_info'])
        {
            $GLOBALS['tmpl']->assign("message_login_tip",sprintf($GLOBALS['lang']['MESSAGE_LOGIN_TIP'],url("shop","user#login"),url("shop","user#register")));
        }
        
        //如果该项目is_update=1,当前用户登录时，显示确认界面，其他用户显示“暂时无法投标”
        $display_html = "page/deal.html";

        if($deal['is_update'] == 1 && $deal['update_json'] && $deal['user_id'] == intval($GLOBALS['user_info']['id'])){
            $update_arr = array();
            $display_html = "page/deal_update.html";
            
            $update_json = json_decode($deal['update_json'], true);

            $deal = $this->make_update_arr($deal);
            $deal['update_json'] = $this->make_update_arr($update_json,true);

            $GLOBALS['tmpl']->assign('update_deal_lang',$GLOBALS['dict']['UPDATE_DEAL_LANG']);
        }

        // add by wangyiming 20140120 改版新加内容
        if ($deal['min_loan_money'] >= 10000) {
            $deal['min_loan'] = intval($deal['min_loan_money'] / 10000);
        } else {
            $deal['min_loan'] = number_format($deal['min_loan_money'] / 10000, 1);
        }
        $deal['borrow_amount_format'] = intval($deal['borrow_amount'] / 10000);
        $deal['borrow_amount_format_detail'] = intval($deal['borrow_amount_format_detail']);
        $deal['loan_rate'] = round((1-$deal['need_money_decimal']/$deal['borrow_amount'])*100, 2);
        $deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        $deal['need_money_format'] = number_format($deal['need_money_decimal'], 2);
        if ($GLOBALS['user_info']) {
            $max_loan = $GLOBALS['user_info']['money'] > $deal['need_money_decimal'] ? $deal['need_money_decimal'] : $GLOBALS['user_info']['money'] ;
        } else {
            $max_loan = $deal['need_money_decimal'];
        }
        $max_loan = floor($max_loan);
        $earning = new Earning();
        $money_earning = $earning->getEarningMoney($id, $max_loan);
        if ($max_loan) {
            $expire_rate = $money_earning / $max_loan * 100 ;
        } else {
            $expire_rate = $earning->getEarningRate($id);
        }
        $max_loan = $deal['deal_status'] != 1 || $deal['guarantor_status'] != 2 ? 0 : $max_loan ;

        if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
            $deal_repay_list = DealRepay::instance()->findAll("`deal_id` = '{$deal['id']}'");
            $GLOBALS['tmpl']->assign("deal_repay_list",$deal_repay_list);
        }

        $GLOBALS['tmpl']->assign("max_loan",$max_loan);
        $GLOBALS['tmpl']->assign("expire_rate",$expire_rate);
        $GLOBALS['tmpl']->assign("money_earning",$money_earning);
        $GLOBALS['tmpl']->assign("guarantor",$guarantor);
        $GLOBALS['tmpl']->assign("deal",$deal);
        
        $needShowGuarantor = false;
        if (intval($deal['user_id']) == intval($GLOBALS['user_info']['id'])) {
            $needShowGuarantor = true;
        } else {
            if (is_array($guarantor) && count($guarantor) > 0) {
                foreach($guarantor as $g) {
                    if($g['is_guarantor']) {
                        $needShowGuarantor = true;
                        break;
                    }
                }
            }
        }
        $GLOBALS['tmpl']->assign("needShowGuarantor",$needShowGuarantor);
        
        if($needShowGuarantor){
            //保证反担保合同
            $warrantContractProtocal_tmp = $this->get_warrandice_contract_adv($deal);
            $GLOBALS['tmpl']->assign("warrantContractProtocal_tmp",hide_message($warrantContractProtocal_tmp));
            
            //保证合同
            /* $warrantProtocal_tmp = $this->get_guarantee_protocal_contract($deal);
            $GLOBALS['tmpl']->assign("warrantProtocal_tmp",$warrantProtocal_tmp); */
        }

        $this->set_nav(array("投资列表"=>url("index", "deals"), $deal['name']));

        $GLOBALS['tmpl']->display($display_html);
    }
    
    //贷款申请，展示 “xxx,由..改为..” 的处理
    private function make_update_arr($array,$encode = false){
        if(empty($array) || !is_array($array))    return array();
        foreach($array as $key => $val){
            if(!$val){    //空或者0
                $array[$key] = '无';
            }elseif(in_array($key, array('cate_id', 'agency_id', 'type_id'))){
                $tmp_arr = array(
                        'cate_id' => array('table' => 'deal_cate', 'where' => ' WHERE is_delete = 0 and is_effect = 1'),
                        'agency_id' => array('table' => 'deal_agency', 'where' => ' WHERE is_effect = 1'),
                        'type_id' => array('table' => 'deal_loan_type', 'where' => ' WHERE is_delete = 0 and is_effect = 1')
                );
                $list = $GLOBALS['db']->getAll("SELECT id,name FROM ".DB_PREFIX.$tmp_arr[$key]['table'].$tmp_arr[$key]['where']);
                foreach($list as $list_val){
                    if($val == $list_val['id']){
                        $array[$key] = $list_val['name'];
                        break;
                    }
                }
            }elseif(in_array($key, array('loantype','repay_time','warrant'))){
                $dict_arr = array('loantype' => 'LOAN_TYPE','repay_time' => 'REPAY_TIME','warrant' => 'DEAL_WARRANT');
                if($array['loantype'] == 5 && $key == 'repay_time')
                    $array['repay_time'] = $array['repay_time'] . '天';
                else
                    $array[$key] = $GLOBALS['dict'][$dict_arr[$key]][$val];
            }else{
                $str_arr = array(
                        'rate'=>'%',
                        'services_fee'=>'%',
                        'enddate'=>'天',
                        'min_loan_money'=>'元',
                        'borrow_amount'=>'元',
                        'loan_fee_rate'=>'%',
                        'guarantee_fee_rate'=>'%',
                        'manage_fee_rate'=>'%'
                );
                $array[$key] = $encode ? urldecode($val) : $val;
                $str_tag = isset($str_arr[$key]) ? (strpos($array[$key], $str_arr[$key]) !== false ? '' : $str_arr[$key]) : '';
                $array[$key] .= $str_tag;
            }
        }
        return $array;
    }
    
    //用户确认 前台老系统管理员对贷款申请的修改
    function update_deal_clear(){
        $deal_id = intval($_REQUEST['id']);
        if(!$deal_id)    return app_redirect(url("index"));
    
        $deal = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."deal where id=".$deal_id);
    
        if($deal['is_update'] != 1 || ($deal['user_id'] != intval($GLOBALS['user_info']['id']))){
            return app_redirect(url("index"));
        }
        
        //拒绝确认
        if(isset($_REQUEST['del']) && $_REQUEST['del'] == 1){
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal",array('is_delete' => 1 , 'is_effect' => 0),"UPDATE","id=".$deal_id);
            return showSuccess('操作成功','/');
        }
    
        $update_arr = array('is_effect' => 1, 'is_update' => 0, 'update_json' => '');
        $GLOBALS['db']->autoExecute(DB_PREFIX."deal",$update_arr,"UPDATE","id=".$deal_id);
        
        //取借款类型
        //$deal_type = $GLOBALS['db']->getOne("SELECT name FROM ".DB_PREFIX."deal_loan_type where id = ".$deal['type_id']);
        $deal_type = get_deal_title($deal['name'], '', $deal['id']);
        
        //给用户发送消息
        $content = sprintf("您在%s的借款申请信息 “<a href=\"%s\">%s</a>”已确认。",
                app_conf("SHOP_TITLE"),get_domain().url("index","deal",array("id"=>$deal_id)),$deal_type
        );
        
        $userinfo = $GLOBALS['db']->getRow("SELECT user_name,email,mobile FROM ".DB_PREFIX."user WHERE id=".$deal['user_id']);
        
        $update_title = '借款申请信息已确认';
        
        send_user_msg($update_title, $content, 0, $deal['user_id'], get_gmtime(), 0, true, 1);

        $Msgcenter = new Msgcenter();
        
        $email_data['user_name'] = $userinfo['user_name'];
        $email_data['deal_name'] = $deal_type;
        $email_data['deal_url'] = get_domain().url("index","deal",array("id"=>$deal_id));
        $email_data['site_name'] = app_conf("SHOP_TITLE");
        $email_data['help_url'] = get_domain().url("index","helpcenter");
        
        
        //给用户发送邮件
        $email_data['dest'] = $userinfo['email'];
        $email_data['title'] = $update_title;
        $email_data['content'] = addslashes($email_content);
        $email_data['user_id'] = $deal['user_id'];
        $Msgcenter->setMsg($userinfo['email'], $deal['user_id'], $email_data, 'TPL_DEAL_UPDATE_COMFIRM_EMAIL',$update_title);

        //发短信
        $note_data = array();
        $note_data['dest'] = $userinfo['mobile'];
        $note_data['title'] = $update_title;
        $note_data['content'] = addslashes($sms_content);
        $note_data['deal_name'] = $deal_type;
        $note_data['user_id'] = $deal['user_id'];
        \libs\sms\SmsServer::instance()->send($userinfo['mobile'], 'TPL_DEAL_UPDATE_COMFIRM_SMS', $note_data, $deal['user_id']);
        
        
        //记录日志文件
        FP::import("libs.utils.logger");
        
        $update_json = json_decode($deal['update_json'], true);
        $update_array = $this->make_update_arr($update_json, true);
        $deal = $this->make_update_arr($deal);
        
        $content_array = array();
        $update_lang = $GLOBALS['dict']['UPDATE_DEAL_LANG'];
        foreach($update_array as $key => $val){
            $content_array[] = $update_lang[$key].' 由 '.$val.' 改为 '. $deal[$key];
        }
        
        $log = array(
                'msg' => $update_title,
                'time' => date('Y-m-d H:i:s',time()),
                '用户名' => $userinfo['user_name'],
                '内容对比' => $content_array
        );
        logger::wLog($log);
        
        return app_redirect(url("index","deal",array("id"=>$deal_id)));
    }
    
    /**
     * 整理贷款担保人数据
     * 根据当前登陆用户判断显示内容，对于非保证人或贷款人，只显示已经同意担保的用户（status=3），否则全部显示
     * @param int $deal_id
     * @return array 整理后的担保人数据列表
     */
    private function _guarantor($deal_id){
        //(status=3 or (to_user_id=".$GLOBALS['user_info']['id'].")) and
        $guarantor = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."deal_guarantor where deal_id =".$deal_id." order by id asc");
        if($guarantor){
            
            //取出所有保证人id
            foreach($guarantor as $k=>$v){
                $all_to_user_id[]=$v['to_user_id'];
            }
            
            //确认当前登陆用户是保证人或者贷款人
            $is_guarantor_or_borrower = false;
            if(in_array($GLOBALS['user_info']['id'], $all_to_user_id) or $GLOBALS['user_info']['id']==$guarantor[0]['user_id']){
                $is_guarantor_or_borrower = true;
            }
            
            //担保状态字典
            $guarantor_status_list =  $GLOBALS['dict']['DEAL_GUARANTOR_STATUS'];
            //关系字典
            $guarantor_relation_list =  $GLOBALS['dict']['DICT_RELATIONSHIPS'];
            
            //对于非保证人或贷款人，只显示已经同意担保的用户（status=3）
            $guarantor_trimed = array();
            foreach($guarantor as $k=>$v){
                if($is_guarantor_or_borrower or $v['status']==3){
                    $user = get_user("user_name,real_name,mobile,idno,email", $v['to_user_id']);
                    $guarantor_one = $v;
                    $guarantor_one['user_name'] = $user['user_name'];
                    $guarantor_one['real_name'] = $v['name'];
                    $guarantor_one['idno'] = $v['id_number'];//身份证
                    $guarantor_one['email'] = $v['email'];
                    
                    $guarantor_one['status_name'] = '未确认';
                    if(in_array( $v['status'],array('2','3')))//只显示同意或不同意两种状态
                        $guarantor_one['status_name'] = $guarantor_status_list[$v['status']];
                    $guarantor_one['relation_name'] = $guarantor_relation_list[$v['relationship']];
                    if($v['to_user_id'] == $GLOBALS['user_info']['id']){
                        $guarantor_one['is_guarantor']=1;//登陆用户是保证人之一
                    }
                    $guarantor_trimed[]=$guarantor_one;
                }
            }
            
            return $guarantor_trimed;
        }
    }
    
    function preview(){
        if(!isset($GLOBALS['user_info']['id']))    return app_redirect(url("index"));
        
        $deal['id'] = 'XXX';
        //$deal['name'] = trim($_REQUEST['borrowtitle']);
        $deal_loan_type_list = load_auto_cache("deal_loan_type_list");
        foreach($deal_loan_type_list as $k=>$v){
            if($v['id'] == intval($_REQUEST['borrowtype'])){
                $deal['type_match_row'] = $v['name'];
            }
        }
        
        //借款用途
        if(intval($_REQUEST['borrowtype']) > 0){
            $deal['type_info'] = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."deal_loan_type where id = ".intval($_REQUEST['borrowtype'])." and is_effect = 1 and is_delete = 0");
        }
        
        $deal['borrow_amount_format_detail'] = $deal['need_money_detail'] = format_price(trim($_REQUEST['borrowamount']) / 10000);
        $deal['rate_foramt'] = number_format(trim($_REQUEST['apr']),2);
        $deal['min_loan_money'] = 50;
        $deal['need_money'] = $deal['borrow_amount_format'];
        $deal['repay_time'] = trim($_REQUEST['repaytime']);
        //本息还款金额
        $deal['month_repay_money'] = format_price(pl_it_formula($deal['borrow_amount'],trim($_REQUEST['apr'])/12/100,$deal['repay_time']));
        
        $deal['progress_point'] = 0;
        $deal['buy_count'] = 0;
        $deal['voffice'] = intval($_REQUEST['voffice']);
        $deal['vjobtype'] = intval($_REQUEST['vjobtype']);
        
        $deal['description']= $_REQUEST['borrowdesc'];
        $deal['user_id'] = $GLOBALS['user_info']['id'];
        $deal['is_delete'] = 2;
        
        //统计信息
        $user_statics = sys_user_status($GLOBALS['user_info']['id'],true);
        $GLOBALS['tmpl']->assign("user_statics",$user_statics);
        
        //担保机构
        if($_REQUEST['agency_id']){
            $deal['agency_id'] = intval($_REQUEST['agency_id']);
            $deal['agency_info'] = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."deal_agency where id = ".intval($_REQUEST['agency_id']));
        }

        //图片
        if($_REQUEST['borrowtype'] > 0){
            $type_info = $GLOBALS['db']->getRowCached("select name,brief,uname,icon from ".DB_PREFIX."deal_loan_type where id = ".intval($_REQUEST['borrowtype'])." and is_effect = 1 and is_delete = 0");
            $deal['icon'] = $type_info['icon'];
            $deal['icon'] = str_replace("./public/images/dealtype/","./images/dealtype/",$deal['icon']);
            $deal['name'] = $type_info['name'];
        }
        
        //用户
        $deal['user_deal_name'] = get_deal_username($GLOBALS['user_info']['id']);
        
        //不显示关注
        $deal['show_focus'] = 0;
        
        //利率
        $deal['loantype'] = intval($_REQUEST['loantype']);
        $deal['repay_time'] = intval($_REQUEST['repaytime']);
        //$ajaxresult = get_deal_rate($deal['loantype'], $_REQUEST['repaytime']);
        //$deal['deal_rate'] = $ajaxresult['data']['back_period'];
        $deal['deal_rate'] = $_REQUEST['apr'].'%';
        
        //还款方式
        $deal['loantype_name'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        
        //还款周期
        $deal['loan_period_name'] = get_loan_period($deal['loantype'],$deal['repay_time']);
        
        //本息还款金额
        $deal['rate'] = $_REQUEST['apr'];
        $deal['borrow_amount'] = $deal['need_money'] = format_price(trim($_REQUEST['borrowamount']));
        if($deal['loantype'] == 1)//每月还息，到期还本
            $deal['month_repay_money'] = av_it_formula($deal['borrow_amount'],$deal['rate']/12/100) ;
        else{
            $deal['month_repay_money'] = pl_it_formula($deal['borrow_amount'],$deal['rate']/12/100,$deal['repay_time']);
        }
        $deal['month_repay_money_format'] = format_price($deal['month_repay_money']);
        
        $u_info = get_user("*",$GLOBALS['user_info']['id']);
        $GLOBALS['tmpl']->assign("u_info",$u_info);
        $seo_title = $deal['seo_title']!=''?$deal['seo_title']:$deal['type_match_row'] . " - " . $deal['name'];
        $GLOBALS['tmpl']->assign("page_title",$seo_title);
        $seo_keyword = $deal['seo_keyword']!=''?$deal['seo_keyword']:$deal['type_match_row'].",".$deal['name'];
        $GLOBALS['tmpl']->assign("page_keyword",$seo_keyword.",");
        $seo_description = $deal['seo_description']!=''?$deal['seo_description']:$deal['name'];
        
        $GLOBALS['tmpl']->assign("seo_description",$seo_description.",");
        
        $GLOBALS['tmpl']->assign("deal",$deal);
        $GLOBALS['tmpl']->display("page/deal.html");
    }
    
    function bid(){
                
        if(!$GLOBALS['user_info']){
            set_gopreview();
            return app_redirect(url("index","user#login")); 
        }
        $id = intval($_REQUEST['id']);
        $money = floatval($_REQUEST['money']);
        
        if($GLOBALS['user_info']['idcardpassed'] == 3){
            $info = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."user_passport WHERE uid=".$GLOBALS['user_info']['id']);
            if($info){//港澳台用户
                return showErr('認證信息提交成功,网信理财將在3個工作日內完成信息審核。審核結果將以短信、站內信或電子郵件等方式通知您。',0);
            }else{
                return showErr('认证信息提交成功，网信理财将在3个工作日内完成信息审核。审核结果将以短信、站内信或者电子邮件等方式通知您。',0);
            }
        }
        
        //如果是is_update=1,则返回到首页  add by wenyanlei 20130629
        $deal_info = $GLOBALS['db']->getRow("SELECT is_update,agency_id,warrant FROM ".DB_PREFIX."deal WHERE id=$id");

        $agency_info = get_agency_info($deal_info['agency_id']);
        $GLOBALS['tmpl']->assign('agency_info', $agency_info);
        
        if($deal_info['is_update'] == 1)    return app_redirect(url("index"));

        //如果未绑定手机
        if(intval($GLOBALS['user_info']['mobilepassed'])==0 || intval($GLOBALS['user_info']['idcardpassed'])!=1 || !$GLOBALS['user_info']['real_name'] ){
            $GLOBALS['tmpl']->assign("page_title","成为投资者");
            $GLOBALS['tmpl']->display("page/deal_mobilepaseed.html");
            exit();
        }
        
        $deal = get_deal($id);
        if(empty($deal['need_money_decimal'])){
            return app_redirect(url("index"));
        }
        //2013/06/29 如果此单的is_visible不为1则提示。 Liwei ADD
        if($deal['is_visible'] != 1){
            return showErr($GLOBALS['lang']['DEAL_FAILD_OPEN'],$ajax);        //已不在投标状态
        }        
        
        if(!$deal)
            return app_redirect(url("index")); 
        
        if($deal['user_id'] == $GLOBALS['user_info']['id']){
            return showErr($GLOBALS['lang']['CANT_BID_BY_YOURSELF']);
        }

        //新手标
        if($deal['deal_crowd']=='1'
                        && $GLOBALS['db']->getOne("select count(id) from ".DB_PREFIX."deal_load where user_id ="
                                .$GLOBALS['user_info']['id'])>0 ){
            return showErr('新手专享标为网信理财平台初次投资用户推荐的优惠项目，只有第一次投资的用户才可以投标');
        }
                
        // add by wangyiming 20140226
        $company = get_deal_borrow_info($deal);
        $GLOBALS['tmpl']->assign('company',$company);

        $user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_prove where user_id =".$GLOBALS['user_info']['id']);
        
        if(empty($user_data)){
            $user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id =".$GLOBALS['user_info']['id']);
        }
        
        $GLOBALS['tmpl']->assign('user_data', $user_data);

        $deal_user = User::instance()->find($deal['user_id']);
        $GLOBALS['tmpl']->assign('deal_user', $deal_user);
        $user = User::instance()->find($GLOBALS['user_info']['id']);
        $GLOBALS['tmpl']->assign('user', $user);
        
        //地区处理
        $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2");  //二级地址
        foreach($region_lv2 as &$v2)
        {
            if($v2['id'] == intval($user_data['province_id']))
            {
                $v2['selected'] = 1;
                break;
            }
        }
        $GLOBALS['tmpl']->assign("province_info",$region_lv2);
            
        $region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$user_data['province_id']);  //三级地址
        foreach($region_lv3 as &$v3)
        {
            if($v3['id'] == intval($user_data['city_id']))
            {
                $v3['selected'] = 1;
                break;
            }
        }
        $GLOBALS['tmpl']->assign("city_info",$region_lv3);
        
        $seo_title = $deal['seo_title']!=''?$deal['seo_title']:$deal['type_match_row'] . " - " . $deal['name'];
        $GLOBALS['tmpl']->assign("page_title",$seo_title);
        $seo_keyword = $deal['seo_keyword']!=''?$deal['seo_keyword']:$deal['type_match_row'].",".$deal['name'];
        $GLOBALS['tmpl']->assign("page_keyword",$seo_keyword.",");
        $seo_description = $deal['seo_description']!=''?$deal['seo_description']:$deal['name'];
        
        $deal['min_loan_money_format'] = number_format(round($deal['min_loan_money'] / 10000, 2), 2);
        
//        if($deal['deal_crowd']=='1')//新手专享
//        {
//            if ($deal['need_money_decimal'] < (2*$deal['min_loan_money'])) 
//            {
//                $deal['max_loan_money'] = $deal['need_money_decimal'];
//            }
//        }
        $GLOBALS['tmpl']->assign("deal",$deal);
        $GLOBALS['tmpl']->assign("deal_left_load_money", $deal["need_money"]);
        
        #################################  保证    #################################
        //$guarantee_protocal = hide_message($this->get_guarantee_protocal_contract($deal));
        #################################  借款预签 #################################
        //$loan_protocal = hide_message($this->get_loan_protocal_contract($deal));
        #################################   平台协议 #################################    
        //$lenderProtocal_temp = hide_message($this->lender_protocal_contract($deal));
        #################################  END   ##################################
        //
        
        //var_dump($deal['manage_fee_rate']);
        $default_manage_fee_rate = $deal['manage_fee_rate'];
        /**
        if($deal['manage_fee_rate']>0){
            $default_manage_fee_rate = $deal['manage_fee_rate'];
        }else{
            $default_manage_fee_rate = app_conf('DEFAULT_MANAGE_FEE_RATE');
        }
         * 
         */
        
        if($deal['manage_fee_rate']==0  && empty($deal['manage_fee_text'])){
            $default_manage_fee_text = '（'.app_conf('DEFAULT_MANAGE_FEE_TEXT').'）';
        }
        
        if($deal['manage_fee_text']){
            $default_manage_fee_text = '（'.$deal['manage_fee_text'].'）';
        }
        
        
        //用户银行卡信息
        $bankcard_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_bankcard where user_id =".$GLOBALS['user_info']['id']." limit 1");
        if(!$bankcard_info || $bankcard_info['status'] != 1){
            return showErr('请先填写银行卡信息',0,url("shop","uc_money#bank"),0);
        }
            
        $bank = Bank::instance()->find($bankcard_info['bank_id']);
        $GLOBALS['tmpl']->assign("bank",$bank);
        
        make_delivery_region_js();
        $bank_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."bank ORDER BY is_rec DESC,sort DESC,id ASC");
        $GLOBALS['tmpl']->assign("bank_list",$bank_list);
        
        //地区列表
        $region_lv1 = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."delivery_region where id = " . $bankcard_info['region_lv1']);  //二级地址
        $GLOBALS['tmpl']->assign("region_lv1",$region_lv1['name']);
        $region_lv2 = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."delivery_region where id = " . $bankcard_info['region_lv2']);  //二级地址
        $GLOBALS['tmpl']->assign("region_lv2",$region_lv2['name']);
        $region_lv3 = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."delivery_region where id = " . $bankcard_info['region_lv3']);  //二级地址
        $GLOBALS['tmpl']->assign("region_lv3",$region_lv3['name']);
        $region_lv4 = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."delivery_region where id = " . $bankcard_info['region_lv4']);  //二级地址
        $GLOBALS['tmpl']->assign("region_lv4",$region_lv4['name']);
        
        if($bankcard_info){
            foreach($bank_list as $k=>$v){
                if($v['id'] == $bankcard_info['bank_id']){
                    $bankcard_info['is_rec'] = $v['is_rec'];
                    break;
                }
            }
            $GLOBALS['tmpl']->assign('bankcard_info',$bankcard_info);
        }
        
        $GLOBALS['tmpl']->assign('bankcard_info',$bankcard_info);
        
        // 添加借款合同预签
        //$GLOBALS['tmpl']->assign("preDealProtocal_temp",$loan_protocal);
        // 添加保证合同预签
        //$GLOBALS['tmpl']->assign("preGuarantorProtocal_temp",$guarantee_protocal);
        // 添加出借人平台服务协议
        //$lenderProtocal_temp = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_LENDER_PROTOCAL'");
        //$lenderProtocal_temp['content'] = preg_replace('/\{\$notice\.manage_fee_rate}/',$deal['manage_fee_rate'], $lenderProtocal_temp['content']);
        //$lenderProtocal_temp['content'] = preg_replace('/\{\$.*?\..*?}/','XXX', $lenderProtocal_temp['content']);

        //当前投标用户是否填过顾问信息，有的话自动补全input 3次  add by wenyanlei 20131008
        $advs_sql = "select a.id as load_id,b.recommend_id from ".DB_PREFIX."deal_load a
                    right join ".DB_PREFIX."deal_load_adviser b on a.id = b.deal_load_id
                    where b.user_id = ".$GLOBALS['user_info']['id']." order by b.id asc limit 1";
        $first_adviser = $GLOBALS['db']->getRow($advs_sql);

        $adviser = array('name' => '', 'mobile' => '');
        if($first_adviser['load_id'] && $first_adviser['recommend_id']){
            $after_load_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."deal_load where id > ".$first_adviser['load_id']." and user_id = ".$GLOBALS['user_info']['id']);
            if($after_load_count < 3){
                $adviser = $GLOBALS['db']->getRow("select name,mobile from ".DB_PREFIX."adviser where id = ".$first_adviser['recommend_id']);
            }
        }

        if($money == 0){
            $money = floatval($user['money']);
            if($money == 0 || $money > $deal['need_money_decimal']){                
                    $money = $deal['need_money_decimal'];
            }
            if($deal['deal_crowd']=='1')//新手专享
            {
                if($money > $deal['min_loan_money'])
                {
                    $money = $deal['min_loan_money'];                        
                }
                else
                {
                    $money = $deal['need_money_decimal'];                        
                }
            }
        }

        // 如果是融资租赁使用单独模板
        //if (LoanType::getLoanTypeByTypeId($deal['type_id']) == LoanType::$type[LoanType::TYPE_ASSET]) {
        if (LoanType::getLoanTagByTypeId($deal['type_id']) == LoanType::TYPE_ZCZR) {
            $display_html = "page/deal_bid_lease.html";
        } else {
            $display_html = "page/deal_bid.html";
        }

        // 优惠券显示开关
        $coupon = new core\service\CouponService();
        $turn_on_coupon = $coupon->isShowCoupon($id) ;
        $GLOBALS['tmpl']->assign("turn_on_coupon", $turn_on_coupon);

        $GLOBALS['tmpl']->assign("adviser",$adviser);
        $GLOBALS['tmpl']->assign("money",$money);
        //$GLOBALS['tmpl']->assign("lenderProtocal_temp",$lenderProtocal_temp);
        $GLOBALS['tmpl']->assign("default_loan_fee_rate",app_conf('DEFAULT_LOAN_FEE_RATE'));
        $GLOBALS['tmpl']->assign("default_manage_fee_rate",$default_manage_fee_rate);
        $GLOBALS['tmpl']->assign("default_manage_fee_text",$default_manage_fee_text);
        $GLOBALS['tmpl']->assign("p2p_api_url_is_ok", $GLOBALS['sys_config']['P2P_API_URL_iS_OK']);
        $GLOBALS['tmpl']->display($display_html);
    }
    
    /**
     * 出接人平台服务协议预签
     *
     * @Title: lender_protocal_contract 
     * @Description: 出接人平台服务协议预签
     * @param @param unknown_type $deal   
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function lender_protocal_contract($deal){
        $tpl_name = 'TPL_LENDER_PROTOCAL'.  $this->contract_suffix($deal['contract_tpl_type']);
        $guarantee_protocal_tpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = '".$tpl_name."'");
        $guarantee_protocal_tmpl_content = $guarantee_protocal_tpl['content'];
        
        if(empty($GLOBALS['user_info'])) return false;
        
        $notice = array();
        $notice['loan_user_idno'] = $GLOBALS['user_info']['idno'];
        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];
        $notice['loan_address'] = $GLOBALS['user_info']['address'];
        $notice['loan_phone'] = $GLOBALS['user_info']['mobile'];
        $notice['loan_email'] = $GLOBALS['user_info']['email'];
        $notice['manage_fee_rate'] = format_rate_for_show($deal['manage_fee_rate']);
        $notice['manage_fee_text'] = $deal['manage_fee_text'];
        
        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
        
        $earning = new Earning();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);
        

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");




        $GLOBALS['tmpl']->assign("notice",$notice);
        $guarantee_protocal = $GLOBALS['tmpl']->fetch("str:".$guarantee_protocal_tmpl_content);
        return $guarantee_protocal;
    }
    
    /**
     * 取合同模板后缀
     * @param type $contract_tpl_type
     * @return string
     */
    private function contract_suffix($contract_tpl_type){
        if($contract_tpl_type != 'DF') 
                return '_'.$contract_tpl_type;
        return '';
    }
    
    /**
     * 获取保证预签合同
     *
     * @Title: get_guarantee_protocal_contract 
     * @Description: todo(这里用一句话描述这个方法的作用) 
     * @param @param unknown_type $deal   
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function get_guarantee_protocal_contract($deal){
        /*    */
        $tpl_name = 'TPL_WARRANT_CONTRACT'.  $this->contract_suffix($deal['contract_tpl_type']);
        $guarantee_protocal_tpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = '".$tpl_name."'");
        $guarantee_protocal_tmpl_content = $guarantee_protocal_tpl['content'];
        
        $agency_info = get_agency_info($deal['agency_id']);
        //$borrow_user_info = get_user_info($deal['user_id'],true);
        $borrow_user_info = get_deal_borrow_info($deal);
        
        $notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);
        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_fax'] = $agency_info['fax'];
        $notice['agency_mobile'] = $agency_info['mobile'];
        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];
        
        //新加字段处理   add  by wenyanlei 2013-07-15
        $loan_user_info = get_user_info($GLOBALS['user_info']['id'],true);
        $notice['loan_user_idno'] = !empty($loan_user_info['idno']) ? $loan_user_info['idno']:'';
        $notice['loan_user_address'] = !empty($loan_user_info['address']) ? $loan_user_info['address']:'';
        $notice['loan_user_mobile'] = $loan_user_info['mobile'];
        $notice['loan_user_postcode'] = !empty($loan_user_info['postcode']) ? $loan_user_info['postcode']:'';
        $notice['loan_user_email'] = $loan_user_info['email'];
        $notice['agency_postcode'] = $agency_info['postcode'];
        
        //$notice['borrow_real_name'] = isset($borrow_user_info['real_name']) ? $borrow_user_info['real_name'] : '';
        $notice['start_time'] = to_date(get_gmtime(),"Y年m月d日");
        $endtime = get_gmtime() + $deal['repay_time']*60*24;

        $notice['end_time'] = to_date(strtotime($deal['repay_time']." month"),"Y年m月d日");
        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        
        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
        
        $earning = new Earning();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);
        

        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");


        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $GLOBALS['tmpl']->assign("notice",$notice);
        $guarantee_protocal = $GLOBALS['tmpl']->fetch("str:".$guarantee_protocal_tmpl_content);
        return $guarantee_protocal;
    }    
    /**
     * 获取借款预签合同
     *
     * @Title: get_loan_protocal_contract 
     * @Description: todo(这里用一句话描述这个方法的作用) 
     * @param @param unknown_type $deal   
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function get_loan_protocal_contract($deal){
        /*   */
        $tpl_name = 'TPL_LOAN_CONTRACT'.$this->contract_suffix($deal['contract_tpl_type']);
        $loan_protocal_tpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = '".$tpl_name."'");
        $loan_protocal_tmpl_content = $loan_protocal_tpl['content'];
        //$borrow_user_info = get_user_info($deal['user_id'],true);
        $borrow_user_info = get_deal_borrow_info($deal);
        $agency_info = get_agency_info($deal['agency_id']);
        $notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);
        
        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];
        $notice['loan_user_idno'] = $GLOBALS['user_info']['idno'];
        
        $notice['borrow_real_name'] = $borrow_user_info['borrow_real_name'];
        $notice['borrow_user_idno'] = $borrow_user_info['borrow_user_idno'];
        $notice['repay_time'] = $deal['repay_time'];
        $notice['repay_time_unit'] = $deal['loantype'] == 5 ? $deal['repay_time'].'天' : $deal['repay_time'].'个月';
        
        $notice['start_time'] = to_date(get_gmtime(),"Y年m月d日");
        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        
        //利率不从配置取，改取数据库  edit by wenyanlei 20130816
        //$notice['rate'] = get_deal_rate_data($deal['loantype'],$deal['repay_time']);
        $notice['rate'] = format_rate_for_show($deal['int_rate']);
        $notice['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        
        //借款合同（公司借款）中添加担保公司信息 edit by wenyanlei 20131112
        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_mobile'] = $agency_info['mobile'];
        $notice['agency_postcode'] = $agency_info['postcode'];
        $notice['agency_fax'] = $agency_info['fax'];
        
        //获取用户的银行卡信息
        FP::import("app.common");
        $loan_bank_info = get_user_bank($GLOBALS['user_info']['id']);
        $borrow_bank_info = get_user_bank($deal['user_id']);
        
        //新加的变量处理  add by wenyanlei 2013-07-15
        $notice['loan_user_name'] = $GLOBALS['user_info']['user_name'];
        $notice['loan_bank_user'] = isset($loan_bank_info['card_name']) ? $loan_bank_info['card_name'] : '';
        $notice['loan_bank_card'] = isset($loan_bank_info['bankcard']) ? $loan_bank_info['bankcard'] : '';
        $notice['loan_bank_name'] = isset($loan_bank_info['bankname']) ? $loan_bank_info['bankname'].$loan_bank_info['bankzone'] : '';
        
        $notice['borrow_user_name'] = $borrow_user_info['borrow_user_name'];
        $notice['borrow_bank_user'] = $borrow_bank_info['card_name'];
        $notice['borrow_bank_card'] = $borrow_bank_info['bankcard'];
        $notice['borrow_bank_name'] = $borrow_bank_info['bankname'];
        
        $notice['leasing_contract_num'] = $deal['leasing_contract_num'];
        $notice['lessee_real_name'] = $deal['lessee_real_name'];
        $notice['leasing_money'] = $deal['leasing_money'];
        $notice['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
        
        $earning = new Earning();
        $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
        $notice['repay_money'] = sprintf("%.2f", $all_repay_money);
        $notice['repay_money_uppercase'] = get_amount($all_repay_money);


        $notice['entrusted_loan_entrusted_contract_num'] = $deal['entrusted_loan_entrusted_contract_num'];
        $notice['entrusted_loan_borrow_contract_num'] = $deal['entrusted_loan_borrow_contract_num'];
        $notice['base_contract_repay_time'] = $deal['base_contract_repay_time'] == 0 ? '' : to_date($deal['base_contract_repay_time'], "Y年m月d日");


        unset($borrow_user_info['borrow_real_name']);
        unset($borrow_user_info['borrow_user_idno']);
        unset($borrow_user_info['borrow_user_name']);
        
        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $GLOBALS['tmpl']->assign("notice",$notice);
        
        $loan_protocal = $GLOBALS['tmpl']->fetch("str:".$loan_protocal_tmpl_content);
        
        return $loan_protocal;
    }
    /**
     * 保证反担保
     *
     * @Title: get_warrandice_contract_adv 
     * @Description: todo(这里用一句话描述这个方法的作用) 
     * @param    
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function get_warrandice_contract_adv($deal){
        /**/
        $tpl_name = 'TPL_WARRANDICE_CONTRACT_ADV'.$this->contract_suffix($deal['contract_tpl_type']);
        $warrandice_contract_adv_tpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = '".$tpl_name."'");
        $warrandice_contract_adv_content = $warrandice_contract_adv_tpl['content'];
        $agency_info = get_agency_info($deal['agency_id']);
        $borrow_user_info = get_deal_borrow_info($deal);
        //$borrow_user_info = get_user_info($deal['user_id'],true);
        /* $guarantor_info = $GLOBALS['db']->getRowCached("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']); //获取保证人列表 */
        $guarantor_info = get_user_info($GLOBALS['user_info']['id'], true);
        
        $notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);
        
        $notice['guarantor_name'] = $guarantor_info['user_name'];
        $notice['guarantor_address'] = !empty($guarantor_info['address']) ? $guarantor_info['address']:'';
        $notice['guarantor_mobile'] = $guarantor_info['mobile'];
        
        //新加的变量处理  add by wenyanlei 2013-07-15//
        $notice['guarantor_email'] = $guarantor_info['email'];
        $notice['guarantor_idno'] = !empty($guarantor_info['idno']) ? $guarantor_info['idno'] : '';
        
        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_mobile'] = $agency_info['mobile'];

        //保证人确认页面还没有出借人信息，所以置为空
        $notice['loan_real_name'] = '';
        $notice['loan_user_idno'] = '';
        $notice['loan_contract_num'] = '';
        
        //$notice['borrow_real_name'] = isset($borrow_user_info['real_name']) ? $borrow_user_info['real_name'] : '';
        //$notice['borrow_user_idno'] = isset($borrow_user_info['idno']) ? $borrow_user_info['idno'] : '';

        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息
        
        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        
        $GLOBALS['tmpl']->assign("notice",$notice);
        $warrandice_contract_adv = $GLOBALS['tmpl']->fetch("str:".$warrandice_contract_adv_content);
        return $warrandice_contract_adv;
    }
    /**
     * 获取委托合同
     *
     * @Title: get_warrant_contract_adv 
     * @Description: todo(这里用一句话描述这个方法的作用) 
     * @param    
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function get_warrant_contract_adv($deal){
        /*    */
        $tpl_name = 'TPL_WARRANT_CONTRACT_ADV'.$this->contract_suffix($deal['contract_tpl_type']);
        $warrant_contract_adv_tpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = '".$tpl_name."'");
        $warrant_contract_adv_content = $warrant_contract_adv_tpl['content'];
        //$borrow_user_info = get_user_info($deal['user_id'],true);
        $borrow_user_info = get_deal_borrow_info($deal);
        $agency_info = get_agency_info($deal['agency_id']);
        
        $notice['number'] = mt_rand(10000,90000).mt_rand(100000, 900000);
        
        /* $notice['borrow_real_name'] = $borrow_user_info['real_name'];
        $notice['borrow_address'] = $borrow_user_info['idno'];
        $notice['borrow_real_name'] = $borrow_user_info['idno'];
        $notice['borrow_mobile'] = $borrow_user_info['idno'];
        $notice['borrow_postcode'] = $borrow_user_info['idno']; */
        
        $notice['agency_name'] = $agency_info['name'];
        $notice['agency_address'] = $agency_info['address'];
        $notice['agency_user_realname'] = $agency_info['realname'];
        $notice['agency_mobile'] = $agency_info['mobile'];
        $notice['agency_postcode'] = $agency_info['postcode'];
        $notice['review'] = $agency_info['review'];
        $notice['premium'] = $agency_info['premium'];
        $notice['caution_money'] = $agency_info['caution_money'];
        $notice['loan_real_name'] = $GLOBALS['user_info']['real_name'];
        $notice['sign_time'] = to_date(get_gmtime(),"Y年m月d日");
        
        $notice = array_merge($notice, $borrow_user_info);//借款人信息和公司信息

        $GLOBALS['tmpl']->assign("notice",$notice);
        $warrant_contract_adv = $GLOBALS['tmpl']->fetch("str:".$warrant_contract_adv_content);
        return $warrant_contract_adv;
    }
    
    function dobidstepone() {
        if (! $GLOBALS ['user_info'])
            return showErr ( $GLOBALS ['lang'] ['PLEASE_LOGIN_FIRST'], 1 );
        
        if ($GLOBALS ['user_info'] ['idcardpassed'] == 0) {
            if (trim ( $_REQUEST ['idno'] ) == "") {
                return showErr ( $GLOBALS ['lang'] ['PLEASE_INPUT'] . $GLOBALS ['lang'] ['IDNO'], 1 );
            }
            if (trim ( $_REQUEST ['idno'] ) != trim ( $_REQUEST ['idno_re'] )) {
                return showErr ( $GLOBALS ['lang'] ['TWO_ENTER_IDNO_ERROR'], 1 );
            }
            
            // 用id5验证身份证信息
            $len = strlen ( $_REQUEST ['idno'] );
            if ($len != 15 && $len != 18) {
                return showErr ( $GLOBALS ['lang'] ['IDNO_ERROR'], 1 );
            } else {
                FP::import ( "libs.id5.SynPlat" );
                $id5 = new SynPlatAPI ( $GLOBALS ['sys_config'] ['id5_url'], $GLOBALS ['sys_config'] ['id5_user'], $GLOBALS ['sys_config'] ['id5_passwd'], $GLOBALS ['sys_config'] ['id5_key'], $GLOBALS ['sys_config'] ['id5_iv'] );
                //$reinfo = $id5->checkIdno ( $_REQUEST ['name'], $_REQUEST ['idno'] );
                // 临时策略
                $reinfo = 1;
                if ($reinfo == 1) {
                    $data ['real_name'] = $_REQUEST ['name'];
                    $data ['idno'] = trim ( $_REQUEST ['idno'] );
                    $data ['idcardpassed'] = 1;
                    $data ['idcardpassed_time'] = time ();
                    $data ['sex'] = $id5->getSex ( $data ['idno'] );
                    
                    // 设置出生日期
                    $birth = $id5->getBirthDay ( $data ['idno'] );
                    $data ['byear'] = $birth ['year'];
                    $data ['bmonth'] = $birth ['month'];
                    $data ['bday'] = $birth ['day'];
                    
                    // 记录日志文件
                    FP::import ( "libs.utils.logger" );
                    $log = array (
                            'type' => 'idno',
                            'user_name' => $_REQUEST ['user_name'],
                            'user_login_name' => $GLOBALS ['user_info'] ['user_login_name'],
                            'indo' => $_REQUEST ['idno'],
                            'path' => __FILE__,
                            'function' => 'dobidstepone',
                            'msg' => '身份证认证成功.',
                            'time' => time () 
                    );
                    logger::wLog ( $log );
                } else {
                    // 记录日志文件
                    FP::import ( "libs.utils.logger" );
                    $log = array (
                            'type' => 'idno',
                            'user_name' => $_REQUEST ['user_name'],
                            'user_login_name' => $GLOBALS ['user_info'] ['user_login_name'],
                            'indo' => $_REQUEST ['idno'],
                            'path' => __FILE__,
                            'function' => 'dobidstepone',
                            'msg' => '身份证认证失败.',
                            'time' => time () 
                    );
                    logger::wLog ( $log );
                    
                    // 如果 姓名与身份证号不一致, 姓名与身份证号库中无此号, 姓名与身份证号 未查到数据
                    /* if ($reinfo == 2 || $reinfo == 3 || $reinfo == 4)
                        showErr ( $GLOBALS ['lang'] ['IDNO_ERROR'], 1 );
                    else */
                    return showErr ( $GLOBALS ['lang'] ['IDNO_ERROR'], 1 );
                }
            }
        }
        
        if ($GLOBALS ['user_info'] ['mobilepassed'] == 0) {
            if (trim ( $_REQUEST ['phone'] ) == "") {
                return showErr ( $GLOBALS ['lang'] ['MOBILE_EMPTY_TIP'], 1 );
            }
            if (! check_mobile ( trim ( $_REQUEST ['phone'] ) )) {
                return showErr ( $GLOBALS ['lang'] ['FILL_CORRECT_MOBILE_PHONE'], 1 );
            }
            if (trim ( $_REQUEST ['validateCode'] ) == "") {
                return showErr ( $GLOBALS ['lang'] ['PLEASE_INPUT'] . $GLOBALS ['lang'] ['VERIFY_CODE'], 1 );
            }
            if (trim ( $_REQUEST ['validateCode'] ) != $GLOBALS ['user_info'] ['bind_verify']) {
                return showErr ( $GLOBALS ['lang'] ['BIND_MOBILE_VERIFY_ERROR'], 1 );
            }
            $data ['mobile'] = trim ( $_REQUEST ['phone'] );
            $data ['mobilepassed'] = 1;
        }
        if ($data)
            $GLOBALS ['db']->autoExecute ( DB_PREFIX . "user", $data, "UPDATE", "id=" . $GLOBALS ['user_info'] ['id'] );
        
        return showSuccess ( $GLOBALS ['lang'] ['SUCCESS_TITLE'], 1 );
    }
    
    function dobid(){
        $ajax = intval($_REQUEST["ajax"]);
        
        $id = intval($_REQUEST["id"]);
        $deal = get_deal($id);
        
        /*        */
        if(!$GLOBALS['user_info'])
        {
            return showErr($GLOBALS['lang']['PLEASE_LOGIN_FIRST'],$ajax);
        }    
        if(!$deal){
            return showErr($GLOBALS['lang']['PLEASE_SPEC_DEAL'],$ajax);    //未指定投标
        }
        //2013/06/29 如果此单的is_visible不为1则提示。 Liwei ADD
        if($deal['is_visible'] != 1){
            return showErr($GLOBALS['lang']['DEAL_FAILD_OPEN'],$ajax);        //已不在投标状态
        }
        //检查担保人是否未同意
        if($deal['guarantor_status'] != 2){
            return showErr($GLOBALS['lang']['DEAL_GUARANTOR_NOT'],$ajax);        
        }    
        //加入float判断
        if(preg_match('/^\d+(\.\d{1,2})?$/', $_REQUEST['bid_money']) === 0){
            return showErr($GLOBALS['lang']['BID_MONEY_NOT_TRUE'],$ajax);    //输入正确的投标金额
        }
        if(trim($_REQUEST["bid_money"])=="" || !is_numeric($_REQUEST["bid_money"]) || floatval($_REQUEST["bid_money"])<=0){
            return showErr($GLOBALS['lang']['BID_MONEY_NOT_TRUE'],$ajax);    //输入正确的投标金额
        }
        
        if ($deal['need_money_decimal'] - floatval($_REQUEST["bid_money"]) < $deal['min_loan_money']) {
            if (floatval($_REQUEST["bid_money"]) != $deal['need_money_decimal']) {
                return showErr(sprintf($GLOBALS['lang']['LAST_BID_AT_ONCE_NOT_TRUE'], $deal['need_money']),$ajax);    //输入正确的投标金额
            }
        }
        
        if(floatval($deal['progress_point']) >= 100){
            return showErr($GLOBALS['lang']['DEAL_BID_FULL'],$ajax);        //已经满标
        }
        
        if($deal['is_visible'] == 0){
            return showErr($GLOBALS['lang']['DEAL_BID_FULL'],$ajax);        //已经满标
        }
        
        if(floatval($deal['deal_status']) != 1 ){
            return showErr($GLOBALS['lang']['DEAL_FAILD_OPEN'],$ajax);        //已不在投标状态
        }

        // 定时标
        if ($deal['start_loan_time'] && $deal['start_loan_time']>get_gmtime()) {
            return showErr($GLOBALS['lang']['DEAL_FAILD_OPEN'], $ajax);
        }
        
        //新手标
        if($deal['deal_crowd']=='1'
                        && $GLOBALS['db']->getOne("select count(id) from ".DB_PREFIX."deal_load where user_id ="
                                .$GLOBALS['user_info']['id'])>0 ){
            return showErr('新手专享标为网信理财平台初次投资用户推荐的优惠项目，只有第一次投资的用户才可以投标',$ajax);
        }
                if($deal['deal_crowd']=='1')//新手专享
                {
                    if ($deal['need_money_decimal'] >= (2*$deal['min_loan_money'])
                            && floatval($_REQUEST["bid_money"]) > $deal['max_loan_money'] && $deal['max_loan_money']>0)
                    {
                        return showErr("投资金额须小于等于{$deal['max_loan_money']}元，且仅包含两位小数",$ajax);
                    }
                }
                
        if(floatval($_REQUEST["bid_money"]) > $GLOBALS['user_info']['money']){
            return showErr($GLOBALS['lang']['MONEY_NOT_ENOUGHT'],$ajax);    //余额不足，无法投标
        }

        //判断所投的钱是否超过了剩余投标额度
        if(bccomp(floatval($_REQUEST["bid_money"]), $deal['need_money_decimal']) == 1){
            return showErr(sprintf($GLOBALS['lang']['DEAL_LOAN_NOT_ENOUGHT'],format_price($deal['borrow_amount'] - $deal['load_money'])),$ajax);
        }
        
        //见证人证明书处理
        if($_REQUEST["is_prove"] == 1){
            
            $prove = $_REQUEST['prove'];
            
            foreach ($prove as &$pval){
                $pval = htmlspecialchars(addslashes(trim($pval)));
            }
            
            $prove['city_id'] = intval($prove['city_id']);
            $prove['province_id'] = intval($prove['province_id']);
            
            if($prove['real_name'] == ''){
                return showErr('请填写收件人姓名',$ajax);
            }
            
            if($prove['province_id'] == 0 || $prove['city_id'] == 0){
                return showErr('请选择收件人所在地区',$ajax);
            }
            
            if($prove['address'] == ''){
                return showErr('请填写收件人详细地址',$ajax);
            }
            
            if($prove['postcode'] == ''){
                return showErr('请填写收件人邮编',$ajax);
            }
            
            if($prove['mobile'] == '' || !is_mobile($prove['mobile'])){
                return showErr('收件人手机号格式错误',$ajax);
            }
            
            if($prove['phone'] != '' && $prove['phone1'] == '' || $prove['phone'] == '' && $prove['phone1'] != ''){
                return showErr('收件人电话格式错误',$ajax);
            }
            
            if($prove['phone'] && $prove['phone1']){
                $prove['phone'] .= '-'.$prove['phone1'];
            }
            
            $prove['user_id'] = $GLOBALS['user_info']['id'];

            $prove_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user_prove where user_id =".$GLOBALS['user_info']['id']." limit 1");
            
            $where = '';
            $mode = 'INSERT';
            if($prove_id){
                $mode = 'UPDATE';
                $where = 'id = '.$prove_id;
            }
        }
        
        // 验证表单令牌
        if(!check_token()){
            return showErr($GLOBALS['lang']['TOKEN_ERR'],$ajax);
        }
        
        if($_REQUEST['is_prove'] == 1){
            $GLOBALS['db']->autoExecute(DB_PREFIX."user_prove",$prove,$mode,$where);
        }
        
        // $data['is_parent'] = empty($deal['parent_id']) ?　0 : 1; //如果是父标进行标识 Liwei ADD
        // $data['user_id'] = $GLOBALS['user_info']['id'];
        // $data['user_name'] = $GLOBALS['user_info']['user_name'];
        // $data['deal_id'] = $id;
        // $data['money'] = trim($_REQUEST["bid_money"]);
        // $data['create_time'] = get_gmtime();
        // $GLOBALS['db']->autoExecute(DB_PREFIX."deal_load",$data,"INSERT");
        // $load_id = $GLOBALS['db']->insert_id();

        $deal_load_id = check_deal($deal, $_REQUEST, $ajax);


        if(!empty($deal_load_id['sub'])){
            $load_id = $deal_load_id['sub'];
        }else if(!empty($deal_load_id['general'])){
            $load_id = $deal_load_id['general'];
        }else if(!empty($deal_load_id['parent'])){
            $load_id = $deal_load_id['parent'];
        }

        //优惠券
        if (app_conf('TURN_ON_COUPON')) {
            try {
                $short_alias = trim($_REQUEST['coupon_id']);
                if (!empty($short_alias)) {
                    $coupon = new core\service\CouponService();
                    $consume_user_id = $GLOBALS['user_info']['id'];
                    $money = floatval($_REQUEST["bid_money"]);
                    $coupon->consume($short_alias, $consume_user_id, $load_id, $money);
                }
            } catch (Exception $e) {
            }
        }

        //增加渠道推广的投标日志 关闭-20140423
        //add_deal_channel_log($load_id);


        //见证人证明书处理
        if($_REQUEST["is_prove"] == 1){
            
            $prdata = array(
                    'deal_id' => $id,
                    'load_id' => $load_id,
                    'type' => 1,
                    'apply_time' => get_gmtime()
            );
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_load_prove",$prdata,"INSERT");
            
            $prdata['type'] = 2;
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_load_prove",$prdata,"INSERT");
        }
        
        //处理顾问
        $this->_recommend($_REQUEST['recommend_name'], $_REQUEST['recommend_phone'], $deal_load_id, $id);
        //if($load_id > 0){
            //更改资金记录
            $msg = '编号'.$id.' '.$deal['name'];
            FP::import("libs.libs.user");
            $money_data = array(//投标后冻结资金，不直接扣款
                'lock_money'=>trim($_REQUEST["bid_money"]),
                'score'=>0,
            );
            // TODO finance 前台老系统 投标冻结 | 不处理
            modify_account($money_data,$GLOBALS['user_info']['id'],'投标冻结',1,$msg);
            sys_user_status($GLOBALS['user_info']['id']);
                
            //重新获取 投标信息（重要，否则无法进行下面的操作。）
            $deal = get_deal($id);
            //判断是否已经满标
            $is_deal_full = $this->check_deal_full($deal);
            
            //投标完成 发送邮件和短信等通知  caolong 2013-12-25
            send_tender_deal_message($deal,'tender',number_format(trim($_REQUEST["bid_money"]),2),$load_id);
            
            if($is_deal_full){    
                //满标发送合同清单
                $this->send_contract($deal);
                //向p2p发送相关数据
                $this->put_p2p_data($id);
                
                //开始发邮件和短信等通知
                send_full_failed_deal_message($deal,"full");
                //$this->send_full_deal_message($deal);
                //发送合同的message
                //暂时改由担保公司确认后发送
                //$this->send_contract_email();
            }
            //更新
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal",array("is_send_half_msg"=>1,'update_time'=>get_gmtime()),"UPDATE","id=".$id);
                         return showSuccess(
                            $GLOBALS['lang']['DEAL_BID_SUCCESS']
                            ,$ajax
                            ,url("index" ,"deal", array("id"=>$id))
                            ,0
                            ,array('load_id'=>$load_id, 'money'=>$_REQUEST["bid_money"])
                        );    //投标成功！ 
    }
    /**
     * 向 p2p传输 deal 和  deal_load数据
     *
     * @Title: put_p2p_data 
     * @Description: todo(这里用一句话描述这个方法的作用) 
     * @param @param unknown_type $deal   
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function put_p2p_data($id){
    //public function put_p2p_data(){
    
        $deal = $GLOBALS['db']->getRow("SELECT id,loantype,repay_money,repay_time,type_id FROM ".DB_PREFIX."deal WHERE id=".$id);
        
        if(!$GLOBALS['sys_config']['P2P_API_URL_iS_OK']) return false;
        
        if(empty($deal)) return false;

        $load_list = $GLOBALS['db']->getAll("SELECT id,deal_id,user_id,user_name,user_deal_name,money,create_time,from_deal_id FROM ".DB_PREFIX."deal_load WHERE deal_id = ".$deal['id']);

        foreach ($load_list as &$item)
        {
            $item['adviser'] = $this->getAdviserInfo($item['id']);
            $item['create_time'] = strtotime(to_date($item['create_time']));
        }

        $post_data = array(
            'deal_info' => array(
                0 => $deal
            ),
            'deal_load' => $load_list
        );
        $url = $GLOBALS['sys_config']['P2P_API_URL'].'?act=first_p2p&type=order';

        // 数据插入到队列表
        $data = array();
        $data['dest'] = $url;
        $data['send_type'] = 2;
        $data['content'] = serialize($post_data);
        $data['create_time'] = get_gmtime();
        
        $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$data,"INSERT");

        return true;
    }
    
    /* 获取顾问信息 */
    private function getAdviserInfo($deal_load_id)
    {
        $sql = "select adviser_id,name,mobile,status from ".DB_PREFIX."deal_load_adviser as a , ".DB_PREFIX."adviser as b where a.deal_load_id = '{$deal_load_id}' and a.recommend_id = b.id ";
        $info = $GLOBALS['db']->getRow($sql);
        return $info;
    }
    
    
    /**
     * 顾问处理
     *
     * @Title: _recommend 
     * @Description: 顾问处理
     * @param @param unknown_type $name
     * @param @param unknown_type $phone   
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function _recommend($name, $phone, $deal_load_id, $deal_id){
        if(!$GLOBALS['sys_config']['P2P_API_URL_iS_OK']) return false;
        
        if(empty($name)) return false;
        if(empty($phone)) return false;
        if(empty($deal_load_id)) return false;
        if(empty($deal_id)) return false;
        
        $name = trim($name);
        $phone = trim($phone);
        
        // 查看本地是否有该顾问信息
        $recommend_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."adviser where name = '{$name}' and mobile = '{$phone}' ");
        
        if($recommend_info)
        {
            if ($recommend_info['status'] < 1)
            {
                // 当顾问未通过的验证的时候，再次验证顾问信息
                $recommend_p2p = $this->_ver_recommend($name, $phone);
                if ($recommend_p2p['status'] == 1)
                {
                    //更新本地顾问信息
                    $GLOBALS['db']->autoExecute(DB_PREFIX."adviser",$recommend_p2p,"UPDATE", 'id='.$recommend_info['id']);
                }
            }
            
            $adviser_db_id = $recommend_info['id'];
        }
        else
        {
            //验证顾问信息
            $data = $this->_ver_recommend($name, $phone);
            
            // 本地插入顾问信息
            $GLOBALS['db']->autoExecute(DB_PREFIX."adviser",$data,"INSERT");
            $adviser_db_id = $GLOBALS['db']->insert_id();
        }
        
        //写入顾问和用户投标的关系表
        if($adviser_db_id || $recommend_info){
            $data = array();
            $load_id = "";
//            foreach ($deal_load_id as $v){
//                if(is_array($v)){
//                    $load_id .= implode($v, ',').',';
//                }else{
//                    $load_id .= $v.',';
//                }
//            }
            
            
            #$load_id = empty($deal_load_id['sub']) ? $deal_load_id['parent'] : $deal_load_id['sub'];
            if(!empty($deal_load_id['sub']))
                $load_id = $deal_load_id['sub'];
            else if(!empty($deal_load_id['general']))
                $load_id = $deal_load_id['general'];
            else if(!empty($deal_load_id['parent']))
                $load_id = $deal_load_id['parent'];
                
            $data['deal_load_id'] = trim($load_id,',');            
            $data['deal_id'] = $deal_id;
            $data['user_id'] = $GLOBALS['user_info']['id'];
            $data['recommend_id'] = $adviser_db_id;
            $data['create_time'] = get_gmtime();
            
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_load_adviser",$data,"INSERT");
            $result = $GLOBALS['db']->insert_id();
        }
        return $result;
    }
    
    /**
     * 检查是否已经满标
     *
     * @Title: is_deal_full 
     * @Description: todo(这里用一句话描述这个方法的作用) 
     * @param    
     * @return return_type   
     * @author Liwei
     * @throws 
     *
     */
    private function check_deal_full($deal){
        $deal = get_deal($deal['id']);
        if ($deal['deal_status'] == 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 满标发送合同清单
     *
     * @Title: send_contract 
     * @Description: 满标发送合同清单 
     * @param  $deal 订$arr_ip_access单
     * @param $is_check 是否检查订单类型
     * @return return NULL   
     * @author Liwei
     * @throws 
     *
     */
       private function send_contract($deal,$is_check=FALSE){
        if(empty($deal['contract_tpl_type'])){
            return false;
        }
           //if (empty($deal)) return  FALSE;
    //public function send_contract($deal){
        $contractModule = new sendContract();  //引入合同操作类
        $notice_contrace = array();
        //$borrow_user_info = get_user_info($deal['user_id'],true); //借款人信息
        $borrow_user_info = get_deal_borrow_info($deal); //借款人 或公司信息
        $agency_info = get_agency_info($deal['agency_id']);//担保公司信息
        
         if($deal['deal_status'] == 2 || $is_check){  //如果是普通单子
             //获取出借列表                                                
            //$loan_user_list = $GLOBALS['db']->getAll("SELECT u.*,group_concat(d.`id`) as deal_load_id,d.deal_id,d.money as loan_money FROM ".DB_PREFIX."deal_load as d,".DB_PREFIX."user as u WHERE d.deal_id = ".$deal['id']." AND d.user_id = u.id GROUP BY d.user_id"); 
               $loan_user_list = $GLOBALS['db']->getAll("SELECT u.*,d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time FROM ".DB_PREFIX."deal_load as d,".DB_PREFIX."user as u WHERE d.deal_id = ".$deal['id']." AND d.user_id = u.id");
               $guarantor_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_guarantor WHERE deal_id = ".$deal['id']); //获取保证人列表

               //扩展字段
               $earning = new Earning();
               $all_repay_money = sprintf("%.2f", $earning->getRepayMoney($deal['id']));
               $borrow_user_info['repay_money'] = $all_repay_money;
            $borrow_user_info['repay_money_uppercase'] = get_amount($all_repay_money);
            $borrow_user_info['leasing_contract_num'] = $deal['leasing_contract_num'];
            $borrow_user_info['lessee_real_name'] = $deal['lessee_real_name'];
            $borrow_user_info['leasing_money'] = $deal['leasing_money'];
            $borrow_user_info['leasing_money_uppercase'] = get_amount($deal['leasing_money']);
               
               ################   借款合同 （出借人、借款人）         ################
               //出借人
               $contractModule->push_loan_contract($deal, $loan_user_list, $borrow_user_info, NULL);
            //出借人平台服务协议
            $contractModule->push_lender_protocal($deal, $loan_user_list, $borrow_user_info);
            
               //借款人
               $contractModule->push_loan_contract($deal, $loan_user_list, $borrow_user_info, $deal['user_id']);
            //借款人平台服务协议
            $contractModule->push_borrower_protocal($deal, $borrow_user_info);
               
               ################   委托担保合同 （借款人、担保公司）         ################    
               //借款人
               $contractModule->push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $deal['user_id']);
               //担保公司
               $contractModule->push_entrust_warrant_contract($deal, $guarantor_list, $loan_user_list, $borrow_user_info, $agency_info, $agency_info['id'],"agency");
               ################   保证人反担保（保证人、担保公司）         ################    
               //保证人
               $contractModule->push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, "guarantor");
               //担保公司
               $contractModule->push_warrandice_contract($deal, $guarantor_list, $loan_user_list, $agency_info, $borrow_user_info, "agency");
               ################   担保合同（担保公司、出借人）         ################        
               //担保公司
            $contractModule->push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, $agency_info['id'],"agency");
            //出借人
            $contractModule->push_warrant_contract($deal, $loan_user_list, $borrow_user_info, $agency_info, NULL);
            
            ################   付款委托书（借款人）         ################
            if($deal['contract_tpl_type'] == 'HY'){
                $contractModule->push_payment_order($deal, $loan_user_list, $borrow_user_info);
            }
            
               #########################   END  #################################
             //入库
             $contractModule->save();
         }
       }

    /**
     * 同意或拒绝借款保证人
     */
    public function doguarantor(){
        $guarantor_id = intval($_REQUEST['gid']);
        $status = intval($_REQUEST['status']);//是否同意，2同意，3拒绝
        $ajax = intval($_REQUEST["ajax"]);
        
        
        if(!$guarantor_id || ($status!=2 && $status!=3)){
            return showErr($GLOBALS['lang']['ERROR_TITLE'],$ajax);
        }
        
        //保证人信息
        $guarantor = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_guarantor where id=".$guarantor_id);
        if(!$guarantor['to_user_id']){
           return showErr($GLOBALS['lang']['ERROR_TITLE'],$ajax);//本人不是借款保证人，退出
        }
        
        //借款信息
        $deal = get_deal($guarantor['deal_id']);
        
        //开始更新数据
        $data['status'] = $status;
        $data['active_time'] = time();
        $r = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_guarantor",$data,"UPDATE","id=".$guarantor_id);
        $guarantor_relation_list =  $GLOBALS['dict']['DICT_RELATIONSHIPS'];
        
        if(!$r) return showErr($GLOBALS['lang']['ERROR_TITLE'],$ajax);

        if($status==2) {
            $title = "您已经同意成为借款保证人";
            $notice_title= "同意";
        }
        if($status==3) {
            $title = "您已经拒绝成为借款保证人";
            $notice_title= "拒绝";
        }
        
        //发邮件及短信通知

        //借款人信息
        $deal_user = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id=".$guarantor['user_id']);

        // JIRA#3260 企业账户二期 <fanjingwen@ucf>
        $userServ = new \core\service\UserService($deal['user_id']);
        if ($userServ->isEnterprise()) {
            $userName = get_company_shortname($deal['user_id']);
        } else {
            $userName = $GLOBALS['user_info']['real_name'];
        }

        //消息
        $Msgcenter = new Msgcenter();
        
        //短信
        $notice_sms = array(
            'site_name' => app_conf("SHOP_TITLE"),
            'user_name' => $userName,
            'deal_name' => $deal['name'],
            'verify' => $notice_title,
        );
        //借款人短信
        \libs\sms\SmsServer::instance()->send($deal_user['mobile'], 'TPL_DEAL_GUARANTOR_VERIFY_SMS', $notice_sms, $guarantor['user_id']);
        $notice_sms['user_name'] = '您';
        //给自己发短信
        \libs\sms\SmsServer::instance()->send($GLOBALS['user_info']['mobile'], 'TPL_DEAL_GUARANTOR_VERIFY_SMS', $notice_sms, $GLOBALS['user_info']['id']);

        //邮件
        $notice_mail = array(
            'site_name' => app_conf("SHOP_TITLE"),
            'user_name' => $deal_user['real_name'],
            'msg_user_name' => $GLOBALS['user_info']['real_name'],
            'deal_name' => $deal['name'],
            'guarantor_url' => get_domain().$deal['url'],
            'deal_url' => get_domain().$deal['url'],
            'site_url' => get_domain().APP_ROOT,
            'help_url' => get_domain().url("index","helpcenter"),
            'verify' => $notice_title,
        );
        $mail_title = $GLOBALS['user_info']['real_name'].$notice_title.'成为您的借款保证人';
        //借款人邮件
        $Msgcenter->setMsg($deal_user['email'], $guarantor['to_user_id'], $notice_mail, 'TPL_DEAL_GUARANTOR_VERIFY_MAIL',$mail_title);
        //给自己发邮件
        $notice_mail['user_name'] =$GLOBALS['user_info']['real_name'];
        $notice_mail['msg_user_name']= "您";
        
        $mail_title = '您已经'.$notice_title.'成为'.$deal_user['real_name'].'的借款保证人';
        $Msgcenter->setMsg($GLOBALS['user_info']['email'], $guarantor['user_id'], $notice_mail, 'TPL_DEAL_GUARANTOR_VERIFY_MAIL',$mail_title);
        $count = $Msgcenter->save();
        
        //站内信
        $content = "<p>".$GLOBALS['user_info']['real_name'].$notice_title."成为借款“<a href=\"".$deal['url']."\">".$deal['name']."</a>”的保证人"; 
        send_user_msg("",$content,0,$guarantor['user_id'],get_gmtime(),0,true,1);//借款人
        $content = "<p>您已经".$notice_title."成为借款“<a href=\"".$deal['url']."\">".$deal['name']."</a>”的保证人";
        send_user_msg("",$content,0,$GLOBALS['user_info']['id'],get_gmtime(),0,true,1);//给自己
        
        return showSuccess($title,$ajax,url("index","deal",array("id"=>$guarantor['deal_id'])));
        
        
    }
    
    /* 前台ajax获取可投标余额做页面刷新判断 */
    function ajaxGetDealQuota()
    {
            $deal_id = intval($_GET['id']);
            $deal = get_deal($deal_id);
            echo $deal['need_money_decimal'];
            exit;
    }
    
    function test()
    {
        $deal = get_deal(5);
        
        $this->send_contract($deal);
    }
    
    /**
     * 
     * 验证顾问信息
     * @param string $name
     * @param string $phone
     * @return array 顾问信息
     * @author Guomumin
     * @date 2013-09-23
     */
    private function _ver_recommend($name, $phone){
        $url = $GLOBALS['sys_config']['P2P_API_URL'];
        $enname = urlencode($name);
        $target = $url.'?act=first_p2p&type=single&n='.$enname.'&p='.$phone;
        //http://dev.p2p.988.cn/ajax/ajax.get_commission_permonth.php?act=first_p2p&type=single&n=qlin&p=13521819008
        $cu = curl_init();
        curl_setopt($cu, CURLOPT_URL, $target);
        curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cu,CURLOPT_TIMEOUT,5);
        $ret = curl_exec($cu);
        curl_close($cu);
        
        $data = array('name' => $name, 'mobile' => $phone, 'create_time' => get_gmtime());
        
        if(!empty($ret)){
            $arr = json_decode($ret,true);
            $data['adviser_id'] = $arr['id'];
            $data['email'] = $arr['user_email'];
            $data['user_name'] = $arr['user_login_name'];
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        
        return $data;
    }

    
}
?>
