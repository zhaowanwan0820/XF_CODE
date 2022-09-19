<?php
/**
 * #借款数据导出
 * 11 0 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php bid_deal.php
 * @author wenyanlei  2013-11-22
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

use libs\utils\Finance;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\ContractModel;
use core\dao\DealAgencyModel;
use core\dao\UserCompanyModel;
use core\dao\UserBankcardModel;
use core\dao\AgencyContractModel;
use core\service\CouponService;
use core\service\DealService;
use core\service\EarningService;
use core\service\UserService;

$time = strtotime('-1 day');
set_time_limit(0);
ini_set('memory_limit', '1024M');
//文件名和路径
$csv_name = sprintf("jiekuan_%s.csv", date("Ymd", $time));
$zip_name = sprintf("jiekuan_%s.zip", date("Ymd", $time));

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
        "借款id","借款标题","项目状态","借款期限","还款方式",
        "担保公司","咨询公司","借款人用户名","借款人姓名","借款人id",
        "所属公司","手机号","邮箱","收款人","挂网时间",
        "满标时间","合同签署日","放款日期","最近还款时间","最近还款金额(元)",
        "距离还款日天数(天)","借款金额(元)","放款金额(元)","应还款总额(元)","应还利息(元)",
        "借款人账户余额(元)","借款人总成本(年化)","投资人收益率(年化)","借款担保费率 (年化)","借款担保费率(期间)",
        "借款担保费(元)","借款咨询费率(年化)","借款咨询费率(期间)","借款咨询费(元)","P2P平台借款手续费率(年化)",
        "P2P平台借款手续费率(期间)","P2P平台手续费(元)","机构返利(元)","推荐人返利(元)","投资人返利(元)",
        "先锋集团总收入(元)","P2P平台毛利(元)","先锋集团毛利(元)","标待还总额(元)","机构返点比例",
        "机构返点比例金额(元)"
);
$title = iconv("utf-8", "gbk", implode(',', $title));
fputcsv($fp, explode(',', $title));

$deals_sql = "is_delete = 0 AND deal_status IN (2,4,5) AND parent_id != 0 ORDER BY id DESC";
$all_deals_info = DealModel::instance()->findAll($deals_sql);

$status_conf = array(
        2 => '满标',
        4 => '还款中',
        5 => '已还清',
);

$deal_service = new DealService();
$coupon = new CouponService();
$earning_service = new EarningService();

foreach($all_deals_info as $deal_info){

    $deal_id = $deal_info['id'];
    $deal_status = $deal_info['deal_status'];
    $borrow_user = get_user_data($deal_info['user_id']);//借款人信息

    //合同信息
    $contract_sql = sprintf("deal_id = %d AND user_id = %d", $deal_id, $deal_info['user_id']);
    $contract_info = ContractModel::instance()->findBy($contract_sql);

    if($borrow_user){
        $user_name = $borrow_user['user_name'];//借款人用户名
        $user_real_name = $borrow_user['real_name'];//借款人姓名
        $amount = $deal_info['borrow_amount'];//借款金额
        $status_info = $status_conf[$deal_status];//项目状态
        $loantype = $GLOBALS['dict']['LOAN_TYPE'][$deal_info['loantype']];//还款方式

        $start_time = to_date($deal_info['start_time']);//挂网时间
        $success_time = to_date($deal_info['success_time']);//满标时间
        $money_time = $deal_info['repay_start_time'] ? to_date($deal_info['repay_start_time']) : '';//放款日期
        $next_repay_time = $deal_info['next_repay_time'] && $deal_status == 4 ? to_date($deal_info['next_repay_time'],'Y-m-d') : '';//最近还款时间
        $bank_user = make_user_bank($borrow_user['id']);//借款人银行卡信息

        //担保费期间费率(期间)
        $guarantor_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['guarantee_fee_rate'], $deal_info['repay_time'], false);
        $guarantor_fee_rate_formart = rate_formart($guarantor_fee_rate);

        //平台手续费(期间)
        $loan_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['loan_fee_rate'], $deal_info['repay_time'], false);
        $loan_fee_rate_formart = rate_formart($loan_fee_rate);

        //借款咨询费(期间)
        $consult_fee_rate = Finance::convertToPeriodRate($deal_info['loantype'], $deal_info['consult_fee_rate'], $deal_info['repay_time'], false);
        $consult_fee_rate_formart = rate_formart($consult_fee_rate);

        $consult_fee = ceilfix($amount * floatval($consult_fee_rate) / 100);//借款咨询费
        $guarantee_fee = ceilfix($amount * floatval($guarantor_fee_rate) / 100);//担保费
        $loan_fee = ceilfix($amount * floatval($loan_fee_rate) / 100);//平台手续费
        $fee_amount = ceilfix($amount * (floatval($guarantor_fee_rate) + floatval($loan_fee_rate) + floatval($consult_fee_rate)) / 100);//总手续费

        $left_day = '';//距离还款日天数
        if($deal_info['next_repay_time'] && $deal_status != 5){
            $left_day = ceil(($deal_info['next_repay_time'] - get_gmtime()) / 24 / 3600);
        }

        $contract_time = '';//合同签署日
        if($contract_info && $contract_info['status'] == 1){
            $first_cont_sql = sprintf("deal_id = %d AND pass = 1 ORDER BY create_time DESC LIMIT 1", $deal_id);
            $first_contract = AgencyContractModel::instance()->findBy($first_cont_sql);
            if($first_contract){
                $contract_time = format_date($first_contract['create_time']);//合同签订日
            }
        }
        $all_money = sprintf("%.2f", $earning_service->getRepayMoney($deal_id));//总还款金额
        $interest = $all_money - $amount;//总利息

        //借款期限
        $loan_time = $deal_info['repay_time'].'个月';
        if($deal_info['loantype'] == 5){
            $loan_time = $deal_info['repay_time'].'天';
        }

        //担保公司、咨询公司
        $agency = make_agency_info($deal_info['agency_id']);
        $advisory = make_advisory_info($deal_info['advisory_id']);

        //借款所属公司
        $company_info = make_deal_company($deal_info['user_id']);
        $company_name = empty($company_info) ? '无' : $company_info['name'];

        //最近还款金额
        $next_repay_money = '';
        if($deal_info['next_repay_time']){
            $next_repay_sql = sprintf("deal_id = %d AND repay_time = '%s' AND true_repay_time = 0", $deal_id, $deal_info['next_repay_time']);
            $next_repay = DealRepayModel::instance()->findBy($next_repay_sql);
            if(!isset($next_repay['repay_money'])){
                $repay_sql = sprintf("deal_id = %d AND true_repay_time = 0 ORDER BY repay_time ASC limit 1", $deal_id);
                $next_repay = DealRepayModel::instance()->findBy($repay_sql);
                $next_repay_time = to_date($next_repay['repay_time'],'Y-m-d');//最近还款时间
            }
            $next_repay_money = $next_repay['repay_money'];
        }

        $year_loan_fee_rate = rate_formart($deal_info['loan_fee_rate']);//平台借款手续费率(年化)
        $year_consult_fee_rate = rate_formart($deal_info['consult_fee_rate']);//借款咨询费率(年化)
        $year_guarantee_fee_rate = rate_formart($deal_info['guarantee_fee_rate']);//借款担保费率 (年化)
        $income_fee_rate = rate_formart($deal_info['income_total_rate']);//投资人收益率（年化）
        $sum_all_rate = rate_formart($deal_info['rate'] + $year_loan_fee_rate + $year_consult_fee_rate + $year_guarantee_fee_rate);//借款人总成本 (年化)

        $offline_fee = '-';//机构返利(元)
        $deal_channel_amount_sum = $deal_service->getDealChannelLogMoney($deal_id);//邀请链接返利总额

        //标优惠码相关金额汇总
        $deal_coupon_data = $coupon->getDealCouponAmountData($deal_id);

        $rebate_amount_sum = floatval($deal_coupon_data['rebate_amount_sum']);//投资人返点金额
        $rebate_ratio_amount_sum = floatval($deal_coupon_data['rebate_ratio_amount_sum']);//投资人返点比例金额
        $referer_rebate_amount_sum = floatval($deal_coupon_data['referer_rebate_amount_sum']);//推荐人返点金额
        $referer_rebate_ratio_amount_sum = floatval($deal_coupon_data['referer_rebate_ratio_amount_sum']);//推荐人返点比例金额
        $agency_rebate_ratio_amount_sum = floatval($deal_coupon_data['agency_rebate_ratio_amount_sum']);//机构返点比例金额

        $online_fee = $referer_rebate_amount_sum + $referer_rebate_ratio_amount_sum + $deal_channel_amount_sum;//推荐人返利
        $invitee_fee = $rebate_amount_sum + $rebate_ratio_amount_sum;//投资人返利(元)

        $xf_income = $fee_amount;
        $xf_gross_profit = $xf_income - floatval($offline_fee) - $online_fee - $invitee_fee;
        $p2p_gross_profit = $loan_fee - floatval($offline_fee) - $online_fee - $invitee_fee;

        $left_repay_money = sprintf("%.2f", DealModel::instance()->find($deal_id)->remainRepayMoney());
        $agency_rebate_ratio = sprintf("%.5f", $agency_rebate_ratio_amount_sum / $amount * 100);

        //借款id,借款标题,项目状态,借款期限,还款方式,
        //担保公司,咨询公司,借款人用户名,借款人姓名,借款人id,
        //所属公司,手机号,邮箱,收款人,挂网时间,
        //满标时间,合同签署日,放款日期,最近还款时间,最近还款金额(元),
        //距离还款日天数(天),借款金额(元),放款金额(元),应还款总额(元),应还利息(元),
        //借款人账户余额(元),借款人总成本(年化),投资人收益率(年化),借款担保费率 (年化),借款担保费率(期间),
        //借款担保费(元),借款咨询费率(年化),借款咨询费率(期间),借款咨询费(元),P2P平台借款手续费率(年化),
        //P2P平台借款手续费率(期间),P2P平台手续费(元),机构返利(元),推荐人返利(元),投资人返利(元),
        //先锋集团总收入(元),P2P平台毛利(元),先锋集团毛利(元),"标待还总额","机构返点比例",
        //"机构返点比例金额(元)"

        $row_content = sprintf(
            "%d||%s||%s||%s||%s||%s||%s||\t%s||%s||%d||%s||\t%s||%s||%s||\t%s||\t%s||\t%s||\t%s||\t%s||%s||%s||%s||%s||%s||%.2f||%s||%s||\t%s||\t%s||\t%s||%s||\t%s||\t%s||%s||\t%s||\t%s||%s||%s||%s||%s||%s||%s||%s||%s||\t%s||%s",
            $deal_id,$deal_info['name'],$status_info,$loan_time,$loantype,
            $agency['short_name'],$advisory['short_name'],$user_name,$user_real_name,$deal_info['user_id'],
            $company_name,$borrow_user['mobile'],$borrow_user['email'],$bank_user['card_name'],$start_time,
            $success_time,$contract_time,$money_time,$next_repay_time,$next_repay_money,
            $left_day,$amount,$amount - $fee_amount,$all_money,$interest,
            $borrow_user['money'],$sum_all_rate.'%',$income_fee_rate.'%',$year_guarantee_fee_rate.'%',$guarantor_fee_rate_formart.'%',
            $guarantee_fee,$year_consult_fee_rate.'%',$consult_fee_rate_formart.'%',$consult_fee,$year_loan_fee_rate.'%',
            $loan_fee_rate_formart.'%',$loan_fee,$offline_fee,$online_fee,$invitee_fee,
            $xf_income,$p2p_gross_profit,$xf_gross_profit,$left_repay_money,$agency_rebate_ratio.'%',
            $agency_rebate_ratio_amount_sum
        );

        $row_arr = explode('||', $row_content);
        foreach($row_arr as $row_key => $row_val){
            $row_arr[$row_key] = iconv("utf-8", "gbk", $row_val);
        }
        $fres = fputcsv($fp, $row_arr);
    }
}
fclose($fp);

//发送邮件
FP::import("libs.common.dict");
$email_arr = dict::get("DEAL_BID_EMAIL");

//记录发送日志
$log = array(
        'title' => '每日借款统计',
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
            $title = sprintf("网信理财 %s 借款数据概况", date("Y年m月d日", $time));
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

function rate_formart($rate, $float = 5){
    return sprintf("%.".$float."f", $rate);
}

function get_user_data($user_id){
    $user_id = intval($user_id);
    if($user_id <= 0){
        return array();
    }
    static $user_info = array();
    if(!isset($user_info[$user_id])){
        $user_service = new UserService();
        $user_info[$user_id] = $user_service->getUser($user_id);
    }
    return $user_info[$user_id];
}

function make_agency_info($id){
    $id = intval($id);
    if($id <= 0){
        return array();
    }
    static $agency_info = array();
    if(!isset($agency_info[$id])){
        $agency_sql = sprintf("id = %d AND is_effect = 1 AND type=1", $id);
        $agency_info[$id] = DealAgencyModel::instance()->findBy($agency_sql);;
    }
    return $agency_info[$id];
}

function make_advisory_info($id){
    $id = intval($id);
    if($id <= 0){
        return array();
    }
    static $advisory_info = array();
    if(!isset($advisory_info[$id])){
        $advisory_sql = sprintf("id = %d AND is_effect = 1 AND type=2", $id);
        $advisory_info[$id] = DealAgencyModel::instance()->findBy($advisory_sql);
    }
    return $advisory_info[$id];
}

function make_deal_company($user_id){
    $user_id = intval($user_id);
    if($user_id <= 0){
        return array();
    }
    static $company_info = array();
    if(!isset($company_info[$user_id])){
        $user_company_model = new UserCompanyModel();
        $company_info[$user_id] = $user_company_model->findByUserId($user_id);
    }
    return $company_info[$user_id];
}

function make_user_bank($user_id){
    $user_id = intval($user_id);
    if($user_id <= 0){
        return array();
    }
    static $user_bank = array();
    if(!isset($user_bank[$user_id])){
        $user_bank[$user_id] = UserBankcardModel::instance()->findBy(sprintf("user_id = %d", $user_id));
    }
    return $user_bank[$user_id];
}

