<?php
require_once dirname(__FILE__).'/../init.php';

function p2p_domq_fail($job)
{
    NCFGroup\Task\Gearman\WxGearManWorker::domq4Fail($job);
}
