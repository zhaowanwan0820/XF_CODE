<?php
require_once dirname(__FILE__).'/../init.php';

function p2p_domq_invest_notify($job)
{
    NCFGroup\Task\Gearman\WxGearManWorker::domqBase($job);
}
