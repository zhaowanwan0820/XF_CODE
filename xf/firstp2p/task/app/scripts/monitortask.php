<?php

use NCFGroup\Common\Library\Date\XDateTime;
/**
 * 监控gearman
 *
 * @author jingxu
 */
require __DIR__.'/init.php';

//jobserver live 与 worker数监控
NCFGroup\Task\Gearman\GearManJobServer::tryAlert();

//run now死任务监控
$deadTaskCnt = NCFGroup\Task\Models\Task::getRunNowTaskCnt(XDateTime::now()->addMinute(-50));
if ($deadTaskCnt) {
    $hostName = gethostname();
    $emailSvc = new NCFGroup\Task\Services\EmailService();
    $emailSvc->sendSync(getDI()->get('config')->taskGearman->alertMails->toArray(), "死任务数{$deadTaskCnt}, hostname:{$hostName}", "死任务数{$deadTaskCnt}, hostname:{$hostName}");
}
