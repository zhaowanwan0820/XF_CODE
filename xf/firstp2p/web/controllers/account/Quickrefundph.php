<?php
/**
 * Quickrefund.php
 * @date 2018年8月9日17:18:27
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\DealLoanTypeModel;
use core\service\ncfph\AccountService;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Quickrefundph extends BaseAction {
    public function init() {
        if(!$this->check_login()) {
            return false;
        }
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $id = $this->form->data['id'];

        $accountServcie = new AccountService();
        $result = $accountServcie->getQuickRefund($GLOBALS['user_info']['id'],$id);
        $this->tpl->assign("is_deal_zx", $result['is_deal_zx']);
        $this->tpl->assign("is_ph", true);
        $this->tpl->assign("prepay_mode",  $result['prepay_mode']);
        $this->tpl->assign("prepay_show",  $result['prepay_show']);
        $this->tpl->assign("applied_prepay", $result['applied_prepay']);
        $this->tpl->assign("overdue", $result['overdue']);
        $this->tpl->assign("cannot_prepay", $result['cannot_prepay']);
        $this->tpl->assign("deal", $result['deal']);
        $this->tpl->assign("deal_ext", $result['deal_ext']);
        $this->tpl->assign("loan_list", $result['repay_list']);
        $this->tpl->assign("borrower_money", $result['borrower_money']);
        $this->tpl->assign("inc_file","web/views/account/quickrefund.html");
        $this->template = "web/views/account/quickrefund.html";
    }
}
