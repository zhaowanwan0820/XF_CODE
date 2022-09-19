<?php
require(dirname(__FILE__) . '/../app/init.php');

FP::import("libs.libs.msgcenter");
FP::import("app.deal");
FP::import("libs.libs.user");

SiteApp::init(); //->run();

error_reporting(0);
set_time_limit(0);

use core\dao\DealPrepayModel;
use app\models\dao\DealLoanRepay;
use core\dao\FinanceQueueModel;
use core\dao\DealModel;
use core\dao\UserModel;

$deal_id = $argv[1];
if (!$deal_id) {
    echo "缺少参数";
    exit;
}

$deal_dao = new DealModel();
$model = new DealPrepayModel();
$prepay = $model->findBy("`deal_id`='{$deal_id}'");
$syncRemoteData = array();
$deal = get_deal($deal_id);

            //执行回款
            $deal_loan_repay_model = new \app\models\dao\DealLoanRepay();
            $deal_loan_repay_model->prepayDealLoan($prepay);

            // TODO finance 提前还款 | 已同步
            $deal_loads = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_load WHERE deal_id=".$prepay->deal_id.' and deal_parent_id!=0');

            $msgcenter = new Msgcenter();

            foreach($deal_loads as $k => $deal_load){
                //回款本金
                $principal = $deal_load['money'] * ($prepay->remain_principal / $deal['borrow_amount']);
                $rate = $deal['income_fee_rate'];//年化收益率
                //实际还款总金额
                $prepay_money = prepay_money($principal, $prepay->remain_days, $deal['loan_compensation_days'], $rate);
                //提前还款利息
                $prepay_interest = prepay_money_intrest($principal, $prepay->remain_days, $rate);

                $principal = $deal_dao->floorfix($principal);
                $prepay_money = $deal_dao->floorfix($prepay_money);
                $prepay_interest = $deal_dao->floorfix($prepay_interest);
                $prepay_compensation = $deal_dao->floorfix($prepay_money - $prepay_interest - $principal);

        // TODO finance  后台 提前还款  | 直接扣款，已经同步
            if (bccomp(bcadd($principal,$prepay_interest,2), '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'PREPAYDEAL|' . $deal_load['id'],
                    'payerId' => $prepay->user_id,
                    'receiverId' =>$deal_load['user_id'],
                    'repaymentAmount' => bcmul(bcadd($principal,$prepay_interest,4), 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $deal_id,
                );
            }

                \core\dao\DealLoanRepayModel::instance()->repairMoneyOnrepay($deal_load['id'], $principal, $deal_load['user_id']);

            if ( ($deal['id'] == 4802 && $deal_load['user_id'] == 6995) || ($deal['id'] == 4804 && $deal_load['user_id'] == 7159)) {
                echo "passed\n";
            } else {
                modify_account(array("money"=>$principal+$prepay_interest), $deal_load['user_id'], "提前还款",1,'编号'.$deal['id'].' '.$deal['name']);
            }
                if (bccomp($prepay_compensation, '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'PREPAYCOMPENSATION|' . $deal_load['id'],
                        'payerId' => $prepay->user_id,
                        'receiverId' =>$deal_load['user_id'],
                        'repaymentAmount' => bcmul($prepay_compensation, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $deal_id,
                    );
                }

            if ($prepay_compensation > 0) {
                modify_account(array("money"=>$prepay_compensation), $deal_load['user_id'], "提前还款补偿金",1,'编号'.$deal['id'].' '.$deal['name']);
            }

                $inrepay = array(
                    'deal_id' => $prepay->deal_id,
                    'user_id' => $deal_load['user_id'],
                    'repay_money' => $prepay_money,
                    'manage_money' => $prepay->prepay_money - $prepay_money,
                    'impose' => $prepay_compensation,
                    'principal' => $principal,
                    'interest' => $prepay_interest,
                    'true_repay_time' => get_gmtime(),
                );

                $GLOBALS['db']->autoExecute(DB_PREFIX."deal_inrepay_repay",$inrepay,"INSERT");

                $content = "您在".app_conf("SHOP_TITLE")."的投标“".$deal['name']."”发生提前还款，总额".number_format($prepay_money, 2)."元，其中提前还款本金".number_format($principal, 2)."元，提前还款利息".number_format($prepay_interest, 2)."元，提前还款补偿金".number_format($prepay_compensation, 2)."元。本次投标已回款完毕。";
                send_user_msg("", $content,0, $deal_load['user_id'], get_gmtime(), 0, 1, 9);

                $tmp_arr = array();
                if ($principal > 0) {
                    $tmp_arr[] = "提前还款" . format_price($principal);
                }
                if ($prepay_compensation > 0) {
                    $tmp_arr[] = "提前还款补偿金" . format_price($prepay_compensation);
                }
                if ($prepay_interest > 0) {
                    $tmp_arr[] = "提前还款利息" . format_price($prepay_interest);
                }

                $params = array(
                    "deal_name" => msubstr($deal['name'], 0, 8),
                    "money" => format_price($prepay_money),
                    "content" => implode("，", $tmp_arr),
                );
                $load_user = UserModel::instance()->find($deal_load['user_id']);
                $msgcenter->setMsg($load_user['mobile'], $load_user['id'], $params, 'TPL_SMS_LOAN_REPAY', '提前还款回款通知');
            }
            $msgcenter->save();

            // 提前还款数据同步
            FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
            //save_log('提前还款审核 id:'.$id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));

echo "success";
