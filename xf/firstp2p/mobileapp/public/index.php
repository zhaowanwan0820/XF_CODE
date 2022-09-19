<?php
define('APP', 'mobileapp');
require(dirname(__FILE__).'/../../app/init.php');
use libs\web\App;
$config = require(dirname(__FILE__).'/../../conf/components.conf.php');

App::init($config)->run();
?>
