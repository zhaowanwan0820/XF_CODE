<?php
require_once dirname(__FILE__).'/../init.php';

function p2p_domq_reserve_process_deal($job)
{
    NCFGroup\Task\Gearman\WxGearManWorker::domqBase($job);
}
