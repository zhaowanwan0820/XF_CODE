<?php

namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class PartnerEnum extends AbstractEnum {
    //预定义接口列表，不同合作方按照clientId配置相关的接口输入和返回值映射关系
    const INTERFACE_PUSH_ADDRESS   = 'pushAddress';
    const INTERFACE_PUSH_COUPON    = 'pushCoupon';
    const INTERFACE_GET_COUPON     = 'getCoupon';
    const INTERFACE_NCF_VERIFY     = 'ncfVerify';
    const INTERFACE_PARTNER_VERIFY = 'partnerVerify';
    public static $partnerConf = array(
        //配置说明:
        //1.key为网信给合作方分配的clientId，需要与券组配置时的coupon_provider一致
        //2.common 为合作方通用配置，包含签名算法，加密密钥，给网信分配的id和接口地址,以及参数传送格式
        //3.接口配置以接口名为键存放,合作方使用的接口都需要在此声明并配置
        //4.接口参数包括request-请求参数映射，response-返回值映射，extraData-第三方要求的其他必填值
        //5.接口返回的正常状态为expRes
        //合作商配置信息，key为网信给合作方分配的clientId
        //先锋支付配置
        'ucfpay' => array(
            'common' =>  array('signMethod' =>'ucfpaySign', 'signKey' => 'c172a57ca73123559398a98e3a0e0a40', 'merchantId' => 'M100000003', 'url' => 'http://mapi.ucfpay.com/gateway.do', 'devSignKey' => 'a9a199c79f3cf3d0b17b544634686e47', 'devMerchantId' => 'MT10000000', 'devurl' => 'http://111.203.205.26:8085/gateway.do'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名'
                'request' => array('merchantId' => 'merchantId', 'clientId' => 'clientId', 'phone' => 'phone', 'productId' => 'productId', 'price' => 'price', 'beginTime' =>'beginTime', 'endTime' => 'endTime', 'orderId' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'respCode', 'errorMsg'=> 'respMsg'),
                //第三方额外参数
                'extraData' => array('service' => 'UCF_MOBILEPAY_P2P_ADD_CARD', 'secId' => 'MD5', 'version' => '1.0.0'),
                //第三方成功时的返回码
                'expRes' => '00'
            ),
        ),
        //中免二期，10月8日开始
        'ucfpay1008' => array(
            'common' =>  array('signMethod' =>'ucfpaySign', 'signKey' => 'c172a57ca73123559398a98e3a0e0a40', 'merchantId' => 'M100000003', 'url' => 'http://mapi.ucfpay.com/gateway.do', 'devSignKey' => 'a9a199c79f3cf3d0b17b544634686e47', 'devMerchantId' => 'MT10000000', 'devurl' => 'http://111.203.205.26:8085/gateway.do'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名'
                'request' => array('merchantId' => 'merchantId', 'clientId' => 'clientId', 'phone' => 'phone', 'productId' => 'productId', 'price' => 'price', 'beginTime' =>'beginTime', 'endTime' => 'endTime', 'orderId' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'respCode', 'errorMsg'=> 'respMsg'),
                //第三方额外参数
                'extraData' => array('service' => 'UCF_MOBILEPAY_P2P_ADD_CARD', 'secId' => 'MD5', 'version' => '1.0.0'),
                //第三方成功时的返回码
                'expRes' => '00'
            ),
        ),
        //AA租车配置
        'aazc' => array(
            'common' =>  array('signMethod' =>'ncfgroupSign', 'signKey' => '3b269d81bbc7dc28431304e3dcda31a6', 'merchantId' => 'firstp2p2015aayongche', 'url' => 'http://yongche.aayongche.com/v3/coupon/ucf', 'devSignKey' => '2bfdb0cee75fb161f17f22e46b039c84', 'devMerchantId' => '12345678', 'devurl' => 'http://test.aayongche.com:5001/v3/coupon/ucf'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名'
                'request' => array('merchantId' => 'merchantId', 'clientId' => 'clientId', 'phone' => 'phone', 'productId' => 'productId', 'price' => 'price', 'beginTime' =>'beginTime', 'endTime' => 'endTime', 'orderId' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'errorCode', 'errorMsg'=> 'errorMsg'),
                //第三方成功时的返回码
                'expRes' => 0
            ),
        ),
        //易赏配置
        'yishang' => array(
            'common' =>  array('signMethod' =>'yishangSign', 'signKey' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8', 'merchantId' => '162', 'url' => 'http://api.1shang.com/orders/getAward', 'devSignKey' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8', 'devMerchantId' => '162', 'devurl' => 'http://api.1shang.com/orders/getAward'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名',易赏约定prizePriceTypeId为面值id唯一，可以关联成productId
                'request' => array('userId' => 'merchantId', 'phone' => 'phone', 'prizePriceTypeId' => 'productId', 'customOrderCode' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'result', 'errorMsg'=> 'respMsg'),
                //第三方额外参数
                'extraData' => array('count' => '1', 'operation' => 'recharge' /*直充参数*/,  'orderId' => '2793'),
                //第三方成功时的返回码
                'expRes' => "10000",
                //第三方订单重复时的返回码
                'escapeCode' => array('56', '58')
            ),
        ),
        //易赏高铁流量包配置
        'traffic' => array(
            'common' =>  array('signMethod' =>'yishangTrafficSign', 'signKey' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8', 'merchantId' => '162', 'url' => 'http://api.1shang.com/orders/getTraffic', 'devSignKey' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8', 'devMerchantId' => '162', 'devurl' => 'http://api.1shang.com/orders/getTraffic'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名',易赏约定prizePriceTypeId为面值id唯一，可以关联成productId
                'request' => array('userId' => 'merchantId', 'phone' => 'phone', 'customOrderCode' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'result', 'errorMsg'=> 'respMsg'),
                //第三方额外参数
                'extraData' => array('orderId' => '2932', 'operation' => 'recharge' /*直充参数*/,  'ydPrizePriceTypeId'=>'205', 'dxPrizePriceTypeId' => '195', 'ltPrizePriceTypeId' => '415'),
                //第三方成功时的返回码
                'expRes' => "10000",
                //第三方订单重复时的返回码
                'escapeCode' => array('56', '58')
            ),
        ),
        //易赏高铁话费配置
        'huafei' => array(
            'common' =>  array('signMethod' =>'yishangSign', 'signKey' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8', 'merchantId' => '162', 'url' => 'http://api.1shang.com/orders/getAward', 'devSignKey' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8', 'devMerchantId' => '162', 'devurl' => 'http://api.1shang.com/orders/getAward'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名',易赏约定prizePriceTypeId为面值id唯一，可以关联成productId
                'request' => array('userId' => 'merchantId', 'phone' => 'phone', 'prizePriceTypeId' => 'productId', 'customOrderCode' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'result', 'errorMsg'=> 'respMsg'),
                //第三方额外参数
                'extraData' => array('count' => '1', 'operation' => 'recharge' /*直充参数*/,  'orderId' => '2932'),
                //第三方成功时的返回码
                'expRes' => "10000",
                //第三方订单重复时的返回码
                'escapeCode' => array('56', '58')
            ),
        ),
        //U宝配置
        'ubao' => array(
            'common' =>  array('signMethod' =>'ubaoSign', 'signKey' => 'KHF154daia54enIUB54P', 'merchantId' => '101001', 'url' => 'http://dls.uyou.com/asd_recharge/submit', 'devSignKey' => 'asd_uyou', 'devMerchantId' => '100001', 'devurl' => 'http://117.79.145.177:8080/crm/asd_recharge/submit', 'format' => 'json'),
            self::INTERFACE_PUSH_COUPON => array(
                //第三方和网信接口请求参数映射 '第三方参数名'=>'网信参数名',u宝约定good_id为面值id唯一，可以关联成productId
                'request' => array('bus_id' => 'merchantId', 'charge_num' => 'phone', 'good_id' => 'productId', 'charge_id' => 'orderId'),
                //第三方和网信接口返回参数映射 '网信参数名' => '第三方参数名'
                'response' => array('errorCode' => 'code', 'errorMsg'=> 'message'),
                //第三方额外参数
                'extraData' => array('bus_name' => 'WXLCZC001',/*商户名，必传*/ 'bus_pw' => 'L9uBVJ8K5Kyab'/*商户密码,测试用asd_nt_test */ ,  'ammount' => ''),
                //第三方成功时的返回码
                'expRes' => 200,
                //第三方订单重复时的返回码
                'escapeCode' => array(401)
            ),
        ),
    );

}
