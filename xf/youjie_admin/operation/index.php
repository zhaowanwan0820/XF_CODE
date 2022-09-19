<?php
define('IN_ECS', true);
require_once('../includes/init.php');


/*静态活动页面*/
if (isset($_GET['url'])) {

    $headers = [
        'Cookie :'.$_SERVER['HTTP_COOKIE'],
    ];

    $data = curl_request($_GET['url'], 'GET', '', $headers, false);
    $smarty->assign('data', $data);
    $smarty->display('static.htm');

} else {
    die('Hacking attempt');
}





