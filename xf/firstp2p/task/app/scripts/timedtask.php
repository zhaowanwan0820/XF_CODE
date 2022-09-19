<?php
require __DIR__.'/init.php';
/**
 * 重试Task
 *
 * @author jingxu
 */
try {
    NCFGroup\Task\Instrument\TimedTask::execute(true);
} catch (Exception $e) {
    NCFGroup\Common\Library\Logger::error(" timedtask Exception:{$e->getMessage()}");
}
