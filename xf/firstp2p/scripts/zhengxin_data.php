<?php
/**
 * #征信系统数据导出
 * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php bid_load.php
 * @author wenyanlei  2014-07-07
 */
require_once dirname(__FILE__).'/../app/init.php';

use libs\db\MysqlDb;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\DealAgencyModel;
use core\dao\UserModel;
use core\dao\BankModel;
use core\dao\DeliveryRegionModel;

set_time_limit(0);
ini_set('memory_limit', '512M');
error_reporting(E_ALL ^ E_NOTICE);

$func_arr = array(
        'user' => 'make_user_csv',
        'deal' => 'make_deal_csv',
        'load' => 'make_load_csv',
        'agency' => 'make_agency_csv',
);

if (isset($argv[1]) && isset($func_arr[$argv[1]])) {
    if (app_conf('ENV_FLAG') == 'online') {
        $db = new MysqlDb("10.10.10.72:3306", "bzplan_read", "Ic7rI2UeLE", "firstp2p", "utf8");
    }
    call_user_func($func_arr[$argv[1]]);
}else{
    exit('data type is error');
}

function make_agency_csv(){
    $base_path = APP_ROOT_PATH.'runtime/zhengxin';
    if(!is_dir($base_path)){
        @mkdir($base_path);
    }
    $csv_path = sprintf("%s/%s", $base_path, "zhengxin_agency_data.csv");

    $fp = fopen($csv_path, "w+");

    //写入列名
    $title = array(
            "名称","简介"
    );
    $title = iconv("utf-8", "gbk", implode(',', $title));
    fputcsv($fp, explode(',', $title));

    $load_all = DealAgencyModel::instance()->findAll("type = 1 and is_effect = 1 order by id desc");
    foreach ($load_all as $row){
        $one_content = iconv("utf-8", "gbk",sprintf("%s||%s",$row['name'],$row['brief']));
        $fres = fputcsv($fp, explode('||', $one_content));
    }
    fclose($fp);

    if(file_exists($csv_path)){
        echo $csv_path,' 文件已生成';
    }else{
        echo $csv_path,' 文件生成失败';
    }
}

function make_load_csv(){
    $base_path = APP_ROOT_PATH.'runtime/zhengxin';
    if(!is_dir($base_path)){
        @mkdir($base_path);
    }
    $csv_path = sprintf("%s/%s", $base_path, "zhengxin_load_data.csv");

    $fp = fopen($csv_path, "w+");

    //写入列名
    $title = array(
            "用户id","投资额度","时间","投资的IP地址","贷款id"
    );
    $title = iconv("utf-8", "gbk", implode(',', $title));
    fputcsv($fp, explode(',', $title));

    $load_all = DealLoadModel::instance()->findAll("deal_parent_id != 0 order by id desc");

    foreach ($load_all as $row){
        $one_content = iconv("utf-8", "gbk",sprintf(
                "%s||%s||\t%s||%s||%s",
                md5(md5($row['user_id'])),$row['money'],to_date($row['create_time']),$row['ip'],md5(md5($row['deal_id']))
        ));
        $fres = fputcsv($fp, explode('||', $one_content));
    }
    fclose($fp);

    if(file_exists($csv_path)){
        echo $csv_path,' 文件已生成';
    }else{
        echo $csv_path,' 文件生成失败';
    }
}

function make_deal_csv(){
    $base_path = APP_ROOT_PATH.'runtime/zhengxin';
    if(!is_dir($base_path)){
        @mkdir($base_path);
    }
    $csv_path = sprintf("%s/%s", $base_path, "zhengxin_deal_data.csv");
    $fp = fopen($csv_path, "w+");

    //写入列名
    $title = array(
            "id","标题","借款年利率","金额","用户id",
            "借款类型","借款时间","借款状态","满标放款时间","担保公司名称"
    );
    $title = iconv("utf-8", "gbk", implode(',', $title));
    fputcsv($fp, explode(',', $title));

    $deal_all = DealModel::instance()->findAll('is_effect = 1 and is_delete = 0 and parent_id != 0 order by id desc');
    $agency_all = DealAgencyModel::instance()->findAll();
    $agency_list = array();
    foreach($agency_all as $agency_row){
        $agency_list[$agency_row['id']] = $agency_row['name'];
    }
    $status_conf = array(
            0 => '等待确认',
            1 => '进行中',
            2 => '满标',
            3 => '流标',
            4 => '还款中',
            5 => '已还清',
    );
    foreach ($deal_all as $row){
        $loantype = $GLOBALS['dict']['LOAN_TYPE'][$row['loantype']];//还款方式
        $one_content = iconv("utf-8", "gbk",sprintf(
                "%s||%s||%s||%s||%s||%s||\t%s||%s||\t%s||%s",
                md5(md5($row['id'])),$row['name'],$row['rate'],$row['borrow_amount'],md5(md5($row['user_id'])),
                $loantype,to_date($row['create_time']),$status_conf[$row['deal_status']],
                to_date($row['repay_start_time'], 'Y-m-d'),$agency_list[$row['agency_id']]
        ));
        $fres = fputcsv($fp, explode('||', $one_content));
    }
    fclose($fp);

    if(file_exists($csv_path)){
        echo $csv_path,' 文件已生成';
    }else{
        echo $csv_path,' 文件生成失败';
    }
}

function make_user_csv(){

    $base_path = APP_ROOT_PATH.'runtime/zhengxin';
    if(!is_dir($base_path)){
        @mkdir($base_path);
    }
    $csv_path = sprintf("%s/%s", $base_path, "zhengxin_user_data.csv");
    $fp = fopen($csv_path, "w+");

    //写入列名
    $title = array(
            "id","姓名","性别","出生日期","注册日期",
            "银行","开户行","所在地","关联公司","人的简介","登录IP"
    );
    $title = iconv("utf-8", "gbk", implode(',', $title));
    fputcsv($fp, explode(',', $title));

    $user_sql = "SELECT u.id,u.real_name,u.sex,u.byear,u.bmonth,u.bday,u.create_time,u.info,u.login_ip,
            uc.name as company_name,ub.bank_id,ub.region_lv1,ub.region_lv2,ub.region_lv3,ub.region_lv4,ub.bankzone
            FROM `firstp2p_user` u left join firstp2p_user_company uc on u.id = uc.user_id
            left join firstp2p_user_bankcard ub on u.id = ub.user_id
            where u.idcardpassed = 1 and u.is_effect = 1 and u.is_delete = 0
            order by id desc";

    $user_all = UserModel::instance()->findAllBySql($user_sql);

    $region_all = DeliveryRegionModel::instance()->findAll();
    $region_list = array();
    foreach ($region_all as $region_row){
        $region_list[$region_row['id']] = $region_row['name'].' ';
    }

    $bank_all = BankModel::instance()->findAll();
    $bank_list = array();
    foreach ($bank_all as $bank_row){
        $bank_list[$bank_row['id']] = $bank_row['name'];
    }

    foreach($user_all as $row){
        $sex = ($row['sex'] == 1) ? '男' : ($row['sex'] == 0 ? '女' : '');
        $birthday = $row['byear'] ? $row['byear'].'-'.$row['bmonth'].'-'.$row['bday'] : '';
        $real_name = make_realname($row);

        $region_lv1 = $region_list[$row['region_lv1']];
        $region_lv2 = $region_list[$row['region_lv2']];
        $region_lv3 = $region_list[$row['region_lv3']];
        $region_lv4 = $region_list[$row['region_lv4']];

        $bank_name = $bank_list[$row['bank_id']];

        $one_content = iconv("utf-8", "gbk",sprintf(
                "%s||%s||%s||\t%s||\t%s||%s||%s||%s||%s||%s||%s",
                md5(md5($row['id'])),$real_name,$sex,$birthday,
                to_date($row['create_time']),$bank_name,$row['bankzone'],
                $region_lv1.$region_lv2.$region_lv3.$region_lv4,$row['company_name'],$row['info'],
                $row['login_ip']
        ));
        $fres = fputcsv($fp, explode('||', $one_content));
    }
    fclose($fp);

    if(file_exists($csv_path)){
        echo $csv_path,' 文件已生成';
    }else{
        echo $csv_path,' 文件生成失败';
    }
}

function make_realname($user_info){
    if(empty($user_info['real_name'])){
        return '';
    }
    $sex = $user_info['sex'];
    if($user_info['sex'] == -1){
        $sexnum = -1;
        if(strlen($user_info['idno']) == 15){
            $sexnum = substr($user_info['idno'], -1);
        }elseif(strlen($user_info['idno']) == 18){
            $sexnum = substr($user_info['idno'], -2, 1);
        }
        if($sexnum > 0){
            $sex = $sexnum % 2 ? 1 : 0;
        }
    }
    $sex_tag = ($sex == 1) ? '先生' : ($sex == 0 ? '女士' : '');

    if(preg_match('/^[a-zA-Z0-9]+/',$user_info['real_name'],$out)){
        $pre_name = $out[0];
    }else{
        $pre_name = mb_substr($user_info['real_name'], 0, 1, 'utf-8');
    }
    return $pre_name.$sex_tag;
}