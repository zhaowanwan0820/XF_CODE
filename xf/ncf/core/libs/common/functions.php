<?php

// +----------------------------------------------------------------------
// | firstp2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.firstp2p.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: laijinhai@ucfgroup
// +----------------------------------------------------------------------
// | Update: 2013-10-29
// +----------------------------------------------------------------------




//[CODE_BLOCK_START][system/system_init.php]

FP::import("libs.libs.msgcenter");
require_once APP_ROOT_PATH."system/words.php";

use libs\utils\Finance;
use core\service\deal\DealService;
use core\dao\deal\DealModel;
use core\enum\DealEnum;
use core\dao\jobs\JobsModel;
use NCFGroup\Common\Extensions\Cache\LocalCache;
use NCFGroup\Common\Extensions\Cache\RedisCache;
use NCFGroup\Common\Extensions\Cache\CacheInterface;
use libs\utils\Block;
use libs\utils\Aes;
use libs\utils\Site;
use libs\utils\Logger;
use libs\utils\Rpc;
use libs\rpc\Rpc as RpcRpc;
use libs\common\upload;
use core\service\user\UserLoanRepayStatisticsService;

use NCFGroup\Protos\Contract\RequestGetContractByDealId;
use NCFGroup\Protos\Contract\RequestSendContractStatus;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

use NCFGroup\Common\Library\Idworker;

use core\dao\project\DealProjectModel;
use core\dao\dealqueue\DealParamsConfModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\dealqueue\DealQueueModel;
use libs\sms\SmsServer;
use core\service\user\UserService;
use core\service\msgbox\MsgboxService;
use core\enum\DealExtEnum;
use core\enum\SupervisionEnum;
use core\enum\UserEnum;
use core\enum\EnterpriseEnum;

function app_conf($name)
{
    return isset($GLOBALS['sys_config'][$name]) ? stripslashes($GLOBALS['sys_config'][$name]) : '';
}

function get_http()
{
    if (isset($_SERVER['HTTP_XHTTPS']) && 1 == $_SERVER['HTTP_XHTTPS']) {
        return 'https://';
    } else {
        return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
    }
}
function get_domain()
{
    /* 协议 */
    $protocol = get_http();

    /* 域名或IP地址 */
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))
    {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }
    elseif (isset($_SERVER['HTTP_HOST']))
    {
        $host = $_SERVER['HTTP_HOST'];
    }
    else
    {
        /* 端口 */
        if (isset($_SERVER['SERVER_PORT']))
        {
            $port = ':' . $_SERVER['SERVER_PORT'];

            if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol))
            {
                $port = '';
            }
        }
        else
        {
            $port = '';
        }

        if (isset($_SERVER['SERVER_NAME']))
        {
            $host = $_SERVER['SERVER_NAME'] . $port;
        }
        elseif (isset($_SERVER['SERVER_ADDR']))
        {
            $host = $_SERVER['SERVER_ADDR'] . $port;
        }
    }

    return $protocol . $host;
}


function get_host($needPort = true)
{
    $host = '';

    /* 域名或IP地址 */
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))
    {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }
    elseif (isset($_SERVER['HTTP_HOST']))
    {
        $host = $_SERVER['HTTP_HOST'];
    }
    else
    {
        if (isset($_SERVER['SERVER_NAME']))
        {
            $host = $_SERVER['SERVER_NAME'];
        }
        elseif (isset($_SERVER['SERVER_ADDR']))
        {
            $host = $_SERVER['SERVER_ADDR'];
        }
    }

    if ($needPort) {
        return $host;
    }

    $hostNodes = explode(':', $host);
    return current($hostNodes);
}


//原加密文件翻译出来的结果
//------------------------- start
function init_checker()
{
    $domain_array = array(
        base64_encode(base64_encode('localhost')),
        base64_encode(base64_encode('127.0.0.1')),
        base64_encode(base64_encode('*.firstp2p.com'))
    );
    $str = base64_encode(base64_encode(serialize($domain_array))."|".serialize($domain_array));

    $arr = explode("|",base64_decode($str));
    $arr = unserialize($arr[1]);
    foreach($arr as $k=>$v)
    {
        $arr[$k] = base64_decode(base64_decode($v));
    }
    $host = $_SERVER['HTTP_HOST'];
    $host = explode(":",$host);
    $host = $host[0];
    $passed = false;
    foreach($arr as $k=>$v)
    {
        if(substr($v,0,2)=='*.')
        {
            $preg_str = substr($v,2);
            if(preg_match("/".$preg_str."$/",$host)>0)
            {
                $passed = true;
                break;
            }
        }
    }
    if(!$passed)
    {
        if(!in_array($host,$arr))
        {
            return false;
        }
    }

    return true;
}

//[/CODE_BLOCK_END]





//[CODE_BLOCK_START][system/common.php]

/**
 * PMT年金计算函数
 * @param $i 期间收益率
 * @param $n 期数
 * @param $p 本金
 * @return 每期应还金额
 */
function PMT($i, $n, $p) {
    //return $i * $p * pow((1 + $i), $n) / (1 - pow((1 + $i), $n));
    return $p * $i * pow((1 + $i), $n) / ( pow((1 + $i), $n) -1);
}

/**
 * 消费分期PMT年金计算函数
 * @param $i 第几期
 * @param $repay_times 总还款期数
 * @param $month_rate 月利率
 * @param $total_principal 本金
 * @return float 总还款本金
 */
function installmentPMT($i, $repay_times,$month_rate, $total_principal) {
 return  $total_principal * $month_rate * pow((1 + $month_rate), $i - 1) / (pow((1 + $month_rate), $repay_times) - 1);
}

/**
 * 年化收益率计算
 * @param $pn 期末金额
 * @param $n 期数
 * @param $p 本金
 * @return 期间收益率
 */
function RATE($pn, $n, $p) {
    return pow($pn/$p, 1/$n)-1;
}

//获取真实路径
function get_real_path()
{
    return APP_ROOT_PATH;
}

//获取GMTime
function get_gmtime()
{
    return (time() - date('Z'));
}

function format_date($utc_time, $format = 'Y-m-d H:i:s') {
    if (empty ( $utc_time )) {
        return '';
    }
    return date ($format, $utc_time );
}

function to_date($utc_time, $format = 'Y-m-d H:i:s') {
    if (empty ( $utc_time )) {
        return '';
    }
    $timezone = intval(app_conf('TIME_ZONE'));
    $time = $utc_time + $timezone * 3600;
    return date ($format, $time );
}

function to_timespan($str, $format = 'Y-m-d H:i:s')
{
    $timezone = intval(app_conf('TIME_ZONE'));
    //$timezone = 8;
    $time = intval(strtotime($str));
    if($time!=0)
        $time = $time - $timezone * 3600;
    return $time;
}

/**
 * 将 GMT 的时间戳 加上 配置文件中的时差
 */
function timestamp_to_conf_zone($timestamp)
{
    return intval($timestamp) + intval(app_conf('TIME_ZONE')) * 3600;
}

/**
 * 下个还款日
 */
function next_replay_month($time){
    $y = to_date($time,"Y");
    $m = to_date($time,"m");
    $d = to_date($time,"d");
    if($m == 12){
        ++$y;
        $m = 1;
    }
    else{
        ++$m;
    }

    return to_timespan($y."-".$m."-".$d,"Y-m-d");
}

/**
 * 下个还款日
 */
function next_replay_month_with_delta($time, $delta_month_time){
    $y = to_date($time,"Y");
    $m = to_date($time,"m");
    $d = to_date($time,"d");
    $target_m = $m + $delta_month_time;

    $year = floor($target_m / 12);
    $y += $year;

    $m = $target_m % 12;
    if ($m == 0) {
        $m = 12;
        $y--;
    }

    return to_timespan($y."-".$m."-".$d,"Y-m-d");
}

function next_replay_day_with_delta($time, $day){
    $y = to_date($time,"Y");
    $m = intval(to_date($time,"m"));
    $d = intval(to_date($time,"d"));

    return to_timespan($y."-".$m."-".$d,"Y-m-d") + $day*24*60*60;

}

//获取客户端IP
function get_client_ip() {
    if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
        $ip = getenv ( "HTTP_CLIENT_IP" );
    else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
        $ip = getenv ( "HTTP_X_FORWARDED_FOR" );
    else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
        $ip = getenv ( "REMOTE_ADDR" );
    else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
        $ip = $_SERVER ['REMOTE_ADDR'];
    else
        $ip = "unknown";
    return ($ip);
}

//获取IP 方式先后顺序为HTTP_X_FORWARDEN_FOR,HTTP_CLIENT_IP
function get_real_ip()
{
    if (!empty($_SERVER['HTTP_WAP_CLIENT_IP'])) {
        $ips = explode(', ', $_SERVER['HTTP_WAP_CLIENT_IP']);
        if (!empty($ips[0])) {
            return $ips[0];
        }
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if (!empty($ips[0])) {
            return $ips[0];
        }
    }

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    return 'unknown';
}

//过滤注入
function filter_injection(&$request)
{
    $pattern = "/(select[\s])|(insert[\s])|(update[\s])|(delete[\s])|(from[\s])|(where[\s])/i";
    foreach($request as $k=>$v)
    {
        if(preg_match($pattern,$k,$match))
        {
            die("SQL Injection denied!");
        }

        if(is_array($v))
        {
            filter_injection($v);
        }
        else
        {

            if(preg_match($pattern,$v,$match))
            {
                die("SQL Injection denied!");
            }
        }
    }

}

//过滤请求
function filter_request(&$request)
{
    if(MAGIC_QUOTES_GPC)
    {
        foreach($request as $k=>$v)
        {
            if(is_array($v))
            {
                filter_request($v);
            }
            else
            {
                $request[$k] = stripslashes(trim($v));
            }
        }
    }

}

function adddeepslashes(&$request)
{

    foreach($request as $k=>$v)
    {
        if(is_array($v))
        {
            adddeepslashes($v);
        }
        else
        {
            $request[$k] = addslashes(trim($v));
        }
    }
}

//request转码
function convert_req(&$req)
{
    foreach($req as $k=>$v)
    {
        if(is_array($v))
        {
            convert_req($req[$k]);
        }
        else
        {
            if(!is_u8($v))
            {
                $req[$k] = iconv("gbk","utf-8",$v);
            }
        }
    }
}

function is_u8($string)
{
    return preg_match('%^(?:
        [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $string);
}

//清除缓存
function clear_cache()
{
    //系统后台缓存
    syn_dealing();
    clear_dir_file(get_real_path()."runtime/admin/Cache/");
    clear_dir_file(get_real_path()."runtime/admin/Data/_fields/");
    clear_dir_file(get_real_path()."runtime/admin/Temp/");
    clear_dir_file(get_real_path()."runtime/admin/Logs/");
    @unlink(get_real_path()."runtime/admin/~app.php");
    @unlink(get_real_path()."runtime/admin/~runtime.php");
    @unlink(get_real_path()."runtime/admin/lang.js");
    @unlink(get_real_path()."runtime/app/config_cache.php");


    //数据缓存
    clear_dir_file(get_real_path()."runtime/app/data_caches/");
    clear_dir_file(get_real_path()."runtime/app/db_caches/");
    $GLOBALS['cache']->clear();
    clear_dir_file(get_real_path()."runtime/data/");

    //模板页面缓存
    clear_dir_file(get_real_path()."runtime/app/tpl_caches/");
    clear_dir_file(get_real_path()."runtime/app/tpl_compiled/");
    @unlink(get_real_path()."runtime/app/lang.js");

    //脚本缓存
    clear_dir_file(get_real_path()."runtime/statics/");



}
function clear_dir_file($path)
{
    if ( $dir = opendir( $path ) )
    {
        while ( $file = readdir( $dir ) )
        {
            $check = is_dir( $path. $file );
            if ( !$check )
            {
                @unlink( $path . $file );
            }
            else
            {
                if($file!='.'&&$file!='..')
                {
                    clear_dir_file($path.$file."/");
                }
            }
        }
        closedir( $dir );
        rmdir($path);
        return true;
    }
}

function check_install()
{
    return;
    if(!file_exists(get_real_path()."public/install.lock"))
    {
        clear_cache();
        header('Location:'.APP_ROOT.'/install');
        exit;
    }
}

function syn_brand_status($id)
{
    //同步品牌状态
    $brand_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."brand where id = ".$id);
    //1 无开始与结束时间
    if($brand_info['begin_time']==0&&$brand_info['end_time']==0)
    {
        if($deal_info['time_status']!=0)
        {
            $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
        }
        return 0;
    }

    //2 无开始时间，有结束时间
    if($brand_info['begin_time']==0&&$brand_info['end_time']!=0)
    {

        //进行中
        if($brand_info['end_time']>get_gmtime())
        {
            if($brand_info['time_status']!=0)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
            }
            return 0;
        }
        //过期
        if($brand_info['end_time']<=get_gmtime())
        {
            if($brand_info['time_status']!=2)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 2 where id =".$id);
            }
            return 2;
        }
    }

    //3 有开始时间，无结束时间
    if($brand_info['begin_time']!=0&&$brand_info['end_time']==0)
    {
        //进行中
        if($brand_info['begin_time']<=get_gmtime())
        {
            if($brand_info['time_status']!=0)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
            }
            return 0;
        }
        //未开始
        if($brand_info['begin_time']>get_gmtime())
        {
            if($brand_info['time_status']!=1)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 1 where id =".$id);
            }
            return 1;
        }
    }

    //4 开始结束都有时间
    if($brand_info['begin_time']!=0&&$brand_info['end_time']!=0)
    {
        //未开始
        if($brand_info['begin_time']>get_gmtime())
        {
            if($brand_info['time_status']!=1)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 1 where id =".$id);
            }
            return 1;
        }
        //进行中
        if($brand_info['begin_time']<=get_gmtime()&&$brand_info['end_time']>get_gmtime())
        {
            if($brand_info['time_status']!=0)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 0 where id =".$id);
            }
            return 0;
        }
        //过期

        if($brand_info['end_time']<=get_gmtime())
        {
            if($brand_info['time_status']!=2)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."brand set time_status = 2 where id =".$id);
            }
            return 2;
        }
    }
}

function ceilfix($number, $bits=2) {
    $t = pow(10, $bits);
    //echo $t,'<br>';
    if($t==0) return 0;
    return floatval(round($number * $t)) / $t;
}

//同步XXID的团购商品的状态,time_status,buy_status
function syn_deal_status($id) {
    $deal_dao = \core\dao\deal\DealModel::instance()->find($id);
    $deal_info = $deal_dao->getRow();
    $deal_info['remain_time'] = $deal_info['start_time'] + $deal_info['enddate'] * 24 * 3600 - get_gmtime();
    $deal_info['progress_point'] = $deal_dao['point_percent'] * 100;
    $deal_info['name'] = get_deal_title($deal_info['name'], '', $deal_info['id']);

    if($deal_info['deal_status'] == 5){
        return true;
    }

    // 为减少数据库访问，写之前先将对象的内容清空
    $deal_loan_data = \core\dao\deal\DealLoadModel::instance()->getLoadCount($id);
    if ($deal_info['buy_count'] != $deal_loan_data['buy_count']) {
        $deal_dao->buy_count = $deal_loan_data['buy_count'];
    }
    if ($deal_info['load_money'] != $deal_loan_data['load_money']) {
        $deal_dao->load_money = $deal_loan_data['load_money'];
    }

    if ($deal_info['deal_status']!=3) {
        $data['progress_point'] = $deal_info['point_percent'] * 100;
        if ($deal_info['progress_point'] >=100 || $data['progress_point'] >=100) { // 处理以还清状态逻辑

            // 提前还款
            $deal_prepay = \core\dao\repay\DealPrepayModel::instance()->findBy("`deal_id` = '{$id}' AND `status`='1'");
            if ($deal_prepay) {
                $deal_dao->deal_status = 5;
                $deal_dao->last_repay_time = $deal_prepay['prepay_time'];
                $deal_dao->save();
                return true;
            }
            if (($deal_info['deal_status']==4&&$deal_info['repay_start_time']>0) || ($deal_info['deal_status']==2 && $deal_info['repay_start_time']>0 && $deal_info['repay_start_time'] <= get_gmtime())){
                //判断是否是借款状态还是已还款完毕

                // 根据借款人的还款记录获得他实际已经还了的钱
                $sql = "SELECT sum(repay_money) As all_repay_money ,MAX(repay_time) AS last_repay_time FROM ".DB_PREFIX."deal_repay WHERE deal_id=$id and status>0";

                $repay_info =  $GLOBALS['db']->getRow($sql);
                if($repay_info){
                    // 总共已还的钱
                    $deal_dao->repay_money = $repay_info['all_repay_money'];
                    // 最近的一次还款时间
                    $deal_dao->last_repay_time = $repay_info['last_repay_time'];
                }
                //判断是否完成还款
                FP::import('libs.common.deal');
                FP::import('app.deal');

                if ($deal_info['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
                    $has_repay_money = get_deal_total_repay_money_month_interest($deal_info['loantype'], $deal_info['repay_time'], $deal_info['borrow_amount'], $deal_info['rate']);
                } elseif ($deal_info['id'] > $GLOBALS['dict']['OLD_DEAL_ID']) {
                    $has_repay_money = get_deal_total_repay_money_from_pmt($deal_info['id']);
                } else {
                    $has_repay_money  = get_deal_total_repay_money($deal_info['loantype'], $deal_info['repay_time'], $deal_info['borrow_amount'], $deal_info['rate']);
                }

                if ($deal_dao->repay_money > 0 && $deal_dao->repay_money >= round($has_repay_money,2)) {
                    $deal_dao->deal_status = 5;
                } elseif ($deal_info['deal_status'] != 4 && $deal_info['deal_status'] != 5) {
                    $deal_dao->deal_status = 4;
                }
            } else {
                //获取最后一次的投资记录
                $deal_dao->success_time = $GLOBALS['db']->getOne("SELECT create_time FROM ".DB_PREFIX."deal_load WHERE deal_id=$id ORDER BY id DESC");
                $deal_dao->deal_status = 2;
            }
        }
    }
    if (!empty($deal_dao->_row_new)){
        $res = $deal_dao->save();
        if ($res == false){
            throw new \Exception('更新标的状态失败 syn_deal_status');
        }
    }


    //流标
    if($deal_info['deal_status'] ==3 || isset($data['deal_status']) && $data['deal_status']==3){
        $dealService = new DealService();
        // 添加到jobs
        $function = '\core\service\deal\DealService::failDeal';
        $param = array('deal_id' => $id);
        $res = JobsModel::instance()->addJob($function, $param);
        if ($res == false){
            throw new \Exception('流标jobs  失败');
        }
    }
}


/**
 * 按月付息总的还款金额
 *
 * @return void
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年10月10日 11:28:05
 **/
function get_deal_total_repay_money_month_interest($loantype, $repay_period, $total_loan_amount, $rate) {
    //每期应还利息
    $money_per_period = get_deal_repay_money_month_interest($loantype, $repay_period, $total_loan_amount, $rate);
    //总的还款金额=每期应还利息*还款次数+本金总金额
    return $repay_period * $money_per_period + $total_loan_amount;
}
/**
 * 根据还款类型 以及 还款周期，获得总共需要还的本金和利息
 */
function get_deal_total_repay_money($loantype, $repay_period, $total_loan_amount, $rate) {
    $money_per_period = get_deal_repay_money($loantype, $repay_period, $total_loan_amount, $rate);
    if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        $count = $repay_period / 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        $count = $repay_period;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        $count = 1;
    }
    return $count * $money_per_period;
}

/**
 * 根据还款类型 以及 还款周期，获得每个周期需要还的本金和利息
 *
 * @author edit by wenyanlei  2013-8-15
 * @param $repay_mode 借款类型
 * @param $repay_period 借款期限
 * @param $total_loan_amount 借款金额
 * @param $rate 借款利率
 * @return float
 */
function get_deal_repay_money($repay_mode, $repay_period, $total_loan_amount, $rate) {
    if($repay_mode == 3){//到期支付本金收益
        return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period)));
    }elseif($repay_mode == 2){//按月等额还款
        return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period))/$repay_period);
    }elseif($repay_mode == 1){//按季等额还款
        return ceilfix($total_loan_amount*(1+($rate/100/12*$repay_period))/($repay_period/3));
    }
    return 0;
}

/**
 * 计算按月支付收益到期还本类型的每期还款额
 *
 * @param $repay_mode 借款类型
 * @param $repay_period 借款期限
 * @param $total_loan_amount 借款金额
 * @param $repay_mode 借款类型
 * @param $rate 借款利率
 * @param $is_last 是否最后一次还款
 * @param $month_interest 每月还款利息部分
 * @param $month_loan_amount 每月还款本金部分
 * @return float
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com> 2013年10月10日 11:09:13
 **/
function get_deal_repay_money_month_interest($repay_mode, $repay_period, $total_loan_amount, $rate, $is_last = false, &$month_interest= null, &$month_loan_amount = null) {
    $month_loan_amount = $total_loan_amount / $repay_period; //计算每月应还本金
    $month_amount = $total_loan_amount*(1+($rate/100/12*$repay_period))/$repay_period; //每月应还总额
    $month_interest = $month_amount - $month_loan_amount; //每月应还利息
    if($is_last) {
        return ceilfix($month_interest + $total_loan_amount);
    } else {
        return ceilfix($month_interest);
    }
}

/**
 * 根据还款类型 以及 还款周期，获得总共需要还的本金和利息
 */
function get_deal_total_repay_money_from_pmt($deal_id) {
    $finance = new Finance();
    $info = $finance->getPmtByDealId($deal_id);
    return ceilfix($info['pmt']) * $info['repay_num'];
}


/**
 * 用户账户统计
 * @param unknown $uid 用户uid
 * @param string $is_cache
 * @param string $make_cache
 */
function user_statics($user_id,$is_cache = false,$make_cache=false,$site_id=1){
    if(!$user_id){
        return false;
    }
    //$dts =  new core\service\DtAssetService();
    //$duotouAsset = $dts->getDtAsset($user_id);

    $data = array();
    //总借出笔数    edit by wangyiming 流标的投资不记录在总数内，d.deal_status!=3
    $sql = "SELECT COUNT(*) AS load_count,SUM(d_l.money) AS load_money FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS d_l ON d_l.deal_id = d.id WHERE d.deal_status in (4,5) AND d.loantype != 7 AND d.is_delete =0 AND parent_id!=0 AND d_l.user_id = {$user_id}";
    $u_load = $GLOBALS['db']->get_slave()->getRow($sql);
    $data['load_count'] = $u_load['load_count'];
    //总借出金额  总投资额
    $data['load_money'] = $u_load['load_money'];

    $res =  UserLoanRepayStatisticsService::getUserAssets($user_id);
    $data['load_repay_money'] = $res['load_repay_money'];
    $data['load_earnings'] = $res['load_earnings'];
    $data['load_tq_impose'] = $res['load_tq_impose'];
    $data['load_yq_impose'] = $res['load_yq_impose'];
    $u_stay['principal'] = $res['norepay_principal']; // 待收本金包含通知贷
    $u_stay['interest'] = $res['norepay_interest']; // 待收利息包含通知贷

    //大金所相关统计
    $data['js_norepay_principal'] = 0;//交易所待回本金
    $data['js_norepay_earnings'] = 0;//交易所待收收益
    $data['js_total_earnings'] = 0;//交易所累计收益

    //贴息累计金额 ，add by wangzhen3
    $data['interest_extra_money'] = 0;

    // 待收本金去除公益标 #2812 jinhaidong 2015-09-15
    $data['principal'] = DealModel::instance()->floorfix($u_stay['principal']);
    //待还利息
    $data['interest'] = $u_stay['interest'];

    //已赚总额 总收益 ,累计收益增加贴息收益
    $data['earning_all'] = Finance::addition(array($data['load_tq_impose'], $data['load_earnings'], $data['load_yq_impose'] ,$data['interest_extra_money']), 5);
    //待还资产总额
    $data['stay'] = Finance::addition(array($data['principal'], $data['interest']), 5);

    // 后台展示用
    $data['u_stay'] = $u_stay;

    $data['new_stay'] = $data['principal']; // 去掉待收收益
    $data['p2p_principal'] = $u_stay['principal']; // p2p 待收本金

    // 增加多投宝资产
    $data['new_stay'] = bcadd($data['new_stay'], $res['dt_load_money'], 2);
    //$data['principal'] = bcadd($data['principal'],$res['dt_load_money'],2);       //待收本金+多投宝
    //$data['load_money'] = bcadd($data['load_money'],$res['dt_load_money'],2);  //总投资额+多投宝
    $data['dt_norepay_principal'] = $res['dt_norepay_principal']; // 多投宝剩余金额
    $data['dt_remain'] = bcsub($res['dt_norepay_principal'], $res['dt_load_money'], 2);
    $data['dt_load_money'] = $res['dt_load_money'];

    $data['norepay_principal'] = isset($res['norepay_principal']) ? $res['norepay_principal'] : 0;//存管网贷待回本金
    $data['norepay_interest'] = isset($res['norepay_interest']) ? $res['norepay_interest'] : 0;//存管网贷待收收益

    //存管网贷
    $data['cg_norepay_principal'] = isset($res['cg_norepay_principal']) ? $res['cg_norepay_principal'] : 0;//存管网贷待回本金
    $data['cg_norepay_earnings'] = isset($res['cg_norepay_earnings']) ? $res['cg_norepay_earnings'] : 0;//存管网贷待收收益
    $data['cg_total_earnings'] = isset($res['cg_total_earnings']) ? $res['cg_total_earnings'] : 0;//存管网贷累计收益
    return $data;
}
//更新用户统计
function sys_user_status($user_id,$is_cache = false,$make_cache=false,$site_id=1){
    if($user_id == 0)
        return ;
    $data = false;
    /* if($make_cache == false){
        if($is_cache == true){
            $key = md5("USER_STATICS_".$user_id);
            $data = load_dynamic_cache($key);
        }
    } */
    if($data==false){

        //当前站点id
        $site_id = app_conf('TEMPLATE_ID');

        //留言数
        $data['dp_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."message WHERE user_id=$user_id AND is_effect = 1");
        //总借款额
        $data['borrow_amount'] = $GLOBALS['db']->getOne("SELECT sum(borrow_amount) FROM ".DB_PREFIX."deal WHERE deal_status in(4,5) AND user_id=$user_id AND publish_wait = 0 AND parent_id != 0");
        //已还本息
        $data['repay_amount'] = $GLOBALS['db']->getOne("SELECT sum(repay_money) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id");

        //发布借款笔数
        $data['deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal WHERE user_id=$user_id AND publish_wait = 0 AND parent_id != 0 AND is_delete = 0 AND is_effect=1");
        //成功借款笔数
        $data['success_deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal WHERE deal_status in (4,5) AND user_id=$user_id AND publish_wait = 0 AND parent_id != 0");
        //还清笔数
        $data['repay_deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal WHERE deal_status = 5 AND user_id=$user_id AND publish_wait = 0 AND parent_id != 0");
        //未还清笔数
        $data['wh_repay_deal_count'] = $data['success_deal_count'] - $data['repay_deal_count'];
        //提前还清笔数
        $data['tq_repay_deal_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_inrepay_repay WHERE user_id=$user_id");
        //正常还清笔数
        $data['zc_repay_deal_count'] = $data['repay_deal_count'] - $data['tq_repay_deal_count'];
        //加权平均借款利率
        $data['avg_rate'] = $GLOBALS['db']->getOne("SELECT sum(rate)/count(*) FROM ".DB_PREFIX."deal WHERE deal_status in (4,5) AND user_id=$user_id AND publish_wait = 0");
        //平均每笔借款金额
        $data['avg_borrow_amount'] = $data['success_deal_count'] > 0 ? ($data['borrow_amount'] / $data['success_deal_count']) : 0;

        //逾期本息
        $data['yuqi_amount'] = $GLOBALS['db']->getOne("SELECT (sum(repay_money) + sum(impose_money)) as new_amount FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status in(2,3)");
        //逾期费用
        $data['yuqi_impose'] = $GLOBALS['db']->getOne("SELECT sum(repay_money) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status in(2,3)");

        //逾期次数
        $data['yuqi_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status = 2");
        //严重逾期次数
        $data['yz_yuqi_count'] = $GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."deal_repay WHERE user_id=$user_id AND status = 3");

        //待还本息
        $data['need_repay_amount'] = 0;
        //待还管理费
        $data['need_manage_amount'] = 0;

        $deal_loan_repay_model = new \app\models\dao\DealLoanRepay();
        $arr_total_loan_money = $deal_loan_repay_model->getTotalLoanMoney($user_id);
        //已回收本息
        $data['load_repay_money'] = $arr_total_loan_money['loan_repay_money'];
        //已赚利息
        $data['load_earnings'] = $arr_total_loan_money['loan_earning'];
        //已赚提前还款违约金
        $data['load_tq_impose'] = $deal_loan_repay_model->getTotalCompenstion($user_id);
        //已赚逾期罚息
        $data['load_yq_impose'] = $deal_loan_repay_model->getTotalImposeRepay($user_id);

        //借出加权平均收益率
        $data['load_avg_rate'] = $GLOBALS['db']->getOne("SELECT sum(rate)/count(*) FROM ".DB_PREFIX."deal_load dl LEFT JOIN ".DB_PREFIX."deal d ON d.id=dl.deal_id WHERE d.deal_status in(4,5) AND dl.user_id=$user_id");

        //总借出笔数    edit by wangyiming 流标的投资不记录在总数内，d.deal_status!=3
        $u_load = $GLOBALS['db']->getRow("SELECT count(*) as load_count,sum(dl.money) as load_money FROM ".DB_PREFIX."deal_load dl LEFT JOIN ".DB_PREFIX."deal d ON d.id=dl.deal_id WHERE dl.site_id=$site_id AND dl.user_id=$user_id AND d.parent_id!=0 AND d.deal_status!=3");
        $data['load_count'] = $u_load['load_count'];
        //总借出金额
        $data['load_money'] = $u_load['load_money'];


        //已回收笔数
        $sql = "SELECT count(*)  FROM ".DB_PREFIX."deal_load dl LEFT JOIN ".DB_PREFIX."deal d ON d.id=dl.deal_id WHERE dl.site_id=$site_id AND d.deal_status =5 AND dl.user_id=$user_id AND dl.deal_parent_id!=0";

        $data['reback_load_count'] = $GLOBALS['db']->getOne($sql);

        //待回收笔数
        $data['wait_reback_load_count'] = $data['load_count'] - $data['reback_load_count'];

        //待回收本息
        $data['load_wait_repay_money'] = 0;

        if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."user_sta WHERE user_id=".$user_id) > 0)
            $GLOBALS['db']->autoExecute(DB_PREFIX."user_sta",$data,"UPDATE","user_id=".$user_id);
        else{
            $data['user_id'] = $user_id;
            $GLOBALS['db']->autoExecute(DB_PREFIX."user_sta",$data,"INSERT");
        }

        if($data['deal_count'] > 0 || $data['load_count']){
            if($data['deal_count'] > 0)
                $u_data['is_borrow_in'] = 1;
            if($data['load_count'] > 0)
                $u_data['is_borrow_out'] = 1;
            $GLOBALS['db']->autoExecute(DB_PREFIX."user",$u_data,"UPDATE","id=".$user_id);
        }
        if($is_cache == true || $make_cache == true){
            set_dynamic_cache(false,$data);
        }
    }
    return $data;
}

//发放团购券
function send_deal_coupon($deal_coupon_id)
{
    $GLOBALS['db']->query("update ".DB_PREFIX."deal_coupon set is_valid = 1 where id = ".$deal_coupon_id." and user_id <> 0 and is_delete = 0 and is_valid = 0");
    $rs = $GLOBALS['db']->affected_rows();
    if($rs)
    {
        //发邮件团购券
        send_deal_coupon_mail($deal_coupon_id);
        //发短信团购券
        send_deal_coupon_sms($deal_coupon_id);
    }
}

//发送流标通知邮件
function send_deal_faild_mail($deal_id,$deal_info=false,$user_id){
    send_full_failed_deal_message($deal_id, "failed");
    /*
    if(!$deal_info && $deal_id ==0)
        return false;

    if(app_conf('MAIL_ON')==0)
        return false;

    if(!$deal_info)
        $deal_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);


    if(intval($deal_info['is_send_bad_msg'])==1)
        return false;

    $msg_conf = get_user_msg_conf($user_id);

    if($msg_conf['mail_myfail'] == 1 || !$msg_conf){
        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$deal_info['user_id']);
        $tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_DEAL_FAILED'");
        $tmpl_content = $tmpl['content'];

        $notice['user_name'] = $user_info['user_name'];
        $notice['deal_name'] = $deal_info['name'];
        $notice['deal_publish_time'] = to_date($deal_info['create_time'],"Y年m月d日");
        $notice['site_name'] = app_conf("SHOP_TITLE");
        $notice['site_url'] = get_domain().APP_ROOT;
        $notice['send_deal_url'] = get_domain().url("index","borrow");
        $notice['help_url'] = get_domain().url("index","helpcenter");
        $notice['msg_cof_setting_url'] = get_domain().url("index","uc_msg#setting");
        $notice['bad_msg'] = $deal_info['bad_msg'];

        $GLOBALS['tmpl']->assign("notice",$notice);

        $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
        $msg_data['dest'] = $user_info['email'];
        $msg_data['send_type'] = 1;
        $msg_data['title'] = "您的借款列表“".$deal_info['name']."”已流标！";
        $msg_data['content'] = addslashes($msg);
        $msg_data['send_time'] = 0;
        $msg_data['is_send'] = 0;
        $msg_data['create_time'] = get_gmtime();
        $msg_data['user_id'] = $user_info['id'];
        $msg_data['is_html'] = $tmpl['is_html'];
        $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
    }

    //获取投资列表
    $load_user_list = $GLOBALS['db']->getAll("SELECT user_name,user_id,create_time FROM ".DB_PREFIX."deal_load WHERE deal_id=".$deal_info['id']);
    if($load_user_list){
        $load_tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_LOAD_FAILED'");
        foreach($load_user_list as $k=>$v){
            $user_info = $GLOBALS['db']->getRow("select email from ".DB_PREFIX."user where id = ".$v['user_id']);
            $load_msg_conf = get_user_msg_conf($v['user_id']);
            if($load_msg_conf['mail_myfail'] == 1){
                $tmpl_content = $load_tmpl['content'];
                $notice['user_name'] = $v['user_name'];
                $notice['deal_name'] = $deal_info['name'];
                $notice['deal_url'] = get_domain().$deal_info['url'];
                $notice['deal_load_time'] = to_date($v['create_time'],"Y年m月d日");
                $notice['site_name'] = app_conf("SHOP_TITLE");
                $notice['site_url'] = get_domain().APP_ROOT;
                $notice['help_url'] = get_domain().url("index","helpcenter");
                $notice['msg_cof_setting_url'] = get_domain().url("index","uc_msg#setting");
                $notice['bad_msg'] = $deal_info['bad_msg'];

                $GLOBALS['tmpl']->assign("notice",$notice);

                $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
                $msg_data['dest'] = $user_info['email'];
                $msg_data['send_type'] = 1;
                $msg_data['title'] = "您的所投的借款列表“".$deal_info['name']."”已流标！";
                $msg_data['content'] = addslashes($msg);
                $msg_data['send_time'] = 0;
                $msg_data['is_send'] = 0;
                $msg_data['create_time'] = get_gmtime();
                $msg_data['user_id'] =  $v['user_id'];
                $msg_data['is_html'] = $load_tmpl['is_html'];
                $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
            }
        }
    }
     */
}

/**
 * 给 出借人 发送投标完成 |投标放款通知 caolong 2013-12-25
 * @param unknown $deal
 * @param unknown $type
 * @param string $money
 * @return boolean|Ambigous <number, boolean>
 */
function send_tender_deal_message($deal,$type,$money='',$load_id='', $siteId = 1){
    if(empty($deal)) return false;

    // 智多鑫标的不推送消息
    $dealService = new DealService();
    if ($dealService->isDealDT($deal['id']) === true) {
        return true;
    }

    $site_host = get_deal_domain($deal['id']);
    switch ($type) {
    case 'tender':
        $mail_title          = "投标完成提示";
        $mail_tpl            = "TPL_DEAL_TENDER_EMAIL";
        $sms_tpl             = "TPL_DEAL_TENDER_SMS";
        $is_noticel          = 18;
        break;
    case 'loan':
        $mail_title          = "投标放款提示";
        $mail_tpl            = "TPL_DEAL_LOAN_EMAIL";
        $sms_tpl             = "TPL_DEAL_LOAN_SMS";
        $is_noticel          = 19;
        break;
    }

    //url("index","deal",array("id"=>$deal['id']));
    ##################### 给出借人发送消息  ########################
    if($type == 'tender' ) {
        $content = '<p>您已向“'.$deal['name'].'”项目投标，投资款'.$money.'元';
        $sql = "SELECT * FROM ".DB_PREFIX."deal_load WHERE id =".$load_id;
        $load_info = $GLOBALS['db']->getRow($sql);
        if(!empty($load_info)) {
            // TODO 发送站内信
            //send_user_msg($mail_title,$content,0,$load_info['user_id'],get_gmtime(),0,true,$is_noticel);
        }
    }else if($type == 'loan' ){
        $sql = "SELECT * FROM ".DB_PREFIX."deal_load WHERE deal_id =".$deal['id'];
        $load_list = $GLOBALS['db']->getAll($sql);

        $msgcenter = new Msgcenter();
        foreach ((array)$load_list as $key=>$val) {
            $content = '<p>您投资的“<a href="'.$deal['url'].'">'.$deal['name'].'</a>”项目已成交，投资款'.number_format(trim($val['money']), 2).'元已划至融资方账户中。';
            send_user_msg($mail_title,$content,0,$val['user_id'],get_gmtime(),0,true,$is_noticel);

            // JIRA#1099 放款发短信
            $data = array(
                'deal_name' => msubstr($deal['name'], 0, 9),
                'repay_start_time' => to_date(get_gmtime(), 'm-d H:i'),
            );
            // SMSSend 放款短信
            $user = \core\service\user\UserService::getUserById($val['user_id'],'mobile');
            $_mobile = $user['mobile'];
            if ($user['user_type'] == \core\enum\UserEnum::USER_TYPE_ENTERPRISE)
            {
                $_mobile = 'enterprise';
            }
            $msgcenter->setMsg($_mobile, $user['id'], $data, 'TPL_DEAL_LOAN_SMS');
        }

        $msgcenter->save();
    }
    return true;
}

/**
 * 发送满标or流标相关Message
 *
 * @param  $deal 标数据
 * @param  $type 满标或流标
 * @return 写入数据数量
 */
function send_full_failed_deal_message($deal,$type){

    // 智多鑫标的不发消息
    $dealService = new DealService();
    if ($dealService->isDealDT($deal['id']) === true) {
        return true;
    }
    $site_host = get_deal_domain($deal['id']);

    if($type == 'full'){
        $mail_title = "满标";
        $message_deal_status = "满标";
        if(!empty($deal['contract_tpl_type'])){
            $message_deal_status .= "，请到个人中心查看合同并确认！";
        }
        $mail_tpl = "TPL_DEAL_FULL_EMAIL";
        $sms_tpl = "TPL_SMS_DEAL_FULL";
    }elseif ($type == 'failed'){
        $mail_title = "流标提示";
        $message_deal_status = "流标";
        $mail_tpl = "TPL_DEAL_FAILED_MAIL";
        $sms_tpl = "TPL_DEAL_FAILED_SMS";
    }

    $deal['url'] = '/d/'.Aes::encryptForDeal($deal['id']);

    $msgBox = new MsgboxService();
    ##################### 给出借人发送消息  ########################
    //获取出借人的信息 非预约投资
    $load_user_list = $GLOBALS['db']->getAll("SELECT * FROM ".DB_PREFIX."deal_load WHERE deal_id =".$deal['id']." AND source_type != " . DealLoadModel::$SOURCE_TYPE['reservation'] . " GROUP BY user_id");
    $loan_user_id_collection = array();
    $emailData = array();
    $arr_user = array();
    foreach ($load_user_list as $k => $v){
        //站内信
        $content = "<p>您投资的融资项目“<a href='".$deal['url']."'>".$deal['name']."</a>”已经".$message_deal_status;

        if ($type == "full" && !in_array($v['user_id'], $loan_user_id_collection)) {
            if ($deal['loantype'] != 7) {
                $content = sprintf("您投资的“%s”已经满标，等待放款后开始计息。", $deal['name']);
            } else {
                $content = sprintf("您捐赠的公益标“%s”已完成捐赠，感谢您的爱心。", $deal['name']);
            }
            $msgBox->create($v['user_id'], 16, $mail_title, $content);
        } else {
            $load_user_info = UserService::getUserById($v['user_id'], 'email,mobile,user_type');

            //发送邮件
            $notice_mail = array(
                'user_name' => $v['user_deal_name'],
                'deal_url' => $site_host . $deal['url'],
                'deal_name' => $deal['name'],
                'help_url' => $site_host . url("index", "helpcenter"),
                'site_url' => $site_host . $deal['url'],
                'site_name' => app_conf("SHOP_TITLE"),
                'msg_cof_setting_url' => $site_host . url("index", "uc_msg#setting"),
                'do' => "投资的",
                'send_deal_url' => $site_host . url('index', 'borrow#aboutborrow'),
                'deal_load_time' => to_date($v['create_time'], "Y年m月d日"),
            );

            //发送短信
            $notice_sms = array(
                'account_title' => UserEnum::MSG_FOR_USER_ACCOUNT_TITLE,
                'title' => msubstr($deal['name'], 0, 9),
                'money' => format_price($v['money']),
            );
            //只给流标已返还的发送消息
            if ( $v['is_repay'] == 1 ) {
                $emailData[$k] = array('userEmail' => $load_user_info['email'], 'userId' => $v['user_id'], 'userId' => $notice_mail, 'tplName' => $mail_tpl, 'title' => $mail_title, 'data' => NULL, 'site' => get_deal_domain_title($deal['id']));
                $msgBox->create($v['user_id'], 11, $mail_title, $content);
                SmsServer::instance()->send($load_user_info['mobile'], 'TPL_SMS_DEAL_FAILD_NEW', $notice_sms, $v['user_id'], $v['site_id']);
            }
        }
        $loan_user_id_collection[$v['user_id']] = $v['user_id'];
        unset($v);
    }

    if(!empty($emailData)) {
        \core\service\email\SendEmailService::batchSendEmail($emailData);
    }
    //写入数据
    return true;
}

//发送流标站内信
function send_deal_faild_site_sms($deal_id,$deal_info=false,$user_id){
    if(!$deal_info && $deal_id ==0)
        return false;

    if(!$deal_info){
        $deal_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);
        $deal_info['name'] = get_deal_title($deal_info['name'], '', $deal_id);
    }

    if(intval($deal_info['is_send_bad_msg'])==1)
        return false;

    $msg_conf = get_user_msg_conf($user_id);

    if($msg_conf['sms_myfail'] == 1){
        $content = "<p>感谢您使用".app_conf("SHOP_TITLE")."贷款融资，但有一些遗憾的通知您，您于".to_date($deal_info['create_time'],"Y年m月d日")."发布的借款列表";
        $content .= "<a href=\"".url("index","deal",array("id"=>$deal_info['id']))."\">“".$deal_info['name']."”</a>流标，导致您本次贷款列表流标的原因可能包括的原因：</p>";
        $content .= $deal_info['bad_msg'];
        send_user_msg("",$content,0,$user_id,get_gmtime(),0,true,10);
    }

    //获取投资列表
    $load_user_list = $GLOBALS['db']->getAll("SELECT user_name,user_id,create_time FROM ".DB_PREFIX."deal_load WHERE deal_id=".$deal_info['id']);
    if($load_user_list){
        foreach($load_user_list as $k=>$v){
            $user_info = $GLOBALS['db']->getRow("select email from ".DB_PREFIX."user where id = ".$v['user_id']);
            $load_msg_conf = get_user_msg_conf($v['user_id']);
            if($load_msg_conf['sms_myfail'] == 1 || !$load_msg_conf){
                $content = "<p>感谢您使用".app_conf("SHOP_TITLE")."贷款融资，但有一些遗憾的通知您，您于".to_date($v['create_time'],"Y年m月d日")."投资的借款列表";
                $content .= "“<a href=\"".url("index","deal",array("id"=>$deal_info['id']))."\">".$deal_info['name']."</a>”流标，导致您本次所投的贷款列表流标的原因可能包括的原因：</p>";
                $content .= "1. 借款者没能按时提交四项必要信用认证的材料。<br>2. 借款者在招标期间没有筹集到足够的借款。";
                send_user_msg("",$content,0,$v['user_id'],get_gmtime(),0,true,11);
            }
        }
    }
}


/**
 * 发送合同相关message
 *
 * @Title: send_contract_email
 * @Description: todo(这里用一句话描述这个方法的作用)
 * @param  $deal_id  订单ID
 * @return return_type
 * @author Liwei
 * @throws
 *
 */
function send_contract_sign_email($deal_id){
    if(empty($deal_id)) return false;
    FP::import("libs.common.app");
    //获取所有未发送的列表
    $contract_id_list = $GLOBALS['db']->get_slave()->getAll("SELECT c.id,c.number,c.deal_id,group_concat(c.`title`) as title,c.user_id,c.agency_id,d.type_match_row as deal_name,d.type_id,d.name as deal_title,d.contract_tpl_type FROM ".DB_PREFIX."contract as c,".DB_PREFIX."deal as d WHERE is_send = 0 AND c.deal_id = ".$deal_id." AND c.deal_id = d.id GROUP BY c.user_id");

    $Msgcenter  = new Msgcenter();

    $site_url = get_deal_domain($deal_id);
    $contract_url = $site_url."/account/contract";
    foreach ($contract_id_list as $contract){
        $contract['deal_name'] = get_deal_title($contract['deal_title'], '', $deal_id);
        $contract['title'] = '"'.$contract['deal_name'].'"的合同已经下发';

        //获取用户信息
        if(empty($contract['agency_id'])){
            $user_info = get_user_info($contract['user_id'],true);
            if (isset($user_info['user_type']) && (int)$user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
                $userName =$user_info['user_name'];
            }else{
                $userName =get_deal_username($user_info['id']);
            }
            $notice_email = array(
                'user_name' => $userName,
                'deal_url' => $site_url.'/d/'.Aes::encryptForDeal($deal_id),
                'deal_name' => $contract['deal_name'],
                'help_url' => $site_url.url("index","helpcenter"),
                'site_url' => $site_url,
                'site_name' => app_conf("SHOP_TITLE"),
                'msg_cof_setting_url' => $site_url.url("index","uc_msg#setting"),
                'contract_url' => $contract_url,
            );
            $notice_phone = array(
                'user_name' => $user_info['user_name'],
                'deal_name' => $contract['deal_name'],
            );
            $content = sprintf('您投资的“%s”合同已下发。', $contract['deal_name']);
            send_user_msg("合同下发",$content,0,$contract['user_id'],get_gmtime(),0,true,32);
            $Msgcenter->setMsg($user_info['email'], $contract['user_id'], $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],'',get_deal_domain_title($deal_id));
        }else{

            $user_info = get_agency_info($contract['agency_id']);

            // 如果是汇赢则发送邮件到配置文件邮箱
            if($contract['contract_tpl_type'] == 'HY')
            {
                $user_info['email'] = $GLOBALS['dict']['HY_EMAIL'];
                $user_info['mobile'] = $GLOBALS['dict']['HY_MOBILE'];
            }

            $notice_email = array(
                'user_name' => $user_info['realname'],
                'deal_url' => $site_url.'/d/'.Aes::encryptForDeal($deal_id),
                'deal_name' => $contract['deal_name'],
                'help_url' => $site_url.url("index","helpcenter"),
                'site_url' => $site_url,
                'site_name' => app_conf("SHOP_TITLE"),
                'msg_cof_setting_url' => $site_url.url("index","uc_msg#setting"),
                'contract_url' => $contract_url,
            );
            $notice_phone = array(
                'user_name' => $user_info['realname'],
                'deal_name' => $contract['deal_name'],
            );
            $Msgcenter->setMsg($user_info['email'], 1000000, $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],'',get_deal_domain_title($deal_id));
        }
    }
    $GLOBALS['db']->autoExecute(DB_PREFIX."contract",array("is_send"=>1),"UPDATE","deal_id=".$deal_id);
    return $Msgcenter->save();
}



/**
 * 发送合同相关message
 *
 * @Title: send_contract_email
 * @Description: todo(这里用一句话描述这个方法的作用)
 * @param  $deal_id  订单ID
 * @return return_type
 * @author Liwei
 * @throws
 *
 */
function send_new_contract_sign_email($dealId){
    if(empty($dealId)) return false;
    $dealService = new DealService();
    //智多鑫不发送消息
    if($dealService->isDealDT($dealId)) {
        return false;
    }
    //获取所有未发送的列表
    $deal = $dealService->getDeal($dealId);
    $Msgcenter  = new Msgcenter();
    $site_url = get_deal_domain($dealId);
    $contract_url = $site_url."/account/contract";

    //获取合同列表
    $rpc = new Rpc('contractRpc');
    $contractRequest = new RequestGetContractByDealId();
    $contractRequest->setDealId(intval($dealId));
    $contractRequest->setSourceType(intval($deal['deal_type']));
    $response = $rpc->go("\NCFGroup\Contract\Services\Contract","getContractByDealId",$contractRequest);
    if($response->errCode == 0){
        $list = $response->list;
    }

    $contract['deal_name'] = get_deal_title($deal['name'], '', $dealId);
    $contract['title'] = '"'.$contract['deal_name'].'"的合同已经下发';

    $has_advisory = false;
    $users = array();

    $isDT = $dealService->isDealDT($dealId);

    // 智多鑫标的不给投资人下发合同消息
    if ($isDT == false) {
        $isSendMsgForUser = array();//记录是否下发合同消息
        foreach($list as $one){
            if($one['user_id'] <> 0){
                if($one['is_send'] == 0){
                    $users[$one['user_id']] = $one['user_id'];

                    //预约投资不单独给用户下发合同消息，若用户同时存在普通投资和预约投资，默认下发合同消息
                    $dealLoad = DealLoadModel::instance()->find($one['deal_load_id']);
                    if (!isset($isSendMsgForUser[$one['user_id']]) || !$isSendMsgForUser[$one['user_id']]) {
                        $isSendMsgForUser[$one['user_id']] = $dealLoad['source_type'] == DealLoadModel::$SOURCE_TYPE['reservation'] ? false : true;
                    }

                    if($one['advisory_id'] > 0){
                        $has_advisory = true;
                    }
                }
            }
        }
    }

    //if(count($users) > 0){
    $users[$deal['user_id']] = $deal['user_id'];
    //}



    //投资人,借款人
    $user_id_collection = array();
    foreach($users as $user){
        $user_info = get_user_info($user,true);
        if (isset($user_info['user_type']) && (int)$user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
            $userName =$user_info['user_name'];
        }else{
            $userName =get_deal_username($user_info['id']);
        }
        $notice_email = array(
            'user_name' => $userName,
            'deal_url' => $site_url.'/deal/'.$dealId,
            'deal_name' => $contract['deal_name'],
            'help_url' => $site_url.url("index","helpcenter"),
            'site_url' => $site_url,
            'site_name' => app_conf("SHOP_TITLE"),
            'msg_cof_setting_url' => $site_url.url("index","uc_msg#setting"),
            'contract_url' => $contract_url,
        );

        $notice_phone = array(
            'user_name' => $user_info['user_name'],
            'deal_name' => $contract['deal_name'],
        );
        if ((!isset($isSendMsgForUser[$user]) || $isSendMsgForUser[$user]) && !in_array($user, $user_id_collection)) { //预约投资不单独给用户下发合同消息
            $content = sprintf('您投资的“%s”合同已下发。', $contract['deal_name']);
            send_user_msg("合同下发",$content,0,$user,get_gmtime(),0,true,32);
        }
        $Msgcenter->setMsg($user_info['email'], $user, $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],'',get_deal_domain_title($dealId));
        $user_id_collection[$user] = $user;
    }

    if(count($users) > 0){
        //担保机构
        $user_info = get_agency_info($deal['agency_id']);
        $notice_email = array(
            'user_name' => $user_info['realname'],
            'deal_url' => $site_url.'/deal/'.$dealId,
            'deal_name' => $contract['deal_name'],
            'help_url' => $site_url.url("index","helpcenter"),
            'site_url' => $site_url,
            'site_name' => app_conf("SHOP_TITLE"),
            'msg_cof_setting_url' => $site_url.url("index","uc_msg#setting"),
            'contract_url' => $contract_url,
        );
        $notice_phone = array(
            'user_name' => $user_info['realname'],
            'deal_name' => $contract['deal_name'],
        );
        $Msgcenter->setMsg($user_info['email'], 1000000, $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],'',get_deal_domain_title($dealId));

        if($has_advisory){
            //咨询机构
            $user_info = get_agency_info($deal['advisory_id']);
            $notice_email = array(
                'user_name' => $user_info['realname'],
                'deal_url' => $site_url.'/deal/'.$dealId,
                'deal_name' => $contract['deal_name'],
                'help_url' => $site_url.url("index","helpcenter"),
                'site_url' => $site_url,
                'site_name' => app_conf("SHOP_TITLE"),
                'msg_cof_setting_url' => $site_url.url("index","uc_msg#setting"),
                'contract_url' => $contract_url,
            );
            $notice_phone = array(
                'user_name' => $user_info['realname'],
                'deal_name' => $contract['deal_name'],
            );
            $Msgcenter->setMsg($user_info['email'], 1000000, $notice_email, 'TPL_SEND_CONTRACT_EMAIL',$contract['title'],'',get_deal_domain_title($dealId));
        }
    }

    //回调发送状态
    $rpc = new Rpc('contractRpc');
    $contractRequest = new RequestSendContractStatus();
    $contractRequest->setDealId(intval($dealId));
    $contractRequest->setSourceType(intval($deal['deal_type']));
    $response = $rpc->go("\NCFGroup\Contract\Services\Contract","sendContractStatus",$contractRequest);
    if($response->errorCode == 0){
        return $Msgcenter->save();
    }else{
        return false;
    }

}




//发邮件团购券
function send_deal_coupon_mail($deal_coupon_id)
{
    if(app_conf("MAIL_ON")==1&&app_conf("MAIL_SEND_COUPON")==1)
    {
        $coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
        if($coupon_data)
        {
            $tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_COUPON'");
            $tmpl_content = $tmpl['content'];
            $coupon_data['begin_time_format'] = $coupon_data['begin_time']==0?$GLOBALS['lang']['NO_BEGIN_TIME']:to_date($coupon_data['begin_time'],'Y-m-d');
            $coupon_data['end_time_format'] = $coupon_data['end_time']==0?$GLOBALS['lang']['NO_END_TIME']:to_date($coupon_data['end_time'],'Y-m-d');
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);
            $coupon_data['user_name'] = $user_info['user_name'];
            $coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
            $coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
            $deal_id = $coupon_data['deal_id'];
            if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
            {
                $deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
                if(!$coupon_data['deal_name'])
                    $coupon_data['deal_name'] = $deal_info['name'];
                if(!$coupon_data['deal_sub_name'])
                    $coupon_data['deal_sub_name'] = $deal_info['sub_name'];
            }
            $order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
            $deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
            if($deal_type == 1&&$order_item)
            {
                $coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
                $coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
            }

            $GLOBALS['tmpl']->assign("coupon",$coupon_data);
            $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
            $msg_data['dest'] = $user_info['email'];
            $msg_data['send_type'] = 1;
            $msg_data['title'] = $GLOBALS['lang']['YOU_GOT_COUPON'];
            $msg_data['content'] = addslashes($msg);
            $msg_data['send_time'] = 0;
            $msg_data['is_send'] = 0;
            $msg_data['create_time'] = get_gmtime();
            $msg_data['user_id'] = $user_info['id'];
            $msg_data['is_html'] = $tmpl['is_html'];
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

        }
    }
}

//发短信团购券
function send_deal_coupon_sms($deal_coupon_id)
{
    if(app_conf("SMS_ON")==1&&app_conf("SMS_SEND_COUPON")==1)
    {
        $coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
        if($coupon_data)
        {
            $forbid_sms = intval($GLOBALS['db']->getOne("select forbid_sms from ".DB_PREFIX."deal where id = ".$coupon_data['deal_id']));
            if($forbid_sms==0)
            {
                $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);
                if($user_info['mobile']!='')
                {
                    $tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_COUPON'");
                    $tmpl_content = $tmpl['content'];
                    $coupon_data['begin_time_format'] = $coupon_data['begin_time']==0?$GLOBALS['lang']['NO_BEGIN_TIME']:to_date($coupon_data['begin_time'],'Y-m-d');
                    $coupon_data['end_time_format'] = $coupon_data['end_time']==0?$GLOBALS['lang']['NO_END_TIME']:to_date($coupon_data['end_time'],'Y-m-d');
                    $coupon_data['user_name'] = $user_info['user_name'];
                    $coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
                    $coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
                    $deal_id = $coupon_data['deal_id'];
                    if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
                    {
                        $deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
                        if(!$coupon_data['deal_name'])
                            $coupon_data['deal_name'] = $deal_info['name'];
                        if(!$coupon_data['deal_sub_name'])
                            $coupon_data['deal_sub_name'] = $deal_info['sub_name'];
                    }
                    $order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
                    $deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
                    if($deal_type == 1&&$order_item)
                    {
                        $coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
                        $coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
                    }


                    $GLOBALS['tmpl']->assign("coupon",$coupon_data);
                    $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
                    $msg_data['dest'] = $user_info['mobile'];
                    $msg_data['send_type'] = 0;
                    $msg_data['content'] = addslashes($msg);;
                    $msg_data['send_time'] = 0;
                    $msg_data['is_send'] = 0;
                    $msg_data['create_time'] = get_gmtime();
                    $msg_data['user_id'] = $user_info['id'];
                    $msg_data['is_html'] = $tmpl['is_html'];
                    $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
                }
            }
        }
    }
}


//发团购券确认使用的短信
function send_use_coupon_sms($deal_coupon_id)
{
    if(app_conf("SMS_ON")==1&&app_conf("SMS_USE_COUPON")==1)
    {
        $coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
        if($coupon_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);
            if($user_info['mobile']!='')
            {
                $tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_USE_COUPON'");
                $tmpl_content = $tmpl['content'];
                $coupon_data['confirm_time_format'] = to_date($coupon_data['confirm_time'],'Y-m-d H:i:s');
                $coupon_data['user_name'] = $user_info['user_name'];
                $coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
                $coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
                $deal_id = $coupon_data['deal_id'];
                if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
                {
                    $deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
                    if(!$coupon_data['deal_name'])
                        $coupon_data['deal_name'] = $deal_info['name'];
                    if(!$coupon_data['deal_sub_name'])
                        $coupon_data['deal_sub_name'] = $deal_info['sub_name'];
                }
                $order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
                $deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
                if($deal_type == 1&&$order_item)
                {
                    $coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
                    $coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
                }
                $GLOBALS['tmpl']->assign("coupon",$coupon_data);
                $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
                $msg_data['dest'] = $user_info['mobile'];
                $msg_data['send_type'] = 0;
                $msg_data['content'] = addslashes($msg);;
                $msg_data['send_time'] = 0;
                $msg_data['is_send'] = 0;
                $msg_data['create_time'] = get_gmtime();
                $msg_data['user_id'] = $user_info['id'];
                $msg_data['is_html'] = $tmpl['is_html'];
                $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
            }
        }
    }
}


//发团购券确认使用的邮件
function send_use_coupon_mail($deal_coupon_id)
{
    if(app_conf("MAIL_ON")==1&&app_conf("MAIL_USE_COUPON")==1)
    {
        $coupon_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_coupon where id = ".$deal_coupon_id);
        if($coupon_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$coupon_data['user_id']);

            $tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_USE_COUPON'");
            $tmpl_content = $tmpl['content'];
            $coupon_data['confirm_time_format'] = to_date($coupon_data['confirm_time'],'Y-m-d H:i:s');
            $coupon_data['user_name'] = $user_info['user_name'];
            $coupon_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
            $coupon_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal_order_item where id = ".$coupon_data['order_deal_id']);
            $deal_id = $coupon_data['deal_id'];
            if(!$coupon_data['deal_name']||!$coupon_data['deal_sub_name'])
            {
                $deal_info = $GLOBALS['db']->getRow("select name,sub_name from ".DB_PREFIX."deal where id = ".$deal_id);
                if(!$coupon_data['deal_name'])
                    $coupon_data['deal_name'] = $deal_info['name'];
                if(!$coupon_data['deal_sub_name'])
                    $coupon_data['deal_sub_name'] = $deal_info['sub_name'];
            }
            $order_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order_item where id = ".intval($coupon_data['order_deal_id']));
            $deal_type = intval($GLOBALS['db']->getOne("select deal_type from ".DB_PREFIX."deal where id = ".intval($order_item['deal_id'])));
            if($deal_type == 1&&$order_item)
            {
                $coupon_data['deal_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
                $coupon_data['deal_sub_name'].= " ".$GLOBALS['lang']['BUY_NUMBER']."(".$order_item['number'].")";
            }
            $GLOBALS['tmpl']->assign("coupon",$coupon_data);
            $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
            $msg_data['dest'] = $user_info['email'];
            $msg_data['send_type'] = 1;
            $msg_data['content'] = addslashes($msg);;
            $msg_data['send_time'] = 0;
            $msg_data['is_send'] = 0;
            $msg_data['create_time'] = get_gmtime();
            $msg_data['user_id'] = $user_info['id'];
            $msg_data['is_html'] = $tmpl['is_html'];
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

        }
    }
}


//发短信抽奖
function send_lottery_sms($lottery_id)
{
    if(app_conf("SMS_ON")==1&&app_conf("LOTTERY_SN_SMS")==1&&$lottery_id>0)
    {
        $lottery_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."lottery where id = ".$lottery_id);
        if($lottery_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$lottery_data['user_id']);

            $tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_LOTTERY'");
            $tmpl_content = $tmpl['content'];
            $lottery_data['user_name'] = $user_info['user_name'];
            $lottery_data['deal_name'] = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal where id = ".$lottery_data['deal_id']);
            $lottery_data['deal_sub_name'] = $GLOBALS['db']->getOne("select sub_name from ".DB_PREFIX."deal where id = ".$lottery_data['deal_id']);

            $GLOBALS['tmpl']->assign("lottery",$lottery_data);
            $msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
            $msg_data['dest'] = $lottery_data['mobile'];
            $msg_data['send_type'] = 0;
            $msg_data['content'] = addslashes($msg);;
            $msg_data['send_time'] = 0;
            $msg_data['is_send'] = 0;
            $msg_data['create_time'] = get_gmtime();
            $msg_data['user_id'] = $user_info['id'];
            $msg_data['is_html'] = $tmpl['is_html'];
            $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入

        }
    }
}

//发注册验证邮件
function send_user_verify_mail($user_id)
{
    if(app_conf("MAIL_ON")==1)
    {
        $verify_code = rand(111111,999999);
        $GLOBALS['db']->query("update ".DB_PREFIX."user set verify = '".$verify_code."' where id = ".$user_id);
        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
        if($user_info) {
            $user_info['verify_url'] = get_domain().url("index","user#verify",array("id"=>$user_info['id'],"code"=>$user_info['verify']));
            $msgcenter = new Msgcenter();
            $msgcenter->setMsg($user_info['email'], $user_info['id'], $user_info, 'TPL_MAIL_USER_VERIFY', $GLOBALS['lang']['REGISTER_SUCCESS']);
            $msgcenter->save();
        }
    }
}


//发密码验证邮件
function send_user_password_mail($user_id)
{
    if(app_conf("MAIL_ON")==1)
    {
        $verify_code = rand(111111,999999);
        $GLOBALS['db']->query("update ".DB_PREFIX."user set password_verify = '".$verify_code."' where id = ".$user_id);
        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
        if($user_info) {
            $user_info['password_url'] = get_domain().url("index","user#modify_password", array("code"=>$user_info['password_verify'],"id"=>$user_info['id']));

            $msgcenter = new Msgcenter();
            $msgcenter->setMsg($user_info['email'], $user_info['id'], $user_info, 'TPL_MAIL_USER_PASSWORD', $GLOBALS['lang']['RESET_PASSWORD']);
            $msgcenter->save();
        }
    }
}


/**
 * 发送基金赎回到账短信
 */
function send_fund_sms($noticeId, $type = 'fund_redeem')
{
    $templates = [
        'fund_redeem' => '您投资的“%s”已到账，金额为%2f元。',
    ];
    if(app_conf("SMS_ON")==1)
    {
        $noticeData= $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$noticeId);
        if($noticeData)
        {
            $userInfo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$noticeData['user_id']);
            $order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$noticeData['order_id']);
            // user表mobile 或者 deal_order表mobile 不为空 或者 企业用户，发送短信
            if($userInfo['mobile']!=''||$order_info['mobile']!='' || $userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
            {
                $fundInfo = explode(',', $noticeData['memo']);
                $suffix = mb_strlen($fundInfo[1], 'UTF-8') > 10 ? '...' : '';
                $fundTitle = mb_substr($fundInfo[1], 0, 10, 'UTF-8').$suffix;

                $sms_content = array(
                    'title' => $fundTitle,
                    'money' => format_price($noticeData['money']),
                );
                $dest = empty($userInfo['mobile']) ? $order_info['mobile'] : $userInfo['mobile'] ;
                $siteTitle = \libs\utils\Site::getTitleById($noticeData['site_id']);
                $msgcenter = new Msgcenter();
                $_mobile = $dest;
                if ($userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                }
                $msgcenter->setMsg($_mobile, $userInfo['id'], $sms_content, 'TPL_SMS_FUND_REDEEM','赎回到账', '', $siteTitle);
                $msgcenter->save();
            }
        }
    }

}


//发短信收款单
function send_payment_sms($notice_id)
{
    if(app_conf("SMS_ON")==1)
    {
        $notice_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id);
        if($notice_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
            $order_info = array('mobile'=>'');//$GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$notice_data['order_id']);
            // user表mobile 或者 deal_order表mobile 不为空 或者 企业用户，发送短信
            if($user_info['mobile']!=''||$order_info['mobile']!='' || $user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
            {
                //$notice_data['user_name'] = $user_info['user_name'];
                //$notice_data['order_sn'] = $GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$notice_data['order_id']);
                //$notice_data['pay_time_format'] = to_date($notice_data['pay_time']);
                //$notice_data['money_format'] = format_price($notice_data['money']);

                if ($user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($user_info['id']);
                } else {
                    $_mobile = empty($user_info['mobile']) ? $order_info['mobile'] : $user_info['mobile'];
                    $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
                }
                $sms_content = array(
                    'account_title' => $accountTitle,
                    'pay_time' => to_date($notice_data['pay_time']),
                    'money' => format_price($notice_data['money']),
                );

                SmsServer::instance()->send($_mobile, 'TPL_SMS_PAYMENT_NEW', $sms_content, $user_info['id'], $notice_data['site_id']);
            }
        }
    }
}

//发邮件收款单
function send_payment_mail($notice_id)
{
    if(app_conf("MAIL_ON")==1&&app_conf("MAIL_SEND_PAYMENT")==1)
    {
        $notice_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id);
        if($notice_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
            if($user_info['email']!='') {
                $notice_data['user_name'] = $user_info['user_name'];
                $notice_data['order_sn'] = $notice_data['notice_sn'];//$GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$notice_data['order_id']);
                $notice_data['pay_time_format'] = to_date($notice_data['pay_time']);
                $notice_data['money_format'] = format_price($notice_data['money']);

                $msgcenter = new Msgcenter();
                $msgcenter->setMsg($user_info['email'], $user_info['id'], $notice_data, 'TPL_MAIL_PAYMENT', $GLOBALS['lang']['PAYMENT_NOTICE']);
                $msgcenter->save();
            }
        }
    }
}

//发送存管充值短信
function send_supervision_charge_msg($outOrderId)
{
    if(app_conf("SMS_ON")==1)
    {
        //充值成功的
        $chargeData = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supervision_charge where out_order_id = '" . intval($outOrderId) . "' and pay_status = " . SupervisionEnum::PAY_STATUS_SUCCESS);
        if($chargeData)
        {
            $userInfo = UserService::getUserById($chargeData['user_id'], 'id,mobile,user_type');
            $orderInfo = array('mobile'=>'');
            // user表mobile 或者 deal_order表mobile 不为空 或者 企业用户，发送短信
            if($userInfo['mobile']!=''||$orderInfo['mobile']!='' || $userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
            {
                if ($userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($userInfo['id']);
                } else {
                    $_mobile = empty($userInfo['mobile']) ? $orderInfo['mobile'] : $userInfo['mobile'];
                    $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
                }
                $smsContent = array(
                    'account_title' => $accountTitle,
                    'pay_time' => date('Y-m-d H:i:s', $chargeData['update_time']),
                    'money' => format_price(bcdiv($chargeData['amount'], 100, 2)),
                );

                SmsServer::instance()->send($_mobile, 'TPL_SMS_PAYMENT_NEW', $smsContent, $userInfo['id']);
            }
        }
    }
}

//发送存管提现消息和短信
function send_supervision_withdraw_msg($outOrderId)
{
    if(app_conf("SMS_ON")==1)
    {
        $withdrawStatus = [
            SupervisionEnum::WITHDRAW_STATUS_SUCCESS,
            SupervisionEnum::WITHDRAW_STATUS_FAILED,
            SupervisionEnum::WITHDRAW_STATUS_PROCESS,
        ];
        //提现成功、处理中、失败
        $withdrawData = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supervision_withdraw where out_order_id = '".intval($outOrderId)."' and withdraw_status in (".implode(',', $withdrawStatus).")");
        if($withdrawData)
        {
            $userInfo = UserService::getUserById($withdrawData['user_id'], 'id,mobile,user_type');
            $orderInfo = array('mobile'=>'');
            // user表mobile 或者 deal_order表mobile 不为空 或者 企业用户，发送短信
            if($userInfo['mobile']!=''||$orderInfo['mobile']!='' || $userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
            {
                $msgbox = new MsgboxService();
                $moneyFormat = format_price(bcdiv($withdrawData['amount'], 100, 2));
                $dateFormat = date("Y年m月d日 H:i:s", $withdrawData['update_time']);
                if ($userInfo['user_type'] == UserEnum::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($userInfo['id']);
                } else {
                    $_mobile = empty($userInfo['mobile']) ? $orderInfo['mobile'] : $userInfo['mobile'];
                    $accountTitle = UserEnum::MSG_FOR_USER_ACCOUNT_TITLE;
                }
                //根据提现状态，发送短信
                if ($withdrawData['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_PROCESS) {
                    //放款提现不发送提现申请短信
                    if ($withdrawData['bid'] != 0) {
                        return;
                    }
                    $smsContent = array(
                        'account_title' => $accountTitle,
                        'pay_time' => date('Y-m-d H:i:s', $withdrawData['update_time']),
                        'money' => $moneyFormat
                    );
                    $tpl = 'TPL_SMS_SUPERVISION_WITHDRAW_APPLY';
                    $mark = '提现申请';

                    //消息
                    $msgContent = sprintf('您于%s申请提现%s，您的申请预计在1个工作日内提交至存管银行，实际到账时间依据存管银行及提现到账银行服务时效有所差异。', $dateFormat, $moneyFormat);

                } else if ($withdrawData['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_SUCCESS) {
                    $smsContent = array(
                        'account_title' => $accountTitle,
                        'money' => $moneyFormat
                    );
                    $tpl = 'TPL_SMS_SUPERVISION_WITHDRAW_SUCCESS';
                    $mark = '提现成功';

                    //消息
                    $msgContent = sprintf('您于%s申请的%s提现已汇款，具体到账时间根据各银行规定，请注意查收。 ', $dateFormat, $moneyFormat);

                } else if ($withdrawData['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_FAILED) {
                    $smsContent = array(
                        'account_title' => $accountTitle,
                        'money' => $moneyFormat
                    );
                    $tpl = 'TPL_SMS_SUPERVISION_WITHDRAW_FAIL';
                    $mark = '提现失败';

                    //消息
                    $msgContent = sprintf('您于%s提交的%s提现银行受理失败，如有疑问请拨打客服热线 95782。', $dateFormat, $moneyFormat);
                }
                // 资产端借款用户屏蔽短信优化需求-JIRA4931
                if ($withdrawData['bid'] == 0) {
                    //发短信
                    SmsServer::instance()->send($_mobile, $tpl, $smsContent, $userInfo['id']);
                }

                //发消息
                $typeMap = ['提现申请' => 5, '提现成功' => 6, '提现失败' => 7];
                $structuredContent = array(
                    'main_content' => $msgContent,
                    'money' => (6 == $typeMap[$mark]) ? sprintf("-%s", number_format(bcdiv($withdrawData['amount'], 100, 2), 2)) : '',
                    'turn_type' => MsgBoxEnum::TURN_TYPE_MONEY_LOG,
                );
                $msgbox->create($userInfo['id'], $typeMap[$mark], $mark, $msgContent, $structuredContent);
            }
        }
    }
}


//发邮件发货单
function send_delivery_mail($notice_sn,$deal_names = '',$order_id)
{
    if(app_conf("MAIL_ON")==1&&app_conf("MAIL_SEND_DELIVERY")==1)
    {
        $notice_data = $GLOBALS['db']->getRow("select dn.* from ".DB_PREFIX."delivery_notice as dn left join ".DB_PREFIX."deal_order_item as doi on dn.order_item_id = doi.id where dn.notice_sn = '".$notice_sn."' and doi.order_id = ".$order_id);
        if($notice_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
            if($user_info['email']!='') {
                $notice_data['user_name'] = $user_info['user_name'];
                $notice_data['order_sn'] = $GLOBALS['db']->getOne("select do.order_sn from ".DB_PREFIX."deal_order_item as doi left join ".DB_PREFIX."deal_order as do on doi.order_id = do.id where doi.id = ".$notice_data['order_item_id']);
                $notice_data['delivery_time_format'] = to_date($notice_data['delivery_time']);
                $notice_data['deal_names'] = $deal_names;

                $msgcenter = new Msgcenter();
                $msgcenter->setMsg($user_info['email'], $user_info['id'], $notice_data, 'TPL_MAIL_DELIVERY', $GLOBALS['lang']['DELIVERY_NOTICE']);
                $msgcenter->save();
            }
        }
    }
}

//发短信发货单
function send_delivery_sms($notice_sn,$deal_names = '',$order_id)
{
    if(app_conf("SMS_ON")==1&&app_conf("SMS_SEND_DELIVERY")==1)
    {
        $notice_data = $GLOBALS['db']->getRow("select dn.* from ".DB_PREFIX."delivery_notice as dn left join ".DB_PREFIX."deal_order_item as doi on dn.order_item_id = doi.id where dn.notice_sn = '".$notice_sn."' and doi.order_id = ".$order_id);
        if($notice_data)
        {
            $order_info = array('mobile'=>'');//$GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$order_id);
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$notice_data['user_id']);
            if($user_info['mobile']!=''||$order_info['mobile']!='') {
                $notice_data['user_name'] = $user_info['user_name'];
                $notice_data['order_sn'] = $notice_sn;//$GLOBALS['db']->getOne("select do.order_sn from ".DB_PREFIX."deal_order_item as doi left join ".DB_PREFIX."deal_order as do on doi.order_id = do.id where doi.id = ".$notice_data['order_item_id']);
                $notice_data['delivery_time_format'] = to_date($notice_data['delivery_time']);
                $notice_data['deal_names'] = $deal_names;

                $msgcenter = new Msgcenter();
                if($user_info['mobile']!='') {
                    $msgcenter->setMsg($user_info['mobile'], $user_info['id'], $notice_data, 'TPL_SMS_DELIVERY');
                }
                if($order_info['mobile']!=''&&$order_info['mobile']!=$user_info['mobile']) {
                    $msgcenter->setMsg($order_info['mobile'], $user_info['id'], $notice_data, 'TPL_SMS_DELIVERY');
                }
                $msgcenter->save();
            }
        }
    }
}


//发短信验证码
function send_verify_sms($mobile, $code, $user_info) {
    if (app_conf("SMS_ON") == 1) {
        $verify['mobile'] = $mobile;
        $verify['code']   = $code;

        $msgcenter = new Msgcenter();
        $msgcenter->setMsg($mobile, $user_info['id'], $verify, 'TPL_SMS_VERIFY_CODE');
        $msgcenter->save();
    }
}


//发邮件退订验证
function send_unsubscribe_mail($email) {
    if (app_conf("MAIL_ON") == 1) {
        if ($email) {
            $GLOBALS['db']->query("update " . DB_PREFIX . "mail_list set code = '" . rand(1111, 9999) . "' where mail_address='" . $email . "' and code = ''");
            $email_item = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "mail_list where mail_address = '" . $email . "' and code <> ''");
            if ($email_item) {
                $mail        = $email_item;
                $mail['url'] = get_domain() . url("index", "subscribe#dounsubscribe", array("code" => base64_encode($mail['code'] . "|" . $mail['mail_address'])));

                $msgcenter = new Msgcenter();
                $msgcenter->setMsg($mail['mail_address'], 0, $mail, 'TPL_MAIL_UNSUBSCRIBE', $GLOBALS['lang']['MAIL_UNSUBSCRIBE']);
                $msgcenter->save();
            }
        }
    }
}

function get_deal_cate_name($cate_id)
{
    return $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_cate where id =".$cate_id);
}

function get_loan_type_name($type_id){
    return $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_loan_type where id =".$type_id);
}

/**
 * 四舍五入利率小数位数，前台展示用，2位
 */
function format_rate_for_show($rate){
    return number_format($rate, 2);
}

/**
 * 四舍五入利率小数位数，合同用，3位
 */
function format_rate_for_cont($rate){
    return number_format($rate, 3);
}

/**
 * 四舍五入利率小数位数，后台或者入库用，5位
 */
function format_rate_for_db($rate){
    return number_format($rate, 5);
}

/**
 *
 * @param number $price 钱数
 * @param string $tag 类型
 * @param string $isshow 如果是零是否显示
 * @return string
 */
function format_price($price,$tag = true,$isshow=true)
{
    //$money_tag = $tag ? app_conf("CURRENCY_UNIT") : '';
    $money_tag = $tag ? '元' : '';
    //如果是零 并且不显示
    if(!$isshow && $price == 0){
        return '';
    }
    //return $money_tag.number_format($price,2);
    return number_format($price,2).$money_tag;

}
function format_score($score)
{
    return intval($score)."".app_conf("SCORE_UNIT");
}

//utf8 字符串截取
function msubstr($str, $start=0, $length=15, $charset="utf-8", $suffix=true)
{
    if(function_exists("mb_substr"))
    {
        $slice =  mb_substr($str, $start, $length, $charset);
        if($suffix&$slice!=$str) return $slice."…";
        return $slice;
    }
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix&&$slice!=$str) return $slice."…";
    return $slice;
}

/**
 * PHP获取字符串中英文混合长度
 * @param $str string 字符串
 * @param $$charset string 编码
 * @return 返回长度，1中文=1位，2英文=1位
 */
function strLength($str,$charset='utf-8'){
    if($charset=='utf-8') $str = iconv('utf-8','gb2312',$str);
    $num = strlen($str);
    $cnNum = 0;
    for($i=0;$i<$num;$i++){
        if(ord(substr($str,$i+1,1))>127){
            $cnNum++;
            $i++;
        }
    }
    $enNum = $num-($cnNum*2);
    $number = ($enNum/2)+$cnNum;
    return ceil($number);
}


//字符编码转换
if(!function_exists("iconv"))
{
    function iconv($in_charset,$out_charset,$str)
    {
        FP::import('libs.libs.iconv');
        $chinese = new Chinese();
        return $chinese->Convert($in_charset,$out_charset,$str);
    }
}

//JSON兼容
if(!function_exists("json_encode"))
{
    function json_encode($data)
    {
        FP::import('libs.libs.json');
        $JSON = new JSON();
        return $JSON->encode($data);
    }
}
if(!function_exists("json_decode"))
{
    function json_decode($data)
    {
        FP::import('libs.libs.json');
        $JSON = new JSON();
        return $JSON->decode($data,1);
    }
}

//邮件格式验证的函数
function check_email($email)
{
    if(!preg_match("/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/",$email))
    {
        return false;
    }
    else
        return true;
}

//验证手机号码
function check_mobile($mobile)
{
    if(!empty($mobile) && !preg_match("/^\d{6,}$/",$mobile))
    {
        return false;
    }
    else
        return true;
}

//跳转
function app_redirect($url,$time=0,$msg='')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if(empty($msg))
        $msg    =   "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if(0===$time) {
            if(substr($url,0,1)=="/")
            {
                header("Location:".get_domain().$url);
            }
            else
            {
                header("Location:".$url);
            }

        }else {
            header("refresh:{$time};url={$url}");
            echo $msg;
        }
    }else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if($time!=0)
            $str   .=   $msg;
        echo $str;
    }
    $trace_obj = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2);
    $trace_obj = isset($trace_obj[1]) ? $trace_obj[1] : [];
    if($trace_obj && isset($trace_obj['object'])){
        $trace_obj['object']->template = null;
    }
    $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
    $xhttp = isset($_SERVER['HTTP_XHTTPS']) ? $_SERVER['HTTP_XHTTPS'] : '';
    setLog(
        array('output' => array('ajax' => 0, 'jump' => $url, 'msg'=> $msg,  'https'=> $https, 'xhttps'=>$xhttp ))
    );

    return false;

}


/**
 * 验证访问IP的有效性
 * @param ip地址 $ip_str
 * @param 访问页面 $module
 * @param 时间间隔 $time_span
 * @param 数据ID $id
 */
function check_ipop_limit($ip_str,$module,$time_span=0,$id=0)
{
    $op = es_session::get($module."_".$id."_ip");
    if(empty($op))
    {
        $check['ip']    =     get_client_ip();
        $check['time']    =    get_gmtime();
        es_session::set($module."_".$id."_ip",$check);
        return true;  //不存在session时验证通过
    }
    else
    {
        $check['ip']    =     get_client_ip();
        $check['time']    =    get_gmtime();
        $origin    =    es_session::get($module."_".$id."_ip");

        if($check['ip']==$origin['ip'])
        {
            if($check['time'] - $origin['time'] < $time_span)
            {
                return false;
            }
            else
            {
                es_session::set($module."_".$id."_ip",$check);
                return true;  //不存在session时验证通过
            }
        }
        else
        {
            es_session::set($module."_".$id."_ip",$check);
            return true;  //不存在session时验证通过
        }
    }
}

function get_deal_mail_content($deal_rs)
{
    $tmpl_content = file_get_contents(APP_ROOT_PATH."app/Tpl/".app_conf("TEMPLATE")."/deal_mail.html");
    $GLOBALS['tmpl']->assign("APP_ROOT",APP_ROOT);

    if($deal_rs)
    {
        foreach($deal_rs as $k=>$deal)
        {
            $deal_city = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_city where id = ".$deal['city_id']);
            $deal['city_name'] = $deal_city['name'];

            $send_date = to_date(get_gmtime(),'Y年m月d日');
            $weekarray = array("日","一","二","三","四","五","六");
            $send_date .= " 星期".$weekarray[to_date(get_gmtime(),"w")];
            $deal['send_date'] = $send_date;


            $deal['url'] = url("tuan","deal",array("id"=>$deal['id'],"city"=>$deal_city['uname']));

            if($deal['origin_price']>0&&floatval($deal['discount'])==0) //手动折扣
                $deal['save_money'] = $deal['origin_price'] - $deal['current_price'];
            else
                $deal['save_money'] = $deal['origin_price']*((10-$deal['discount'])/10);

            if($deal['origin_price']>0&&floatval($deal['discount'])==0)
                $deal['discount'] = round(($deal['current_price']/$deal['origin_price'])*10,2);

            $deal['discount'] = round($deal['discount'],2);


            $supplier_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supplier where id = ".$deal['supplier_id']);
            $supplier_address_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."supplier_location where supplier_id = ".$deal['supplier_id']." and is_main = 1");
            $deal['saler_name'] = $supplier_info['name'];
            $deal['saler_address'] = $supplier_address_info['address'];
            $deal['saler_tel'] = $supplier_address_info['tel'];

            if(app_conf("INVITE_REFERRALS_TYPE")==0)
            {
                $deal['referrals'] = format_price(app_conf("INVITE_REFERRALS"));
            }
            else
            {
                $deal['referrals'] = format_score(app_conf("INVITE_REFERRALS"));
            }


            $deal['referrals_url'] = url("tuan","referral",array("id"=>$deal['deal_id'],"city"=>$deal_city['uname']));
            $deal_rs[$k] = $deal;

        }
        $GLOBALS['tmpl']->assign("deal_rs",$deal_rs);
        $content = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);

        $tmpl_path = app_conf("TMPL_DOMAIN_ROOT")==''?get_domain().APP_ROOT."/app/Tpl/":app_conf("TMPL_DOMAIN_ROOT")."/";
        $content = str_replace("deal_mail/",$tmpl_path.app_conf("TEMPLATE")."/deal_mail/",$content);
        return $content;
    }
    else
        return '';
}

/**
 * $notice.site_name
 * $notice.deal_name
 * $notice.site_url
 * @param $deal_id
 */
function get_deal_sms_content($deal_id)
{
    $tmpl_content = $GLOBALS['db']->getOne("select content from ".DB_PREFIX."msg_template where name = 'TPL_DEAL_NOTICE_SMS'");
    $deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);
    if($deal)
    {
        $notice['site_name'] = app_conf("SHOP_TITLE");
        $notice['deal_name'] = $deal['sub_name'];
        $notice['site_url'] = get_domain().APP_ROOT;
        $GLOBALS['tmpl']->assign("notice",$notice);
        $content = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
        return $content;
    }
    else
        return '';
}

/**
 * $bond.sn
 * $bond.password
 * $bond.name
 * $bond.user_name
 * $bond.begin_time_format
 * $bond.end_time_format
 * $bond.tel
 * $bond.address
 * $bond.route
 * $bond.open_time
 * @param $coupon_id
 * @param $location_id
 */


function gzip_out($content)
{
    //header("Content-type: text/html; charset=utf-8");
    //header("Cache-control: private");  //支持页面回跳
    $gzip = app_conf("GZIP_ON");
    if( intval($gzip)==1 )
    {
        if(!headers_sent()&&extension_loaded("zlib")&&preg_match("/gzip/i",$_SERVER["HTTP_ACCEPT_ENCODING"]))
        {
            $content = gzencode($content,9);
            header("Content-Encoding: gzip");
            header("Content-Length: ".strlen($content));
            echo $content;
        }
        else
            echo $content;
    }else{
        echo $content;
    }

}

function order_log($log_info,$order_id)
{
    $data['id'] = 0;
    $data['log_info'] = $log_info;
    $data['log_time'] = get_gmtime();
    $data['order_id'] = $order_id;
    $GLOBALS['db']->autoExecute(DB_PREFIX."deal_order_log", $data);
}


/**
 * 保存图片
 * @param array $upd_file  即上传的$_FILES数组
 * @param array $key $_FILES 中的键名 为空则保存 $_FILES 中的所有图片
 * @param string $dir 保存到的目录
 * @param array $whs
 可生成多个缩略图
 数组 参数1 为宽度，
 参数2为高度，
 参数3为处理方式:0(缩放,默认)，1(剪裁)，
 参数4为是否水印 默认为 0(不生成水印)
 array(
     'thumb1'=>array(300,300,0,0),
            'thumb2'=>array(100,100,0,0),
            'origin'=>array(0,0,0,0),  宽与高为0为直接上传
            ...
        )，
        * @param array $is_water 原图是否水印
        * @return array
        array(
            'key'=>array(
                'name'=>图片名称，
                'url'=>原图web路径，
                'path'=>原图物理路径，
                有略图时
                'thumb'=>array(
                    'thumb1'=>array('url'=>web路径,'path'=>物理路径),
                    'thumb2'=>array('url'=>web路径,'path'=>物理路径),
                    ...
                )
            )
            ....
        )
 */
//$img = save_image_upload($_FILES,'avatar','temp',array('avatar'=>array(300,300,1,1)),1);
function save_image_upload($upd_file, $key='',$dir='temp', $whs=array(),$is_water=false,$need_return = false)
{
    require_once APP_ROOT_PATH."system/utils/es_imagecls.php";
    $image = new es_imagecls();
    $image->max_size = intval(app_conf("MAX_IMAGE_SIZE"));

    $list = array();

    if(empty($key))
    {
        foreach($upd_file as $fkey=>$file)
        {
            $list[$fkey] = false;
            $image->init($file,$dir);
            if($image->save())
            {
                $list[$fkey] = array();
                $list[$fkey]['url'] = $image->file['target'];
                $list[$fkey]['path'] = $image->file['local_target'];
                $list[$fkey]['name'] = $image->file['prefix'];
            }
            else
            {
                if($image->error_code==-105)
                {
                    if($need_return)
                    {
                        return array('error'=>1,'message'=>'上传的图片太大');
                    }
                    else
                        echo "上传的图片太大";
                }
                elseif($image->error_code==-104||$image->error_code==-103||$image->error_code==-102||$image->error_code==-101)
                {
                    if($need_return)
                    {
                        return array('error'=>1,'message'=>'非法图像');
                    }
                    else
                        echo "非法图像";
                }
                exit;
            }
        }
    }
    else
    {
        $list[$key] = false;
        $image->init($upd_file[$key],$dir);
        if($image->save())
        {
            $list[$key] = array();
            $list[$key]['url'] = $image->file['target'];
            $list[$key]['path'] = $image->file['local_target'];
            $list[$key]['name'] = $image->file['prefix'];
        }
        else
        {
            if($image->error_code==-105)
            {
                if($need_return)
                {
                    return array('error'=>1,'message'=>'上传的图片太大');
                }
                else
                    echo "上传的图片太大";
            }
            elseif($image->error_code==-104||$image->error_code==-103||$image->error_code==-102||$image->error_code==-101)
            {
                if($need_return)
                {
                    return array('error'=>1,'message'=>'非法图像');
                }
                else
                    echo "非法图像";
            }
            exit;
        }
    }

    $water_image = APP_ROOT_PATH.app_conf("WATER_MARK");
    $alpha = app_conf("WATER_ALPHA");
    $place = app_conf("WATER_POSITION");

    foreach($list as $lkey=>$item)
    {
        //循环生成规格图
        foreach($whs as $tkey=>$wh)
        {
            $list[$lkey]['thumb'][$tkey]['url'] = false;
            $list[$lkey]['thumb'][$tkey]['path'] = false;
            if($wh[0] > 0 || $wh[1] > 0)  //有宽高度
            {
                $thumb_type = isset($wh[2]) ? intval($wh[2]) : 0;  //剪裁还是缩放， 0缩放 1剪裁
                if($thumb = $image->thumb($item['path'],$wh[0],$wh[1],$thumb_type))
                {
                    $list[$lkey]['thumb'][$tkey]['url'] = $thumb['url'];
                    $list[$lkey]['thumb'][$tkey]['path'] = $thumb['path'];
                    if(isset($wh[3]) && intval($wh[3]) > 0)//需要水印
                    {
                        $paths = pathinfo($list[$lkey]['thumb'][$tkey]['path']);
                        $path = $paths['dirname'];
                        $path = $path."/origin/";
                        if (!is_dir($path)) {
                            @mkdir($path);
                            @chmod($path, 0777);
                        }
                        $filename = $paths['basename'];
                        @file_put_contents($path.$filename,@file_get_contents($list[$lkey]['thumb'][$tkey]['path']));
                        $image->water($list[$lkey]['thumb'][$tkey]['path'],$water_image,$alpha, $place);
                    }
                }
            }
        }
        if($is_water)
        {
            $paths = pathinfo($item['path']);
            $path = $paths['dirname'];
            $path = $path."/origin/";
            if (!is_dir($path)) {
                @mkdir($path);
                @chmod($path, 0777);
            }
            $filename = $paths['basename'];
            @file_put_contents($path.$filename,@file_get_contents($item['path']));
            $image->water($item['path'],$water_image,$alpha, $place);
        }
    }
    return $list;
}

function empty_tag($string)
{
    $string = preg_replace(array("/\[img\]\d+\[\/img\]/","/\[[^\]]+\]/"),array("",""),$string);
    if(trim($string)=='')
        return $GLOBALS['lang']['ONLY_IMG'];
    else
        return $string;
    //$string = str_replace(array("[img]","[/img]"),array("",""),$string);
}

//验证是否有非法字汇，未完成
function valid_str($string)
{
    $string = msubstr($string,0,5000);
    if(app_conf("FILTER_WORD")!='')
        $string = preg_replace("/".app_conf("FILTER_WORD")."/","*",$string);
    return $string;
}


/**
 * utf8字符转Unicode字符
 * @param string $char 要转换的单字符
 * @return void
 */
function utf8_to_unicode($char)
{
    switch(strlen($char))
    {
    case 1:
        return ord($char);
    case 2:
        $n = (ord($char[0]) & 0x3f) << 6;
        $n += ord($char[1]) & 0x3f;
        return $n;
    case 3:
        $n = (ord($char[0]) & 0x1f) << 12;
        $n += (ord($char[1]) & 0x3f) << 6;
        $n += ord($char[2]) & 0x3f;
        return $n;
    case 4:
        $n = (ord($char[0]) & 0x0f) << 18;
        $n += (ord($char[1]) & 0x3f) << 12;
        $n += (ord($char[2]) & 0x3f) << 6;
        $n += ord($char[3]) & 0x3f;
        return $n;
    }
}

/**
 * utf8字符串分隔为unicode字符串
 * @param string $str 要转换的字符串
 * @param string $depart 分隔,默认为空格为单字
 * @return string
 */
function str_to_unicode_word($str,$depart=' ')
{
    $arr = array();
    $str_len = mb_strlen($str,'utf-8');
    for($i = 0;$i < $str_len;$i++)
    {
        $s = mb_substr($str,$i,1,'utf-8');
        if($s != ' ' && $s != '　')
        {
            $arr[] = 'ux'.utf8_to_unicode($s);
        }
    }
    return implode($depart,$arr);
}


/**
 * utf8字符串分隔为unicode字符串
 * @param string $str 要转换的字符串
 * @return string
 */
function str_to_unicode_string($str)
{
    $string = str_to_unicode_word($str,'');
    return $string;
}

//分词
function div_str($str)
{
    $words = words::segment($str);
    $words[] = $str;
    return $words;
}


/**
 *
 * @param $tag  //要插入的关键词
 * @param $table  //表名
 * @param $id  //数据ID
 * @param $field        // tag_match/name_match/cate_match/locate_match
 */
function insert_match_item($tag,$table,$id,$field)
{
    if($tag=='') {
        return ;
    }

    $unicode_tag = str_to_unicode_string($tag);
    $sql = "SELECT `{$field}` FROM " . DB_PREFIX . "{$table} WHERE `id` = '{$id}'";
    $rs = $GLOBALS['db']->getOne($sql);
    if (strpos(strval($rs), $unicode_tag) !== false) {
        return ;
    } else {
        $match_row = $GLOBALS['db']->getRow("select * from ".DB_PREFIX.$table." where id = ".$id);
        if($match_row[$field]=="")
        {
            $match_row[$field] = $unicode_tag;
            $match_row[$field."_row"] = $tag;
        }
        else
        {
            $match_row[$field] = $match_row[$field].",".$unicode_tag;
            $match_row[$field."_row"] = $match_row[$field."_row"].",".$tag;
        }
        $GLOBALS['db']->autoExecute(DB_PREFIX.$table, $match_row, $mode = 'UPDATE', "id=".$id, $querymode = 'SILENT');

    }
}

function get_all_parent_id($id,$table,&$arr = array())
{
    if(intval($id)>0)
    {
        $arr[] = $id;
        $pid = $GLOBALS['db']->getOne("select pid from ".$table." where id = ".$id);
        if($pid>0)
        {
            get_all_parent_id($pid,$table,$arr);
        }
    }
}

function syn_deal_match($deal_id)
{
    $deal = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal where id = ".$deal_id);
    if($deal)
    {
        $deal['name_match'] = "";
        $deal['name_match_row'] = "";
        $deal['deal_cate_match'] = "";
        $deal['deal_cate_match_row'] = "";
        $deal['type_match'] = "";
        $deal['type_match_row'] = "";
        $deal['tag_match'] = "";
        $deal['tag_match_row'] = "";
        $GLOBALS['db']->autoExecute(DB_PREFIX."deal", $deal, $mode = 'UPDATE', "id=".$deal_id, $querymode = 'SILENT');

        //同步名称
        $name_arr = div_str(trim($deal['name']));
        foreach($name_arr as $name_item)
        {
            insert_match_item($name_item,"deal",$deal_id,"name_match");
        }

        //分类类别
        $deal_cate =array();
        get_all_parent_id(intval($deal['cate_id']),DB_PREFIX."deal_cate",$deal_cate);
        if(count($deal_cate)>0)
        {
            $deal_cates = $GLOBALS['db']->getAll("select name from ".DB_PREFIX."deal_cate where id in (".implode(",",$deal_cate).")");
            foreach ($deal_cates as $row)
            {
                insert_match_item(trim($row['name']),"deal",$deal_id,"deal_cate_match");
            }
        }
        $goods_cate =array();
        get_all_parent_id(intval($deal['type_id']),DB_PREFIX."deal_loan_type",$goods_cate);
        if(count($goods_cate)>0)
        {
            $goods_cates = $GLOBALS['db']->getAll("select name from ".DB_PREFIX."deal_loan_type where id in (".implode(",",$goods_cate).")");
            foreach ($goods_cates as $row)
            {
                insert_match_item(trim($row['name']),"deal",$deal_id,"type_match");
            }
        }


    }
}


//封装url

function url($app_index,$route="index",$param=array())
{
    $key = md5("URL_KEY_".$app_index.$route.serialize($param));
    if(isset($GLOBALS[$key]))
    {
        $url = $GLOBALS[$key];
        return $url;
    }

    $url = load_dynamic_cache($key);
    if($url!==false)
    {
        $GLOBALS[$key] = $url;
        return $url;
    }

    $show_city = isset($GLOBALS['city_count']) && intval($GLOBALS['city_count'])>1?true:false;  //有多个城市时显示城市
    $route_array = explode("#",$route);

    if(isset($param)&&$param!=''&&!is_array($param))
    {
        $param['id'] = $param;
    }

    $module = isset($route_array[0]) ? strtolower(trim($route_array[0])) : '';
    $action = isset($route_array[1]) ? strtolower(trim($route_array[1])) : '';

    if(!$module||$module=='index')$module="";
    if(!$action||$action=='index')$action="";

    if(app_conf("URL_MODEL")==0)
    {
        //过滤主要的应用url
        if($app_index==app_conf("MAIN_APP"))
            $app_index = "index";

        //原始模式
        $url = APP_ROOT."/".$app_index.".php";
        if($module!=''||$action!=''||count($param)>0||$show_city) //有后缀参数
        {
            $url.="?";
        }

        if(isset($param['city']))
        {
            $url .= "city=".$param['city']."&";
            unset($param['city']);
        }
        if($module&&$module!='')
            $url .= "ctl=".$module."&";
        if($action&&$action!='')
            $url .= "act=".$action."&";
        if(count($param)>0)
        {
            foreach($param as $k=>$v)
            {
                if($k&&$v)
                    $url =$url.$k."=".urlencode($v)."&";
            }
        }
        if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);
        $GLOBALS[$key] = $url;
        set_dynamic_cache($key,$url);
        return $url;
    }
    else
    {
        //重写的默认
        $url = APP_ROOT;

        if($app_index!='index')
            $url .= "/".$app_index;

        if($module&&$module!='')
            $url .= "/".$module;
        if($action&&$action!='')
            $url .= "-".$action;

        if(count($param)>0)
        {
            $url.="/";
            foreach($param as $k=>$v)
            {
                if($k!='city')
                    $url =$url.$k."-".urlencode($v)."-";
            }
        }

        //过滤主要的应用url
        if($app_index==app_conf("MAIN_APP"))
            $url = str_replace("/".app_conf("MAIN_APP"),"",$url);

        $route = $module."#".$action;
        switch ($route)
        {
        case "xxx":
            break;
        default:
            break;
        }

        if(substr($url,-1,1)=='/'||substr($url,-1,1)=='-') $url = substr($url,0,-1);



        if(isset($param['city']))
        {
            $city_uname = $param['city'];
            if($city_uname=="all")
            {
                return get_http()."www.".app_conf("DOMAIN_ROOT").$url."/city-all";
            }
            else
            {
                $domain = get_http().$city_uname.".".app_conf("DOMAIN_ROOT");
                return $domain.$url;
            }
        }
        if($url=='')$url="/";
        $GLOBALS[$key] = $url;
        set_dynamic_cache($key,$url);
        return $url;
    }


}


function unicode_encode($name) {//to Unicode
    $name = iconv('UTF-8', 'UCS-2', $name);
    $len = strlen($name);
    $str = '';
    for($i = 0; $i < $len - 1; $i = $i + 2) {
        $c = $name[$i];
        $c2 = $name[$i + 1];
        if (ord($c) > 0) {// 两个字节的字
            $cn_word = '\\'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
            $str .= strtoupper($cn_word);
        } else {
            $str .= $c2;
        }
    }
    return $str;
}

function unicode_decode($name) {//Unicode to
    $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
    preg_match_all($pattern, $name, $matches);
    if (!empty($matches)) {
        $name = '';
        for ($j = 0; $j < count($matches[0]); $j++) {
            $str = $matches[0][$j];
            if (strpos($str, '\\u') === 0) {
                $code = base_convert(substr($str, 2, 2), 16, 10);
                $code2 = base_convert(substr($str, 4), 16, 10);
                $c = chr($code).chr($code2);
                $c = iconv('UCS-2', 'UTF-8', $c);
                $name .= $c;
            } else {
                $name .= $str;
            }
        }
    }
    return $name;
}

//生成短信发送的优惠券
/**
 *
 * @param $youhui_id 优惠券ID
 * @param $mobile 手机号
 * @param $user_id 会员ID
 * 以下参数仅供 send_type = 2 预订验证券使用
 * @param $order_count 预订的人数
 * @param $is_private_room  预订是否包间
 * @param $date_time  预订时间
 */
function gen_verify_youhui($youhui_id,$mobile,$user_id,$order_count=0,$is_private_room=0,$date_time=0)
{

    $data = array();
    $data['youhui_id'] = intval($youhui_id);
    $data['user_id'] = intval($user_id);
    $data['user_id'] = intval($user_id);
    $data['mobile'] = $mobile;
    $data['order_count'] = intval($order_count);
    $data['order_count'] = intval($order_count);
    $data['is_private_room'] = intval($is_private_room);
    $data['date_time'] = intval($date_time);
    $data['create_time'] = get_gmtime();
    $data['youhui_sn'] = rand(10000000,99999999);
    do{
        $GLOBALS['db']->autoExecute(DB_PREFIX."youhui_log", $data, $mode = 'INSERT', "", $querymode = 'SILENT');
        $rs = $GLOBALS['db']->insert_id();
    }while(intval($rs)==0);
    return $rs;
}


//发送优惠券短信(直接下载无验证类型), 函数不验证发送次数是否超限，前台发送时验证
function send_youhui_sms($youhui_id,$user_id,$mobile)
{
    if(app_conf("SMS_ON")==1&&$mobile!='')
    {

        $youhui_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."youhui where id = ".$youhui_id);
        if($youhui_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
            if($user_info)
            {
                $msg_data['dest'] = $mobile;
                $msg_data['send_type'] = 0;
                $msg_data['content'] = $youhui_data['sms_content'];
                $msg_data['send_time'] = 0;
                $msg_data['is_send'] = 0;
                $msg_data['create_time'] = get_gmtime();
                $msg_data['user_id'] = $user_info['id'];
                $msg_data['is_html'] = 0;
                $msg_data['is_youhui'] = 1;
                $msg_data['youhui_id'] = $youhui_id;
                $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
                $id = $GLOBALS['db']->insert_id();
                if($id)
                {
                    $GLOBALS['db']->query("update ".DB_PREFIX."youhui set sms_count = sms_count +1,view_count = view_count +1 where id = ".$youhui_id);
                    return $id;
                }
                else
                    return false;

            }
            else
                return false;
        }
        else
            return false;
    }
    else
    {
        return false;
    }
}
//发送优惠券短信(验证类型), 函数不验证发送次数是否超限，前台发送时验证
function send_youhui_log_sms($log_id)
{
    if(app_conf("SMS_ON")==1)
    {
        $log_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."youhui_log where id = ".$log_id);
        $youhui_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."youhui where id = ".$log_data['youhui_id']);
        if($youhui_data)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$log_data['user_id']);
            if($user_info)
            {
                $msg_data['dest'] = $log_data['mobile'];
                $msg_data['send_type'] = 0;
                $msg_data['content'] = $youhui_data['sms_content']." - 验证码:".$log_data['youhui_sn'];
                $msg_data['send_time'] = 0;
                $msg_data['is_send'] = 0;
                $msg_data['create_time'] = get_gmtime();
                $msg_data['user_id'] = $user_info['id'];
                $msg_data['is_html'] = 0;
                $msg_data['is_youhui'] = 1;
                $msg_data['youhui_id'] = $youhui_data['id'];
                $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
                $id = $GLOBALS['db']->insert_id();
                if($id)
                {
                    $GLOBALS['db']->query("update ".DB_PREFIX."youhui set sms_count = sms_count +1,view_count = view_count +1 where id = ".$youhui_data['id']);
                    return $id;
                }
                else
                    return false;

            }
            else
                return false;
        }
        else
            return false;
    }
    else
    {
        return false;
    }
}

//载入动态缓存数据
function load_dynamic_cache($name)
{
    if(isset($GLOBALS['dynamic_cache'][$name]))
    {
        return $GLOBALS['dynamic_cache'][$name];
    }
    else
    {
        return false;
    }
}

function set_dynamic_cache($name,$value)
{
    if(!isset($GLOBALS['dynamic_cache'][$name]))
    {
        if(isset($GLOBALS['dynamic_cache']) && count($GLOBALS['dynamic_cache'])>MAX_DYNAMIC_CACHE_SIZE)
        {
            array_shift($GLOBALS['dynamic_cache']);
        }
        $GLOBALS['dynamic_cache'][$name] = $value;
    }
}

/**
 * 检查自己是否是多投内部用户
 */
function is_duotou_inner_user() {
    if((int) app_conf('DUOTOU_INNER_SWITCH') !== 1) return true;

    //多投对内开放
    if(empty($GLOBALS['user_info']['group_id'])) return false;

    FP::import("libs.common.dict");
    return (in_array($GLOBALS['user_info']['group_id'], dict::get('DUOTOU_INNER_GROUP')) === true) ? true : false;
}

function load_auto_cache($key,$param=array())
{
    require_once ROOT_PATH."core/libs/cache/auto_cache.php";
    $file =  ROOT_PATH."core/system/auto_cache/".$key.".auto_cache.php";
    if(file_exists($file))
    {
        require_once $file;
        $class = $key."_auto_cache";
        $obj = new $class;
        $result = $obj->load($param);
    }
    else
        $result = false;
    return $result;
}

function rm_auto_cache($key,$param=array())
{
    require_once ROOT_PATH."core/libs/cache/auto_cache.php";
    $file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
    if(file_exists($file))
    {
        require_once $file;
        $class = $key."_auto_cache";
        $obj = new $class;
        $obj->rm($param);
    }
}


function clear_auto_cache($key)
{
    require_once ROOT_PATH."core/libs/cache/auto_cache.php";
    $file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
    if(file_exists($file))
    {
        require_once $file;
        $class = $key."_auto_cache";
        $obj = new $class;
        $obj->clear_all();
    }
}

/*ajax返回*/
function ajax_return($data)
{
    header("Content-Type:text/json; charset=utf-8");
    echo(json_encode($data));
    return false;
}

/**
 *
 * @param $location_id 店铺ID
 * @param $data_type  tuan/event/youhui/daijin
 */
function recount_supplier_data_count($location_id,$data_type)
{
    switch ($data_type)
    {
    case "tuan":
        $sql = " select count(*) from ".DB_PREFIX."deal_location_link as l left join ".DB_PREFIX."deal as d on d.id = l.deal_id where d.is_effect = 1 and d.is_delete = 0 and d.is_shop = 0 and d.time_status <> 2 and l.location_id = ".$location_id;
        $count = intval($GLOBALS['db']->getOne($sql));
        $GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set tuan_count = ".$count." where id = ".$location_id);
        break;

    case "daijin":
        $sql = " select count(*) from ".DB_PREFIX."deal_location_link as l left join ".DB_PREFIX."deal as d on d.id = l.deal_id where d.is_effect = 1 and d.is_delete = 0 and d.is_shop = 2 and d.time_status <> 2 and l.location_id = ".$location_id;
        $count = intval($GLOBALS['db']->getOne($sql));
        $GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set daijin_count = ".$count." where id = ".$location_id);
        break;

    case "shop":
        $sql = " select count(*) from ".DB_PREFIX."deal_location_link as l left join ".DB_PREFIX."deal as d on d.id = l.deal_id where d.is_effect = 1 and d.is_delete = 0 and d.is_shop = 1 and d.time_status <> 2 and l.location_id = ".$location_id;
        $count = intval($GLOBALS['db']->getOne($sql));
        $GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set shop_count = ".$count." where id = ".$location_id);
        break;

    case "event":
        $time = get_gmtime();
        $time_condition = '  and (e.event_end_time = 0 or e.event_end_time > '.$time.' ) ';
        $sql = " select count(*) from ".DB_PREFIX."event_location_link as l left join ".DB_PREFIX."event as e on e.id = l.event_id where e.is_effect = 1  $time_condition and l.location_id = ".$location_id;
        $count = intval($GLOBALS['db']->getOne($sql));
        $GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set event_count = ".$count." where id = ".$location_id);
        break;

    case "youhui":
        $time = get_gmtime();
        $time_condition = '  and (y.end_time = 0 or y.end_time > '.$time.' ) ';
        $sql = " select count(*) from ".DB_PREFIX."youhui_location_link as l left join ".DB_PREFIX."youhui as y on y.id = l.youhui_id where y.is_effect = 1  $time_condition and l.location_id = ".$location_id;
        $count = intval($GLOBALS['db']->getOne($sql));
        $GLOBALS['db']->query("update ".DB_PREFIX."supplier_location set youhui_count = ".$count." where id = ".$location_id);
        break;

    }

}

function build_deal_filter_condition($param,$is_store=false)
{
    $area_id = intval($param['aid']);
    $quan_id = intval($param['qid']);
    $cate_id = intval($param['cid']);
    $deal_type_id = intval($param['tid']);
    $purpose_id = intval($param['pid']);
    $purpose_type_id = intval($param['sid']);
    $avg_price = intval($param['a']);
    $city_id = intval($GLOBALS['deal_city']['id']);
    if($is_store){
        $deal_type = intval($param['deal_type']);
        $condition = " and deal_type = $deal_type ";
    }
    else{
        $condition="";
    }
    if($city_id>0)
    {
        $ids = load_auto_cache("deal_city_belone_ids",array("city_id"=>$city_id));
        if($ids)
            $condition .= " and city_id in (".implode(",",$ids).")";
    }
    if($area_id>0)
    {
        if($quan_id>0)
        {

            $area_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."area where id = ".$quan_id);
            $kw_unicodes[] = str_to_unicode_string($area_name);

            $kw_unicode = implode(" ",$kw_unicodes);
            //有筛选
            $condition .=" and (match(locate_match) against('".$kw_unicode."' IN BOOLEAN MODE)) ";
        }
        else
        {
            $ids = load_auto_cache("deal_quan_ids",array("quan_id"=>$area_id));
            $quan_list = $GLOBALS['db']->getAll("select `name` from ".DB_PREFIX."area where id in (".implode(",",$ids).")");
            $unicode_quans = array();
            foreach($quan_list as $k=>$v){
                $unicode_quans[] = str_to_unicode_string($v['name']);
            }
            $kw_unicode = implode(" ", $unicode_quans);
            $condition .= " and (match(locate_match) against('".$kw_unicode."' IN BOOLEAN MODE))";
        }
    }

    if($cate_id>0)
    {
        $cate_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_cate where id = ".$cate_id);
        $cate_name_unicode = str_to_unicode_string($cate_name);

        if($deal_type_id>0)
        {
            $deal_type_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."deal_cate_type where id = ".$deal_type_id);
            $deal_type_name_unicode = str_to_unicode_string($deal_type_name);
            $condition .= " and (match(deal_cate_match) against('+".$cate_name_unicode." +".$deal_type_name_unicode."' IN BOOLEAN MODE)) ";
        }
        else
        {
            $condition .= " and (match(deal_cate_match) against('".$cate_name_unicode."' IN BOOLEAN MODE)) ";
        }
    }

    if($purpose_id>0)
    {
        $unicode_purpose = array();
        if($purpose_type_id > 0){
            $purpose_type_name = $GLOBALS['db']->getOne("select name from ".DB_PREFIX."purpose_cate_type where id = ".$purpose_type_id);
            $unicode_purpose[] = str_to_unicode_string(str_replace("，","",$purpose_type_name));
        }
        else{
            $purpose_name= $GLOBALS['db']->getOne("select name from ".DB_PREFIX."purpose_cate where id = ".$purpose_id);
            $unicode_purpose[] = str_to_unicode_string(str_replace("，","",$purpose_name));
        }
        $kw_unicode = implode(" ", $unicode_purpose);
        $condition .= " and (match(purpose_match) against('".$kw_unicode."' IN BOOLEAN MODE))";
    }

    if($avg_price > 0){
        $condition .= " and avg_price = $avg_price ";
    }

    return $condition;
}

function is_animated_gif($filename){
    $fp=fopen($filename, 'rb');
    $filecontent=fread($fp, filesize($filename));
    fclose($fp);
    return strpos($filecontent,chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0')===false?0:1;
}


function make_deal_cate_js()
{
    $js_file = APP_RUNTIME_PATH."app/deal_cate_conf.js";
    if(!file_exists($js_file))
    {
        $js_str = "var deal_cate_conf = [";
        $deal_cates = $GLOBALS['db']->getAll("select id,name from ".DB_PREFIX."deal_cate where is_delete = 0 and is_effect = 1 order by sort desc");
        foreach($deal_cates as $k=>$v)
        {
            $js_str.='{"n":"'.$v['name'].'","i":"'.$v['id'].'","s":[';
            $js_str .= ']},';
        }
        if($deal_cates)
            $js_str = substr($js_str,0,-1);
        $js_str.="];";
        @file_put_contents($js_file,$js_str);
    }
}

function make_deal_region_js()
{
    $dir = APP_RUNTIME_PATH."app/deal_region_conf/";
    if (!is_dir($dir))
    {
        @mkdir($dir);
        @chmod($dir, 0777);
    }
    $js_file = $dir.intval($GLOBALS['deal_city']['id']).".js";
    if(!file_exists($js_file))
    {
        $js_str = "var deal_region_conf = [";
        $js_str.="];";
        @file_put_contents($js_file,$js_str);
    }
}

/**
 * 默认不生成 只有后台修改的时候生成
 * @param string $is_make
 */
function make_delivery_region_js($is_make=false)
{
    //$path = APP_STATIC_PATH."region.js";
    $path_app = APP_ROOT_PATH.'public/attachment/region.js';
    if(!$is_make){
        if(!file_exists($path_app)){
            $jsStr = "var regionConf = ".get_delivery_region_js();
            @file_put_contents($path_app,$jsStr,FILE_USE_INCLUDE_PATH);//前台
        }
        return;
    }
    @chmod($path_app, 0755);
    $jsStr = "var regionConf = ".get_delivery_region_js();
    @file_put_contents($path_app,$jsStr,FILE_USE_INCLUDE_PATH);//前台
}
function get_delivery_region_js($pid = 0)
{
    $jsStr = "";
    $redis = SiteApp::init()->dataCache->getRedisInstance();
    $withoutRedis = false;
    if(!$redis) {
        $withoutRedis = true;
    }
    $key = '_private_dr_' . $pid;
    $childRegionList = array();
    if ($withoutRedis || !$redis->exists($key)) {
        $childRegionList = $GLOBALS['db']->getAll('SELECT * FROM ' . DB_PREFIX . 'delivery_region WHERE pid = \'' . $pid . '\' ORDER BY id');
        if (!$withoutRedis) {
            $redis->set($key, json_encode($childRegionList), 'ex', 6*3600);
            $childRegionList = json_decode($redis->get($key), true);
        }
    }
    foreach($childRegionList as $childRegion) {
        if (empty($jsStr)) {
            $jsStr .= '{';
        }
        else {
            $jsStr .= ',';
        }
        $childStr = get_delivery_region_js($childRegion['id']);
        $jsStr .= "\"r{$childRegion['id']}\":{\"i\":{$childRegion['id']},\"n\":\"{$childRegion['name']}\",\"c\":{$childStr}}";
    }

    if (!empty($jsStr)) {
        $jsStr .= '}';
    }
    else {
        $jsStr .= '""';
    }

    return $jsStr;
}

function update_sys_config()
{
    $filename = APP_ROOT_PATH."public/sys_config.php";
    if(!file_exists($filename))
    {
        //定义DB
        require APP_ROOT_PATH.'system/db/db.php';
        $dbcfg = require APP_ROOT_PATH."public/db_config.php";
        define('DB_PREFIX', $dbcfg['DB_PREFIX']);
        if(!file_exists(APP_RUNTIME_PATH.'app/db_caches/'))
            mkdir(APP_RUNTIME_PATH.'app/db_caches/',0777);
        $pconnect = false;
        $db = new libs\db\MysqlDb($dbcfg['DB_HOST'].":".$dbcfg['DB_PORT'], $dbcfg['DB_USER'],$dbcfg['DB_PWD'],$dbcfg['DB_NAME'],'utf8',$pconnect);
        //end 定义DB

        $sys_configs = $db->getAll("select * from ".DB_PREFIX."conf");
        $config_str = "<?php\n";
        $config_str .= "return array(\n";
        foreach($sys_configs as $k=>$v)
        {
            $config_str.="'".$v['name']."'=>'".addslashes($v['value'])."',\n";
        }
        $config_str.=");\n ?>";
        file_put_contents($filename,$config_str);
        $url = APP_ROOT."/";
        return app_redirect($url);
    }
    }

    /**
     * 等额本息还款计算方式
     * $money 贷款金额
     * $rate 月利率
     * $remoth 还几个月
     * 返回  每月还款额
     */
    function pl_it_formula($money,$rate,$remoth){
        return $money * ($rate*pow(1+$rate,$remoth)/(pow(1+$rate,$remoth)-1));
    }

    /**
     * 按月还款计算方式
     * $total_money 贷款金额
     * $rate 年利率
     * 返回月应该还多少利息
     */
    function av_it_formula($total_money,$rate){
        return $total_money * $rate;
    }


    function is_has_empty_strings($param_array) {
        foreach($param_array as $param) {
            if (empty($param)) {
                return true;
            }
        }
        return false;
    }





/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
    {
        if (preg_match($chars, $user_email))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}

/**
 * 判断是否为手机号
 *
 * @Title: is_mobile
 * @Description: 判断是否为手机号
 * @param @param unknown_type $mobile
 * @return return_type
 * @author Liwei
 * @throws
 *
 */
function is_mobile($mobile) {
    //$chars = "/^13[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$/";
    $chars = '/^1[3456789]\d{9}$/';
    return preg_match($chars, $mobile) ? true : false;
}

/**
 * 判断是否是固定电话
 *
 * @param string companyPhone
 * @return bool
 */
function is_telephone($data) {
    return preg_match("/(^0?(1[3456789]\d{9}$))|(^0\d{2,3}-\d{7,8}(-\d{1,6})?$)/", $data);
}


function get_wordnum($str = ''){
    return mb_strlen($str,'UTF-8');
}

/**
 * 字符串截取(两个英文或者数字当成一个中文处理)，保证截取的整齐
 * @author wenyanlei  2013-8-20
 * @param $string string 要截取的字符串
 * @param $length int 截取的长度
 * @param $encoding string 字符编码
 * @return string
 */
function cutstr($string, $length = 10, $end = '...', $encoding = 'utf-8') {
    $string = trim ( $string );

    if ($length && strlen ( $string ) <= $length) return $string;

    // 截断字符
    $wordscut = '';
    if (strtolower ( $encoding ) == 'utf-8') {
        // utf8编码
        $n = $tn = $noc = 0;
        while ( $n < strlen ( $string ) ) {
            $t = ord ( $string [$n] );
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
            if ($noc >= $length) break;
        }
        if ($noc > $length) $n -= $tn;
        $wordscut = substr ( $string, 0, $n );
    } else {
        for($i = 0; $i < $length - 1; $i ++) {
            if (ord ( $string [$i] ) > 127) {
                $wordscut .= $string [$i] . $string [$i + 1];
                $i ++;
            } else {
                $wordscut .= $string [$i];
            }
        }
    }

    if(strLength($string) > $length/2)    $wordscut .= $end;
    return trim($wordscut);
}

/**
 * 根据配置，获取借款标题
 * @author wenyanlei  2013-8-20
 * @param $title string 借款说明
 * @param $name string  借款用途的名称
 * @param $deal_id int 借款id
 * @return string
 */
function get_deal_title($title, $name = '', $deal_id = 0){
    $type = app_conf('DEAL_TITLE_TYPE');
    if($type == 1){
        return $title;
    }

    if($name != ''){
        return $name;
    }

    if($name == '' && $deal_id == 0){
        return $title;
    }

    if($deal_id > 0){
        return $GLOBALS['db']->get_slave()->getOne("SELECT b.name FROM ".DB_PREFIX."deal a left join ".DB_PREFIX."deal_loan_type b on a.type_id = b.id where a.id = ".$deal_id);
    }
}

/**
 * 生成合同编号
 */
function get_contract_number($deal, $user_id, $load_id, $type=NULL){
    $load_id = str_replace(",", "", $load_id);
    //判断子母单和普通单的情况
    if ($deal['parent_id'] == -1){
        return str_pad($deal['id'],6,"0",STR_PAD_LEFT).'01'.str_pad($type,2,"0",STR_PAD_LEFT).str_pad($user_id,8,"0",STR_PAD_LEFT).str_pad($load_id,10,"0",STR_PAD_LEFT);
    }elseif ($deal['parent_id'] > 0){
        return str_pad($deal['id'].'02'.$deal['parent_id'].$type.$user_id.$load_id,16,"0",STR_PAD_LEFT);
    }else {
        return str_pad($deal['id'].'03'.$type.$user_id.$load_id,16,"0",STR_PAD_LEFT);
    }
}

/**
 * 下载word文件
 * @author wenyanlei  2013-8-22
 * @param $msg string 文件内容
 * @param $filename string 文件名
 * @return file
 */
function export_word_doc($msg, $filename = ''){

    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    $wordStr = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40">';

    $wordStr .= $msg;

    $wordStr .= '</html>';

    if($filename == ''){
        $filename = format_date(time(), 'YmdHi');
    }
    $file = iconv("utf-8", "GBK", $filename);

    header("Content-Type: application/doc");
    header("Content-Disposition: attachment; filename=" . $file . ".doc");
    echo $wordStr;
}

function get_deal_status($deal_status)
{
    $status = array(
        0 => '等待确认',
        1 => '进行中',
        2 => '满标',
        3 => '流标',
        4 => '还款中',
        5 => '已还清',
    );
    $text = $status[$deal_status];
    return $text ? $text : '未知';
}

function get_user_info($id,$return_all=false)
{
    if(empty($id)) return false;
    $user_info = $GLOBALS['db']->get_slave()->getRow("select * from ".DB_PREFIX."user where id = ".$id);
    if($return_all) return $user_info;
    $str = $user_info['user_name'];
    if($user_info['mobile']!='')
    {
        $str .="(".$GLOBALS['lang']['MOBILE'].":".$user_info['mobile'].")";
    }
    return $str;
}

/**
 * 用于后台手工添加渠道信息
 */
function add_deal_channel_by_post() {
    $channel_value = $_POST['channel_value'];
    $channelFeeService = new \core\service\ChannelFeeService();
    return $channelFeeService->add_deal_channel($channel_value);
}

/**
 * 获得合同分类
 * @author wenyanlei  2013-11-1
 * @return array
 */
function get_contract_type(){
    $type_list = $GLOBALS ['db']->getAll( "select * from " . DB_PREFIX . "msg_category where use_status = 1 and is_delete = 0 and is_contract = 1 and type_tag != ''" );
    $type_arr = array();
    if($type_list){
        foreach($type_list as $val){
            $type_arr[$val['type_tag']] = $val['type_name'];
        }
    }else{
        $type_arr = $GLOBALS['dict']['CONTRACT_TPL_TYPE'];
    }

    return $type_arr;
}

/**
 * 获得标的所属站点
 * @author wenyanlei  2013-11-20
 * @param $deal_id
 * @param $istitle 是否返回域名
 * @return string
 */
function get_deal_domain($deal_id,$istitle=false){
    $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN'];
    $site_id = $GLOBALS ['db']->get_slave()->getOne( "select site_id from " . DB_PREFIX . "deal_site where deal_id = ".intval($deal_id)." limit 1");
    if(empty($site_id)){
        $site_id = 1;
    }
    $site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);
    $deal_site = $site_list[$site_id];
    if($istitle){
        return $deal_site;
    }
    $deal_domain = $site_domain[$deal_site];
    if (!$deal_domain) {
        $deal_domain = $site_domain['firstp2p'];
    }
    $protocol = get_http();
    return $protocol.$deal_domain;
}

/**
 * 获取标的站点id
 * @param $deal_id
 * @return int
 */
function get_deal_siteid($deal_id) {
    $site_id = $GLOBALS ['db']->get_slave()->getOne( "select site_id from " . DB_PREFIX . "deal_site where deal_id = ".intval($deal_id)." limit 1");
    if(empty($site_id)){
        $site_id = 1;
    }
    return $site_id;
}

/**
 * 当前所有站点模板列表
 * @return mix
 */
function get_sites_template_list(){
    return $GLOBALS['sys_config']['TEMPLATE_LIST'];
}

/**
 * 返回当前订单所属的网站id
 * @param int $deal_id
 * @return mix array(1=>default,2=>9888)
 */
function get_deal_site($deal_id){

    $sql = "select * from ".DB_PREFIX."deal_site where deal_id=".$deal_id;
    $deal_sites = $GLOBALS['db']->getAll($sql);
    if(!$deal_sites) return array();

    $template_list = get_sites_template_list();
    $template_list_flip = array_flip($template_list);//id做key,name做value

    $deal_sites_data = array();
    foreach($deal_sites as $k=>$v){
        $deal_sites_data[$v['site_id']]= $template_list_flip[$v['site_id']];
    }
    return $deal_sites_data;
}

/**
 * 获得标的是否属于当前站点
 * @param $deal_id
 * @return bool
 */
function deal_belong_current_site($deal_id){
    //此逻辑有问题，临时关闭
    return true;
    $deal_site = $GLOBALS ['db']->get_slave()->getOne( "select site_id from " . DB_PREFIX . "deal_site where deal_id = ".intval($deal_id)." limit 1");
    if(empty($deal_site)){
        $deal_site = 1;
    }
    $siteId = \libs\utils\Site::getId();
    $dealSiteAllow = get_config_db('DEAL_SITE_ALLOW', $siteId);
    $dealSiteAllow = explode(",", $dealSiteAllow);
    return in_array($deal_site, $dealSiteAllow);
}
/**
 * 订单 对应的 站点名称
 * @param unknown $deal_id
 * @return Ambigous <string>
 */
function get_deal_domain_title($deal_id){
    $site_id = get_deal_domain($deal_id,true);
    $site_list = $GLOBALS['sys_config']['SITE_LIST_TITLE'];
    return $site_list[$site_id];
}

/**
 * 是否主站
 */
function isMainSite() {
    return app_conf('TEMPLATE_ID') == 1;
}

/**
 * 判断若为分站，是否可用优惠券
 */
function isCouponValidForBranchSite(){
    //return false;
    //优惠券总开关关闭
    if(app_conf('TURN_ON_COUPON') != '1'){
        return false;
    }
    $siteId = \libs\utils\Site::getId();
    return get_config_db('TURN_ON_COUPON_BRANCH_SITE', $siteId) == '1' || isMainSite();
}

/**
 * 后台根据订单获取对应分站的system_conf配置信息
 *
 * @param $deal_id 订单id
 * @param $conf_name 配置项字段名
 * @return string 配置项值
 */
function get_system_conf_by_deal($deal_id, $conf_name){
    $deal_domain = get_deal_domain($deal_id, true);
    $config = require(APP_ROOT_PATH."conf/system_".$deal_domain.".conf.php");
    if (isset($config[$conf_name])) {
        return stripslashes($config[$conf_name]);
    } else {
        return app_conf($conf_name);
    }
}


/**
 * 后台获取对应分站的配置信息
 *
 * @param $conf_name 配置项字段名
 * @param $site_id 分站id
 * @return string 配置项值
 */
function get_config_db($conf_name, $site_id) {
    if (empty($site_id)) {
        return app_conf($conf_name);
    }

    $config_db_all = $GLOBALS['sys_config_db'];
    if (isset($config_db_all[$site_id][$conf_name])) {
        return stripslashes($config_db_all[$site_id][$conf_name]);
    } else {
        return app_conf($conf_name);
    }

}

/**
 * 获取POST GET 字符串参数
 * changlu
 * @param string $parm
 * @param string $default ''
 * return string A;
 */
function getRequestString($parm, $default = ''){
    $string = isset($_GET[$parm]) ? $_GET[$parm] : (isset($_POST[$parm]) ? $_POST[$parm] : $default);
    $string = trim($string);
    if(!get_magic_quotes_gpc())$string = addslashes($string);

    return $string;
}

/**
 * 获取POST GET 整型参数
 * changlu
 * @param string $parm
 * @param string $default 0
 * return int A;
 */
function getRequestInt($parm, $default = 0){
    $string = isset($_GET[$parm]) ? $_GET[$parm] : (isset($_POST[$parm]) ? $_POST[$parm] : "");
    return empty($string)?$default : intval($string);
}


/**
 * 姓名格式化
 * @param unknown $str
 * @param number $start
 * @param unknown $end
 * @param string $suffix
 */
function nameFormat($name,$lengh=1,$suffix="*"){
    $name = trim($name);
    if(!$name){
        return false;
    }
    $show = msubstr($name,0,$lengh,'utf-8',false);
    $show = $show .str_repeat($suffix, mb_strlen($name,'utf8')-$lengh);
    return $show;
}
/**
 * 用户名 格式化 隐藏部分内容
 * changlu
 * 姓名格式化
 * @param unknown $str
 * @param number $start
 * @param unknown $end
 * @param string $suffix
 */
function user_name_format($name,$lengh=2){
    $name = trim($name);
    if(!$name){
        return false;
    }
    $len = mb_strlen($name,'utf8');
    $limit = $lengh*2;
    if($len > $limit){
        return msubstr($name,0,$lengh,'utf-8',false).'***'.msubstr($name,-$lengh,$lengh,'utf-8',false);
    }
    return msubstr($name,0,$lengh-1,'utf-8',false)."***";
}

/**
 * idno 格式化
 */
function idnoFormat($id){
    $idLen = strlen($id);
    if(!empty($id) && $idLen <= 8){
        $hideNumber = $idLen < 4 ? 1 : floor($idLen / 4);
        return substr($id,0,$hideNumber).str_repeat("*", $idLen-2*$hideNumber).substr($id, -$hideNumber);
    }
    return bankidFormat($id);
}

/**
 *身份证号显示前三后四
 */
function idnoNewFormat($id){
    if(!$id){
        return '';
    }
    $id = trim($id);
    return substr($id, 0,3).str_repeat("*", strlen($id)-7).substr($id, -4);
}
/**
 * 格式化 银行卡号
 * @param unknown $id
 */
function bankidFormat($id, $hideNumber = 2) {
    if(!$id){
        return '';
    }
    $id = trim($id);
    return substr($id, 0, $hideNumber) . str_repeat("*", strlen($id)-(2*$hideNumber)) . substr($id, -$hideNumber);
}
/**
 * 格式化 银行卡号
 * @param string $id 银行卡号
 */
function bankNoFormat($id, $prefixHide = 6, $suffixHide = 4) {
    if(empty($id)) {
        return '';
    }
    $id = trim($id);
    return substr($id, 0, $prefixHide) . str_repeat("*", (strlen($id)-$prefixHide-$suffixHide)) . substr($id, -$suffixHide);
}
/**
 * 手机号码格式化
 * @param int $id
 * @param int $mobile_code default = ''
 */
function moblieFormat($id,$mobile_code = ''){
    if(!$id){
        return '';
    }

    $mobile_code = (!empty($mobile_code) && $mobile_code != '86')? $mobile_code.'-':'';

    return $mobile_code.substr($id, 0,strlen($id)-8).str_repeat("*", 4).substr($id, -4);
}
/**
 * 格式化企业用户接收短信，用于前端页面展示
 * @param string $mobileString
 * @author guofeng
 */
function enterpriseMobileFormat($mobileString) {
    $mobileNewString = trim($mobileString, ',');
    if (!empty($mobileNewString)) {
        $mobileList = explode(',', $mobileNewString);
        $mobileList = array_unique($mobileList);
        $enterpriseMobileArray = array();
        foreach ($mobileList as $k => $mobileItem) {
            if (strpos($mobileItem, '-') !== false) {
                list($countryCode, $mobile) = explode('-', $mobileItem);
            }
            $enterpriseMobileArray[] = moblieFormat(isset($mobile) ? $mobile : $mobileItem, isset($countryCode) ? $countryCode : '');
        }
        return join(',', $enterpriseMobileArray);
    }
    return '';
}
/**
 * 邮件格式化 前台显示
 * @param string $mail
 * @param int $type 0- 24********@qq.com  1- abcd********.com
 * return string
 */
function mailFormat($mail, $type=0){
    if ($type == 0) {
        $mail = trim($mail);
        $arr = explode("@", $mail);
        if($arr[0] && $arr[1]){
            $str = substr($arr[0], 0,2).str_repeat("*", strlen($arr[0])-2).'@'.$arr[1];
            return $str;
        }
    } elseif ($type == 1) {
        $str = substr($mail, 0, 4) . str_repeat("*", 8) . substr($mail, strlen($mail)-4);
        return $str;
    }
}

/**
 * 隐藏 用户敏感信息 主要用于合同模板
 * @param unknown $str
 * @return mixed
 */
function hide_message($str){
    //FIRSTPTOP-332 合同中个人信息均显示出来，不做星号隐去   edit by wenyanlei
    return $str;

    $str = preg_replace("#(证号：\s*)(.{8,18})(<\/p>)#e","$1.idnoFormat('$2')",$str);
    $str = preg_replace("#(证号\（或营业执照号\）：)(.{8,18})(<\/p>)#e","$1.idnoFormat('$2')",$str);
    $str = preg_replace("#(行账号：)(\d{12,20})#e","$1.bankidFormat('$2')",$str);
    $str = preg_replace("#(话：)(\d{11})#e","$1.moblieFormat('$2')",$str);
    $str = preg_replace("#(乙方\）：)(.+)(<\/p>)#e","$1.nameFormat('$2')",$str);
    //$str = preg_replace("#(甲方\）：)(.+)(<\/p>)#e","$1.nameFormat('$2')",$str);
    $str = preg_replace("#(开户名：)(.+)(<\/p>)#e","$1.nameFormat('$2')",$str);
    $str = preg_replace("#(电子邮箱：)(.+)(<\/p>)#e","$1.mailFormat('$2')",$str);
    return $str;
}


//上传图像公用方法

/**
 * 获取 文件存放地址
 * @param int $type 默认yi  以后扩展存储方式的时候用 暂时不用
 * @return string
 */
function get_dir_path($app=''){

    //     $app = check_upload_app($app);
    $dir_name = 'attachment';
    if($app){
        $dir_name = $dir_name . "/" . $app;
    }
    $dir_name = $dir_name . "/" . to_date(get_gmtime(), "Ym");
    $dir_name = $dir_name . "/" . to_date(get_gmtime(), "d");
    $dir_name = $dir_name . "/" . to_date(get_gmtime(), "H");
    $dir_name = $dir_name . "/" . md5(mt_rand(1, 9999) . uniqid().time());
    return $dir_name;
}

/**
 * 检测 app id 的合法性 暂时为空
 * @param unknown $app
 */
function check_upload_app($app=''){
    $app = '';//默认空
    $arr = array();
    // todo something  过滤不合法的 app
    return trim($app);
}

/**
 * 上传文件公用方法
 *
 * @author  wangqunqiang<wangqunqiang@ucfgroup.com>
 * @param array $fileInfo
 * <code>
 $fileInfo = array(
     'file' => '', //文件域信息数组
     'isImage' => 0|1,  //是否是图片
     'asAttachment' => 0|1, //是否作为附件保存
     'app' => '', // 记录上传入口名称
     'asPrivate' => 0|1, // 是否是隐私文件，存放到私有域
     'other' => '', // 补充信息,
     'limitSizeInKB' => 1.5*1024, // 上传文件需要限制大小，单位KB
 );
* </code>
    * @return array $results 返回上传结果
 */
function uploadFile($fileInfo= array())
{
    // 返回校验结果
    $results = array();
    $results['is_priv'] = isset($fileInfo['asPrivate']) ? intval($fileInfo['asPrivate']) : 0;
    $results['is_image'] = isset($fileInfo['isimage']) ? intval($fileInfo['isimage']) : 0;
    $results['status'] = false;

    //参数校验
    if (!isset($fileInfo['file']))
    {
        $results['errrors'] = array('请选择要上传的文件。');
        return $results;
    }

    // 初始化未填写参数
    // 是否是图片
    $fileInfo['isImage'] = $fileInfo['isImage'] ?: 0;
    // 是否作为附件保存
    $fileInfo['asAttachment'] = $fileInfo['asAttachment'] ?: 0;
    // 分站主站标志
    $fileInfo['app'] = $fileInfo['app']?: '';
    // 作为隐私文件
    $fileInfo['asPrivate'] = $fileInfo['asPrivate'] ?: false;
    // 附加信息
    $fileInfo['other'] = $fileInfo['other'] ?: '';
    $fileInfo['desc'] = $fileInfo['desc'] ?: '';
    $fileInfo['remark'] = $fileInfo['remark'] ?: '';
    // 上传文件大小限制，目前只针对图像做处理（默认单位KB, 默认值1.5MB）
    $fileInfo['limitSizeInMB'] = floatval($fileInfo['limitSizeInMB']) ?: 0;

    FP::import("app.Lib.upload");
    $app = trim($fileInfo['app']);

    // 是否指定保存路径
    if (!empty($fileInfo['savePath'])) {
        $dir_name = $fileInfo['savePath'];
        $new_file_name = sprintf('sign_%s.%s', uniqid(), pathinfo($fileInfo['file']['name'], PATHINFO_EXTENSION));
    } else {
        $dir_name = get_dir_path($app);
        $new_file_name = 'index.jpg';
    }

    $upload = new upload($dir_name,$fileInfo['isImage'],$fileInfo['asPrivate']);
    if(!$fileInfo['file']){
        $file = $_FILES['file'];
    }

    // NOTE: 如果是图片的话限制最大1MB
    $tmpfile = is_file($fileInfo['file']) ? $fileInfo['file'] : @$fileInfo['file']['tmp_name'];
    $imageinfo = getimagesize($tmpfile);
    $imagetype = image_type_to_extension($imageinfo[2]);

    if($fileInfo['isImage'] || $imagetype == ".png" || $imagetype == ".jpeg" || $imagetype == ".gif") {
        $upload->file($fileInfo['file'],$fileInfo['asPrivate']);
        // 如果没有设置文件大小限制，使用前台1.5MB,后台1MB的默认值
        if(empty($fileInfo['limitSizeInMB']))
        {
            $fileInfo['limitSizeInMB'] = (stripos($_SERVER['HTTP_HOST'], 'admin') !== false) ? 1:1.5;
        }
        $upload->set_max_file_size($fileInfo['limitSizeInMB']);//限制1.5M 以内
        $results = $upload->upload($new_file_name, $fileInfo['asPrivate']);//图片统一用jpg 方便处理 本来是不加后缀的这样需要改rewrite
    }else{
        $upload->file($fileInfo['file']);
        $results = $upload->upload(null, $fileInfo['asPrivate']);
    }
    $results['is_priv']  = $fileInfo['asPrivate'];
    $results['isimage'] = $fileInfo['isImage'];
    if($fileInfo['asAttachment'] && $results['status']){
        //写入数据库
        $data = array();
        $data['app_name'] = $app;
        $data['filename'] = $results['original_filename'];
        $data['filesize'] = $results['size_in_bytes'];
        $data['attachment'] = $results['full_path'];
        $data['description'] = $fileInfo['desc'];
        $data['remark'] = $fileInfo['remark'];
        $data['isimage'] = $fileInfo['isImage'];
        $data['is_priv'] = $fileInfo['asPrivate'];
        $data['other'] = $fileInfo['other'];
        $data['create_time'] = time();
        isset($fileInfo['userId']) && $data['user_id'] = $fileInfo['userId']; // 用户ID标识
        $re = $GLOBALS['db']->autoExecute(DB_PREFIX."attachment",$data,'INSERT','','SILENT');
        $aid = $GLOBALS['db']->insert_id();
        //附件表id
        $results['aid'] = $aid;
    }
    return $results;
}

/**
 * 简化的保存文件方法
 * @param $file string 文件名
 */
function add_file($file) {
    if (!$file) {
        return false;
    } else {
        $data = array(
            "filename" => basename($file),
            "attachment" => $file,
            "create_time" => get_gmtime(),
        );
        $GLOBALS['db']->autoExecute(DB_PREFIX."attachment",$data,'INSERT','','SILENT');
        return $GLOBALS['db']->insert_id();
    }
}

/**
 * 获取 后台附件 图片url
 * @param unknown $url
 * @return string
 */
function get_www_url($url="", $domain_suffix = 'com'){

    $url = trim(trim($url),'./');
    $host = $_SERVER['SERVER_NAME'];
    $arr = explode(".", $host);
    $count = count($arr);

    if($arr[0] && $arr[0]!='admin'){
        if($count == 2){//firstp2p.com 这种的
            return sprintf('%swww.firstp2p.%s/%s', get_http(), $domain_suffix, $url);
        }
        return sprintf('%s%s.firstp2p.%s/%s', get_http(), $arr[0], $domain_suffix, $url);
    }else{
        return sprintf('%swww.firstp2p.%s/%s', get_http(), $domain_suffix, $url);
    }
}

/**
 * 获取附件
 * @param unknown $id
 * @param string isssrc 是否是路径
 * @param string $is_admin 是否是后台
 * @param string $is_zip 兼容每日借款和投资统计 zip附件存储在runtime文件夹的情况
 */
function get_attr($id, $issrc=false, $is_admin=true, $is_zip=false){
    $id = intval($id);
    if(!$id){
        return false;
    }
    $sql = 'SELECT * FROM '.DB_PREFIX.'attachment WHERE id='.$id;
    $info = $GLOBALS['db']->getRow($sql);
    if(!$info || $info['is_delete']){
        return false;
    }

    //兼容每日借款和投资统计 zip附件存储在runtime文件夹的情况
    if($is_zip){
        return $info;
    }

    if($is_admin == false && $info['attachment']){
        require_once APP_ROOT_PATH . 'libs/vfs/VfsHelper.php';
        return libs\vfs\VfsHelper::image('/'.$info['attachment'], $is_admin);
    }

    $www = false !== strpos($_SERVER['SERVER_NAME'], 'local') ? '' : 'www.';
    $http .= get_http() . str_replace('admin.', $www, $_SERVER['SERVER_NAME']) . '/';
    if($is_admin) {
        // 读取管理员session信息
        $adminInfo = \es_session::get(md5(app_conf('AUTH_KEY')));
        // 设置管理员浏览图片的临时cookie
        $adminInfo && setGlobalTmpCookie('am_vwpic', json_encode(array('adm_id'=>$adminInfo['adm_id'], 'adm_name'=>$adminInfo['adm_name'], 'adm_role_id'=>$adminInfo['adm_role_id'])));
    }
    return $http . 'attachment-view?file='. urlencode($info['attachment']);
}

/**
 * 删除 附件 物理删除
 * @param unknown $id
 */
function del_attr($id){
    $id = intval($id);
    if(!$id){
        return ;
    }
    $sql = 'SELECT * FROM '.DB_PREFIX.'attachment WHERE id='.$id;
    $info = $GLOBALS['db']->getRow($sql);
    if($info['attachment']){
        $path = APP_WEBROOT_PATH.$info['attachment'];
        if(file_exists($path)){
            unlink($path);
            rmdir(substr($path,0,strripos($path,'/')));//删除目录
        }
    }
    if($id){
        $sql_del = 'DELETE FROM '.DB_PREFIX.'attachment WHERE `id` = '.$id;
        return $GLOBALS['db']->query($sql_del);
    }
    return false;
}

/**
 * 获取投资数目
 */
function get_loan_num() {
    $user_id = intval($GLOBALS['user_info']['id']);
    //$num = \app\models\dao\DealLoad::instance()->getLoanNumByUserId($user_id);
    $dealLoadService = new \core\service\DealLoadService();
    $result = $dealLoadService->getUserLoadList($user_id);
    $num = $result['count'];
    return $num > 0 ? "<em>{$num}</em>" : "";
}

/**
 * 显示数字图片
 * @param $string string 数字字符串
 * @param $type int 字体颜色 1-橙色 2-黑色
 * @return string html 内容
 */
function get_num_pic($string, $type) {
    $content = "";
    $len = strlen($string);
    for ($i=0; $i<$len; $i++) {
        $char = $string{$i};
        switch ($char) {
        case "0": $num = 'zero'; break;
        case "1": $num = 'one'; break;
        case "2": $num = 'two'; break;
        case "3": $num = 'three'; break;
        case "4": $num = 'four'; break;
        case "5": $num = 'five'; break;
        case "6": $num = 'six'; break;
        case "7": $num = 'seven'; break;
        case "8": $num = 'eight'; break;
        case "9": $num = 'nine'; break;
        case ",": $num = 'comma'; break;
        case ".": $num = 'point'; break;
        default : continue 2;
        }

        if ($type == 1) {
            $content .= '<i class="ico_';
        } elseif ($type == 2) {
            $content .= '<i class="';
        }

        $content .= $num . '" alt="' . $char . '"></i>';
    }
    return $content;
}

/**
 * 获取广告信息
 */
function get_adv ($adv_id) {
    $adv_service = new \core\service\AdvService();
    $advlist = \SiteApp::init()->dataCache->call($adv_service, 'getAdv', array($adv_id), 30);
    return $advlist;
}

/**
 * 浮动利率提示框
 */
function get_rate_tips() {
    $content = '<i title="平台补贴利率" class="ico_sigh j_tips"></i>';
    return $content;
}

/**
 * 格式化银行卡
 * @author  caolong
 * @date    2014-1-23
 * @param string $str
 * @return string|boolean
 */
function formatBankcard($str = '', $type=0) {
    if(!empty($str)) {
        $len = mb_strlen($str,'utf8');
        if($len < 8) {
            return $str;
        }else{
            $start = Vsubstr($str, 0,4);
            $end   = Vsubstr($str,$len-4,4);
            if ($type == 0) {
                $string= str_repeat('*',$len-8);
            } elseif ($type == 1) {
                $string = str_repeat('*', 8);
            }
            return $start.$string.$end;
        }
    }else{
        return false;
    }
}
/**
 * 中文截取
 * @param unknown $str
 * @param unknown $start
 * @param unknown $length
 * @param string $end
 * @param string $isextend
 * @return Ambigous <unknown, string>
 * @author caolong
 */
function Vsubstr($str, $start, $length) {
    $totalLength = mb_strlen($str,'utf8');
    $cutNum = $length;
    $subStr = mb_substr($str,$start,$cutNum,'utf8');
    $sub_str = strstr(substr($subStr,-13),'[');
    if($sub_str){
        $cutNum = $cutNum -strlen($sub_str);
        $subStr = mb_substr($str,$start,$cutNum,'utf8');
    }
    return $subStr;
}
/**
 * 获取机构用户id列表
 * @return array
 */
function get_agency_user_ids() {
    $deal_agency = new \app\models\dao\DealAgency();
    $user_ids = $deal_agency->findAllBySql("SELECT `user_id` FROM " . $deal_agency->tableName());
    $result = array();
    foreach ($user_ids as $val) {
        $result[] = $val['user_id'];
    }
    return array_unique($result);
}

/**
 * 将管理系统上传的文件替换至静态域名下
 * @param string $content
 * @return string
 */
function convert_upload($content) {
    $domain = app_conf("PUBLIC_DOMAIN_ROOT")=='' ? get_domain() : app_conf("PUBLIC_DOMAIN_ROOT");
    // $content = str_replace($GLOBALS['IMG_APP_ROOT']."./public/",$domain."/public/", $content);
    $content = str_replace("./public/",$domain."/public/",$content);
    $content = str_replace("./attachment", app_conf("STATIC_UPLOAD_HOST"), $content);
    $content = str_replace("./static/", \SiteApp::init()->asset->getStaticRoot(), $content);
    $content = str_replace("./",get_domain().APP_ROOT."/",$content);
    return $content;
}

/**
 * 敏感信息隐藏显示
 * @param $str string
 * @param $type int 0-注册地址 1-企业名称 2-营业执照号 3-姓名 4-电话号码
 * @return string
 */
function block_info($str, $type) {
    $len = get_wordnum($str);

    if ($type == 0) {
        if ($len >= 4) {
            return mb_substr($str, 0, 2, 'UTF-8') . str_repeat("*", 6) . mb_substr($str, $len-2, $len, 'UTF-8');
        } else {
            return $str;
        }
    }
    if ($type == 1) {
        if ($len >= 4) {
            return mb_substr($str, 0, 2, 'UTF-8') . str_repeat("*", 4) . mb_substr($str, $len-2, $len, 'UTF-8');
        } else {
            return $str;
        }
    } elseif ($type == 2) {
        if ($len > 4) {
            return mb_substr($str, 0, 2, 'UTF-8') . str_repeat("*", $len-4) . mb_substr($str, $len-2, $len, 'UTF-8');
        } else {
            return $str;
        }
    } elseif ($type == 3) {
        if ($len >= 2) {
            return mb_substr($str, 0, 1, 'UTF-8') . str_repeat("*", $len-1);
        } else {
            return $str;
        }
    } elseif ($type == 4) {
        $arr = explode("-", $str, 2);
        if (count($arr) == 2) {
            $l = mb_strlen($arr[1]);
            if ($l>4) {
                return $arr[0] . "-" . mb_substr($arr['1'], 0, 4, 'UTF-8') . str_repeat("*", $l-4);
            } else {
                return $str;
            }
        } else {
            if ($len>5) {
                return mb_substr($str, 0, 3, 'UTF-8') . str_repeat("*", $len-5) . mb_substr($str, $len-2, $len, 'UTF-8');
            } else {
                return $str;
            }
        }
    } else {
        return "";
    }
}

/**
 * 格式化配置字符串
 */
function formatConf($conf)
{
    if(!empty($conf))
    {
        $conf = str_replace('，',',', $conf);
        return trim($conf,',');
    }
    else
    {
        return NULL;
    }
}

/*
 * 根据类型得到一对时间戳 start  end
 * @param string $type
 */
function getTimeStartEnd($type){
    $data = array();

    switch($type){
    case 'last_month':   //获得上个月的开始/结束 时间
        $data['start'] = mktime(-8,0,0,date('m')-1,1,date('Y'));
        $data['end'] = mktime(15,59,59,date('m'),0,date('Y'));
        break;
    case 'cur_month':      //   @todo我不太清楚这2种哪一种是对的 我认为上面那种貌似是对的。
        $data['start'] = mktime(-8,0,0,date('m'),1,date('Y'));
        $data['end'] = mktime(15,59,59,date('m'),date('t'),date('Y'));
        break;
    case 'next_month':
        $data['start'] = mktime(-8,0,0,date('m')+1,1,date('Y'));
        $data['end'] = mktime(15,59,59,date('m')+2,0,date('Y'));
        break;
    }
    return $data;
}

/**
 * 根据一个时间 8位 （例如 20150205 得到的是这个月的区间） 数字 获取当月的 月初时间和 月末时间戳
 * @Author  pengchanglu@ucfgroup.com 2015年2月5日18:43:56
 * @param $date
 * @param $range 浮动 可以是负数
 * @return array|bool
 */
function getMonthStartEnd($date,$range = 0){
    $date = trim(trim($date),'0');
    if(strlen($date) !=8 || !preg_match("#\d{8}#", $date)){
    return false;
    }
    $y=substr($date,0,4);//指定的年
    $m=substr($date,4,2);//指定的月
    $d=date('j',mktime(0,0,1,($m==12?1:$m+1),1,($m==12?$y+1:$y))-24*3600);
    $data = array();

    $data['start'] = mktime(-8, 0, 0, $m+$range, 01, $y);
    $data['end'] = mktime(-8, 0, 0, $m+$range, $d+1, $y)-1;
    return $data;
}


function makePdf($cont_info) {
    $wk_path = '/usr/local/bin/wkhtmltopdf';
    $pdftk_path = '/usr/bin/pdftk';
    //本地生成pdf
    $user_id = $cont_info['user_id'];
    //$html_name = md5 ( $cont_info['number'] ) .'_'. getmypid().".html";
    $html_name = $user_id .'_'. getmypid().".html";
    $file_path = APP_ROOT_PATH.'runtime/';
    $pdf_name = $user_id.'_'.md5 ( $user_id.'_'.$cont_info['number'] ) . ".pdf";

    $fp = fopen($file_path.$html_name,'w');
    if(!$fp) {
        die("open file $file_path faild!");
    }
    $content = str_replace('lc.p2pstatic','p2pstatic',$cont_info['content']);
    fwrite($fp,$content);
    fclose($fp);

    //test eviroment use   online need  delete this line @todo
    system("mv -f $file_path$html_name /apps/product/nginx/htdocs/firstp2p/firstp2p/runtime/");
    $pdf = system($wk_path.' --page-size A3 -T 0 http://localhost/'.$html_name.' '.$file_path.$pdf_name.' >wk 2>&1');

    // 加密
    $pwd = substr($cont_info['number'], -6);
    $new_pdf = $cont_info['user_id'].'_'.$pdf_name;
    $rs = system($pdftk_path.' '.$file_path.$pdf_name.' output '.$file_path.$new_pdf." user_pw '".$pwd."'");


    //存储到vfs
    $arr_time = getTimeStartEnd('last_month');
    $last_month = date('m',$arr_time['end']);
    $file['name'] = '网信电子对账单'.date('Y',$arr_time['end']).'年'.($last_month-0).'月份.pdf';
    //$file['name'] = $new_pdf;
    $file['type'] = 'application/pdf';
    $file['tmp_name'] = $file_path.$new_pdf;
    $file['error'] = 0;
    $file['size'] = filesize($file_path.$new_pdf);

    $uploadFileInfo = array(
        'file' => $file,
        'asAttachment' => 1,
        'asPrivate' => true,
    );
    $doupload = uploadFile($uploadFileInfo);
        /*
        if($doupload['status']) {
            system("rm -f $file_path$new_pdf $file_path$pdf_name  $file_path$html_name");
        }
         */
    return $doupload;
}

/**
 * 循环生成还款期限列表
 */
function get_repay_time_month($start=2, $end=36, $step=1, $period="个月") {
    $res = array();
    for ($i=$start; $i<=$end; $i+=$step) {
        $res[$i] = $i . $period;
    }
    return $res;
}

function getClassName4Db($obj)
{
    return strtr(get_class($obj), '\\', '_');
}

/**
 * checkHttps检测链接是否是https连接
 * @author zhanglei5 <zhanglei5@group.com>
 *
 * @date 2014-09-18
 * @access public
 * @return bool
 */
function checkHttps() {
    if (!isset($_SERVER['HTTPS'])) {
        return false;
    }
    if($_SERVER['HTTPS'] === 1) {  //Apache
        return true;
    } elseif($_SERVER['HTTPS'] === 'on') { //IIS
        return true;
    } elseif($_SERVER['SERVER_PORT'] == 443) { //其他
        return true;
    }
    return false;
}

/**
 * checkHttpsFromProxy 判断来自代理机的请求是否 https
 * @author zhanglei5 <zhanglei5@group.com>
 *
 * @date 2014-09-18
 * @access public
 * @return bool
 */
function checkHttpsFromProxy() {
    return empty($_SERVER['HTTP_XHTTPS']) ? false : true ;
}

/*
 * 根据referer 来封禁
 * @param bool $forbidEmpty  禁止空referer
 * @param string $forbidSelfUri 禁止自身uri
 * @param bool $forbidOtherDomain  禁止外域
 * @return bool $forbid 返回是否封禁 true:封禁 false:不封禁
 */


function forbidReferer($forbidEmpty = true, $forbidSelfUri = '', $forbidOtherDomain = true){
    $referer = $_SERVER['HTTP_REFERER'];
    $forbid = true;
    $forbidFlag = '';
    $forbidReason = array(
        'referer' => $referer,
        'uri' => $forbidSelfUri
    );
    do{
        //禁止 空referer
        if($forbidEmpty && empty($referer)){
            $forbidFlag = 'empty_referer';
            break;
        }
        //referer不能为action自身
        if(!empty($forbidSelfUri) && stristr($referer, $forbidSelfUri) !== false){
            $forbidFlag = 'self_uri';
            break;
        }
        //referer 不能为外域

        if($forbidOtherDomain){
            $refererRootDomain = '.'.implode('.', array_slice((explode('.', parse_url($referer, PHP_URL_HOST))), -2));
            $isSubDomain = false;
            foreach($GLOBALS['sys_config']['SITE_DOMAIN'] as $item){
                if(stristr($item, $refererRootDomain) === $refererRootDomain){
                    $isSubDomain = true;
                    break;
                }
            }
            if(!$isSubDomain){
                $forbidFlag = 'other_domain';
                break;
            }
        }
        $forbid = false;
    }while(false);

    //return $forbid;

    $forbidReason['forbid'] = $forbid;
    $forbidReason['flag'] = $forbidFlag;

    return $forbidReason;

}


/**
 ** 显示手机端数字图片
 ** @param $string string 数字字符串
 ** @param $type int 字体大小
 ** @return string html 内容
 **/
function get_app_num_pic($string, $path='') {
    $content = "";
    $len = strlen($string);
    for ($i=0; $i<$len; $i++) {
        $char = $string{$i};
        if($char == '.'){
            $html = '<img height="13px" src="'.$path.'/v2/images/num/jiange-200.png" class="jiange">';
        }elseif(is_numeric($char)){
            $html = '<img height="13px" src="'.$path.'/v2/images/num/'.$char.'-200.png">';
        }elseif($char == '%'){
            $html = '<span><img height="14px" src="'.$path.'/v2/images/num/perc.png" class="prec"></span>';
        }else{
            $html = $char;
        }

        $content .= $html;
    }
    return $content;
}

/**
 * 根据标的id获取队列名
 * @param int $deal_id
 * @return string
 */
function get_deal_queue($deal_id) {
    $model = new \core\dao\dealqueue\DealQueueInfoModel();
    $deal_queue = $model->getDealQueueByDealId($deal_id);
    if ($deal_queue) {
        return $deal_queue->name;
    } else {
        return "";
    }
}

/**
 * sendSubscribeEmail 发送基金预约通知邮件
 *
 * @param mixed $email 邮件地址
 * @param mixed $userid 用户ID
 * @param mixed $notice_mail 邮件参数
 * @access public
 * @return void
 */
function sendSubscribeEmail($userid,$notice_mail){
    FP::import("libs.common.dict");
    $email_list = dict::get("FUND_SUBSCRIBE_EMAIL");
    if(empty($email_list)){
        return false;
    }
    $Msgcenter = new Msgcenter();
    $mail_tpl = 'TPL_FUND_SUBSCIBE';
    $mail_title = "{$notice_mail['id']} \"{$notice_mail['username']}\" 预约 \"{$notice_mail['fund_title']}\" {$notice_mail['money']}元,手机号：{$notice_mail['phone']}";
    foreach($email_list as $email_item){
        $Msgcenter->setMsg($email_item, $userid, $notice_mail, $mail_tpl, $mail_title);
    }

    return $Msgcenter->save();
}

/**
 ** 设置log记录点 k,v
 ** @param $data  array($k=>$v);
 ** @return bool
 **/
function setLog($data){
    global $_log;
    if(is_array($_log)){
        $_log = array_merge($_log, $data);
    }else{
        $_log = $data;
    }
    return true;
}

function getLog(){
    global $_log;
    return $_log;
}

/*
 * 获得允许身份验证的类型信息
 * return array
 */
function getIdTypes() {
    $idTypes = $GLOBALS['dict']['ID_TYPE'];
    //前段屏蔽id_type中的军官证和其他
    foreach ($idTypes as $key => $value) {
        if (strpos($value, '军官证') !== false || strpos($value, '其他') !== false) {
            unset($idTypes[$key]);
        }
    }
    return $idTypes;
}

function getRepayTime($repay_time,$loantype) {
    $str = $repay_time;
    if ($loantype == 5) {
        $str .= '天';
    }else {
        $str .= '个月';
    }
    return $str;
}

function get_loantype($loantype){
    return $GLOBALS['dict']['LOAN_TYPE_CN'][$loantype];
}

function get_loantype_cn($loantype){
    return $GLOBALS['dict']['LOAN_TYPE_CN'][$loantype];
}


function get_loan_money_type($loan_money_type){
    return $GLOBALS['dict']['LOAN_MONEY_TYPE'][$loan_money_type];
}

function plus($x, $y) {
    return $x + $y;
}

/**
 * getCityByIp
 * 根据ip获取所在地
 * @param array $ip ip地址数组
 * @access public
 * @return void
 */
function getCityByIp($ip = array(), $fast = true){
    if(is_array($ip)){
        $ip = implode('|',$ip);
    }
    if($fast === true){
        $url = 'http://toolkit.firstp2p.com/getFastBatchIp?ip='.$ip;
    }else{
        $url = 'http://toolkit.firstp2p.com/getBatchIp?ip='.$ip;
    }
    return \libs\utils\Curl::get($url);
}

function adminMobileFormat($mobile){
    return substr($mobile, 0,4).str_repeat("*", strlen($mobile)-7).substr($mobile, -3);
}

function userNameFormat($userName){
    $len = mb_strlen($userName);
    $hideLen = floor($len/3);
    return mb_substr($userName, 0, $hideLen).str_repeat("*", $hideLen).mb_substr($userName, 2*$hideLen);
}

function adminEmailFormat($mail){
    $mail = trim($mail);
    $arr = explode("@", $mail);
    if($arr[0] && $arr[1]){
        return substr($arr[0], 0,-4).str_repeat("*", 4).'@'.$arr[1];
    }else{
        return $mail;
    }
}

/**
 * 改版 v2 分页函数
 * @param number $page 当前页
 * @param number $pages 总页数
 * @param number $displayedPages 中间显示分页数 默认8个
 * @param string $pstr get方式分页传递的参数 如："p="
 * @param string $content get方式分页传递的其他参数 如: "&cate=10"
 * @access public
 * @return string
 */
function pagination($page, $pages, $displayedPages=8, $pstr='p=', $content=''){
    //当前页处理
    $page = ($page < 1) ? 1 : ( ($page > $pages) ? $pages : $page);
    $halfDisplayed = floor($displayedPages / 2);
    $diff = $pages - $halfDisplayed;
    $pageNext = ($page + 1) > $pages ? $pages : ($page + 1);
    $pagePrex = ($page - 1) < 1 ? 1 : ($page - 1);
    $edges = 2;
    $halfDisplayed = floor($displayedPages / 2);
    $firstPage = 1;
    $interval = array(
        "start"=> ceil($page > $halfDisplayed ? max(min($page - $halfDisplayed, ($pages - $displayedPages)), 0) : 0),
        "end"=> ceil($page > $halfDisplayed ? min($page + $halfDisplayed, $pages) : min($displayedPages, $pages))
    );
    $cache = array();

    array_push($cache, '<ul>');
    // 生成首页按钮
    array_push($cache,
        $firstPage != $page
        ?
        '<li><a href="?'.$pstr.$firstPage.$content.'" class="page-link next" title="首页">首页</a></li>'
        :
        '<li><span class="current prev" title="首页">首页</span></li>'
    );
    // 生成上一页按钮
    array_push($cache,
        $pagePrex != $page
        ?
        '<li><a href="?'.$pstr.$pagePrex.$content.'" class="page-link next" title="上一页">上一页</a></li>'
        :
        '<li><span class="current prev" title="上一页">上一页</span></li>'
    );

    // 生成中间部分页码
    for ($i = $interval["start"]; $i < $interval["end"]; $i++) {
        //分页
        array_push($cache,
            ($i + 1) == $page
            ?
            '<li class="active"><span class="current">'.($i + 1).'</span></li>'
            :
            '<li><a href="?'.$pstr.($i + 1).$content.'" class="page-link">'.($i + 1).'</a></li>'
        );
    }
    // 生成下一页按钮
    array_push($cache,
        $pageNext != $page
        ?
        '<li><a href="?'.$pstr.$pageNext.$content.'" class="page-link next" title="下一页">下一页</a></li>'
        :
        '<li><span class="current next" title="下一页">下一页</span></li>'
    );

    array_push($cache, '</ul>');
    return join('', $cache);
}

function paginationWithNoCount($pageParams) { //$page, $pages = 0, $displayedPages=8, $pstr='p=', $content='', $extraParams = []) {

    extract($pageParams);
    $pages = 0;
    $page = ($page < 1) ? 1 : $page;

    $halfDisplayed = floor($displayedPages / 2);

    if ($currentPageSize >= $pageSize) {
        $pageNext = $page + 1;
    } else {
        $pageNext = $page;
    }

    $pagePrex = ($page - 1) < 1 ? 1 : ($page - 1);
    $edges = 2;
    $firstPage = 1;

    $interval = array(
        "start"=> ceil($page > $halfDisplayed ? max($page - $halfDisplayed, 0) : 0),
        "end"=> ceil($page > $halfDisplayed ? $page + $halfDisplayed : $displayedPages)
    );

    if ($currentPageSize < $pageSize) {
        $interval['end'] = $page;
    }

    $cache = array();

    array_push($cache, '<ul>');
    // 生成首页按钮
    array_push($cache,
        $firstPage != $page
        ?
        '<li><a href="?'.$pstr.$firstPage.$content.'" class="page-link next" title="首页">首页</a></li>'
        :
        '<li><span class="current prev" title="首页">首页</span></li>'
    );
    // 生成上一页按钮
    array_push($cache,
        $pagePrex != $page
        ?
        '<li><a href="?'.$pstr.$pagePrex.$content.'" class="page-link next" title="上一页">上一页</a></li>'
        :
        '<li><span class="current prev" title="上一页">上一页</span></li>'
    );

    // 生成中间部分页码
    for ($i = $interval["start"]; $i < $interval["end"]; $i++) {
        //分页
        array_push($cache,
            ($i + 1) == $page
            ?
            '<li class="active"><span class="current">'.($i + 1).'</span></li>'
            :
            '<li><a href="?'.$pstr.($i + 1).$content.'" class="page-link">'.($i + 1).'</a></li>'
        );
    }
    // 生成下一页按钮
    array_push($cache,
        $pageNext > $page
        ?
        '<li><a href="?'.$pstr.$pageNext.$content.'" class="page-link next" title="下一页">下一页</a></li>'
        :
        '<li><span class="current next" title="下一页">下一页</span></li>'
    );

    array_push($cache, '</ul>');
    return join('', $cache);
}

//clean sensitive post field
function cleanSensitiveField($data){
    if(!is_array($data)){
        return $data;
    }
    $needRemoveField = array(
        'password' => null,
        'old_password' => null,
        'new_password' => null ,
        're_new_password' => null,
        'confirmPassword' => null,
        'pwd' => null,
    );
    if(isset($data['mobile'])) $data['mobile'] = user_name_format($data['mobile'],3);
    if(isset($data['phone'])) $data['phone'] = user_name_format($data['phone'],3);
    if(isset($data['account'])) $data['account'] = user_name_format($data['account'],3);
    if(isset($data['user_name'])) $data['user_name'] = user_name_format($data['user_name'],3);
    if(isset($data['username'])) $data['username'] = user_name_format($data['username'],3);
    if(isset($data['bankcard'])) $data['bankcard'] = user_name_format($data['bankcard'],3);
    if(isset($data['idno'])) $data['idno'] = user_name_format($data['idno'],3);

    return array_diff_key($data, $needRemoveField);
}

function format_date_by_type($time, $type=0) {
    if (!$time) {
        return false;
    }
    $type = intval($type);
    $formatArray = array(0 => 'Y.m.d H:i:s', 1=> 'Y-m-d');
    $format = $formatArray[$type] ? $formatArray[$type] : 'Y.m.d H:i:s';
    return date($format, $time);
}

function msubstr_replace($str, $replace, $start, $len) {
    $str = mb_substr($str, 0, $start) . $replace . mb_substr($str, $start+$len, mb_strlen($str));
    return $str;
}

function get_deal_contract_status($deal_id, $is_agency=0) {
    $condition = "`deal_id`='{$deal_id}' AND " . ($is_agency ? "`agency_id`!='0'" : "`agency_id`='0'");
   return 'TODO 需要调用接口';
    switch ($row['status']) {
        case null:return "/";
        case 0:return "未签署";
        case 1:return "已签署";
        case 2:return "签署中";
    }
}

/**
 *  获取标的签署状态
 * @param number $deal_id 标id
 * @param number $agency_id 机构
 * @return string
 */
function get_deal_contract_sign_status($deal_id, $agency_id=0) {
    $condition = "`deal_id`='{$deal_id}' AND `agency_id` = {$agency_id}" ;
   // $row = \core\dao\DealContractModel::instance()->findBy($condition);
    return 'TODO 需要调用接口';
    switch ($row['status']) {
        case null:return "/";
        case 0:return "未签署";
        case 1:return "已签署";
        case 2:return "签署中";
    }
}

function format_mobile($mobile) {
    return msubstr_replace($mobile, '****', 3, 4);
}

/**
 * @记录用户最近的登录时间(维护一个栈)
 * @param  int $time
 * @param  int $user_id
 * @return bool
 */
function user_last_time_stack($user_id, $time)
{
    $key_name       = 'last_time_stack_' . $user_id;
    $structure_name = array(0 => 1);

    //    $cacheObj = new RedisCache();
    //不使用phalcon-common里面的redis，使用高可用的redis。
    $cacheObj = \SiteApp::init()->dataCache->getRedisInstance();
    $stack_data = $cacheObj->get($key_name);
    if(empty($stack_data)){
        array_unshift($structure_name, $time);
        array_pop($structure_name);
        $json_data = json_encode($structure_name);

        return $cacheObj->set($key_name, $json_data);
    }

    $stack_data  = json_decode($stack_data, true);
    $stack_total = count($stack_data);
    if($stack_total > 2){
        for($i=2; $i<$stack_total; $i++){
            unset($stack_data[$i]);
        }
    }

    array_unshift($stack_data, $time);
    $json_data = json_encode($stack_data);

    return $cacheObj->set($key_name, $json_data);
}

function get_user_last_time($user_id)
{
    $key_name = 'last_time_stack_' . $user_id;

    //    $cacheObj = new RedisCache();
    //使用高可用的redis，而不是phalcon-common redis
    $cacheObj = \SiteApp::init()->dataCache->getRedisInstance();
    $stack_data = $cacheObj->get($key_name);
    if(empty($stack_data)) return false;

    $stack_data  = json_decode($stack_data, true);
    $stack_total = count($stack_data);
    if(!$stack_total) return to_date(get_gmtime(), 'Y/m/d H:i:s');

    $date_strotime = ($stack_total == 1) ? $stack_data[0] : $stack_data[1];

    return to_date($date_strotime, 'Y/m/d H:i:s');
}

/**
 * @保存用户的密保问题
 * @param  int   $user_id
 * @param  array $user_security
 * @return bool
 */
function set_user_security($user_id, $user_security)
{
    $user_md5_string = substr(md5($user_id), 8,16);
    $user_extend_info['user_id']     = $user_id;

    $security_md5_answer   = array();
    foreach($user_security as $k => $v){
        $problem_md5_string = substr(md5($k), 8, 16);
        $security_md5_problem[] = $problem_md5_string;
        $security_md5_answer[$problem_md5_string] = array($k => $v);
    }

    $user_security_string = json_encode(array('data' => $security_md5_answer, 'extend' => $user_extend_info));

    //    $cacheObj = new RedisCache();
    //使用高可用的redis。
    $cacheObj = \SiteApp::init()->dataCache->getRedisInstance();
    return $cacheObj->set($user_md5_string, $user_security_string);
}

/**
 * @获取用户的密保问题
 * @param  int $user_id
 * @return array
 */
function get_user_security($user_id)
{
    $user_md5_string = substr(md5($user_id), 8, 16);

    //    $cacheObj = new RedisCache();
    //使用高可用的redis。
    $cacheObj = \SiteApp::init()->dataCache->getRedisInstance();

    $user_security_value = $cacheObj->get($user_md5_string);
    if(!$user_security_value) return false;

    return json_decode($user_security_value, true);
}

/**
 * @封装web防套利函数
 * @param  string $par 用户会员名称
 * @return bool
 */
function set_restrict_cookie($par)
{
    if ($_COOKIE['lp']) {
        $arr_decode_json=Aes::decode($_COOKIE['lp'], 'set_restrict_cookie');
        if ($arr_decode_json == false) {
            return true;
        }
        $arr_decode=json_decode($arr_decode_json, true);
    }
    if ($arr_decode['login_first_time'] && time() >= (intval($arr_decode['login_first_time'])+86400)) {//如果记录超出24小时则将相应的cookie清除,并重置cookie
        $arr_data=array(
            'num'=> $par,
            'login_first_time'=>time(),
            'count'=> '1',
        );
        $arr_result = json_encode($arr_data, JSON_UNESCAPED_UNICODE);
        $arr_result = Aes::encode($arr_result, 'set_restrict_cookie');
        //重置cookie
        setcookie('lp', $arr_result, time()+86400, '/');
        //开启约束条件
        $clientIp = get_client_ip();
        $check_client_ip_minute = Block::check('USERNAME_IP_CHECK_MINUTE', $clientIp, false);//启动client_Ip访问限制，30次/min，如果超过则需要验证码
        $check_username_minute = Block::check('USERNAME_CHECK_MINUTE', $par, false);//启动username访问限制，10次/min，如果超过则需要验证码
        if (intval(app_conf("WEB_PREVENT_PROFIT")) == 1) {
            return true;
        }
    } else {//如果记录没有超出24小时则判断相应的登录用户
        if (!$arr_decode['num']) {
            $arr_data=array(
                'num'=> $par,
                'login_first_time'=>time(),
                'count'=> '1',
            );
            $arr_result = json_encode($arr_data, JSON_UNESCAPED_UNICODE);
            $arr_result = Aes::encode($arr_result, 'set_restrict_cookie');
            setcookie('lp', $arr_result, time()+86400, '/');
            if (intval(app_conf("WEB_PREVENT_PROFIT")) == 1) {
                return true;
            }
            return false;
        } elseif (intval($arr_decode['count'])<(app_conf("WEB_PREVENT_PROFIT")-1)) {
            $cookie_arr=explode(":", $arr_decode['num']);
            if (!in_array($par, $cookie_arr)) {
                $arr_data=array(
                    'num'=> $arr_decode['num'].':'.$par,
                    'login_first_time'=>$arr_decode['login_first_time'],
                    'count'=> intval($arr_decode['count'])+1,
                );
                $arr_result = json_encode($arr_data, JSON_UNESCAPED_UNICODE);
                $arr_result = Aes::encode($arr_result, 'set_restrict_cookie');
                setcookie('lp', $arr_result, time()+86400, '/');
                return false;
            }
        } elseif (intval($arr_decode['count'])==(app_conf("WEB_PREVENT_PROFIT")-1) || $check_client_ip_minute==false || $check_username_minute==false || intval($arr_decode['count']) >= (app_conf("WEB_PREVENT_PROFIT")-1)) {
            $cookie_arr=explode(":", $arr_decode['num']);
            if (!in_array($par, $cookie_arr)) {
                return true;
            }
        }
    }
}
/**
 * @封装登录密码正则匹配低、中、高安全程度函数
 * @param  int $pasword,$len
 * @return string
 */
function login_pwd_safe($len,$password)
{
    if ((preg_match('/^[a-zA-Z]+$/',$password)
        || preg_match('/^[0-9]+$/',$password)
        || preg_match('/^[`~!@#\$%\^&\*\(\)_\-\\\+<>\?:\"{},\.\/;\'\[\]|\=]+$/',$password))
        && $len <9) {
            return array('errorCode' => 0,'errorMsg' => '低',);
        } elseif ((preg_match('/^(?=.*[0-9].*)(?=.*[a-z].*)(?=.*[A-Z].*)[a-zA-Z0-9]+$/',$password)
            || preg_match('/^(?=.*[a-z].*)(?=.*[A-Z].*)(?=.*[`~!@#\$%\^&\*\(\)_\-\\\+\=<>\?:\"{},\.\/;\'\[\]|].*)[a-zA-Z`~!@#\$%\^&\*\(\)_\+\=<>\?:\"{},\.\/;\'\[\]|]+$/',$password)
            || preg_match(' /^(?=.*[0-9].*)(?=.*[A-Z].*)(?=.*[`~!@#\$%\^&\*\(\)_\-\\\+\=<>\?:\"{},\.\/;\'\[\]|].*)[A-Z0-9`~!@#\$%\^&\*\(\)_\+\=<>\?:\"{},\.\/;\'\[\]|]+$/',$password)
            || preg_match(' /^(?=.*[0-9].*)(?=.*[a-z].*)(?=.*[`~!@#\$%\^&\*\(\)_\-\\\+\=<>\?:\"{},\.\/;\'\[\]|].*)[a-z0-9`~!@#\$%\^&\*\(\)_\+\=<>\?:\"{},\.\/;\'\[\]|]+$/',$password))
            && ($len >=12 && $len <=20)) {
                return array('errorCode' => 0,'errorMsg' => '高',);
            } elseif (preg_match('/^(?=.*[0-9].*)(?=.*[a-z].*)(?=.*[A-Z].*)(?=.*[`~!@#\$%\^&\*\(\)_\-\\\+\=<>\?:\"{},\.\/;\'\[\]|].*)[a-zA-Z0-9`~!@#\$%\^&\*\(\)_\+\=<>\?:\"{},\.\/;\'\[\]|]+$/', $password)
                && $len>=9 && $len<=20) {
                    return array('errorCode' => 0,'errorMsg' => '高',);
                } else {
                    return array('errorCode' => 0,'errorMsg' => '中',);
                }
}
/**
 * @封装登录密码基本规则判断函数
 * @param  int $pasword,$len,$mobile
 * @return string
 */
function login_pwd_base_rule($len,$mobile,$password)
{
    if ($len<6 || $len >20) {
        return array('errorCode' => 1,'errorMsg' => '登录密码为6-20位',);
    }
    if (strpos($password, ' ') !== false) {
        return array('errorCode' => 1,'errorMsg' => '登录密码不允许包含空格',);
    }
    if (!(preg_match('/^[a-zA-Z0-9`~!@#\$%\^&\*\(\)_\-\\\+\=<>\?:\"{},\.\/;\'\[\]|]{6,20}$/',$password))) {
        return array('errorCode' => 1,'errorMsg' => '登录密码不允许包含特殊符号',);
    }
}
/**
 * @封装登录密码禁用规则及黑明单判断
 * @param  int $pasword,$mobile,$blacklist
 * @return string
 */
function login_pwd_forbid_blacklist($password,$blacklist,$mobile)
{
    if ((array_search($password, $blacklist) !== false && $blacklist)
        || (stripos('01234567890 09876543210 abcdefghijklmnopqrstuvwxyz zyxwvutsrqponmlkjihgfedcba', $password)) !==false
        || ($password == $mobile)
        || (count(count_chars($password,1))) == 1) {
            return array('errorCode' => 1,'errorMsg' => '登录密码过于简单，试试数字、大小写字母、标点符号组合',);
        }
}
//发送自定义邮件
function send_my_mail(array $to, $subject, $body){
    $msgcenter = new Msgcenter();
    $msgcenter->setMsg(implode(',', $to), 0, $body, false, $subject);
    return $msgcenter->save();
}

/**
 * 根据身份证号获得用户年龄
 * @param type $id
 * @return string
 */
function getAgeByID($id)
{
    if (empty($id))
        return '';
    $date = substr($id, 6, 8);
    $today = date("Ymd");
    $diff = substr($today, 0, 4) - substr($date, 0, 4);
    $age = substr($date, 4) > substr($today, 4) ? ($diff - 1) : $diff;

    return $age;
}

/**
 * 企业会员编号展示
 */
function numTo32Enterprise($no)
{
    return numTo32($no, 1);
}

//会员编号
//type 用户类型：0 个人会员 1:企业会员
function numTo32($no, $type=0){
    $no+=34000000;
    $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
        "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M",
        "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    $rtn = "";
    while($no >= 32) {
        $rtn = $char_array[fmod($no, 32)].$rtn;
        $no = floor($no/32);
    }

    $prefix = '00';
    if($type == 1){
        $prefix = '66';
    }
    return $prefix.$char_array[$no].$rtn;
}

//转换会员编号
function de32Tonum($no_32) {
    $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
        "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M",
        "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    $num = substr($no_32, 2);//2wwch
    $no = 0;
    for ($i = 0;$i <= strlen($num)-1;$i++) {
        $no = $no * 32 + array_search($num[$i],$char_array);
    }
    $no = $no - 34000000;
    return $no;
}
function risk_check()
{
    $token_id = isset($_COOKIE["FRMS_FINGERPRINT"]) ? (string) $_COOKIE["FRMS_FINGERPRINT"] : '';
    if(!empty($token_id)) return $token_id;

    return false;
}

//判断url参数是否当前的根域
function isMainDomain($url) {
    $rootDomain = implode('.', array_slice((explode('.', get_host())), -2));
    $urlSliced = parse_url($url);

    $urlDomain = implode('.', array_slice((explode('.', $urlSliced['host'])), -2));
    $preg = "/^[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(\.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+$/";
    if (!preg_match($preg, $urlSliced['host'])) {
        //url无效
        return false;
    }
    if (is_firstp2p_url($url) || $rootDomain == $urlDomain) {
        return true;
    }
    return false;
}

/**
 * [根据用户的id，获取用户类型对应的名称]
 * @author <fanjingwen@ucfgroup.com>
 * @param int $userID [用户id]
 * @param int $flag [flag标识]代理人是否显示成企业用户(0:不显示1:显示)
 * @return string [用户类型名称]
 */
function getUserTypeName($userID, $flag = 1)
{
    $userInfo = core\service\user\UserService::getUserByCondition("id={$userID}");
    // user_type为0：个人客户
    if (\core\enum\UserEnum::USER_TYPE_NORMAL == $userInfo['user_type']) {
        // JIRA#FIRSTPTOP-4024 变更企业会员账户唯一标识
        if ($flag == 0) {
            return \core\enum\UserEnum::USER_TYPE_NORMAL_NAME;
        } else {
            $company = core\service\user\UserService::getUserCompanyInfo("id={$userID}", 'name');
            if($company) {
                return \core\enum\UserEnum::USER_TYPE_ENTERPRISE_NAME;
            } else {
                return \core\enum\UserEnum::USER_TYPE_NORMAL_NAME;
            }
        }
    } elseif (\core\enum\UserEnum::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
        return \core\enum\UserEnum::USER_TYPE_ENTERPRISE_NAME;
    } else {
        return '';
    }
}

/**
 * [获取user某些字段的url链接]
 * @author <fanjingwen@ucfgroup.com>
 * @param array $userInfo [用户信息，所需的key值为user表对应的字段]
 * @param string $field [本次生成url的字段]
 * @return string [返回生成的url]
 */
function getUserFieldUrl($userInfo, $field = 'user_name')
{
    if (\core\dao\EnterpriseModel::TABLE_FIELD_COMPANY_NAME == $field) {
        $user_action = 'Enterprise';
        $field = \core\dao\EnterpriseModel::TABLE_FIELD_COMPANY_NAME;
        // 获取企业名称
        $enterpriseInfo = \core\dao\EnterpriseModel::instance()->getEnterpriseInfoByUserID($userInfo['id']);
        $userInfo[$field] = $enterpriseInfo[$field];
    } else {
        $user_action = 'User';
    }

    if(!$userInfo[$field])
    {
        return $field == 'user_name' ? l("NO_USER") : '';
    } else {
        return "<a href='?m=". $user_action . "&a=index&" . $field . "=" . $userInfo[$field] ."' target='_blank'>".$userInfo[$field]."</a>";
    }
}

//网信理财
function is_wxlc() {
    $host = strtolower(get_host());
    return $host == strtolower(app_conf('WXLC_DOMAIN')) || $host == strtolower(app_conf('FIRSTP2P_COM_DOMAIN')) || $host == strtolower(app_conf('NCFWX_DOMAIN'));
}

//是否主站
function is_firstp2p() {
    return strtolower(get_host()) == strtolower(app_conf('FIRSTP2P_CN_DOMAIN'));
}

//企业独立站
function is_qiye_site() {
    return app_conf('ENTERPRISE_SITE_ID') == \libs\utils\Site::getId() ? true : false;
}

//农担分站
function is_nongdan_site($siteId = 0) {
    $siteId = !empty($siteId) ? $siteId : \libs\utils\Site::getId();
    return $GLOBALS['sys_config']['TEMPLATE_LIST']['nongdan'] == $siteId ? true : false;
}

//是否普惠url
function is_firstp2p_url($url) {
    $urlSliced = parse_url($url);
    return strtolower($urlSliced['host']) == strtolower(app_conf('FIRSTP2P_CN_DOMAIN'));
}

//数据打包
function dataPack($code, $msg = '', $data = array()) {
    return array("code" => $code, 'msg' => $msg, 'data' => $data);
}

/**
 * [根据项目id获取项目的委托状态]
 * @author <fanjingwe@ucfgroup.com>
 * @param int $projectID
 * @return string [‘已委托’ || ‘未委托’]
 */
function get_project_entrust_sign($projectID,$contract_type = 'entrust_sign')
{
    $proObj = \core\dao\project\DealProjectModel::instance()->findViaSlave($projectID);
    $entrustSign = "未委托";
    if (0 == $proObj->$contract_type) {
        $entrustSign = "未委托";
    } else {
        $entrustSign = "已委托";
    }

    return $entrustSign;
}

/**
 * [根据标的id和所属的用户id获取委托签署人]
 * @author <fanjingwe@ucfgroup.com>
 * @param int $dealID [标的id]
 * @param int $userID [标的所属人]
 * @return string [‘’ || ‘adm_name’]
 */
function get_entrustor_name($dealID, $userID=0, $agencyID=0 )
{
    $cond = " `deal_id` = '{$dealID}' AND `user_id` = '{$userID}' AND `agency_id` = {$agencyID}";
    return 'todo 需要调用接口';

    // 获取委托人姓名
    $admName = "";
    if (!empty($dealContObj)) {
        $admName = $GLOBALS['db']->getOne("SELECT `adm_name` FROM " . DB_PREFIX . "admin WHERE `id` = '{$dealContObj->adm_id}'");
    }

    return $admName;
}

/**
 * [根据user_id获取企业简称]
 * @author <fanjingwen@ucf>
 * @param int $userID
 * @return string $shortname [如果存在企业账户简称，就返回；否则返回空字符串]
 */
function get_company_shortname($userId)
{
    $enterpriseInfo = UserService::getEnterpriseInfo($userId);
    if (!empty($enterpriseInfo)) {
        return $enterpriseInfo['company_shortname'];
    } else {
        return '';
    }
}

/**
 * [根据user_id获取企业全称]
 * @author <duxuefeng@ucf>
 * @param int $userID
 * @return string $company_name [如果存在企业账户全称，就返回；否则返回空字符串]
 */
function get_company_name($userID)
{
    $enterpriseInfo = \core\dao\EnterpriseModel::instance()->getEnterpriseInfoByUserID($userID);
    if (!empty($enterpriseInfo)) {
        return $enterpriseInfo['company_name'];
    } else {
        return '';
    }
}

function get_root_domain() {
    return preg_match('~([^\.]+\.[^\.]+)$~', get_host(), $matches) ? $matches[1] : false;
}

/**
 * 读取主域名临时cookie
 * @param string $cookieKey
 */
function getGlobalTmpCookie($cookieKey)
{
    $encryptValue = \es_cookie::get($cookieKey);
    return $encryptValue ? aesAuthCode($encryptValue) : '';
}

/**
 * 设置主域名临时cookie
 * @param string $cookieKey
 * @param string $cookieValue
 * @param string $cookieDomain
 */
function setGlobalTmpCookie($cookieKey, $cookieValue, $cookieExpire = 0, $cookiePath = '', $cookieDomain = '')
{
    $cookieDomain || $cookieDomain = '.'.implode('.', array_slice((explode('.', get_host())), -2));
    $encryptValue = aesAuthCode($cookieValue, 'ENCODE');
    \es_cookie::set($cookieKey, $encryptValue, $cookieExpire, $cookiePath, $cookieDomain);
}

/**
 * 删除主域名临时cookie
 * @param string $cookieKey
 */
function delGlobalTmpCookie($cookieKey)
{
    \es_cookie::delete($cookieKey);
}

/**
 * Aes加解密封装
 * @param string $string
 * @param string $operation
 * @param string $key
 */
function aesAuthCode($string, $operation = 'DECODE', $key = '')
{
    $key || $key = 'a0b923$*0dcc509a';
    $operation == 'ENCODE' && $data = \libs\utils\Aes::encode($string, $key);
    $operation == 'DECODE' && $data = \libs\utils\Aes::decode($string, $key);
    return $data;
}

/**
 * 获取旧版的标题名 [JIRA#3844 产品名称整体更新 - 显示旧版的标名]
 * @param int $deal_id
 * @param int $project_id 默认为0，代表需要获取项目id
 * @param string $old_deal_name_with_prefix
 */
function getOldDealNameWithPrefix($deal_id, $project_id = 0)
{
    $deal_service = new DealService();
    if (!$project_id) {
        $deal_info = $deal_service->getDealInfo($deal_id);
        $project_id = $deal_info['project_id'];
    }

    return $deal_service->getOldDealNameWithPrefix($deal_id, $project_id);
}

/*
 * 会员编号转用户名
 * @param string $userNum
 * @return string
 */
function userNumToUserName($userNum) {
    $userName = '';
    $userId = de32Tonum($userNum);
    if($userId) {
        $userInfo = UserService::getUserById((int)$userId);
        if(!empty($userInfo)){
            $userName = $userInfo['user_name'];
        }
    }
    return $userName;
}

/**
 * 会员名称转会员编号
 * @param string $userName
 * @return string
 */
function userNameToUserNum($userName) {
    $userNum = '';
    $userInfo = UserService::getUserByName(trim($userName));
    if(!empty($userInfo)) {
        $userNum = numTo32($userInfo['id']);
    }
    return $userNum;
}

/**
 * 根据标的参数配置方案id，获取方案名
 **/
function getDealParamsConfName($deal_params_conf_id)
{
    $id = intval($deal_params_conf_id);
    $conf = DealParamsConfModel::instance()->findViaSlave($id, 'name');
    return !empty($conf) ? $conf->name : '';
}

/**
 * 获取根域名
 */
function getRootDomain(){
    preg_match('/(?<rootdomain>\w+\.\w+)$/', APP_HOST, $matches);
    return $matches['rootdomain'];
}

function trimSpace($data) {
    if (!is_string($data) && !is_array($data)) {
        return $data;
    }

    if (is_string($data)) {
        return mb_ereg_replace('^(　| )+', '', mb_ereg_replace('(　| )+$', '', $data));
    }

    foreach ($data as $key => $val) {
        $data[$key] = trimSpace($val);
    }

    return $data;
}

/**
 * 全局异常处理
 */
function throwWXException($exceptionKey) {
    throw new \libs\common\WXException($exceptionKey);
}

/**
 * admin 显示：获取费用收取方式
 */
function get_deal_ext_fee_type($id)
{
    $deal_ext = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_deal_ext WHERE `deal_id`='" . $id . "'");
    // 用来判断 各种手续费 收费方式 是否同时满足指定的类型
    $func_in_type_arr = function ($fee_rate_type_arr) use ($deal_ext) {
        return in_array($deal_ext['loan_fee_rate_type'], $fee_rate_type_arr) && in_array($deal_ext['consult_fee_rate_type'], $fee_rate_type_arr) && in_array($deal_ext['guarantee_fee_rate_type'], $fee_rate_type_arr) && in_array($deal_ext['pay_fee_rate_type'], $fee_rate_type_arr);
    };
    if (call_user_func($func_in_type_arr, array(DealExtEnum::FEE_RATE_TYPE_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE))) { // 前收 或 固定比例前收
        return '前收';
    } elseif (call_user_func($func_in_type_arr, array(DealExtEnum::FEE_RATE_TYPE_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND))) { // 后收 或 固定比例是后收
        return '后收';
    } elseif (call_user_func($func_in_type_arr, array(DealExtEnum::FEE_RATE_TYPE_PERIOD, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD))) { // 分期收 或 固定比例是分期收
        return '分期收';
    } else {
        return '<span style="color: red">费用收取方式不一致</span>';
    }
}

/**
 *  根据deal_type判断标的是否为P2P
 */
function isDealP2P($deal_type)
{
    return (DealEnum::DEAL_TYPE_GENERAL == $deal_type);
}


function getDealReportStatus($dealId){
    $deal = \core\dao\deal\DealModel::instance()->findViaSlave($dealId,"report_status");
    return $deal['report_status'] == 1 ? '已报备' : '未报备';
}

/**
 * 是否是灰度
 */
function isP2PRc()
{
    $p2pRc = get_cfg_var('p2p_rc');
    if ($p2pRc == 1) {
        return true;
    }
    return false;
}

/**
 * 判断角色是否需要签署
 */
function isSignRole($tpl_sign_role_value, $enum_sign_role_value)
{
    return $tpl_sign_role_value & $enum_sign_role_value;
}

/**
 * 根剧用款数和已用款数计算计算可用款限额等级
 * 0：不预警，1：后台红字提示，2：邮件通知 ，3：短信提示
 * @param int $money,$use_money
 * @return $level
 */
function getWarningLevelByMoney($money,$use_money) {
    $level = 0;
    $level = ($money - $use_money) > $money*0.1 && ($money - $use_money) <= $money*0.2 ? 1 : 0;
    $level = ($money - $use_money) > $money*0.05 && ($money - $use_money) <= $money*0.1 ? 2 : $level;
    $level = ($money - $use_money) <= $money*0.05 ? 3 : $level;
    return $level;
}

/**
 *  根据项目的业务状态获取状态名
 *  @param int $business_status
 */
function getProjectBusinessStatusNameByValue($business_status)
{
    return isset(DealProjectModel::$PROJECT_BUSINESS_STATUS_MAP[$business_status]) ? DealProjectModel::$PROJECT_BUSINESS_STATUS_MAP[$business_status] : '';
}

/**
 *  获取标的投资状态
 *  @param int $buy_status
 *  @param int $deal_id
 */
function a_get_buy_status($buy_status,$deal_id)
{
    if($buy_status==2){
        return "<span style='color:red'>".l("DEAL_STATUS_".$buy_status)."</span>";
    }
    else{
        return l("DEAL_STATUS_".$buy_status);
    }
}

/**
 * 精度数舍余
 * @param $value
 * @param int $precision 保留小数点位数
 * @return float|int
 */
function floorfix($value, $precision = 2,$roundPlaces = 5) {
    $t = pow(10, $precision);
    if (!$t) {
        return 0;
    }
    // 为解决0.5被处理成0.499999的情况，首先在第5位小数进行四舍五入
    $value = round($value*$t, $roundPlaces);
    return bcadd((floor($value) / $t),0,$precision);
}

/**
 * 获取admin id
 */
function get_admin_id() {
    if (!defined('ADMIN_ROOT')) {
        return 0;
    }

    $session = \es_session::get(md5(conf('AUTH_KEY')));
    return isset($session['adm_id']) ? $session['adm_id'] : 0;
}

/**
 * 检查交易日
 */
function check_trading_day($timestamp){
    if (empty($timestamp)){
        return false;
    }
    \FP::import("libs.common.dict");
    // 非工作日字典
    $holidays = \dict::get('REDEEM_HOLIDAYS');
    $day = date("Y-m-d",$timestamp);
    $n = date("N",$timestamp);
    if (!in_array($n,array(6,7)) && !in_array($day,$holidays)){ // 不在周末且不在工作日
        return true;
    }
    return false;
}

/**
 * bonus=200|bonus_id=123456|steps=100|tip=文案提示来了转换为数组已等号前的为key，等号后面的为value
 * @param $conf_value
 * @return array|bool
 */
function confToArray($conf_value) {
    if (empty($conf_value)) {
        return false;
    }
    $confTmp = explode('|',$conf_value);
    $confValue = array();
    foreach ($confTmp as $value) {
        $strToArr = explode('=',$value);
        $confValue[$strToArr['0']] = $strToArr['1'];
    }
    return $confValue;
}

/**
 * 通过二进制流 读取文件后缀信息
 * @param string $filename
 */
function getImagePostFix($filename) {
    $file     = fopen($filename, "rb");
    $bin      = fread($file, 2); //只读2字节
    fclose($file);
    $strinfo  = @unpack("c2chars", $bin);
    $typecode = intval($strinfo['chars1'].$strinfo['chars2']);
    $filetype = "";
    switch ($typecode) {
    case 7790: $filetype = 'exe';break;
    case 7784: $filetype = 'midi';break;
    case 8297: $filetype = 'rar';break;
    case 255216:$filetype = 'jpg';break;
    case 7173: $filetype = 'gif';break;
    case 6677: $filetype = 'bmp';break;
    case 13780:$filetype = 'png';break;
    default:   $filetype = 'unknown'.$typecode;
    }
    if ($strinfo['chars1']=='-1' && $strinfo['chars2']=='-40' ) {
        return 'jpg';
    }
    if ($strinfo['chars1']=='-119' && $strinfo['chars2']=='80' ) {
        return 'png';
    }
    return $filetype;
}

//支持二维数组求唯一
function super_unique($array, $key)
{
    $temp_array = [];
    foreach ($array as &$v) {
        if (!isset($temp_array[$v[$key]]))
            $temp_array[$v[$key]] =& $v;
    }
    $array = array_values($temp_array);
    return $array;
}

/**
 * 获取开放平台euid需要落单的邀请码
 */
function get_adunion_order_coupon() {
    $adunionOrderCoupon = [];
    $couponList = explode(',', trim(app_conf('ADUNION_ORDER_COUNPON')));
    foreach ($couponList as $coupon) {
        $adunionOrderCoupon[] = strtoupper(trim($coupon));
    }
    return $adunionOrderCoupon;
}

/*
 * 普惠把p2p所有的"收益"改为"利息"
 */
function p2pTextFilter($str, $dealType = DealEnum::DEAL_TYPE_GENERAL)
{
    if ($str && isDealP2P($dealType)) {
        return str_replace('收益', '利息' , $str);
    }
    return $str;
}

/**
 * 获取http请求头
 * @return array
 */
function getAllHeaders()
{
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if ('HTTP_' == substr($key, 0, 5)) {
            $headers[str_replace('_', '-', substr($key, 5))] = $value;
        }
    }
    return $headers;
}

/**
 * 读取测试套件配置表用于在不同的环境中运行测试套件
 *
 * @param string $key 读取的配置键名
 * @return string | null
 */
function tcconf($key)
{
    static $config = [];
    $confFile = ROOT_PATH.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'TestSuite.php';
    if (file_exists($confFile) && empty($config))
    {
        $conf = @include($confFile);
        $config = $conf;
    }
    if (empty($config))
    {
        return null;
    }
    return isset($config[$key]) ? $config[$key] : null;
}


/**
 * 根据贷款类型，获得每两次还款的间隔时间，单位为“月”
 */
function get_delta_month_time($loantype, $repay_time) {
    if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
        $delta_month_time = 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
        $delta_month_time = 1;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
        $delta_month_time = $repay_time;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
        $delta_month_time = 1;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
        $delta_month_time = 3;
    } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
        $delta_month_time = 1;
    } else if($loantype == 5) {
        $delta_month_time = $repay_time;
    }

    return $delta_month_time;
}

function get_user_url($user_info,$field = 'user_name')
{
    $user_name = "";
    if(empty($user_info)) {
        return $user_name;
    }

    // 会员类型
    if (UserEnum::USER_TYPE_ENTERPRISE == $user_info['user_type']) {
        $user_action = 'Enterprise';
        if (EnterpriseEnum::TABLE_FIELD_COMPANY_NAME == $field){
            $field = EnterpriseEnum::TABLE_FIELD_COMPANY_NAME;
        }

    } else {
        $user_action = 'User';
    }

    if(!isset($user_info[$field])) {
        return $field == 'user_name' ? l("NO_USER") : '';
    }else {
        return "<a href='?m=" . $user_action . "&a=index&user_id=" . $user_info['id'] . "' target='_blank'>" . $user_info[$field] . "</a>";
    }
}

function remove_xss($val) {
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <java\0script>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=@avascript:alert('XSS')>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
       // ;? matches the ;, which is optional
       // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

       // @ @ search for the hex values
       $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
       // @ @ 0{0,7} matches '0' zero to seven times
       $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
    }

    // now the only remaining whitespace attacks are \t, \n, and \r
    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra = array_merge($ra1, $ra2);

    $found = true; // keep replacing as long as the previous round replaced something
    while ($found == true) {
       $val_before = $val;
       for ($i = 0; $i < sizeof($ra); $i++) {
          $pattern = '/';
          for ($j = 0; $j < strlen($ra[$i]); $j++) {
             if ($j > 0) {
                $pattern .= '(';
                $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                $pattern .= '|';
                $pattern .= '|(&#0{0,8}([9|10|13]);)';
                $pattern .= ')*';
             }
             $pattern .= $ra[$i][$j];
          }
          $pattern .= '/i';
          $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
          $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
          if ($val_before == $val) {
             // no replacements were made, so exit the loop
             $found = false;
          }
       }
    }
    return $val;
 }

/**
 * @param  String $src_imagename 源文件名        比如 “source.jpg”
 * @param  int    $maxwidth      压缩后最大宽度
 * @param  int    $maxheight     压缩后最大高度
 * @param  String $savename      保存的文件名    “d:save”
 * @param  String $filetype      保存文件的格式 比如 ”.jpg“
 * @param  int    $isFix         是否等比例 0 是
 */
function resizeImage($src_imagename, $maxwidth, $maxheight, $savename, $filetype,$isFix=0) {
    $file_contents = file_get_contents($src_imagename);
    $im = imagecreatefromstring($file_contents);
    // 获取到当前图片的宽和高
    $current_width = imagesx($im);
    $current_height = imagesy($im);

    if (($maxwidth && $current_width != $maxwidth) || ($maxheight && $current_height != $maxheight)) {
        if ($maxwidth && $current_width != $maxwidth) {
            $widthratio = $maxwidth / $current_width;
            $resizewidth_tag = true;
        }

        if ($maxheight && $current_height != $maxheight) {
            $heightratio = $maxheight / $current_height;
            $resizeheight_tag = true;
        }
        //等比例压缩图片
        if($isFix == 0){
            // 计算压缩比例因子
            if ($resizewidth_tag && $resizeheight_tag) {
                $ratio = $widthratio < $heightratio ? $widthratio : $heightratio;
            }

            if ($resizewidth_tag && !$resizeheight_tag) {
                $ratio = $widthratio;
            }

            if($resizeheight_tag && !$resizewidth_tag) {
                $ratio = $heightratio;
            }
            $newwidth = $current_width * $ratio;
            $newheight = $current_height * $ratio;
        }else{
            //指定长度和高度压缩图片
            $newwidth = isset($widthratio) ? ($current_width * $widthratio) : $current_width;
            $newheight = isset($heightratio) ? ($current_height * $heightratio) : $current_height;
        }

        if (function_exists("imagecopyresampled")) {
            $newim = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
        } else {
            $newim = imagecreate($newwidth, $newheight);
            imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
        }

        $savename = $savename . $filetype;
        imagejpeg($newim, $savename);
        imagedestroy($newim);
    } else {
        $savename = $savename . $filetype;
        imagejpeg($im, $savename);
        imagedestroy($im);
    }
}


 /**
  * 测试运行前检查是否可以执行测试
  */
 function checkBeforeTestRun()
 {
    $env = app_conf('ENV_FLAG');
    if ($env == 'product' || $env == 'online')
    {
        exit();
    }
 }


// 是否需要跳转https
function should_jump_https($is_fenzhan = false) {
    if (checkHttpsFromProxy()) {  // 本身就是https
        return false;
    }

    $env  = strtolower(app_conf('ENV_FLAG')); // 当前环境
    $switch_env = $is_fenzhan ? 'FENZHAN_HTTPS_ENV' : 'ZHUZHAN_HTTPS_ENV';
    $envs = array_map('trim', explode(',', strtolower(app_conf($switch_env))));  // 需要支持https的环境
    return $env && in_array($env, $envs);
}

// 跳转https
function jump_to_https($is_fenzhan = false) {
    if (!should_jump_https($is_fenzhan)) {
        return true;
    }

    $host = get_host(false);
    $switch_reg = $is_fenzhan ? 'FENZHAN_HTTPS_REG' : 'ZHUZHAN_HTTPS_REG';
    if (@preg_match(app_conf($switch_reg), $host)) {
        header(sprintf('location:https://%s%s', $host, $_SERVER['REQUEST_URI']));
        exit;
    }
}



/**
 * 发送满标给运营人员Message
 *
 * @param  $deal 标数据
 * @return 写入数据数量
 */
function send_full_deal_message_to_operator($deal)
{
    // 智多鑫标的不发消息
    $dealService = new DealService();
    if ($dealService->isDealDT($deal['id']) === true) {
        return true;
    }
    $project = \core\dao\project\DealProjectModel::instance()->findViaSlave($deal['project_id']);
    //获取短信配置
    $operators = app_conf('FULL_MES_TO_OP');
    $operators = explode('|',$operators);
    foreach($operators as  $v){
        $dd = explode(',',$v);
        $s['agency_id'] = $dd[0];
        $s['product_class'] = explode('/',$dd[1]);
        $s['mobile'] =  explode('/',$dd[2]);
        $mes_user_list[]= $s;
        unset($s);
    }
    $operators_vx = app_conf('FULL_VX_TO_OP');
    $operators_vx = explode('|',$operators_vx);
    foreach($operators_vx as  $v){
        $dd = explode(',',$v);
        $s['product_class'] = explode('/',$dd[0]);
        $s['name'] = strtr($dd[1],'/','|');   
        $vx_user_list[]= $s;
        unset($s);
    }
    $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);
    $notice_sms['success_time'] =  date('Y-m-d H:i', ($deal['success_time']+28800));//满标时间
    $notice_sms['repay_time'] = ($deal['loantype']==5) ?$deal['repay_time'].'天':$deal['repay_time'].'个月';//借款期限
    $notice_sms['rate'] = number_format($deal['rate'], 2).'%';//费率
    $notice_sms['borrow_amount'] =  floorfix($deal['borrow_amount']).'元';//借款总金额
    $allFee= \core\dao\deal\DealModel::instance()->getAllFee($deal['id']);//需要计算    预计到账金额
    $allDealFee = $allFee['loan_fee']+$allFee['consult_fee']+$allFee['guarantee_fee']+$allFee['pay_fee']+$allFee['manage_fee'];
    // 放款提现后再收费
    if($dealService->isAfterGrantFee($deal['id'])){
        //各项费用清0，放款后首
        $allDealFee =  0;
    }

    if($deal['loan_type']== DealExtEnum::LOAN_AFTER_CHARGE){
        $allDealFee =  0;
    }
    $notice_sms['pay_amount']  =  floorfix(($deal['borrow_amount']-$allDealFee)).'元';//借款总金额
    $notice_sms['name'] = $project['name'];//项目名称
    if($deal_ext['loan_fee_rate_type'] == 1) {
        $notice_sms['loan_type'] ='前收';
    } else if($deal_ext['loan_fee_rate_type'] == 2) {
        $notice_sms['loan_type'] ='后收';
        $notice_sms['pay_amount'] = floorfix(($deal['borrow_amount'])).'元';
    } else if($deal_ext['loan_fee_rate_type'] == 3) {
        $notice_sms['loan_type'] = "分期收";
    }
    if($project['loan_money_type'] == 0 || $project['loan_money_type'] == 1) {///放款方式 在项目列表
        $notice_sms['loan_money_type']  = "实际放款";
    } else if($project['loan_money_type'] == 2) {
        $notice_sms['loan_money_type'] = "非实际放款";
    } else if($project['loan_money_type'] == 3) {
        $notice_sms['loan_money_type'] = "受托支付";
    }
    $notice_sms['borrow_user_name'] =UserService::getUserRealName($deal['user_id']) ;//借款企业名称user_id
    $deal_agency_service  = new \core\service\deal\DealAgencyService();
    $deal_agency= $deal_agency_service -> getDealAgency($deal['agency_id']);
    $notice_sms['agency_name'] = $deal_agency['name'];////担保企业名称
    $content['con'] = implode($notice_sms,'，');

    foreach ($mes_user_list as $k => $v) {
        //发送短信
        if($deal['advisory_id'] ==$v['agency_id']  && in_array($project['product_class'],$v['product_class']) ){
            foreach($v['mobile'] as $s){
                SmsServer::instance()->send($s, 'FULL_MES_TO_OP', $content);//itil 里配置短信模板
            }
        }
        unset($v);
    }
    foreach ($vx_user_list as $k => $v) {
        //发送企业微信
        if( in_array($project['product_class'],$v['product_class']) ){

                // 微信群发

                $result = file_get_contents('http://itil.firstp2p.com/api/weixin/sendText?to='.$v["name"].'&content='.urlencode($content['con'].'，已满标').'&sms=0&appId=deal-full-notice');
               $ret = json_decode($result);
               if($ret->code ==0 ){
                Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ",企业微信推送成功 params:".json_encode( $content['con']));
              }

        }
        unset($v);
    }
  }



