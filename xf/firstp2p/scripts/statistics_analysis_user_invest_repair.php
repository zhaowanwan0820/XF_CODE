<?php
//每天的
//crontab: 0 1 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts && /apps/product/php/bin/php statistics_analysis_user_invest.php

/**
 * 每日数据统计，统计-投资用户行为分析
 * @author 彭长路 2014年3月27日18:15:53
 *
 **/

require_once dirname(__FILE__).'/../app/init.php';

use libs\db\MysqlDb;
$db = new MysqlDb("10.10.10.72:3306", "bzplan_read", "Ic7rI2UeLE", "firstp2p", "utf8");
$GLOBALS['db'] = $db;

set_time_limit(0);
$title_date = to_date(get_gmtime()-24 * 60 * 60, "Y年m月d日 日报");
//昨日零点
$yesterday_start = mktime(-8, 0, 0, date("m"), date("d")-1, date("Y"));
//今日零点
$today_start = mktime(-8, 0, 0, date("m"), date("d"), date("Y"));

$date = date("Y-m-d",$yesterday_start+8*3600);

FP::import("libs.common.dict");
$url = app_conf('STATISTICS_EMAIL_URL');

//$url = "http://10.10.10.145/api/ncfrs/";//线上环境的post接口地址
//$url = "http://10.18.6.77/api/ncfrs/";//测试环境的post接口地址

$data_type = "touzi";
$repair_count = $argv[1];
if($repair_count){//批量 $argv[1] 几天前的数据
    for($i=0;$i<$repair_count;$i++){
        if($url){
            echo $i."\n";
            $start = $yesterday_start-$i*86400;
            $end = $today_start-$i*86400;
            $data = deal_load($start,$end);
            $date = date("Y-m-d",$start+8*3600);
            echo $start+8*3600,'====',$start,'===',$end,'====',$date,'==='."\n";
            post_data($data,$url,$data_type,$date);
        }
    }
}else{
    if($url){
        $data = deal_load($yesterday_start,$today_start);
        post_data($data,$url,$data_type,$date);
    }
}


/**
 * 一段时间的用户投标情况
 * @param unknown $time_start 开始时间
 * @param unknown $time_end 结束时间
 */
function deal_load($time_start, $time_end){

    $data = array();
    $where = " WHERE source_type = 0 AND is_repay = 0 AND  deal_parent_id != 0 AND  create_time >= $time_start and create_time < $time_end ";
    $sql_list = "SELECT deal_id,user_id,money,create_time,ip FROM `".DB_PREFIX."deal_load` ".$where;
    $list = $GLOBALS['db']->getAll($sql_list);

    if(!$list){
        return $data;
    }
    foreach($list as $k=>$v){

        $sql_deal = "SELECT d.name AS deal_name,borrow_amount,min_loan_money,rate,repay_time,type_id,t.name AS type_name FROM `".DB_PREFIX."deal` AS d JOIN `".DB_PREFIX."deal_loan_type` AS t ON type_id = t.id WHERE d.id = {$v['deal_id']}";
        $deal_info = $GLOBALS['db']->getRow($sql_deal);

        $deal_info = ($deal_info == Null) ? array():$deal_info;
        $v = array_merge($v,$deal_info);

        if(!$v['user_id']){
            continue;
        }
        $sql_user = "SELECT user_name,sex,byear,bmonth,bday,idno FROM `".DB_PREFIX."user` WHERE id = {$v['user_id']}";
        $user_info = $GLOBALS['db']->getRow($sql_user);

        $user_info = ($user_info == Null) ? array():$user_info;
        $v = array_merge($v,$user_info);

        if($user_info){
            $sql_bank = "SELECT region_lv4 FROM `".DB_PREFIX."user_bankcard` WHERE user_id = {$v['user_id']}";
            $region_lv4 = $GLOBALS['db']->getOne($sql_bank);
            if($region_lv4){
                $region_sql = "SELECT pr.name AS p,ci.name AS c,re.name AS r FROM `firstp2p_delivery_region` AS re,`firstp2p_delivery_region` AS pr,`firstp2p_delivery_region` AS ci WHERE  re.id = {$region_lv4} AND re.pid = ci.id AND ci.pid = pr.id ";
                $region_info = $GLOBALS['db']->getRow($region_sql);
                $region_info = ($region_info == Null) ? array():$region_info;
                $v = array_merge($v,$region_info);
            }else{
                $sql_bank = "SELECT region_lv3 FROM `".DB_PREFIX."user_bankcard` WHERE user_id = {$v['user_id']}";
                $region_lv3 = $GLOBALS['db']->getOne($sql_bank);
                if($region_lv3){
                    $region_sql = "SELECT pr.name AS p,ci.name AS c FROM `firstp2p_delivery_region` AS pr,`firstp2p_delivery_region` AS ci WHERE  ci.id = {$region_lv3} AND ci.pid = pr.id ";
                    $region_info = $GLOBALS['db']->getRow($region_sql);
                    $region_info = ($region_info == Null) ? array():$region_info;
                    $v = array_merge($v,$region_info);
                }else{
                    $sql_bank = "SELECT region_lv2 FROM `".DB_PREFIX."user_bankcard` WHERE user_id = {$v['user_id']}";
                    $region_lv2 = $GLOBALS['db']->getOne($sql_bank);
                    if($region_lv2){
                        $region_sql = "SELECT name AS p FROM `firstp2p_delivery_region` WHERE id = {$region_lv2} ";
                        $region_info = $GLOBALS['db']->getRow($region_sql);
                        $region_info = ($region_info == Null) ? array():$region_info;
                        $v = array_merge($v,$region_info);
                    }
                }
            }
        }

        $sql_user_counts = "SELECT COUNT(*) FROM `".DB_PREFIX."deal_load` WHERE user_id = {$v['user_id']} ";
        $v['sql_user_counts'] = $GLOBALS['db']->getOne($sql_user_counts);
        //7周投资次数
        $start = $time_start-7*86400;
        $sql_user_7 = $sql_user_counts." AND  create_time >= {$start} and create_time < $time_end ";
        $v['sql_user_7'] = $GLOBALS['db']->getOne($sql_user_7);

        //30天投资次数
        $start = $time_start-30*86400;
        $sql_user_30 = $sql_user_counts." AND  create_time >= {$start} and create_time < $time_end ";
        $v['sql_user_30'] = $GLOBALS['db']->getOne($sql_user_30);

        //90天投资次数
        $start = $time_start-90*86400;
        $sql_user_90 = $sql_user_counts." AND  create_time >= {$start} and create_time < $time_end ";
        $v['sql_user_90'] = $GLOBALS['db']->getOne($sql_user_90);

        $v['sex'] = $v['sex'] == 1 ? "男":"女";
        if(!$v['byear']){
            if($v['idno'] && strlen(trim($v['idno'])) == 18){
                $v['birthday'] = substr(trim($v['idno']),6,8);
                $v['sex'] = substr(trim($v['idno']),-2,1)%2 == 1?"男":"女";
            }else{
                $v['birthday'] = '';
            }
        }else{
            $v['birthday'] = $v['byear'].sprintf("%02d",$v['bmonth']).sprintf("%02d",$v['bday']);
        }
        unset($v['idno']);
        $data[] = $v;
    }
    return $data;
}

/**
[deal_id] => 714 订单id
[user_id] => 1967 用户id
[money] => 200000 投资金额
[create_time] => 1396121098 投资日期
[deal_name] => 1万起，汇赢5号2期018 订单名称
[borrow_amount] => 400000 订单金额
[min_loan_money] => 10000 订单最小投资金额
[rate] => 9.50000  利率
[repay_time] => 143 期限  如果是 3，6，9，12 表示月份 其他表示天
[type_id] => 16
[type_name] => 产融贷 类型名称
[user_name] => yang_2013 用户名
[sex] => 女 性别
[byear] => 1975 出生年
[bmonth] => 6 出生月
[bday] => 1 出生日
[sql_user_counts] => 5 用户总投资笔数
[sql_user_7] => 3 用户7天投资笔数
[sql_user_30] => 3 用户30天投资笔数
[sql_user_90] => 3 用户90天投资笔数
[birthday] => 19750601 用户生日
 */

/**
 * post 请求接口数据
 * @param array $data post 数据
 * @param string $url 接口地址
 *
 */
function post_data($data,$url,$type="daily",$date=''){
    if(!$data || !$url){
        return false;
    }

    if(!$date){
        $date = date("Y-m-d");
    }
    $data = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $data = str_replace("+","\u002b",$data);
    $data = array(
        'pid' => 'ncf',//固定值
        'prd' => 'firstp2p',//固定值
        'ainfo'	=> $type,//日数据：daily，周数据：week 投资 touzi
        'date'=> $date,//数据日期，date("Y-m-d")
        'data'=> $data,
    );
    $data['sign'] = md5("ABC123".$data['date'].$data['data']);//数据校验码
    $data_str = http_build_query($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
    $output = curl_exec($ch);
    if (curl_errno($ch)){
        echo curl_error($ch);
    } else {
        echo $output;
    }
    curl_close($ch);
}