<?php
require_once dirname(__FILE__).'/../app/init.php';
$env = app_conf("ENV_FLAG");

$file = $_GET['file'];
if (!$file) {
    exit('param error');
}

$id = $_GET['id'];

if (in_array($env, array('dev', 'test', 'producttest')) && file_exists(dirname(__FILE__).'/../scripts/'.$file.'.php')) {
    if ($id) {
        system('/apps/product/php/bin/php ' . dirname(__FILE__).'/../scripts/'.$file.'.php '.$id);
    } else {
        require_once dirname(__FILE__).'/../scripts/'.$file.'.php';
    }
}else{
    exit('File not found.');
}
?>
