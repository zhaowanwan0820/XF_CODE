<?php
/**
 * Quickrefund.php
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\DealLoanTypeModel;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Quickrefund extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
    	$this->form = new Form();
    	$this->form->rules = array(
            'id' => array("filter" => "int"),
    	);
    	$this->form->validate();
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $deal_repay_info = $this->rpc->local("DealRepayService\getDealRepayInfo", array($id, $GLOBALS['user_info']['id']));
        if ($deal_repay_info === false) {
            return $this->show_error('操作失败！');
        }
        $isDealZx = $this->rpc->local("DealService\isDealEx", array($deal_repay_info['deal']['deal_type']));
        $deal_loan_type_dao = new DealLoanTypeModel();
        $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($deal_repay_info['deal']->type_id);
        $today = to_timespan(date('Y-m-d'));
        //还款方式为消费分期和消费贷时,repayMode为1（代垫还款),其余为0
        $repayMode = 0;
        if(($type_tag == DealLoanTypeModel::TYPE_XFFQ)||($type_tag == DealLoanTypeModel::TYPE_XFD)){
            //消费分期的标的使用代垫机构还款
            $repayMode = 1;
        }


        $dps = new \core\service\DealRepayService();
        $interest_time =  $dps->getLastRepayTimeByDealId($deal_repay_info['deal']);

        $has_dt_stats = 0;
        $dealService = new \core\service\DealService();
        //打了智多鑫tag的并且在处于满标或者还款中的标的
        if(in_array($deal_repay_info['deal']['deal_status'],array(2,4)) && $dealService->isDealDT($deal_repay_info['deal']['id'])) {
            $has_dt_stats = 1;
        }
        $deal_repay_info['deal']['has_dt_stats'] = $has_dt_stats;

        // 如果是 掌众/消费分期/功夫贷 就不显示提前还款入口
        $prepay_show = (in_array($type_tag, array(DealLoanTypeModel::TYPE_XFFQ, DealLoanTypeModel::TYPE_ZHANGZHONG, DealLoanTypeModel::TYPE_XJDGFD, DealLoanTypeModel::TYPE_XSJK))  || $deal_repay_info['deal']->deal_type == 1 || $today >=$interest_time) ? false : true;
        $this->tpl->assign("is_deal_zx", $isDealZx);
        $this->tpl->assign("prepay_mode", $repayMode);
        $this->tpl->assign("prepay_show", $prepay_show);
        $this->tpl->assign("applied_prepay", $deal_repay_info['applied_prepay']);
        $this->tpl->assign("overdue", $deal_repay_info['overdue']);
        $this->tpl->assign("cannot_prepay", $deal_repay_info['cannot_prepay']);
        $this->tpl->assign("deal", $deal_repay_info['deal']);
        $this->tpl->assign("deal_ext", $deal_repay_info['deal_ext']);
        $this->tpl->assign("loan_list", $deal_repay_info['repay_list']);
//    	$this->tpl->assign("inc_file","web/views/account/quickrefund.html");
    	$this->template = "web/views/account/quickrefund.html";
    }
}
