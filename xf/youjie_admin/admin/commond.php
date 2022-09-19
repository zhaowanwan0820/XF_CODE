<?php

/**
 * ECSHOP 定时任务
 * ===========================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: ；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: cron.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);
$function = $argv[1].'_commond';

if(!function_exists($function)){
    exit("执行方法不存在\n");
}
require(dirname(__FILE__) . '/../includes/init.php');

/* 取得当前ecshop所在的根目录 */
if(!defined('ADMIN_PATH'))
{
    define('ADMIN_PATH','admin');
}
define('ROOT_PATH', str_replace(ADMIN_PATH . '/commond.php', '', str_replace('\\', '/', __FILE__)));

/* 初始化配置 */
require(ROOT_PATH . 'data/config.php');
/* 初始化数据库类 */
//include_once(ROOT_PATH . 'includes/cls_mysql.php');
//include_once(ROOT_PATH . 'includes/lib_base.php');
include_once(ROOT_PATH . 'includes/Classes/PHPExcel.php');
include_once(ROOT_PATH . 'includes/Classes/PHPExcel/IOFactory.php');
//$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);

//执行方法
$function();

//解析导入运单
function import_invoice_commond()
{
    $lock = '/tmp/import_invoice_commond.pid';
    $fpLock = enterLock(['fnLock' => $lock]);
    if (!$fpLock) {
        echo "已有脚本在运行中\n";
        return false;
    }
    global $db;
    $sql = "select * from " . $GLOBALS['ecs']->table('import_invoice') . " where status = 0";
    $result = $db->getAll($sql);
    if (empty($result)) {
        echo "暂无数据\n";
        exit;
    }
    //$sql = "update " . $GLOBALS['ecs']->table('import_invoice') . " set status = 1 where status = 0";
    //$db->query($sql);
    $path = __DIR__ . '/';
    foreach ($result as $key => $value) {
        $file_path = $path . $value['file_path'];
        $admin_name = $value['admin_name'];
        $file_name = $value['file_name'];
        $prefix = sub_str($file_name,6,false);
        if (!file_exists($file_path)) {
            echo "{$value['id']}：文件不存在\n";
            $sql = "update " . $GLOBALS['ecs']->table('import_invoice') . " set status = 3, remark = '文件不存在' where id = {$value['id']}";
            $db->query($sql);
            continue;
        }
        $sql = "SELECT suppliers_id,type from " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_name = '$admin_name'";
        $user_data = $db->getRow($sql);

        $excel = PHPExcel_IOFactory::load($file_path);
            $sheet = $excel->getSheet();
            $rows = $sheet->getHighestRow();
            $clos = $sheet->getHighestColumn();
            $data = array();
            for ($i = 1; $i <= $rows; $i++) {
                for ($j = 'A'; $j <= $clos; $j++) {
                    $data[$i - 1][] = $sheet->getCell($j . $i)->getValue();
                }
            }
        $file_type = $clos == 'C' ? 1 : 0;
        $new_data[0] = ['订单号', '运单号', '快递公司', '处理结果', '失败原因'];
        $success_num = 0;
        $fail_num = 0;
        foreach ($data as $k => $v) {
            if ($k == 0) continue;
            $fail_num++;
            $arr = array_map('trim', $v);
            if ($file_type) {
                $or_sn = trim($arr[0]);
                $invo = trim($arr[1]);
                $shipp = trim($arr[2]);
            } else {
                $or_sn = trim($arr[1]);
                $invo = trim($arr[14]);
                $shipp = trim($arr[13]);
            }
            $invo = numToStr($invo);
            $new_data[$k][0] = $or_sn;
            $new_data[$k][1] = $invo;
            $new_data[$k][2] = $shipp;
            if (empty($or_sn)) {
                continue;
            }
            if ($user_data['type'] == 0 && $user_data['suppliers_id'] == 0) {
                $sql = "select * from " . $GLOBALS['ecs']->table('order_info') . " where order_sn = '" . $or_sn . "'";
            } else {
                $sql = "select * from " . $GLOBALS['ecs']->table('order_info') . " where order_sn = '" . $or_sn . "' AND suppliers_id in(" . $user_data['suppliers_id'] . ")";
            }
            $order = $db->getRow($sql);
            if (empty($order)) {
                $new_data[$k][3] = '失败';
                $new_data[$k][4] = '订单不存在';
                //echo "订单不存在\n";
                continue;
            }

            if ($prefix == 'hh_修复_' && $order['shipping_status'] > 0 && $order['pay_status'] == 2) {

            } else {
                if (!in_array($order['order_status'], [1, 5, 6]) || !in_array($order['shipping_status'], [0, 3, 5]) || $order['pay_status'] != 2) {
                    $new_data[$k][3] = '失败';
                    $new_data[$k][4] = "订单状态异常：order_status = {$order['order_status']},shipping_status = {$order['shipping_status']},pay_status = {$order['pay_status']}";
                    //echo "状态不为未发货\n";
                    continue;
                }
            }


            if (empty($invo)) {
                $new_data[$k][3] = '失败';
                $new_data[$k][4] = '运单号不能为空';
                //echo "运单号为空\n";
                continue;
            }
            //效验运单号对应一个用户    2019.4.30去掉
            if(strpos($invo,';')){
                $invo_arr = explode(';',$invo);
            }else{
                $invo_arr = explode(',',$invo);
            }
            $invo_str = trim(implode(',',$invo_arr),',');

//            $pre = false;
//            foreach ($invo_arr as $key => $val) {
//                $sql = "select user_id from " . $GLOBALS['ecs']->table('order_info') . "  where find_in_set('" . $val . "',invoice_no) GROUP BY user_id";
//                $users = $db->getAll($sql);
//                if (!empty($users)) {
//                    if (count($users) > 1 || $users[0]['user_id'] != $order['user_id']) {
//                        file_put_contents(ROOT_PATH . 'temp/common/common.log',
//                            PHP_EOL . date('Y-m-d H:i:s', time()) ."记录运单号错误日志:" .
//                            PHP_EOL . '执行sql:'.$sql .
//                            PHP_EOL . '执行结果:'.json_encode($users),
//                            FILE_APPEND);
//                        $new_data[$k][3] = '失败';
//                        $new_data[$k][4] = '运单号' . $val . '对应多个用户';
//                        $pre = true;
//                        break;
//                    }
//                }
//            }
//            if ($pre) {
//                continue;
//            }
            $shipping_id = 0;
            $shipping = [
                '顺丰'=>'8',
                '顺风'=>'8',
                '中通'=>'9',
                '申通'=>'10',
                '圆通'=>'11',
                '园通'=>'11',
                'EMS'=>'12',
                '德邦'=>'13',
                '邦德'=>'13',
                '邮政'=>'14',
                '京东'=>'19',
                '韵达'=>'20',
                '天天'=>'24',
                '汇通'=>'21',
                '百世'=>'21',
                '白世'=>'21',
                '百事'=>'21',
                '优速'=>'36',
                '品骏'=>'60',
                '人人'=>'71',
                'DHL'=>'117',
                '宅急送'=>'121',
                '高峰'=>'129',
                '苏宁'=>'132',
                '中通快运'=>'123',
            ];
            foreach($shipping as $name =>$id ){
                if(mb_strpos($shipp,$name)!==false){
                    $shipping_id = $id;
                }
            }
            $sql = "select * from " . $GLOBALS['ecs']->table('shipping') . " where shipping_name = '" . $shipp . "'";
            if($shipping_id>0){
                $sql = "select * from " . $GLOBALS['ecs']->table('shipping') . " where shipping_id = $shipping_id";
            }
            $shippp_arr = $db->getRow($sql);
            if (empty($shippp_arr)) {
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('shipping') . " (shipping_name,enabled) VALUES ('" . $shipp . "',1)";
                $db->query($sql);
                $shipping_id = $db->insert_id($sql);
                if (!$shipping_id) {
                    $new_data[$k][3] = '失败';
                    $new_data[$k][4] = '更新物流公司失败';
                    //echo "更新运单号失败\n";
                    continue;
                }
            } else {
                $shipping_id = $shippp_arr['shipping_id'];
                $shipp       = $shippp_arr['shipping_name'];
            }

            if ($prefix == 'hh_修复_' && $order['shipping_status'] > 0 && $order['pay_status'] == 2) {
                $sql = "update " . $GLOBALS['ecs']->table('order_info') . " set invoice_no = '" . $invo_str . "',shipping_id =" . $shipping_id . ",shipping_name ='" . $shipp . "'where order_id = {$order['order_id']}";
            } else {
                $sql = "update " . $GLOBALS['ecs']->table('order_info') . " set shipping_status = 1, invoice_no = '" . $invo_str . "',shipping_time = " . time() . ',shipping_id =' . $shipping_id . ",shipping_name ='" . $shipp . "'where order_id = {$order['order_id']}";
            }
            $row = $db->query($sql);
            if (!$row) {
                $new_data[$k][3] = '失败';
                $new_data[$k][4] = '更新运单号失败';
                //echo "更新运单号失败\n";
                continue;
            }

            if ($prefix == 'hh_修复_' && $order['shipping_status'] > 0 && $order['pay_status'] == 2) {
                $new_data[$k][3] = '运单号数据修复成功';
                $new_data[$k][4] = '';
            } else {
                $new_data[$k][3] = '成功';
                $new_data[$k][4] = '';
            }



            $fail_num--;
            $success_num++;
            $action_note = '导入【' . $shipp . '】' . trim($invo);
            /* 记录log */
            order_action_change($or_sn, $order['order_status'], 1, $order['pay_status'], $action_note, $admin_name);
        }

        $file_path = mb_substr($value['file_path'], 0, -4) . "_new.csv";
        $new_file = $path . $file_path;
        $fp = @fopen($new_file, 'w');
        if (!$fp) {
            echo "开打文件失败：{$new_file}\n";
        }
        foreach ($new_data as $line) {
            $line[0] = "'" . $line[0];
            $line[1] = "'" . $line[1];
            print_r($line);
            $length = fputcsv($fp, $line);
            if (!$length) {
                echo "写入文件失败\n";
            }
        }
        @fclose($fp);
        $sql = "update " . $GLOBALS['ecs']->table('import_invoice') . " set status = 2,success_num = {$success_num},fail_num = {$fail_num},file_path = '{$file_path}' where id = {$value['id']}";
        $row = $db->query($sql);
        if (!$row) {
            echo "更新失败\n";
        }
        echo "id = {$value['id']} 统计：(成功：{$success_num}，失败：{$fail_num})\n";
    }
    //释放文件锁
    releaseLock(['fnLock' => $lock, 'fpLock' => $fpLock]);
    exit;
}

/**
 * 跑脚本加锁
 */
function enterLock($config){
    if(empty($config['fnLock'])){
        return false;
    }
    $fnLock = $config['fnLock'];
    $fpLock = fopen($fnLock, 'w+');
    if($fpLock){
        if ( flock( $fpLock, LOCK_EX | LOCK_NB ) ) {
            return $fpLock;
        }
        fclose( $fpLock );
        $fpLock = null;
    }
    return false;
}

/**
 * 检查跑脚本加锁
 */
function releaseLock($config){
    if (!$config['fpLock']){
        return;
    }
    $fpLock = $config['fpLock'];
    $fnLock = $config['fnLock'];
    flock($fpLock, LOCK_UN);
    fclose($fpLock);
    unlink($fnLock);
}

/**
 * 订单操作
 */
function order_action_change($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null, $place = 0)
{
    if (is_null($username))
    {
        $username = $_SESSION['admin_name'];
    }

    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('order_action')  .
        ' (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
        'SELECT ' .
        "order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$place', '$note', '" .time() . "' " .
        'FROM ' . $GLOBALS['ecs']->table('order_info')  . " WHERE order_sn = '$order_sn'";
    return $GLOBALS['db']->query($sql);
}

function numToStr($num)
{
    $result = "";
    if (stripos($num, '.') === false) {
        return $num;
    }
    while ($num > 0) {
        $v = $num - floor($num / 10) * 10;
        $num = floor($num / 10);
        $result = $v . $result;
    }
    return $result;
}
?>
