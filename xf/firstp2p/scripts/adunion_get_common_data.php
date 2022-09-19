<?php
/**
 * @Author daiyuxin@ucfgroup.com 20140722
 * #拉取 commonData , 每两小时拉取一次
 * *\/2 * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php adunion_get_common_data.php
 * @author daiyuxin  2014-07-22
 */
require_once dirname(__FILE__).'/../app/init.php';
// error_reporting(E_ALL);

FP::import("libs.utils.logger");
// FP::import("libs.caching.rediscache");
use core\service\DealService;
use core\service\EarningService;
use libs\caching\RedisCache;
$redisConf = array('hostname'=>'10.18.6.50','port'=>'8899');

const WM_DATA_KEY = "FIRSTP2P_WM_DATA";
const COMMON_DATA_SUBKEY = "COMMON_DATA";


const WM_SET_DATA_API = "http://u.firstp2p.com/api/setWmData";
const AES_KEY = "firstp2p20140709";



try{

    $earningService = new EarningService;

    $ret = $earningService->getIncomeViewNew();

    $data = array();

    if(isset($ret['load']) && isset($ret['income_sum'])){
       $data['total_amount'] = str_replace(",",'',str_replace("元", "", $ret['load'])) * 10000;
       $data['total_amount_w'] = sprintf("%.2f", $data['total_amount'] /10000) ;
       $data['total_amount_y'] = sprintf("%.2f", $data['total_amount'] / 100000000);
       $data['total_profit'] = str_replace(",",'',str_replace("元", "", $ret['income_sum']));
       $data['total_profit_w'] = sprintf("%.2f", $data['total_profit'] /10000) ;

       $data['total_profit'] = sprintf("%s", str_replace("元", "", format_price($data['total_profit'])));
       $data['total_amount'] = sprintf("%s", str_replace("元", "", format_price($data['total_amount'])));
    }else{
      throw new Exception(__FILE__."\tcommonData is not availabel");
    }

    $retJson = json_encode($data);
    // $redis = new RedisCache($redisConf);
    // $ret = $redis->executeCommand('HSET', array(WM_DATA_KEY, COMMON_DATA_SUBKEY, $retJson));


    $postData = array(
      COMMON_DATA_SUBKEY => $retJson,
      'sign' => md5($retJson.AES_KEY)
      );
    $postData = http_build_query($postData);

    post(WM_SET_DATA_API, $postData);


}catch(Exception $e){
    logger::warn($e->getMessage());
}

function post($url, $param="") {
    if (empty($url)) {
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch,CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if ($result === false) {
        curl_errno($ch);
    }
    curl_close($ch);
    return $result;
}
