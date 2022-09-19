<?php
require(dirname(__FILE__) . '/../app/init.php');

use core\service\PtpTaskClient;
use libs\utils\Logger;
try {
    $taskService = new PtpTaskClient();
    $taskService->compensate();
}catch (\Exception $e) {
    echo $e->getMessage();
    Logger::info('compensate_missing_message:errMsg='.$e->getMessage());
}
