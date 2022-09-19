<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/12/11
 * Time: 11:56
 */

require __DIR__.'/init.php';

try {
    NCFGroup\Task\Instrument\TimedRedisTask::execute();
} catch (Exception $e) {
    NCFGroup\Common\Library\Logger::error(" timedRedisTask Exception:{$e->getMessage()}");
}