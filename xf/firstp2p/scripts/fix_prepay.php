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
    echo "param empty";
    exit;
}

$prepay = DealPrepayModel::instance()->findBy("`deal_id`='{$deal_id}'");
$deal = DealModel::instance()->find($deal_id);
$id = $prepay->id;
$deal_user_info = get_user("mobile", $prepay->user_id);
$mobile = $deal_user_info['mobile'];
$data = array(
    'name' => $deal['name'],
    'id' => $deal['id'],
    'tel' => app_conf('SHOP_TEL'),
);

$GLOBALS['db']->startTrans();

try {
            // 修改回款计划表状态
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_repay", array('status' => 4, 'true_repay_time'=>$prepay->prepay_time), "UPDATE", "`deal_id`='{$prepay->deal_id}' AND `status`='0'");
            if ($res === false) {
                throw new \Exception("update deal_repay error");
            }

            $user_dao = new \app\models\dao\User();
            $user_dao = $user_dao->find($prepay->user_id);

            $deal_dao = new \app\models\dao\Deal();

            //记录日志
            $log_info = array(
                'lock_money'=>  -floatval($prepay->prepay_money),
                'log_info' => "提前还款申请通过",
                'log_time' => get_gmtime(),
                'user_id' => $prepay->user_id,
               // 'log_admin_id'=>$adm_info['adm_id'],
                'log_admin_id'=>0,
                'note'=>"编号".$data['id'].' '.$data[name],
                'remaining_money' => $user_dao->money,
                'remaining_total_money' => $user_dao->money + $user_dao->lock_money,
            );
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."user_log",$log_info,"INSERT");
            if ($res === false) {
                throw new \Exception("insert user_log error");
            }
            $content = "尊敬的客户，“".$data[name]."”的提前还款申请已通过审核，本次借款已全部还清。";
            send_user_msg("",$content,0,$prepay->user_id, get_gmtime(),0,1,8);
            $Msgcenter = new Msgcenter();
            $Msgcenter->setMsg($mobile,$prepay->user_id, $data, 'TPL_SMS_PREPAY_PASS');
            $Msgcenter->save();

            $user = new \core\dao\UserModel();

            $syncRemoteData = array();

            // JIRA#1108 还款时收取服务费
            // 手续费
            if ($prepay->loan_fee) {
                $user = $user->find(app_conf('DEAL_CONSULT_FEE_USER_ID'));
                $res = $user->changeMoney($prepay->loan_fee, '手续费', "编号".$deal['id'].' '.$deal['name']);
                if ($res === false) {
                    throw new \Exception("change money error");
                }
                if (bccomp($prepay->loan_fee, '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'LOAN_FEE|' . $deal['id'],
                        'payerId' => $prepay->user_id,
                        'receiverId' => app_conf('DEAL_CONSULT_FEE_USER_ID'),
                        'repaymentAmount' => bcmul($prepay->loan_fee, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 3,
                        'batchId' => '',
                    );
                }
            }
            // 咨询费
            if ($prepay->consult_fee) {
                $advisory_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
                $consult_user_id = $advisory_info['user_id']; // 咨询机构账户
                $user = $user->find($consult_user_id);
                $res = $user->changeMoney($prepay->consult_fee, '咨询费', "编号".$deal['id'].' '.$deal['name']);
                if ($res === false) {
                    throw new \Exception("change money error");
                }
                if (bccomp($prepay->consult_fee, '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'CONSULT_FEE' . $deal['id'],
                        'payerId' => $prepay->user_id,
                        'receiverId' => $consult_user_id,
                        'repaymentAmount' => bcmul($prepay->consult_fee, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 3,
                        'batchId' => '',
                    );
                }
            }
            // 担保费
            if ($prepay->guarantee_fee) {
                $agency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 咨询机构
                $guarantee_user_id = $agency_info['user_id']; // 担保机构账户
                $user = $user->find($guarantee_user_id);
                $res = $user->changeMoney($prepay->guarantee_fee, '担保费', "编号".$deal['id'].' '.$deal['name']);
                if ($res === false) {
                    throw new \Exception("change money error");
                }
                if (bccomp($prepay->guarantee_fee, '0.00',2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'GUARANTEE_FEE|' . $deal['id'],
                        'payerId' => $prepay->user_id,
                        'receiverId' => $guarantee_user_id,
                        'repaymentAmount' => bcmul($prepay->guarantee_fee, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 3,
                        'batchId' => '',
                    );
                }
            }

            FP::import("libs.libs.user");
            //执行回款
            $deal_loan_repay_model = new \app\models\dao\DealLoanRepay();
            $res = $deal_loan_repay_model->prepayDealLoan($prepay);
            if ($res === false) {
                throw new \Exception("prepay error");
            }

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
                    'batchId' => $deal_load['deal_id'],
                );
            }

                \core\dao\DealLoanRepayModel::instance()->repairMoneyOnrepay($deal_load['id'], $principal, $deal_load['user_id']);
                modify_account(array("money"=>$principal+$prepay_interest), $deal_load['user_id'], "提前还款",1,'编号'.$deal['id'].' '.$deal['name']);
        // TODO finance 后台 提前还款补偿金 | 直接扣款 已经同步
                if (bccomp($prepay_compensation, '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'PREPAYCOMPENSATION|' . $deal_load['id'],
                        'payerId' => $prepay->user_id,
                        'receiverId' =>$deal_load['user_id'],
                        'repaymentAmount' => bcmul($prepay_compensation, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $deal_load['deal_id'],
                    );
                }

                modify_account(array("money"=>$prepay_compensation), $deal_load['user_id'], "提前还款补偿金",1,'编号'.$deal['id'].' '.$deal['name']);

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

                $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_inrepay_repay",$inrepay,"INSERT");
                if ($res === false) {
                    throw new \Exception("insert inrepay error");
                }

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
            $res = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
            if ($res === false) {
                throw new \Exception("Finance push error");
            }
            //save_log('提前还款审核 id:'.$id,C('SUCCESS'), '', '', C('SAVE_LOG_FILE'));


    $GLOBALS['db']->commit();

} catch (\Exception $e) {
    $GLOBALS['db']->rollback();
    echo "fail\t".$e->getMessage();
    exit;
}

echo "done";
