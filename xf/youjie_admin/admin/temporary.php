<?php

define('IN_ECS', true);
$get_keyword = trim($_GET['hashid']); // 获取关键字
list($a,$b,$c) = explode('.',$_SERVER['SERVER_NAME']);
setcookie('expandDebtUser',$get_keyword,time() + 60 * 60 * 24 * 7,'/',$b.'.'.$c);
header("location:".urldecode($_GET['url']));
?>