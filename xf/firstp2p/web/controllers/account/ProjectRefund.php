<?php

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

use core\dao\DealProjectModel;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class ProjectRefund extends BaseAction
{

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
                'status'=>array("filter"=>'int'), // 0 | empty：还款中；1：已还清
                'p'=>array("filter"=>'int'),
        );

        $this->form->validate();
    }

    public function invoke() {

        $user_info = $GLOBALS ['user_info'];

        $data = $this->form->data;
        $status = intval($data['status']);
        $this->tpl->assign('status', $status);
        if (!empty($status) && 1 == $status) {
            $business_status_arr = array(DealProjectModel::$PROJECT_BUSINESS_STATUS['repaid']);
        } else {
            $business_status_arr = array(DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying'], DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay']);
        }

        $page = $data['p'] <= 0 ? 1 : intval($data['p']);

        list($project_list, $project_count) = $this->rpc->local('DealProjectService\getRepayEntrustProjectInfoByUserId', array($user_info['id'], $business_status_arr, ($page-1)*app_conf("PAGE_SIZE"), app_conf("PAGE_SIZE")));
        $this->tpl->assign("project_list", $project_list);


        // 分页
        $page = new \Page($project_count, app_conf("PAGE_SIZE"));
        $p  =  $page->show();
        $this->tpl->assign('pages',$p);
        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_DEAL_REFUND']);


        // iframe
        $this->tpl->assign("inc_file","web/views/account/project_refund.html");
        $this->template = "web/views/account/frame.html";
    }
}
