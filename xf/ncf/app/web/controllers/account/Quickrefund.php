<?php
/**
 * Quickrefund.php
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace web\controllers\account;

use core\enum\AccountEnum;
use core\enum\UserAccountEnum;
use core\service\account\AccountService;
use libs\web\Form;
use libs\utils\Page;
use web\controllers\BaseAction;
use core\dao\deal\DealLoanTypeModel;
use core\enum\DealLoanTypeEnum;
use core\service\repay\DealRepayService;
use core\service\deal\DealService;
use core\service\repay\DealPartRepayService;


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
        $dealRepayService = new DealRepayService();
        $deal_repay_info = $dealRepayService->getDealRepayInfo($id, $GLOBALS['user_info']['id']);
        if ($deal_repay_info === false) {
            return $this->show_error('操作失败！');
        }
        $deal_loan_type_dao = new DealLoanTypeModel();
        $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($deal_repay_info['deal']->type_id);
        $today = to_timespan(date('Y-m-d'));
        //还款方式为消费分期和消费贷时,repayMode为1（代垫还款),其余为0
        $repayMode = 0;
        if(($type_tag == DealLoanTypeEnum::TYPE_XFFQ)||($type_tag == DealLoanTypeEnum::TYPE_XFD)){
            //消费分期的标的使用代垫机构还款
            $repayMode = 1;
        }

        $interest_time =  $dealRepayService->getLastRepayTimeByDealId($deal_repay_info['deal']);
        $has_dt_stats = 0;
        $dealService = new DealService();
        //打了智多鑫tag的并且在处于满标或者还款中的标的
        if(in_array($deal_repay_info['deal']['deal_status'],array(2,4)) && $dealService->isDealDT($deal_repay_info['deal']['id'])) {
            $has_dt_stats = 1;
        }
        $deal_repay_info['deal']['has_dt_stats'] = $has_dt_stats;
        // 借款人存管账户余额
        $borrowerMoney = AccountService::getAccountMoney($GLOBALS['user_info']['id'],UserAccountEnum::ACCOUNT_FINANCE);
        // 如果是 掌众/消费分期/功夫贷 就不显示提前还款入口
        $prepay_show = (in_array($type_tag, array(DealLoanTypeEnum::TYPE_XFFQ, DealLoanTypeEnum::TYPE_ZHANGZHONG, DealLoanTypeEnum::TYPE_XJDGFD, DealLoanTypeEnum::TYPE_XSJK))  || $deal_repay_info['deal']->deal_type == 1 || $today >=$interest_time) ? false : true;
        $this->tpl->assign("prepay_mode", $repayMode);
        $this->tpl->assign("prepay_show", $prepay_show);
        $this->tpl->assign("applied_prepay", $deal_repay_info['applied_prepay']);
        $this->tpl->assign("overdue", $deal_repay_info['overdue']);
        $this->tpl->assign("cannot_prepay", $deal_repay_info['cannot_prepay']);
        $this->tpl->assign("deal", $deal_repay_info['deal']);
        $this->tpl->assign("deal_ext", $deal_repay_info['deal_ext']);

        $loanList = [];
        foreach ($deal_repay_info['repay_list'] as $line) {
            $partRepayInfo = DealPartRepayService::getPartRepayMoney($line, 0);
            $line['month_repay_money_principal'] = $partRepayInfo['needToRepayPrincipal'] ?: 0;
            $line['month_repay_money_interest'] = $partRepayInfo['needToRepayInterest'] ?: 0;

            $line['month_has_repay_money_all'] = $line['status'] != 0 ? number_format($line['repay_money'], 2) : ($line['part_repay_money'] > 0 ? number_format($line['part_repay_money'], 2) : 0);
            $line['month_need_all_repay_money'] = $line['status'] == 0 ? number_format($partRepayInfo['needToRepayTotal'], 2) : 0;
            $loanList[] = $line;
        }

        $this->tpl->assign("loan_list", $loanList);
        $this->tpl->assign("borrower_money", $borrowerMoney['money']);
        $this->template = "web/views/account/quickrefund.html";

    }
}
