<?php
require_once dirname(__FILE__).'/../app/init.php';
$env = app_conf("ENV_FLAG");
if (in_array($env, array('dev', 'test', 'producttest'))) {
    require_once dirname(__FILE__).'/../scripts/jobs_worker.php';
}else{
    exit('File not found.');
}
?>