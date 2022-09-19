<?php
/**
 * #逾期数据导出
 * @author wangjiantong  2015-08-07
 */

require_once dirname(__FILE__) . '/../app/init.php';
require_once dirname(__FILE__) . '/../libs/common/app.php';
require_once dirname(__FILE__) . '/../libs/common/functions.php';
require_once dirname(__FILE__) . '/../system/libs/msgcenter.php';

use core\dao\DealLoanRepayModel;
use core\service\DealCompoundService;

set_time_limit(0);
ini_set('memory_limit', '1024M');

$title = "编号,借款标题,借款金额,年化借款利率,借款期限, 还款方式,赎回周期(天),借款人ID-用户名-姓名-手机号,账户余额,今日应还";
$deal_loan_repay = DealLoanRepayModel::instance()->getLGLDelayList(0, 10000);

$deal_compound_service = new DealCompoundService();

$record_content = $title.'<br />';

foreach ($deal_loan_repay as $row) {
    $row['list_name'] = "{$row['borrow_user_id']} - " . $row['user_name'] . " - " . $row['real_name'] . " - " . $row['mobile'];
    $row['repay_time'] = getRepayTime($row['repay_time'], $row['loantype']);
    $row['loan_type'] = get_loantype($row['loantype']);
    $row['borrow_amount'] = str_replace(',', '', format_price($row['borrow_amount'], false));
    $row['repay_date'] = date('Y-m-d', get_gmtime());
    $deal_compound = $deal_compound_service->getDealCompound($row['deal_id']);
    $row['redemption_period'] = $deal_compound['redemption_period'];

    $record_line = "{$row['deal_id']},{$row['name']},{$row['borrow_amount']},{$row['rate']}%,{$row['repay_time']},{$row['loan_type']},{$row['redemption_period']},{$row['list_name']},{$row['money']},{$row['need_repay']}";

    $record_content .= $record_line.'<br />';
}

//发送邮件
FP::import("libs.common.dict");
$email_arr = dict::get("DEAL_YUQI_EMAIL");

if ($email_arr) {
    $title = sprintf("网信理财 %s 逾期数据概况", date("Y年m月d日", time()));
    $msgcenter = new msgcenter();
    foreach ($email_arr as $email) {
        $msg_count = $msgcenter->setMsg($email, 0, $record_content, false, $title);
    }
    $msg_save = $msgcenter->save();
    echo 'success';
}

