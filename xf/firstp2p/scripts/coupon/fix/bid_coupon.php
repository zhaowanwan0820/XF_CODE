<?php
/**
 * #投资数据导出
 * 10 0 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php bid_coupon.php
 * @author wenyanlei  2014-06-20
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

$time = time();
set_time_limit(0);
ini_set('memory_limit', '512M');

//文件名和路径
$csv_name = sprintf("touzi_coupon.csv", date("Ymd", $time));

$base_path = APP_ROOT_PATH.'runtime';
$csv_path = sprintf("%s/%s", $base_path, $csv_name);

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
        "邀请码","推荐人身份证号","推荐人分组","推荐人级别","投资人返点金额(元)",
        "投资人返点比例金额(元)","推荐人返点金额(元)","推荐人返点比例金额(元)","机构返点金额(元)","机构返点比例金额(元)"
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
    $coupon_data = array();
    $coupon_log = CouponLogModel::instance()->findBy(sprintf("deal_load_id = %d", $bid['id']));
    if($coupon_log){
        $coupon_data = get_user_data($coupon_log['refer_user_id']);
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
    //"邀请码","推荐人身份证号","推荐人分组","推荐人级别","投资人返点金额",
    //"投资人返点比例金额(元)","推荐人返点金额(元)","推荐人返点比例金额(元)","机构返点金额(元)","机构返点比例金额(元)"
    $one_content = iconv("utf-8", "gbk",sprintf(
            "%s||%s||\t%s||\t%s||%s||%s||%s||\t%s||%s||\t%s||\t%s||%s||%s||%s||\t%s||%s||%s||\t%s||%s||%s||%s||\t%s||%s||%s||%s||%s||%s||%s||%s||%s",
            $bid['id'],$bid['deal_id'],$real_name,$user_name,$deal['name'],
            $deal['borrow_amount'],$repay_time,$contract_num,$deal['income_fee_rate'],$bid_user_name,
            $bid_idno,$bid_real_name,$bdu['user_group_name'],$bdu['user_level_num'],$bid_regname,
            $bid['money'],$bid_usermoney,$bid_time,$bid_is_staff,$deal['advisory'],
            $coupon_log['short_alias'],$coupon_data['idno'],$coupon_data['user_group_name'],$coupon_data['user_level_num'],$coupon_log['rebate_amount'],
            $coupon_log['rebate_ratio_amount'],$coupon_log['referer_rebate_amount'],$coupon_log['referer_rebate_ratio_amount'],$coupon_log['agency_rebate_amount'],$coupon_log['agency_rebate_ratio_amount']

    ));
    $fres = fputcsv($fp, explode('||', $one_content));
}

fclose($fp);

//发送邮件
FP::import("libs.common.dict");

if(file_exists($csv_path)){
    $title = sprintf("网信理财 %s 投资数据概况", date("Y年m月d日", $time));
    $content = sprintf("您好，附件是%s，请查收。", $title);

    $msgcenter = new msgcenter();
    $attach_id = add_file($csv_path);
    if(!$attach_id){
        exit('attach_id添加附件表失败');
    }
    $msgcenter->setMsg('wenyanlei@ucfgroup.com', 0, $content, false, $title, $attach_id);
    $msg_save = $msgcenter->save();
    if($msg_save == 0){
        exit('$msgcenter->setMsg 返回结果是0');
    }else{
        exit('success');
    }
}else{
    //记录发送日志
    exit('csv生成失败');
}

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
