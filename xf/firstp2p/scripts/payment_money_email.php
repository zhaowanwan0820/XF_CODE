<?php
/**
 * 每日0点统计用户总余额
 *
 * @author 张若识<zhangruoshi@ucfgroup.com>
 */
set_time_limit(0);
require(dirname(__FILE__) . '/../app/init.php');
require(dirname(__FILE__) . '/../system/utils/es_mail.php');
define('ADMIN_ROOT', true);

$body = "截止" . format_date(time()) . "用户账户总余额统计";
$body.= "<style>.table-c table{border-right:1px solid #ccc;border-bottom:1px solid #ccc}.table-c table td{border-left:1px solid #ccc;border-top:1px solid #ccc}</style>";
$body.= "<div class='table-c'><table border='0' cellspacing='0' cellpadding='0'><tr><td>用户组</td><td>正余额</td><td>负余额</td><td>正冻结</td><td>负冻结</td><td>合计</td></tr>";

$sql = "select id,name from firstp2p_user_group";
$res = $GLOBALS['db']->get_slave()->getAll($sql);
$res[] = array('id'=>'0','name'=>'未分组');
$res[] = array('id'=>'999','name'=>'总计');

foreach ($res as $row){
    $body.="<tr><td>".$row['name']."</td>";
    $sql_group = '';
    if($row['id']!='999'){
        $sql_group = ' and group_id='.$row['id'];
    }
    //余额为正的总数
    $sql = 'select sum(money) as total from firstp2p_user where money>0'.$sql_group;

    $total_money_positive = $GLOBALS['db']->get_slave()->getOne($sql);
    if(!$total_money_positive) $total_money_positive=0;
    $body.="<td>".$total_money_positive."</td>";

    //余额为负的总数
    $sql = 'select sum(money) as total from firstp2p_user where money<0'.$sql_group;
    $total_money_negative = $GLOBALS['db']->get_slave()->getOne($sql);
    if(!$total_money_negative) $total_money_negative=0;
    $body.="<td>".$total_money_negative."</td>";

    //冻结余额为正的总数
    $sql = 'select sum(lock_money) as total from firstp2p_user where lock_money>0'.$sql_group;
    $total_lock_money_positive = $GLOBALS['db']->get_slave()->getOne($sql);
    if(!$total_lock_money_positive) $total_lock_money_positive = 0;
    $body.="<td>".$total_lock_money_positive."</td>";

    //冻结资金为负的总数
    $sql = 'select sum(lock_money) as total from firstp2p_user where lock_money<0'.$sql_group;
    $total_lock_money_negative = $GLOBALS['db']->get_slave()->getOne($sql);
    if(!$total_lock_money_negative) $total_lock_money_negative=0;
    $body.="<td>".$total_lock_money_negative."</td>";

    //用户总资金余额+冻结
    $sql = 'select sum(money)+sum(lock_money) as total from firstp2p_user where 1=1'.$sql_group;
    $total_money = $GLOBALS['db']->get_slave()->getOne($sql);

    if(!$total_money) $total_money=0;
    $body.="<td>".$total_money."</td>";
    $body.="</tr>";
}

$body.="</table></div>";

//发送邮件
$title = "firstp2p用户总余额";
$msgcenter = new msgcenter();
$msgcenter->setMsg(app_conf('PAYMENT_USER_TOTAL_MAIL'), 0, $body, false, $title);
$r = $msgcenter->save();
