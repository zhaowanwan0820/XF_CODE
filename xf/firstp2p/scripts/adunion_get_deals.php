<?php
/**
 * #拉取top20 的标
 * * * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php adunion_get_deals.php
 * * * * * * sleep 30; cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php adunion_get_deals.php
 * @author daiyuxin  2014-07-22
 */
require_once dirname(__FILE__).'/../app/init.php';
// error_reporting(E_ALL);

FP::import("libs.utils.logger");

use core\service\DealService;
use libs\caching\RedisCache;
use libs\utils\Aes;
$redisConf = array('hostname'=>'10.18.6.50','port'=>'8899');
const WM_DATA_KEY = "FIRSTP2P_WM_DATA";
const DEALS_SUBKEY = "DEALS";
const DEAL_URL_PREFIX = "http://www.firstp2p.com/d/%s";

const WM_SET_DATA_API = "http://u.firstp2p.com/api/setWmData";
const AES_KEY = "firstp2p20140709";



try{

    $dealService = new DealService;

    $data = array(
      'offset' => null,
      'count' => null,
      'type' => null,
      'sort' => null,
      'field' => null,
      'site_id' => null,
    );
    $site_id = $data['site_id'] ? $data['site_id'] : 1 ;
    $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1 ;
    $deals = $dealService -> getList($data['type'], $data['sort'], $data['field'], $p, $data['count'], false, $site_id, TRUE);


    $result = array();
    foreach ($deals['list']['list'] as $k => $v) {
        $result[$k]['productID'] = $v['id'];
        $result[$k]['type'] = $v['type_match_row'];
        $result[$k]['title'] = $v['old_name'];
        $result[$k]['timelimit'] = $v['repay_time'] . ($v['loantype']==5 ? "天" : "个月");
        $result[$k]['total'] = $v['borrow_amount_format_detail'] . "万";
        $result[$k]['avaliable'] = $v['need_money_detail'];
        $result[$k]['progress'] = sprintf("%.2f", 100 - (preg_replace('/,/','',$v['need_money_detail'])/ $v['borrow_amount_format_detail'] / 100));
        $result[$k]['repayment'] = $v['loantype_name'];
        $result[$k]['mini'] = $v['min_loan_money'];
        $result[$k]['stats'] = $v['deal_status'];
        $result[$k]['rate'] = $v['income_total_show_rate'];
        $result[$k]['url'] = sprintf(DEAL_URL_PREFIX, Aes::encryptForDeal($v['id']));
        $result[$k]['dealTagName'] = $v['deal_tag_name'];
        $result[$k]['dealTagDesc'] = $v['deal_tag_desc'];
        $result[$k]['dealCrowd'] = $v['deal_crowd'];
    }


    $sortedDeals = array();
    //标排序
    $groupedDeals = array(
        'deal_crowd_1' => array(), //新手专享
        'deal_status_1' => array(),//投资
        'deal_status_0' => array(),//查看
        'deal_status_2' => array(),//满标
        'deal_status_4' => array(),//还款中
    );

    foreach($result as $k => $item){

        if($item['stats'] == 1 && $item['dealCrowd'] == 1 ){
            $groupedDeals['deal_crowd_1'][] = $item;
            continue;
        }
        switch($item['stats']){
            case 0:
                $groupedDeals['deal_status_0'][] = $item;
                break;
            case 1:
                $groupedDeals['deal_status_1'][] = $item;
                break;
            case 2:
                $groupedDeals['deal_status_2'][] = $item;
                break;
            case 4:
                $groupedDeals['deal_status_4'][] = $item;
                break;
        }

    }

    foreach($groupedDeals as &$group){
        $mini = array();
        foreach($group as $k=>$deal){
            //起投金额ASC, 相等起投金额按收益DESC
            $mini[$k] = $deal['mini'] + (100-$deal['rate']);
        }
        array_multisort($mini, SORT_NUMERIC, SORT_ASC, $group);

        $sortedDeals = array_merge($sortedDeals, $group);
    }


    $retJson = json_encode($sortedDeals);

    //$redis = new RedisCache($redisConf);
    // $ret = $redis->executeCommand('HSET', array(WM_DATA_KEY, DEALS_SUBKEY, $retJson));

    $postData = array(
      DEALS_SUBKEY => $retJson,
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


