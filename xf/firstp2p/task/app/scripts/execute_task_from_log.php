<?php
include __DIR__.'/../Instrument/EnvHandler.php';

use NCFGroup\Task\Instrument\EnvHandler;
EnvHandler::requireInit();

$serializeLogs = file(__DIR__.'/testlog.txt');
foreach ($serializeLogs as $serializeLog) {
    $logs = explode('|', $serializeLog);
    var_dump(unserialize(base64_decode($logs[1])));
}

