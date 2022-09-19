<?php
/**
 * 重试Task
 *
 * @author jingxu
 */
require_once dirname(__FILE__).'/../app/init.php';
$env = app_conf("ENV_FLAG");
if (in_array($env, array('test', 'producttest'))) {
    exec('/apps/php/bin/php /apps/nginx/htdocs/firstp2p/firstp2p/task/app/scripts/timedtask.php',$output);
    var_dump($output);
}elseif($env == 'dev'){
    exec('/apps/php/bin/php /home/dev/git/firstp2p/task/app/scripts/timedtask.php',$output);
    var_dump($output);
}else{
    header("http/1.1 404 Not Found");
    exit('File not found.');
}
?>
