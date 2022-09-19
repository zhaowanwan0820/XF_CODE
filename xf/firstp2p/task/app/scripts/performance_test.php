<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/11
 * Time: 10:45
 */

require_once __DIR__ . "/init.php";

$helperStr = "
    how to use:
        php performance_test.php --worker (a integer number) --runtime (a integer number)
        for examper:
            php performance_test.php --worker 10 --runtime 10
        it means 10 workers run 10 minutes.
";

$shortopts = "w:r::";
$longopts = array(
    "worker:",
    "runtime:",
);

$option = getopt($shortopts, $longopts);
$workerCount = intval($option['worker']);
$runtime = floatval($option['runtime']);
if ($workerCount <= 0 ) {
    exit($helperStr);
}

if ($runtime <= 0) {
    exit($helperStr);
}

$performanceTest = new \NCFGroup\Task\Instrument\PerformanceTest($workerCount, $runtime);
\NCFGroup\Task\Instrument\PerformanceTest::test();
\NCFGroup\Task\Instrument\PerformanceTest::analyze();