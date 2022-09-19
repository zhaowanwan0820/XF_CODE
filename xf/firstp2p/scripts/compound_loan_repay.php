<?php
/**
 * 为通知贷增加本金的回款计划
 */
require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");
use core\dao\DealLoanRepayModel;
use core\dao\DealModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');

$sql = "select * from firstp2p_deal_load where deal_type = 1 and id not in (select deal_load_id from firstp2p_compound_redemption_apply)";
$list = $GLOBALS['db']->getAll($sql);

foreach ($list as $v) {
    $dlr = new DealLoanRepayModel;
    $row = $dlr->findBy("`deal_loan_id`='{$v['id']}'");
    if ($row) {
        continue;
    }

    $deal = DealModel::instance()->find($v['deal_id']);

    $dlr->deal_id = $v['deal_id'];
    $dlr->deal_repay_id = 0;
    $dlr->deal_loan_id = $v['id'];
    $dlr->loan_user_id = $v['user_id'];
    $dlr->borrow_user_id = $deal['user_id'];
    $dlr->status = 0;
    $dlr->create_time = get_gmtime();
    $dlr->update_time = get_gmtime();
    $dlr->time = 0;
    $dlr->money = $v['money'];
    $dlr->type = 8;
    $r = $dlr->insert();

    if ($r === false) {
        echo "插入回款本金失败: {$v['id']}\n";
    }

    $c = $dlr->count("`deal_loan_id`='{$v['id']}'");
    if ($c != 1) {
        echo "回款计划条数出现异常: {$v['id']}\n";
    }
}
