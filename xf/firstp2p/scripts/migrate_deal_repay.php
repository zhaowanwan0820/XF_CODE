<?php
require(dirname(__FILE__) . '/../app/init.php');

SiteApp::init(); //->run();

define("SQL_DEBUG", true);

use app\models\dao\Deal;

$deal_id = intval($argv[1]);
if (!$deal_id) {
    die('error');
}
$deal = Deal::instance()->find($deal_id);
$deal->createDealRepayList();
