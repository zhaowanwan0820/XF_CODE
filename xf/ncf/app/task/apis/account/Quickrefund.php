<?php
/**
 * 还款详情
 * @date 2018年8月9日14:46:30
 */

namespace task\apis\account;

use libs\web\Form;
use libs\utils\Page;
use libs\utils\Logger;
use core\dao\deal\DealLoanTypeModel;
use core\enum\DealLoanTypeEnum;
use core\enum\UserAccountEnum;
use core\service\repay\DealRepayService;
use core\service\deal\DealService;
use core\service\account\AccountService;
use task\lib\ApiAction;

class Quickrefund extends ApiAction {

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));
        $deal_id = intval($params['dealId']); // 标的id
        $user_id = intval($params['userId']);
        // 检查参数
        if($deal_id <= 0 || $user_id <= 0){
            $this->json_data = array();
            return;
        }
        $dealRepayService = new DealRepayService();
        $deal_repay_info = $dealRepayService->getDealRepayInfo($deal_id, $user_id);
        if ($deal_repay_info === false) {
            $this->json_data = array();
            return;
        }
        $deal_loan_type_dao = new DealLoanTypeModel();
        $type_tag = $deal_loan_type_dao->getLoanTagByTypeId($deal_repay_info['deal']->type_id);
        $today = to_timespan(date('Y-m-d'));
        // 还款方式为消费分期和消费贷时,repayMode为1（代垫还款),其余为0
        $repayMode = 0;
        if(($type_tag == DealLoanTypeEnum::TYPE_XFFQ)||($type_tag == DealLoanTypeEnum::TYPE_XFD)){
            // 消费分期的标的使用代垫机构还款
            $repayMode = 1;
        }

        $interest_time =  $dealRepayService->getLastRepayTimeByDealId($deal_repay_info['deal']);
        $has_dt_stats = 0;
        $dealService = new DealService();
        // 打了智多鑫tag的并且在处于满标或者还款中的标的
        if(in_array($deal_repay_info['deal']['deal_status'],array(2,4)) && $dealService->isDealDT($deal_repay_info['deal']['id'])) {
            $has_dt_stats = 1;
        }
        $deal_repay_info['deal']['has_dt_stats'] = $has_dt_stats;

        // 借款人存管账户余额
        $borrowerMoney = AccountService::getAccountMoney($user_id,UserAccountEnum::ACCOUNT_FINANCE);
        // 如果是 掌众/消费分期/功夫贷 就不显示提前还款入口
        $prepay_show = (in_array($type_tag, array(DealLoanTypeEnum::TYPE_XFFQ, DealLoanTypeEnum::TYPE_ZHANGZHONG, DealLoanTypeEnum::TYPE_XJDGFD, DealLoanTypeEnum::TYPE_XSJK))  || $deal_repay_info['deal']->deal_type == 1 || $today >=$interest_time) ? false : true;

        $ret = array(
            "is_deal_zx" => false,
            "prepay_mode" => $repayMode,
            "prepay_show" => $prepay_show,
            "applied_prepay" => $deal_repay_info['applied_prepay'],
            "overdue" => $deal_repay_info['overdue'],
            "cannot_prepay" => $deal_repay_info['cannot_prepay'],
            "deal" => $deal_repay_info['deal'],
            "deal_ext" => $deal_repay_info['deal_ext'],
            "repay_list" => $deal_repay_info['repay_list'],
            "borrower_money" => $borrowerMoney['money'],
        );
        $this->json_data = $ret;
    }

}
