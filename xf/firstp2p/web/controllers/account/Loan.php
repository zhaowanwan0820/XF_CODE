<?php
/**
 * 回款计划
 * @author pengchanglu@ucfgroup.com
 **/

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Loan extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'repay_status'=>array("filter"=>'string'),
            'history'=>array("filter"=>'string'),
            'money_type'=>array("filter"=>'string'),
            'start_time'=>array("filter"=>'reg', "message"=>"起始时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'end_time'=>array("filter"=>'reg', "message"=>"结束时间不合法", "option"=>array("regexp"=>"/^\d{4}-\d{2}-\d{2}$/" ,'optional' => true)),
            'p'=>array("filter"=>'int'),
            'type' => array('filter' => 'int'),
        );

        if ( !$this->form->validate()) {
            $msg = $this->form->getErrorMsg();
            $this->show_error($msg, '参数不合法', 0, 0, '/account/loan');
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        //搜索条件设置
        $repay_status = $data['repay_status'];
        $money_type = $data['money_type'];
        $start_time = $data['start_time'] ? to_timespan($data['start_time']) : '';
        $end_time = $data['end_time'] ? to_timespan($data['end_time']) : "";
        $page = $data['p'] <= 0 ? 1 : $data['p'];
        $type = $data['type'];
        $history = isset($data['history']) ? intval($data['history']) : 0;
        if ($type != 1 && $type != 2) {
            $type = 1;
        }

        $user_id = $GLOBALS['user_info']['id'];
        if($repay_status !== "" && $repay_status !== NULl){
            $repay_status = intval($repay_status);
        }else{
            $repay_status = null;
        }
        //前台页面显示问题
        $money_type = $money_type ? intval($money_type) : null;
        if ($type == 2) {
            $result = (new \core\service\ncfph\AccountService())->getLoan($user_id,$start_time,$end_time,array(($page-1)*app_conf("PAGE_SIZE"),app_conf("PAGE_SIZE")),'web',$money_type,$repay_status,false,$history);
        } else {
            $result = $this->rpc->local('DealLoanRepayService\getRepayList',array($user_id,$start_time,$end_time,array(($page-1)*app_conf("PAGE_SIZE"),app_conf("PAGE_SIZE")),'web',$money_type,$repay_status));
        }

        $page = new \Page($result['counts'], app_conf("PAGE_SIZE"));
        $page_str = $page->show();

        $totalPages = ceil($result['counts']/app_conf('PAGE_SIZE'));

        $repay_status_all = $result['status'];
        $money_type_all = $result['type'];
        $search = array('money_type' => $money_type, 'repay_status' => $repay_status, 'start_time' => $_GET['start_time'], 'end_time' => $_GET['end_time']);
        $this->tpl->assign("type", $type);
        $this->tpl->assign('pages',$page_str);
        $this->tpl->assign("list",$result['list']);
        $this->tpl->assign('money_type',$money_type_all);
        $this->tpl->assign('repay_status',$repay_status_all);
        $this->tpl->assign("inc_file","web/views/account/loan.html");
        $this->tpl->assign('history', $history);
        $this->tpl->assign('last_page',$totalPages == $data['p'] || $totalPages == 1 ? "1" : 0);
        $this->tpl->assign('search', $search);
        $this->template = "web/views/account/frame.html";
        
    }


}
