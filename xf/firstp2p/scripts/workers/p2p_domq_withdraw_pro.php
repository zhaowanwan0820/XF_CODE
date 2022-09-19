<?php
require_once dirname(__FILE__).'/../init.php';

function p2p_domq_withdraw_pro($job)
{
    NCFGroup\Task\Gearman\WxGearManWorker::domqBase($job);
}
