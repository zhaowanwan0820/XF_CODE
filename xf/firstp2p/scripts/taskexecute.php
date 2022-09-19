<?php
/**
 * 任务的消费
 */
require_once dirname(__FILE__).'/../app/init.php';

libs\event\TaskExecutor::launch();
