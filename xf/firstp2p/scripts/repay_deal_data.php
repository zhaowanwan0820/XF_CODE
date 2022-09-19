<?php
/**
 * 未来15天即将到期的标的投标数据
 * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php bid_deal.php
 * @author wenyanlei  20140507
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/utils/es_mail.php';

set_time_limit(0);

use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealRepayModel;

//存储路径
$filepath = APP_ROOT_PATH.'runtime';

if(!is_dir($filepath)){
    @mkdir($filepath);
}

$time = time();
$filename = sprintf("%s/deal_repay_data_%s.csv", $filepath, date("Ymd",$time));

$fp = fopen($filename, "w+"); //打开文件指针，没有文件则尝试创建文件

if(!is_writable($filename)){
    die("文件:" .$filename. "不可写，请检查！");
}

//表头
$title = iconv("utf-8", "gbk", 
        "借款人,借款标题,借款期限,出借人收益率(%),投标人用户名,投标人姓名,性别,手机号码,投标金额(元),最近还款时间,最近还款金额(元),距离还款日天数(天),累计投标笔数,累计标金额(元)"
);

fputcsv($fp, explode(',', $title));

$id_arr = array(
        379,378,377,376,375,374,373,372,808,390,
        389,821,402,757,201,200,199,195,193,189,
        188,187,444,456,450,446,445,443,442,438
);

$email_arr = array(
        'liubaikui@ucfgroup.com',
        'heping@ucfgroup.com',
);

//测试数据
//$id_arr = array();
//$email_arr = array('wenyanlei@ucfgroup.com');

$where = $id_arr ? " a.id in (".implode(',', $id_arr).") AND " : '';

//获取数据列表
$sql = "SELECT a.id,a.name,a.user_id as deal_userid,a.borrow_amount,a.income_total_rate,
        a.repay_time,a.loantype,a.income_fee_rate,a.next_repay_time,a.deal_status,
        b.id as bid,b.user_id as bid_userid,b.create_time,b.money as bid_money
        FROM ".DB_PREFIX."deal a RIGHT JOIN ".DB_PREFIX."deal_load b 
        ON a.id = b.deal_id WHERE ".$where." a.is_delete = 0 AND b.deal_parent_id != 0 
        ORDER BY a.id DESC";

$list = DealModel::instance()->findAllBySql($sql, true);

foreach($list as $bid){
    
    $deal_user = get_user_data($bid['deal_userid']);//借款人信息
    $bid_user = get_user_data($bid['bid_userid']);//投标人信息
    
    //处理数据
    $sex = $bid_user['sex'] == 1 ? '男' : '女'; //性别
    $real_name = $deal_user['real_name'] ? $deal_user['real_name'] : $deal_user['user_name'];//借款人
    $repay_time = $bid['loantype'] == 5 ? $bid['repay_time'].'天' : $bid['repay_time'].'个月';//借款期限
    
    //最近还款数据
    $next_repay_time = $bid['next_repay_time'] && $bid['deal_status'] == 4 ? $bid['next_repay_time'] : '';
    
    $next_repay_data = get_next_repay_data($bid['id'], $next_repay_time);
    $next_repay_time = $next_repay_data['next_repay_time'];//最近还款时间
    $next_repay_money = $next_repay_data['next_repay_money'];//最近还款金额
    
    $left_day = '';//距离还款日天数
    if($next_repay_time && $bid['deal_status'] != 5){
        $left_day = ceil(($next_repay_time - get_gmtime()) / 24 / 3600);
    }
    
    //用户投资信息
    $user_stat = get_user_stat($bid['bid_userid']);
    
    //借款人,借款标题,借款期限,出借人收益率(%),投标人用户名,
    //投标人姓名,性别,手机号码,投标金额(元),最近还款时间,
    //最近还款金额(元),距离还款日天数(天),累计投标笔数,累计标金额(元)
    $one_content = iconv("utf-8", "gbk",sprintf(
            "%s||%s||%s||\t%s||%s||%s||%s||\t%s||%s||\t%s||%s||%s||%s||%s",
            $real_name, $bid['name'], $repay_time, $bid['income_total_rate'].'%',$bid_user['user_name'],
            $bid_user['real_name'], $sex, $bid_user['mobile'], $bid['bid_money'], to_date($next_repay_time,'Y-m-d'),
            $next_repay_money, $left_day, $user_stat['load_count'], $user_stat['load_money']
    ));
    
    $fres = fputcsv($fp, explode('||', $one_content));
}

fclose($fp);

//发送邮件
if(file_exists($filename)){
    $title = "投标数据导出";
    $content = "您好，附件是".$title."，请查收。";
    $attach_id = add_file($filename);
    
    $msgcenter = new msgcenter();
    
    FP::import("libs.common.dict");
    
    if($email_arr){
        foreach ($email_arr as $email) {
            $msgcenter->setMsg($email, 0, $content, false, $title, $attach_id);
        }
        $msgcenter->save();
        echo '附件已生成，邮件已发送';
    }
}else{
    echo '附件生成失败！！！';
}

function get_user_data($user_id){
    static $user_info = array();
    if(isset($user_info[$user_id])){
        $res = $user_info[$user_id];
    }else{
        $res = UserModel::instance()->find($user_id, 'user_name,real_name,mobile,sex');
        $user_info[$user_id] = $res;
    }
    return $res;
}

function get_next_repay_data($deal_id, $next_repay_time){
    static $repay_data = array();
    if(isset($repay_data[$deal_id])){
        $res = $repay_data[$deal_id];
    }else{
        if($next_repay_time){
            $where = "deal_id = ".$deal_id." AND repay_time = '".$next_repay_time."' AND true_repay_time = 0";
            $next_repay = DealRepayModel::instance()->findBy($where);
            if(empty($next_repay)){
                $where = "deal_id = ".$deal_id." AND true_repay_time = 0 ORDER BY repay_time ASC limit 1";
                $next_repay = DealRepayModel::instance()->findBy($where);
                if($next_repay && $next_repay['repay_time']){
                    $next_repay_time = $next_repay['repay_time'];
                }
            }
        }
        $next_repay_money = $next_repay ? $next_repay['repay_money'] : '';
        
        $res = array(
                'next_repay_time' => $next_repay_time, 
                'next_repay_money' => $next_repay_money
        );
        
        $repay_data[$deal_id] = $res;
    }
    return $res;
}

function get_user_stat($user_id){
    static $user_stat = array();
    if(isset($user_stat[$user_id])){
        $res = $user_stat[$user_id];
    }else{
        $count_sql = "SELECT COUNT(*) AS load_count,SUM(d_l.money) AS load_money
                FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l
                ON d_l.deal_id = d.id WHERE d.deal_status != 3 AND d.is_delete = 0
                AND parent_id != 0 AND d_l.user_id =".$user_id;
        
        $res = UserModel::instance()->findBySql($count_sql);
        $user_stat[$user_id] = $res;
    }
    return $res;
}
