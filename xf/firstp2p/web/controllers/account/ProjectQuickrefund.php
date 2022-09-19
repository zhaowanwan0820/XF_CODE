<?php
/**
 * 借款人提前还款 - 还款详情
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\DealLoanTypeModel;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class ProjectQuickrefund extends BaseAction
{
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $project_id = $this->form->data['id'];
        $user_info = $GLOBALS ['user_info'];

        // 校验当前登陆用户
        $project_info = $this->rpc->local("DealProjectService\getProInfo", array($project_id));
        if ($user_info['id'] != $project_info['user_id']) {
            $this->show_error('只能查看自己的借款！');
        }

        $repay_info = $this->rpc->local("DealProjectRepayService\getProjectRepayInfo", array($project_id));

        $this->tpl->assign("prepay_show", $repay_info['prepay_show']);
        $this->tpl->assign("applied_prepay", $repay_info['applied_prepay']);
        $this->tpl->assign("overdue", $repay_info['overdue']);
        $this->tpl->assign("cannot_prepay", $repay_info['cannot_prepay']);
        $this->tpl->assign("loan_list", $repay_info['repay_list']);
        $this->tpl->assign("deal", $repay_info['deal']);
        $this->tpl->assign("total_repay_money", $repay_info['total_repay_money']);
        $this->template = "web/views/account/project_quickrefund.html";
    }
}
