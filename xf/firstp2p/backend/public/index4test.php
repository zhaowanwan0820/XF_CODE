<?php
require dirname(__FILE__).'/../../Common/Library/TestCoverage/TestCoverageClient.php';
$testCoverage = \NCFGroup\Common\Library\TestCoverage\TestCoverageClient::create('http://testcoverage.firstp2plocal.com:8097', realpath(__DIR__.'/../../'), "firstp2p");
$testCoverage->start();

include 'index_common.php';

$testCoverage->stop();
