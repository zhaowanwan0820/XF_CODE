<?php
/**
 * 先锋支付通用网关接口 (正式环境)
 */
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
    'SIGNATURE_SALT' => 'c172a57ca73123559398a98e3a0e0a40',
    //商户代码
    'MERCHANT_ID' => 'M100000003',
    //接口版本
    'VERSION' => '1.0.0',
    //签名算法
    'SEC_ID' => 'MD5',
    //接口列表
    'API_LIST' => array(
        //创建充值订单
        'h5charge' => array(
            'service' => 'MP_H5_ORDER_CREATE',
            'noticeUrl' => $http.$notifyDomain.'/payment/chargeNotifyH5',
            'version' => '1.0.0',
        ),
        //快捷支付支持银行卡及限额列表
        'banklist' => array(
            'service' => 'MP_H5_BANKLIMIT_QUERY',
            'version' => '1.0.0',
        ),
        // 查询银行交易限额信息
        'banklimit' => array(
            'service' => 'MOBILEPAY_SERVICE_QUERY_BANKLIMIT',
            'secId' => 'MD5',
            'version' => '3.0.0',
            'busi' => 'CERTPAY',
            'busiType' => 'RECHARGE',
            'scene' => '010101',
            'source' => 'SDK',
        ),
        // 四要素认证
        'cardValidate' => array(
            'service' => 'MP_H5_FACTOR_AUTH',
            'noticeUrl' => $http.$notifyDomain.'/payment/bindCardNotify',
            'version' => '1.0.0',
        ),
        // H5 绑卡页面
        'h5AuthBindCard' => array(
            'service' => 'MP_H5_CARD_AUTH',
            'secId' => 'MD5',
            'returnUrl' => $http.'m.ncfwx.com/account',
            'failUrl' => $http.'m.ncfwx.com/account',
            'noticeUrl' => $http.'api.ncfwx.com/account/AuthBindCardNotify',
            'version' => '1.0.0',
        ),

    ),
);
