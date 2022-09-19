<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');


if ($_REQUEST['act'] == 'board_list')
{
    $smarty->display('goods_board_list.htm');
}

?>
