<?php

/**
 * Async Server management
 *
 * run (php run.php help) for more information.
 * @file itzlib/plugins/swoole/bootstrap.php
 */

define('ASYNC_DIR', __DIR__);
require(dirname(dirname(dirname(dirname(ASYNC_DIR)))) . "/itzlib/plugins/swoole/bootstrap.php");
