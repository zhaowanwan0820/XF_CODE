<?php
/**
 * Created by PhpStorm.
 * User: godtou
 * Date: 2018/8/29
 * Time: 20:30
 */
$rootPath = realpath(dirname(__FILE__).'/../../../').DIRECTORY_SEPARATOR;
require $rootPath . 'Common/Library/TestCoverage/TestCoverageClient.php';
$testCoverage = \NCFGroup\Common\Library\TestCoverage\TestCoverageClient::create('http://testcoverage.firstp2plocal.com:8097', $rootPath, "ncfph");
$testCoverage->start();

include 'index_common.php';

$testCoverage->stop();