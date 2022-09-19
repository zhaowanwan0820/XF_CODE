<?php
/**
 * #投资数据导出
 * 10 0 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php bid_load.php
 * @author wenyanlei  2013-11-22
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/utils/es_mail.php';

FP::import("libs.utils.logger");

use core\dao\DealModel;
use core\dao\ContractModel;
use core\dao\CouponLogModel;
use core\dao\DealLoadModel;
use core\dao\DealAgencyModel;
use core\service\UserService;

error_reporting(E_ALL ^ E_WARNING);
ini_set('display_error', 1);
ini_set('memory_limit', '2048M');

$time = strtotime('-1 day');
set_time_limit(0);

//文件名和路径
$csv_name = sprintf("touzi_%s.csv", date("Ymd", $time));
$zip_name = sprintf("touzi_%s.zip", date("Ymd", $time));

$base_path = APP_ROOT_PATH.'runtime';
$csv_path = sprintf("%s/%s", $base_path, $csv_name);
$zip_path = sprintf("%s/%s", $base_path, $zip_name);

if(!is_dir($base_path)){
    @mkdir($base_path);
}

$fp = fopen($csv_path, "w+"); //打开文件指针，没有文件则尝试创建文件

if (!is_writable($csv_path)){
    die("文件:" .$csv_path. "不可写，请检查！");
}

//写入列名
$title = array(
        "投资id","借款id","借款人","借款人用户名","借款标题",
        "借款金额(元)","借款期限","借款合同编号","出借人收益率(%)","投资人用户名",
        "投资人身份证号","投资人姓名","投资人分组","投资人级别","投资人注册时间",
        "投资金额(元)","投资人账户余额(元)","投资时间","投资人是否内部客户","业务咨询方",
        "邀请码","推荐人分组","推荐人级别"
);
$title = iconv("utf-8", "gbk", implode(',', $title));
fputcsv($fp, explode(',', $title));

//查询数据列表
$bid_list = DealLoadModel::instance()->findAll("deal_parent_id != 0 order by id desc", true);
foreach($bid_list as $bid){
    $deal = get_deal_info($bid['deal_id']);
    if(empty($deal)){
        continue;
    }
    $bdu = get_user_data($bid['user_id']);//投资人用户信息
    $bwu = get_user_data($deal['user_id']);//借款人用户信息

    //优惠券短码
    $coupon_code = '';
    $coupon_data = array();
    $coupon_log = CouponLogModel::instance()->findBy(sprintf("deal_load_id = %d", $bid['id']));
    if($coupon_log){
        $coupon_code = $coupon_log['short_alias'];
        $coupon_data = get_coupon_data($coupon_log['refer_user_id']);
    }

    //组织数据
    $real_name = isset($bwu['real_name']) ? $bwu['real_name'] : '';//借款人
    $user_name = isset($bwu['user_name']) ? $bwu['user_name'] : '';//借款人用户名

    $bid_user_name = isset($bdu['user_name']) ? $bdu['user_name'] : ''; //投资人用户名
    $bid_real_name = isset($bdu['real_name']) ? $bdu['real_name'] : ''; //投资人姓名
    $bid_time = $bid['create_time'] ? to_date($bid['create_time']) : '';//投资时间

    $bid_idno = isset($bdu['idno']) ? $bdu['idno'] : ''; //投资人身份证号
    $bid_regname = isset($bdu['create_time']) ? to_date($bdu['create_time']) : '';
    $bid_usermoney = $bdu['money'] ? $bdu['money'] : '';//投资人剩余money
    $bid_is_staff = $bdu['is_staff'] == 1 ? '是' : '否';

    $repay_time = $deal['repay_time'].'个月';
    if($deal['loantype'] == 5){
        $repay_time = $deal['repay_time'].'天';
    }
    $contract_num = '';
    if(in_array($deal['deal_status'], array(2,4,5))){
        $contract_num = ContractModel::genContractNumber($bid['deal_id'], $deal['parent_id'], $bid['user_id'], $bid['id'], 1);
    }

    //"投资id","借款id","借款人","借款人用户名","借款标题",
    //"借款金额(元)","借款期限","借款合同编号","出借人收益率(%)","投资人用户名",
    //"投资人身份证号","投资人姓名","投资人分组","投资人级别","投资人注册时间",
    //"投资金额(元)","投资人账户余额(元)","投资时间","投资人是否内部客户","业务咨询方",
    //"邀请码","推荐人分组","推荐人级别"
    $row_content = sprintf(
        "%s||%s||\t%s||\t%s||%s||%s||%s||\t%s||%s||\t%s||\t%s||%s||%s||%s||\t%s||%s||%s||\t%s||%s||%s||%s||%s||%s",
        $bid['id'],$bid['deal_id'],$real_name,$user_name,$deal['name'],
        $deal['borrow_amount'],$repay_time,$contract_num,$deal['income_fee_rate'],$bid_user_name,
        $bid_idno,$bid_real_name,$bdu['user_group_name'],$bdu['user_level_num'],$bid_regname,
        $bid['money'],$bid_usermoney,$bid_time,$bid_is_staff,$deal['advisory'],
        $coupon_code,$coupon_data['user_group_name'],$coupon_data['user_level_num']
    );

    $row_arr = explode('||', $row_content);
    foreach($row_arr as $row_key => $row_val){
        $row_arr[$row_key] = iconv("utf-8", "gbk", $row_val);
    }
    $fres = fputcsv($fp, $row_arr);
}

fclose($fp);

//发送邮件
FP::import("libs.common.dict");
$email_arr = dict::get("DEAL_LOAD_EMAIL");

//记录发送日志
$log = array(
        'title' => '每日投资统计',
        'time' => date('Y-m-d H:i:s',time())
);

if(file_exists($csv_path) && $email_arr){
    //创建zip压缩加密
    $password = app_conf('CSV_ZIP_PASSWORD');
    exec(sprintf("cd %s;zip -P %s %s %s", $base_path, $password, $zip_name, $csv_name), $exec_res);

    //添加附件表
    if(file_exists($zip_path)){
        $attach_id = add_file($zip_path);
        if($attach_id){
            $title = sprintf("网信理财 %s 投资数据概况", date("Y年m月d日", $time));
            $content = sprintf("您好，附件是%s，请查收。", $title);

            $msgcenter = new msgcenter();
            foreach ($email_arr as $email) {
                $msg_count = $msgcenter->setMsg($email, 0, $content, false, $title, $attach_id);
            }
            $msg_save = $msgcenter->save();
            if($msg_count == 0 || $msg_save == 0){
                $log['error'] = '$msgcenter->setMsg 返回结果是0';
            }
        }else{
            $log['error'] = 'add_file添加附件表操作失败，未获取到附件id';
        }
    }else{
        $log['error'] = 'zip文件生成失败!'.$exec_res[0];
    }
}else{
    //记录发送日志
    $log['error'] = 'csv生成失败或者DEAL_LOAD_EMAIL为空';
}

$log['result'] = isset($log['error']) ? '操作失败' : '操作成功';
echo isset($log['error']) ? '操作失败:'.$log['error'] : '操作成功';
logger::wLog($log);

function get_user_data($user_id){
    $user_id = intval($user_id);
    if($user_id <= 0){
        return array();
    }
    static $user_info = array();
    if(!isset($user_info[$user_id])){
        $user_service = new UserService();
        $user_data = $user_service->getUser($user_id);

        if($user_data){
            $coupon_data = get_coupon_data($user_id);
            if($coupon_data){
                $user_data['user_group_name'] = $coupon_data['user_group_name'];
                $user_data['user_level_num'] = $coupon_data['user_level_num'];
            }
        }
        $user_info[$user_id] = $user_data;
    }
    return $user_info[$user_id];
}

function get_coupon_data($user_id){
    $user_id = intval($user_id);
    if($user_id <= 0){
        return array();
    }
    static $coupon_info = array();
    if(!isset($coupon_info[$user_id])){
        $coupon_leven_service = new \core\service\CouponLevelService();
        $coupon_data = $coupon_leven_service->getUserLevel($user_id);

        $return['user_group_name'] = $coupon_data['group_name'];
        $return['user_level_num'] = $coupon_data['level'];

        $coupon_info[$user_id] = $return;
    }
    return $coupon_info[$user_id];
}

function get_deal_info($deal_id){
    $deal_id = intval($deal_id);
    if($deal_id <= 0){
        return array();
    }
    static $deal_info = array();
    if(!isset($deal_info[$deal_id])){
        $res = DealModel::instance()->findBy(sprintf("id = %d AND is_delete = 0", $deal_id));
        if($res){
            $advisory = DealAgencyModel::instance()->find($res['advisory_id']);
            $res['advisory'] = $advisory['short_name'];
        }
        $deal_info[$deal_id] = $res;
    }
    return $deal_info[$deal_id];
}
