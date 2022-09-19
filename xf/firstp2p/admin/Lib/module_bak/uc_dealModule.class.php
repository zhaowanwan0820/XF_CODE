<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use app\models\dao\DealRepay;
use app\models\dao\Deal;
use app\models\dao\User;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\UserModel;

FP::import("app.uc");
FP::import("app.deal");
FP::import("libs.libs.msgcenter");
class uc_dealModule extends SiteBaseModule
{
    public function prepay(){
        $deal = get_deal($_GET['id']);
        $prepay_time = get_gmtime();
        $remain_days = get_remain_day($deal, $prepay_time);
        $prepay_time = to_date($prepay_time, "Y年m月d日");
        $GLOBALS['tmpl']->assign("prepay_time", $prepay_time);
        $GLOBALS['tmpl']->assign("remain_days", $remain_days);
        $GLOBALS['tmpl']->assign("last_repay_day", to_date(get_last_repay_time($deal), "Y年m月d日"));
        $remain_principal = get_remain_principal($deal);
        $prepay_money = prepay_money($remain_principal, $remain_days, $deal['compensation_days'], $deal['int_rate'], $deal['prepay_rate']);
        $GLOBALS['tmpl']->assign("prepay_money", number_format($prepay_money, 2));
        $GLOBALS['tmpl']->assign("remain_principal", number_format($remain_principal, 2));
        $deal['repay_start_time'] = to_date($deal['repay_start_time'], "Y年m月d日");
        $GLOBALS['tmpl']->assign("deal", $deal);
        $GLOBALS['tmpl']->assign("money", number_format($GLOBALS['user_info']['money'], 2));
        $GLOBALS['tmpl']->display("inc/uc/uc_deal_prepay.html");	
    }

    public function do_prepay(){
        $deal_id = intval($_POST['id']);
        if($deal_id == 0){
            showErr("操作失败！", 1);
        }
        $deal = get_deal($deal_id);
        if(!$deal || $deal['deal_status'] != 4){
            showErr("操作失败！", 1);
        }
        if($deal['user_id'] != $GLOBALS['user_info']['id']){
            showErr("无操作权限！", 1);
        }
        $prepay_time = get_gmtime();
        $remain_days = get_remain_day($deal, $prepay_time);
        $remain_principal = get_remain_principal($deal);
        $prepay_money = prepay_money($remain_principal, $remain_days, $deal['compensation_days'], $deal['int_rate']);
        $prepay_interest = prepay_money_intrest($remain_principal, $remain_days, $deal['int_rate']);
        $prepay_compensation = $prepay_money - $prepay_interest - $remain_principal;
        $money = $GLOBALS['user_info']['money'];
        if($money < $prepay_money){
            showErr("余额不足，请充值后重新申请！", 1);
        }
        $data = array(
            'deal_id'             => $deal_id,
            'user_id'             => $GLOBALS['user_info']['id'],
            'prepay_time'         => get_gmtime(),
            'remain_days'         => $remain_days,
            'prepay_money'        => $prepay_money,
            'remain_principal'    => $remain_principal,
            'prepay_interest'     => $prepay_interest,
            'prepay_compensation' => $prepay_compensation,
        );
        $sql = "select count(*) from ".DB_PREFIX."deal_prepay where deal_id= $deal_id and (status =0 or status = 1)";
        $count = intval($GLOBALS['db']->getOne($sql));
        if($count > 0) {
            showErr("请勿重复申请！", 1);
        }
        if($GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"INSERT")){
            lock_money($prepay_money, $GLOBALS['user_info']['id'], $message = "提前还款申请",1,'编号'.$deal['id'].' '.$deal['name']);
            return showSuccess("操作成功!",1);
        }
    }

    public function refund(){
        $user_id = $GLOBALS['user_info']['id'];

        $status = intval($_REQUEST['status']);

        $GLOBALS['tmpl']->assign("status",$status);

        //输出借款记录
        $page = intval($_REQUEST['p']);
        if($page==0)
            $page = 1;
        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");

        $deal_status = 4;
        if($status == 1){
            $deal_status = 5;
        }

        $result = get_deal_list($limit,0,"deal_status =$deal_status AND borrow_amount>0 AND user_id=".$user_id,"id DESC", true, 1);
        $GLOBALS['tmpl']->assign("deal_list",$result['list']);

        $page = new Page($result['count'],app_conf("PAGE_SIZE"));   //初始化分页对象 		
        $p  =  $page->show();
        $GLOBALS['tmpl']->assign('pages',$p);

        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_DEAL_REFUND']);
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_deal_refund.html");
        $this->display();	
    }

    public function contract(){
        $id = intval($_REQUEST['id']);
        if($id == 0){
            showErr("操作失败！");
        }
        $deal = get_deal($id);
        if(!$deal || $deal['user_id']!=$GLOBALS['user_info']['id'] || $deal['deal_status']!=4){
            showErr("操作失败！");
        }
        $GLOBALS['tmpl']->assign('deal',$deal);

        $loan_list = $GLOBALS['db']->getAll("select * FROM ".DB_PREFIX."deal_load WHERE deal_id=".$id." ORDER BY create_time ASC");
        foreach($loan_list as $k=>$v){
            if($deal['loantype']==0)
            {
                $loan_list[$k]['get_repay_money'] = pl_it_formula($v['money'],$deal['rate']/12/100,$deal['repay_time']);
            }else{
                $loan_list[$k]['get_repay_money'] = av_it_formula($v['money'],$deal['rate']/12/100);
            }
        }
        $GLOBALS['tmpl']->assign('loan_list',$loan_list);

        $GLOBALS['tmpl']->display("inc/uc/uc_deal_contract.html");	
    }

    //正常还款操作界面
    public function quick_refund(){
        $id = intval($_REQUEST['id']);
        if($id == 0){
            showErr("操作失败！");
        }
        $deal = Deal::instance()->find($id);
        if($deal['user_id']!=$GLOBALS['user_info']['id'] || $deal['deal_status']!=4){
            showErr("操作失败！");
        }
        $deal_repay = new DealRepay();
        $loan_list = $deal_repay->findAll("deal_id =".$id." order by id asc");
        foreach ($loan_list as $loan) {
            $loan['can_repay'] = $loan->canRepay();
            $loan['fee_of_overdue'] = $loan->feeOfOverdue();
        }

        $applied_prepay = $deal->isAppliedPrepay();
        $overdue = $deal->isOverdue();
        $cannot_prepay = !$deal->canPrepay();
        $deal['remain_repay_money'] = $deal->remainRepayMoney();
        $deal['remain_repay_money'] = $deal->remainRepayMoney();
        $deal['loantype_name'] = $deal->getLoantypeName();
        $deal['total_repay_money'] = $deal->totalRepayMoney();

        $GLOBALS['tmpl']->assign('remain_repay_money', $remain_repay_money);
        $GLOBALS['tmpl']->assign('applied_prepay', $applied_prepay);
        $GLOBALS['tmpl']->assign('overdue', $overdue);
        $GLOBALS['tmpl']->assign('cannot_prepay', $cannot_prepay);
        $GLOBALS['tmpl']->assign('deal',$deal);
        $GLOBALS['tmpl']->assign("loan_list",$loan_list);

        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_DEAL_REFUND']);
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_deal_quick_refund.html");
        $this->display();	
    }

    //正常还款执行界面
    public function repay_borrow_money(){
        $id = intval($_REQUEST['id']);
        if($id == 0){
            showErr("操作失败！",1);
            exit();
        }
        $deal = new DealModel();
        $deal = $deal->find($id);
        if(!$deal || $deal['user_id']!=$GLOBALS['user_info']['id'] || $deal['deal_status']!=4){
            showErr("操作失败！",1);
            exit();
        }
        $ids = explode(",",$_REQUEST['ids']);
        //逐一执行还款
        foreach ($ids as $id) {
            $id = intval($id);
            if($id == 0){
                showErr("操作失败！",1);
                exit();
            }
            $deal_repay = new DealRepayModel();
            $deal_repay = $deal_repay->find($id);
            if($deal_repay){
                $user = UserModel::instance()->find($deal->user_id);
                if($user->money>=$deal_repay->repay_money){
                    if ($deal_repay->repay() == false) {
                        showErr("操作失败！",1);
                        exit;
                    }
                }else{
                    showErr("对不起，您的余额不足！",1);
                    exit();
                }
            }
        }
        return showSuccess("操作成功!",1);
    }

    public function borrowed(){
        $user_id = $GLOBALS['user_info']['id'];

        //输出借款记录
        $page = intval($_REQUEST['p']);
        if($page==0)
            $page = 1;
        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");


        $result = get_deal_list($limit,0,"user_id=".$user_id,"id DESC");

        $GLOBALS['tmpl']->assign("deal_list",$result['list']);

        $page = new Page($result['count'],app_conf("PAGE_SIZE"));   //初始化分页对象 		
        $p  =  $page->show();
        $GLOBALS['tmpl']->assign('pages',$p);

        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_DEAL_BORROWED']);
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_deal_borrowed.html");
        $GLOBALS['tmpl']->display("page/uc.html");	
    }

    public function borrow_stat(){
        $user_statics = sys_user_status($GLOBALS['user_info']['id'],false,true);
        $GLOBALS['tmpl']->assign("user_statics",$user_statics);

        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_DEAL_BORROW_STAT']);
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_deal_borrow_stat.html");
        $GLOBALS['tmpl']->display("page/uc.html");	
    }

    //我担保的贷款
    public function guarantor(){
        $user_id = $GLOBALS['user_info']['id'];

        //输出借款记录
        $page = isset($_REQUEST['p']) ? intval($_REQUEST['p']) : 0;
        if($page==0){
            $page = 1;
        }
        $limit = (($page-1)*app_conf("PAGE_SIZE")).",".app_conf("PAGE_SIZE");

        $sql = "select a.* from ".DB_PREFIX."deal_guarantor a left join ".DB_PREFIX."deal b on a.deal_id = b.id where a.to_user_id=".$user_id." and b.is_delete = 0 order by a.create_time DESC limit ".$limit;
        $count_sql = " select count(*) as total from ".DB_PREFIX."deal_guarantor a left join ".DB_PREFIX."deal b on a.deal_id = b.id where a.to_user_id=".$user_id." and b.is_delete = 0 ";
        //$count_sql = " select count(*) as total from ".DB_PREFIX."deal_guarantor a left join ".DB_PREFIX."deal b on a.deal_id = b.id and b.is_delete = 0 where a.to_user_id=".$user_id;

        //担保状态字典
        $guarantor_status_list =  $GLOBALS['dict']['DEAL_GUARANTOR_STATUS'];

        //查询担保信息
        $guarantor = $GLOBALS['db']->getAll($sql);

        foreach($guarantor as $k=>$v){
            $deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where is_delete = 0 and id=".$v['deal_id']);
            $user = get_deal_borrow_info($deal);
            $deal['user_name']=$user['show_name'];
            $deal['borrow_amount_format'] = format_price($deal['borrow_amount']);
            $deal['url'] = url("index","deal",array("id"=>$deal['id']));
            $deal['name'] = get_deal_title($deal['name'], '', $deal['id']);

            //前台只显示未操作，同意担保，拒绝担保
            $guarantor[$k]['status_name'] = $guarantor_status_list[$v['status']];
            if(in_array($v['status'],array(0,1))) $guarantor[$k]['status_name']= "未操作";

            $guarantor[$k]['deal'] = $deal;
        }

        $guarantor_count = $GLOBALS['db']->getOne($count_sql);

        $GLOBALS['tmpl']->assign("guarantor_list",$guarantor);

        $page = new Page($guarantor_count,app_conf("PAGE_SIZE"));   //初始化分页对象 		
        $p  =  $page->show();
        $GLOBALS['tmpl']->assign('pages',$p);

        $GLOBALS['tmpl']->assign("page_title",$GLOBALS['lang']['UC_DEAL_GUARANTOR']);
        $GLOBALS['tmpl']->assign("inc_file","inc/uc/uc_deal_guarantor_list.html");
        $GLOBALS['tmpl']->display("page/uc.html");	
    }
}
?>
