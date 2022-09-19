<?php
require(dirname(__FILE__) . '/../app/init.php');
error_reporting(E_ERROR);
ini_set('display_errors', 1);
use core\tmevent\passport\UpdateIdentityEvent;
use core\service\PassportService;
use core\dao\WangxinPassportModel;
$passportService = new PassportService();
$oldMobile = $argv[1];
$newMobile = $argv[2];
try {
    $passportInfo = WangxinPassportModel::instance()->getPassportByMobile($oldMobile);
    if (empty($passportInfo)) {
        die('该用户没有创建通行证' . PHP_EOL);
    }
    $event = new UpdateIdentityEvent($passportInfo['ppid'], $oldMobile, $newMobile);
    $event->commit();
} catch (\Exception $e) {
    die($e->getMessage() . PHP_EOL);
}
