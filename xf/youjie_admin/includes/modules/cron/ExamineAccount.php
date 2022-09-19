<?php
ini_set('max_execution_time', 0);
ini_set ('memory_limit', '256M');
/**
 * 用户账户对账
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$cron_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/cron/ExamineAccount.php';
if (file_exists($cron_lang))
{
    global $_LANG;

    include_once($cron_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'ExamineAccount_desc';

    /* 作者 */
    $modules[$i]['author']  = 'admin';

    /* 网址 */
    $modules[$i]['website'] = '';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.1';

    /* 配置信息 */
    $modules[$i]['config']  = array();
    return;
}
    return;
    file_put_contents(ROOT_PATH.'temp/common/common.log',"\r\n".date('Y-m-d H:i:s',time())."启动脚本",FILE_APPEND);
    //用户兑换金额
    $debtOrderSql = "SELECT user_id,sum(account) as account from " . $GLOBALS['ecs']->table('debt_order') . " WHERE status = 2   GROUP BY user_id";
    $userDebtData = $GLOBALS['db']->getAll($debtOrderSql);
    $userDebtData = array_column($userDebtData,'account','user_id');

    //查询change_type in(96,97)
    $rebateSql = "select user_id, sum(user_money) as user_money from " . $GLOBALS['ecs']->table('account_log') . " where change_type in(96,97) group by user_id";
    $rebateData = $GLOBALS['db']->getAll($rebateSql);
    $rebateData = array_column($rebateData,'user_money','user_id');

    //用户余额
    $userDataSql =  'select  user_id, user_money from ' . $GLOBALS['ecs']->table('users') . ' GROUP BY user_id';
    $userData =   $GLOBALS['db']->getAll($userDataSql);

    //用户订单消费
    $userOrderSql =  'select  user_id, sum(surplus) as surplus_amount from ' . $GLOBALS['ecs']->table('order_info') . ' where pay_status = 2 GROUP BY user_id';
    $userOrderData =   $GLOBALS['db']->getAll($userOrderSql);
    $userOrderData =   array_column($userOrderData,'surplus_amount','user_id');

    //完善用户数组
    foreach ($userData as $k => $v){
       if(isset($userOrderData[$v['user_id']])){
           $userData[$k]['surplus_amount'] = $userOrderData[$v['user_id']];
       }else{
           $userData[$k]['surplus_amount'] = 0;
       }
       if($rebateData[$v['user_id']] > 0){
           $userData[$k]['user_money'] = $userData[$k]['user_money'] - $rebateData[$v['user_id']];
       }else{
           $userData[$k]['user_money'] = $userData[$k]['user_money'] + $rebateData[$v['user_id']];
       }
    }

    file_put_contents(ROOT_PATH.'temp/common/common.log',"\r\n".date('Y-m-d H:i:s',time())."已获取数据，开始比对......",FILE_APPEND);

    //比对数据
    $success = $fail = $few = array();
    $num = 0;
    foreach ($userData as $k => $v) {
        if ($userDebtData[$v['user_id']]) {
            $user_money = bcadd($v['user_money'], $v['surplus_amount'], 2);
            if ($user_money == $userDebtData[$v['user_id']]) {
                $success[] = $v['user_id'];
            } else {
                $arr = [
                    'user_id'            => $v['user_id'],                   //用户id
                    'user_money'         => $v['user_money'],                //用户余额
                    'surplus_amount'     => $v['surplus_amount'],            //消费权益币
                    'user_money_surplus' => $user_money,                     //余额+消费
                    'debt_amount'        => $userDebtData[$v['user_id']],   //用户兑换金额
                    'message'            => 'Abnormal amounts'
                ];
                $fail[] = $arr;
                file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . date('Y-m-d H:i:s', time()) . json_encode($arr), FILE_APPEND);
            }
        } else {
            if($v['user_money'] > 0 || $v['surplus_amount'] > 0){
                $arr = [
                    'user_id'        => $v['user_id'],             //用户id
                    'user_money'     => $v['user_money'],          //用户余额
                    'surplus_amount' => $v['surplus_amount'],      //消费权益币
                    'message'        => 'The user does not have a conversion record'
                ];
                $few[] = $arr;
                file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . date('Y-m-d H:i:s', time()) . json_encode($arr), FILE_APPEND);
            }else{
                $success[] = $v['user_id'];
            }
        }
        $num++;
        unset($userDebtData[$v['user_id']]);
    }
//方便查看结果
//    $array = ['用户id','用户余额','消费权益币','余额+消费','兑换金额'];
//    array_unshift($fail,$array);
//    $new_file = ROOT_PATH.'temp/common/hh.csv';
//    $fp = @fopen($new_file, 'w');
//    if(!$fp){
//        echo "打开文件失败：{$new_file}\n";
//    }
//    foreach ($fail as $line){
//        print_r($line);
//        $length = fputcsv($fp, $line);
//        if(!$length){
//            echo "写入文件失败\n";
//        }
//    }
//    @fclose($fp);

    if(!empty($userDebtData)){
        file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . json_encode($userDebtData), FILE_APPEND);
    }
    $message =  date('Y-m-d', time())."运行结果：对账".$num."次,成功：".count($success)."次，失败：".count($fail)."次，未产生兑换发生消费：".count($few)."次！兑换记录未发生比对".count($userDebtData)."次！";
    file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . $message, FILE_APPEND);



    $mailcontent = '';
    if(!empty($fail)){
        $mailcontent .= "<h1>失败数据<h1>";
        $mailcontent .= "<table border='1'><tr>" .
                        "<td>用户id</td>".
                        "<td>用户余额</td>".
                        "<td>消费积分</td>".
                        "<td>余额+消费</td>".
                        "<td>用户兑换金额</td>".
                        "</tr>";
        foreach ($fail as $k => $v){
            $mailcontent .= "<tr>" .
                "<td>".$v['user_id']."</td>".
                "<td>".$v['user_money']."</td>".
                "<td>".$v['surplus_amount']."</td>".
                "<td>".$v['user_money_surplus']."</td>".
                "<td>".$v['debt_amount']."</td>".
                "</tr>";
        }
        $mailcontent .= "</table>";
    }

    if(!empty($few)){
        $mailcontent .= "<h1>未产生兑换发生消费数据<h1>";
        $mailcontent .= "<table border='1'><tr>" .
            "<td>用户id</td>".
            "<td>用户余额</td>".
            "<td>消费积分</td>".
            "</tr>";
        foreach ($few as $k => $v){
            $mailcontent .= "<tr>" .
                "<td>".$v['user_id']."</td>".
                "<td>".$v['user_money']."</td>".
                "<td>".$v['surplus_amount']."</td>".
                "</tr>";
        }
        $mailcontent .= "</table>";
    }

    if(!empty($userDebtData)){
        $mailcontent .= "<h1>兑换记录未发生比对数据<h1>";
        $mailcontent .= "<table border='1'><tr>" .
            "<td>用户id</td>".
            "<td>用户兑换金额</td>".
            "</tr>";
        foreach ($userDebtData as $k => $v){
            $mailcontent .= "<tr>" .
                "<td>".$k."</td>".
                "<td>".$v."</td>".
                "</tr>";
        }
        $mailcontent .= "</table>";
    }
    if($mailcontent != ''){
        include_once(ROOT_PATH.'includes/Smtp.class.php');
        $res = MessageService::warningEmail('换换自身对账报警',$mailcontent,['lirongze@huanhuanyiwu.com','wangyanan@huanhuanyiwu.com','zhanglihua@huanhuanyiwu.com']);
        if($res['code'] == 0){
            file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . date('Y-m-d H:i:s', time()) ."邮件发送成功", FILE_APPEND);
        }else{
            file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . date('Y-m-d H:i:s', time()) ."邮件发送失败", FILE_APPEND);
        }
    }
    file_put_contents(ROOT_PATH . 'temp/common/common.log', "\r\n" . date('Y-m-d H:i:s', time()) ."脚本结束", FILE_APPEND);







