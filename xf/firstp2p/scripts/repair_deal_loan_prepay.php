<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

error_reporting(0);

define("SQL_DEBUG", true);

use core\dao\DealLoanRepayModel;
use core\dao\DealLoadModel;
use core\dao\DealModel;

$deal_loan_repay_model = new DealLoanRepayModel();
$loan_repay_list = $deal_loan_repay_model->findAll("`borrow_user_id`='0'");

$deal_load_model = new DealLoadModel();
$deal_model = new DealModel();

foreach ($loan_repay_list as $loan_repay) {
    $deal_loan_id = $loan_repay['deal_loan_id'];
    $deal_load = $deal_load_model->find($deal_loan_id);

    $deal_id = $deal_load['deal_id'];
    $deal = $deal_model->find($deal_id);
    $borrow_user_id = $deal['user_id'];

    $loan_repay->deal_id = $deal_id;
    $loan_repay->borrow_user_id = $borrow_user_id;
    $loan_repay->save();
}
