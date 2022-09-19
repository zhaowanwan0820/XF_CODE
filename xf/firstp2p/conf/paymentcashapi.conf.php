<?php
/**
 * 先锋支付现金代发相关接口 (正式环境)
 */
//$switch_https = empty($GLOBALS['sys_config']['IS_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['IS_HTTPS']);
// 读取支付https开关
$payment_https = empty($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']);
$http = 'http://';
if ($payment_https == 1){
    $http = 'https://';
}
// 动态区别网信理财灰度和生产环境通知中心域名
$notifyDomain = 'notify.ncfwx.com';
if (isP2PRc()) {
    $notifyDomain = 'prenotify.ncfwx.com';
}

return array(
    //接口网关Url
    'GATEWAY' => 'https://mapi.ucfpay.com/gateway.do',
    //签名加密串
    'SIGNATURE_SALT' => '74037148ed98e775786d245fca0c67fd',
    //商户代码
    'MERCHANT_ID' => 'M200000302',
    //接口版本
    'VERSION' => '1.0.0',
    //签名算法
    'SEC_ID' => 'MD5',
    //接口列表
    'API_LIST' => array(
        //单笔代发接口
        //'withdraw' => array(
        //    'retry' => true,
        //    'service' => 'REQ_WITHDRAW_P2P',
        //    'noticeUrl' => 'http://www.wangxinlicai.com/payment/cashpresentNotify',
        //    'seniorProductCode' => 'S110126000301010000',
        //),
        //单笔订单查询
        'query' => array(
            'service' => 'REQ_WITHDRAW_QUERY_BY_ID',
        ),
        //创建充值订单
        'h5charge' => array(
            'service' => 'MP_H5_ORDER_CREATE',
            'noticeUrl' =>  $http.$notifyDomain.'/payment/chargeNotifyH5',
        ),
        //快捷支付支持银行卡及限额列表
        'banklist' => array(
            'service' => 'MP_H5_BANKLIMIT_QUERY',
        ),
    ),
);
