<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

use core\dao\DealModel;
use core\dao\DealExtModel;

$deals = DealModel::instance()->findAll();
foreach ($deals as $deal) {
    $deal_ext = DealExtModel::instance()->findBy("`deal_id`='{$deal['id']}'");
    if ($deal_ext && $deal_ext->income_subsidy_rate) {
        $deal->income_total_rate = $deal->income_fee_rate + $deal_ext->income_subsidy_rate;
    } else {
        $deal->income_total_rate = $deal->income_fee_rate;
    }
    $deal->save();
}
