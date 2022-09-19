<?php
$rootPath = realpath(dirname(__FILE__).'/../../../').DIRECTORY_SEPARATOR;
require $rootPath . 'Common/Library/TestCoverage/TestCoverageClient.php';
$testCoverage = \NCFGroup\Common\Library\TestCoverage\TestCoverageClient::create('http://testcoverage.firstp2plocal.com:8097', $rootPath, "ncfph");
$testCoverage->start();

include 'm_common.php';

$testCoverage->stop();
