<?php
/**
 * 先锋支付相关接口
 * 正式环境
 */
//$switch_https = empty($GLOBALS['sys_config']['IS_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['IS_HTTPS']);
// 读取支付https开关
$payment_https = empty($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']);
$http = 'http://';
if ( $payment_https == 1){
    $http = 'https://';
}


// 动态区别网信理财灰度和生产环境通知中心域名
$notifyDomain = 'notify.ncfwx.com';
//普惠回调地址
$ncfphNotifyDomain = 'notify.firstp2p.cn';
if (isP2PRc()) {
    $notifyDomain = 'prenotify.ncfwx.com';
    $ncfphNotifyDomain = 'prenotify.firstp2p.cn';
}

// 网信页面跳转地址
$domainName = 'www.ncfwx.com';
// 先锋支付生产环境网关地址
$ucfpayGateway = 'https://cgw.unitedbank.cn/u-trade';

return array(
    'ucfpay' => array(
        //通用配置
        'common' => array(
            //签名字段计算Salt
            'SIGNATURE_SALT' => 'c172a57ca73123559398a98e3a0e0a40',
            //AES解密Key (注意要转成16字节字符串)
            'AES_KEY' => 'c172a57ca73123559398a98e3a0e0a40',
            //平台商ID
            'MERCHANT_ID' => 'M100000003',
            //远程日志服务配置
            //'REMOTE_LOG_IP' => '10.10.10.240',
            'REMOTE_LOG_IP' => 'pmlog1.wxlc.org',
            'REMOTE_LOG_PORT' => '55001',
            'PAYMENT_ID' => 4, // 支付方式编号(先锋支付4|易宝支付3)
            'PAYMENT_NAME' => '先锋支付', // 支付方式名称
            'CREATE_ORDER_TIPS' => '先锋支付系统维护中,为您提供易宝支付',
        ),
        //后台Token获取
        'tokenMerchant' => array(
            'url' => 'https://mapi.ucfpay.com/token/apply/merchant',
        ),
        //跳转Token获取
        'tokenUser' => array(
            'url' => 'https://mapi.ucfpay.com/token/apply/user',
        ),
        //注册开户接口
        'register' => array(
            'url' => $ucfpayGateway.'/member/b/register',
            'retry' => true,   //是否重试
            'bizType' => 'q001zc',
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        //注册开户绑卡合并接口
        'register_bindbank' => array(
            'url' => $ucfpayGateway.'/member/b/register_bindbank',
            'bizType' => 'q001zc',
            'retry' => true,   //是否重试
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        // 企业用户开户接口
        'compregister' => array(
            'url' => $ucfpayGateway.'/member/b/compregister',
            'params' => array(
                'userId' => 1,
            ),
        ),
        //银行卡四要素验证接口（跳转）
        'authCard' => array(
            'url' => $ucfpayGateway.'/member/r/authCard',
            'redirect' => true,
            'callbackUrl' => $http.$notifyDomain.'/payment/authCardNotify',
            'returnUrl' => $http.$domainName.'/account/editbank',
            'params' => array(
                'userId' => 1,
            )
        ),
        //银行卡四要素验证接口（appH5跳转)
        'h5authCard' => array(
            'url' => $ucfpayGateway.'/member/h5/authCard',
            'redirect' => true,
            'callbackUrl' =>  $http.$notifyDomain.'/payment/authCardNotify',
            'params' => array(
                    'userId' => 1,
            )
        ),
        // 支付h5四要素绑卡页面(跳转)
        'h5AuthBindCard' => array(
            'url' => $ucfpayGateway.'/member/r/authBindCard',
            'redirect' => true,
            'callbackUrl' => $http.$notifyDomain.'/payment/bindCardNotify',
            'params' => array(
                'userId' => 1,
            )
        ),
        //银行卡绑定
        'bindbankcard' => array(
            'url' => $ucfpayGateway.'/member/b/bindbankcard',
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        //用户绑定银行卡信息查询
        'searchbankcards' => array(
            'url' => $ucfpayGateway.'/member/b/searchbankcards',
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        //解绑银行卡
        'unbindCard' => array(
            'url' => $ucfpayGateway.'/member/u/unbindCard',
            'retry' => true,
            'params' => array(
                'userId' => 1, //必填项
                //'cardNo' => 1, //必填项
            ),
        ),
        //手机号码修改接口
        'phoneupdate' => array(
            'url' => $ucfpayGateway.'/member/b/phoneupdate',
            'bizType' => 'h005sjhmxg',
            'params' => array(
                'userId' => 1,
            ),
        ),
        //银行卡提现接口
        'towithdrawal' => array(
            'url' => $ucfpayGateway.'/member/b/towithdrawal',
            'callbackUrl' => $http.$notifyDomain.'/payment/withdrawNotify',
            'params' => array(
                'userId' => 1,
            ),
            'bizTypeMap' => array( // 请求的业务类型
                'default' => 'q007tx', // 默认值，普通提现
                'FKTX' => 'q007fktx', // 放款提现
                'dealLoanType' => array( // firstp2p_deal_loan_type表的type_tag字段
                    'XFD' => 'q007xfdtx', // 消费贷提现
                    'XFFQ' => 'q007xfdtx', // 信分期提现-同消费贷提现
                    'ZZJR' => 'q007xfdtx', // 掌众闪电消费提现
                    'XSJK' => 'q007xfdtx', // 昂励-信石借款
                ),
            ),
        ),
        //转账异步通知结果接口
        'pretransfer' => array(
            'url' => $ucfpayGateway.'/trade/b/pretransfer',
            'retry' => true,   //是否重试
            'bizType' => 'q007tx',
        ),
        //转账接口
        'transfer' => array(
            'url' => $ucfpayGateway.'/trade/b/transfer',
            'retry' => true,   //是否重试
            'bizType' => 'q007tx',
        ),
        //对账信息批量查询接口
        'searchtrades' => array(
            'url' => $ucfpayGateway.'/trade/b/searchtrades',
            'bizType' => 'q007tx',
        ),
        //单笔交易查询接口
        'searchonetrade' => array(
            'url' => $ucfpayGateway.'/trade/b/searchonetrade',
            'retry' => true,   //是否重试
            'bizType' => 'q007tx',
        ),
        //用户余额查询接口
        'searchuserbalance' => array(
            'url' => $ucfpayGateway.'/member/b/searchBalance',
            //'bizType' => 'q007tx',
            'retry' => true,   //是否重试
            'params' => array(
                'userId' => 1,
            ),
        ),
        //批量用户余额查询
        'searchBalances' => array(
            'url' => $ucfpayGateway.'/member/b/searchBalances',
            'retry' => true,   //是否重试
        ),
        //用户信息查询接口
        'searchuserinfo' => array(
            'url' => $ucfpayGateway.'/member/b/searchUserInfo',
            //'bizType' => 'q007tx',
            'retry' => true,   //是否重试
            'params' => array(
                'userId' => 1,
            ),
        ),
        //记录投资项目信息 (接口待定)
        'saveprojectinfo' => array(
            'url' => $ucfpayGateway.'/trade/b/saveProjectInfo',
            //'bizType' => 'q007tx',
            'params' => array(
                'userId' => 1,
            ),
        ),
        //支付密码修改接口 (跳转)
        'pwdupdate' => array(
            'url' => $ucfpayGateway.'/member/f/pwdupdate',
            'bizType' => 'q003zfmmxg',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        //支付密码找回接口 (跳转)
        'pwdfind' => array(
            'url' => $ucfpayGateway.'/member/f/pwdfind',
            'bizType' => 'q004zfmmzh',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        //支付 pc 绑卡接口(跳转)
        'bindCard' => array(
            'url' => $ucfpayGateway.'/member/r/bindCard',
            'redirect' => true,
            'callbackUrl' => $http.$notifyDomain.'/payment/bindCardNotify',
            'params' => array(
                'userId' => 1,
            ),
        ),

        //账户充值接口 (跳转)
        'torecharge' => array(
            'url' => $ucfpayGateway.'/member/f/torecharge',
            'callbackUrl' => $http.$notifyDomain.'/payment/chargeNotify',
            'bizType' => 'h005sjhmxg',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // 查询卡bin接口
        'searchcardbin' => array(
            'url' => $ucfpayGateway.'/member/b/serchcardbin',
            'params' => array(
                'source' => 'p2p_fix_script',
            ),
        ),
        // 第三方平台网信理财投资接口
        'invest' => array(
            'url' => $ucfpayGateway.'/trade/deal/invest',
            'callbackUrl' => $http.$notifyDomain.'/payment/investNotify',
            'retry' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // mobile-p2p 换卡接口
        'mchangecard' => array(
            'url' => 'https://cgw.unitedbank.cn/m-pay/p2pChangeCard/changeCard',
            'retry' => true,
        ),
        // mobile-p2p 查询银行卡认证状态
        'mquerycardstatus' => array(
            'url' => 'https://cgw.unitedbank.cn/m-pay/p2pQueryCardStatus/queryCardStatus',
            'retry' => true,
        ),
        // 企业会员注册接口
        'newcompregister' => array(
            'url' => $ucfpayGateway.'/member/b/newcompregister',
            'retry' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // 企业会员修改接口
        'newcompupdate' => array(
            'url' => $ucfpayGateway.'/member/b/newcompupdate',
            'retry' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // 个人用户基本资料同步接口
        'modifyuserinfo' => [
            'url' => $ucfpayGateway.'/member/b/modifyuserinfo',
            'callbackUrl' => $http.$notifyDomain.'/payment/modifyUserInfoNotify',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 个人用户银行卡修改接口
        'modifybankinfo' => [
            'url' => $ucfpayGateway.'/member/b/modifybankinfo',
            'callbackUrl' => $http.$notifyDomain.'/payment/modifyBankInfoNotify',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 个人用户注销接口
        'canceluser' => [
            'url' => $ucfpayGateway.'/member/b/canceluser',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 托管银行资金划拨
        'towithdrawaltrustbank' => [
            'url' => $ucfpayGateway.'/member/b/towithdrawaltrustbank',
            'domain' => $http.$notifyDomain,
            'callbackUrl' => $http.$notifyDomain.'/payment/withdrawTrustBankNotify',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 网信速贷请求资金划拨接口
        'creditLoanWithdraw' => [
            'url' => 'http://cgw.unitedbank.cn/u-trade/member/b/towithdrawaltrustbank',
            'domain' => $http.$notifyDomain,
            'callbackUrl' => $http.$notifyDomain.'/creditloan/withdrawNotify',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        //用户信息管理接口
        'queryUserInfo' => [
            'url' => $ucfpayGateway.'/superAccount/queryUserInfo',
            'returnUrl' => $http.$domainName.'/account',
            'retry' => true,   //是否重试
            'params' => [
                'userId' => 1,
            ],
        ],
        //用户信息管理接口
        'h5QueryUserInfo' => [
            'url' => $ucfpayGateway.'/superAccount/queryUserInfoH5',
            'returnUrl' => $http.$domainName.'/account',
            'retry' => true,   //是否重试
            'params' => [
                'userId' => 1,
            ],
        ],
        // 静态白名单用户同步验卡方式
        'staticWhitelist' => [
            'url' => $ucfpayGateway.'/member/b/authBindCard',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 掌众暗绑接口
        'quickAuthBindCard' => [
            'url' => $ucfpayGateway.'/member/b/quickAuthBindCard',
            'params' => [
                'userId' => 1,
            ],
        ],
        // 四要素换卡接口
        'quickAuthChangeCard' => [
            'url' => $ucfpayGateway.'/member/b/quickAuthChangeCard',
            'params' => [
                'userId' => 1,
            ],
        ],
        // 线下大额充值
        'offlineCharge' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/openService',
            'retry' => false,
            'callbackUrl' => $http.$notifyDomain.'/payment/offlineChargeNotify',
            'params' => [
                'userId' => 1,
            ],
        ],
        // 线下大额充值
        'offlineChargeV3' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/recharge',
            'retry' => false,
            'callbackUrl' => $http.$notifyDomain.'/payment/offlineChargeNotify',
            'params' => [
                'userId' => 1,
            ],
        ],
        // 后台四要素认证页面
        'factorAuth' => [
            'url' => 'https://riskconfig.firstpay.com/risk-config-web/cardAuth/CardAuthController/toList',
        ],
        // 用户存管预开户
        'moveUser' => [
            'url' => $ucfpayGateway.'/member/b/moveUsers',
            'retry' => true,
        ],
        // 取消网信白名单
        'cancelAuth' => [
            'url' => $ucfpayGateway.'/member/b/cancelAuth',
            'retry' => true,
        ],
        // 新支付充值
        'newH5Charge' => [
            'url' => $ucfpayGateway.'/newrecharge/h5/rechargeService',
            'callbackUrl'   => $http.$notifyDomain.'/payment/chargeNotify',
        ],
        // 下单模式大额充值支付在途订单数量查询接口
        'offlineChargeOrderQuery' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/getOrderNum',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],

        ],
        // 下单模式大额充值支付在途订单列表
        'offlineChargeOrderPage' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/queryOrdersToPage',
            'params' => [
                'userId' => 1,
            ],
        ],
        // 大额充值订单信息查询接口
        'queryOfflineOrders' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/queryOfflineOrders',
            'params' => [
                'userId' => 1,
            ]
        ],
        // 资金流水查询
        'queryAccountRecords' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/queryAccountRecords',
        ],
        // 新支付限额查询接口
        'bankLimit' => [
            'url' => $ucfpayGateway. '/newrecharge/queryBankLimit',
        ],
        //银行卡预留手机号修改
        'h5UpdateBankPhone' => [
            'url' => $ucfpayGateway.'/infomanage/h5/updBankPhone',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
                'bankCardId' => 1,
                'orderId' => 1,
            ),
        ],
        // 网信账户用户限额信息查询接口
        'queryAllBankLimit' => [
            'url' => $ucfpayGateway.'/newrecharge/queryAllBankLimit',
            'params' => [
                'userId' => 1,
                'busType' => 1,
            ]
        ],
        // 大额充值订单处理中详情页面（页面）
        'getOrderInfo' => [
            'url' => $ucfpayGateway.'/newofflinerecharge/getOrderInfo',
            'params' => [
                'userId' => 1,
                'outOrderId' => 1,
            ]
        ],
    ),
    // 易宝支付相关配置
    'yeepay' => array( // 要跟PaymentApi::instance()设置的方法名统一
        'common' => array(
            'MERCHANT_ID' => '10014967914',
            'MERCHANT_PRIVKEY' => 'MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAIcnGfIdpObeB5HIOF2CVgvufqAh7xtgWDiaCevxFr68m+y66xlBIVn/Ijnj/qnXELDRz8DE/8Kn+Xt5avnaNoLt6jzKecHEHKJX7xpsNYFP3s/WGiVQsJ9bF4YXEq/ufEUXevOrmN5ckatMgp4F/uhxjAhVnILVIqL3ZZwwn1UDAgMBAAECgYAp9MiIMg20Ie8loYtl9AU0VQh4O7CXxhP9FkzIMyLFeZXKKsi7IU3yO1Lrt8yh+wLScX/WLxHa4vx/CVVdVRGgpi+/7qOPMLNIph2kIfjqerjbAwYZNn6feXFeb1akwFKoH781ViOUmlYsidu50DRw97EsPZfISR6xoc/1Sr5TIQJBALqqeoGwWiW499+rk1FUNeS/KpnXLydAqlqXpD7ODt9l+UW8F4tD29dNVzDBk8yKx5EFoNOLR3ntBPjR9R6NC5ECQQC5WmIIUXDtRA2bE79HtmTHE1e52b1xbSn85x09bxhvChw5vUk0Fp1BC4Sj8tJ7nI4lSf6U9DrNyjzI6mkkHsVTAkAWcbkZLuMn9f2X30FvXfi88F9m8ACzb4sMKX+OLaiMI+68+8i47gfY82uwaRYkWet0/IBB71VAy8b1RAl9CuiBAkBJ+CHrQ+UXKvNrEeRiEA4DzFpUFusdWv1Iqkrm+3D6z0QYXsvZ97RmAty6OOt63S11ACSS+SyGd9DuNW4kNgt1AkBReKzdcX91W4htTEIa+ufKhUezlNxigFftjeussFI3EaKdpckLg/KIIEDqgzUOQAVArSAo+Gl3bT/Wtw2scJZ9',
            'MERCHANT_PUBKEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCHJxnyHaTm3geRyDhdglYL7n6gIe8bYFg4mgnr8Ra+vJvsuusZQSFZ/yI54/6p1xCw0c/AxP/Cp/l7eWr52jaC7eo8ynnBxByiV+8abDWBT97P1holULCfWxeGFxKv7nxFF3rzq5jeXJGrTIKeBf7ocYwIVZyC1SKi92WcMJ9VAwIDAQAB',
            'YEEPAY_PUBKEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDr6rkXPa9Pl57LCPE3bX0xRb0EqIvtcGXYk79QRXRv7rDDUBIICWa2vsx3CAr8kRWpUElbmxwRXj2DqxsGCy4E/UfiD2wKTR3CB2XfkmkYjMcKJl7AasfqfDN7degepp90g0t/VXwco29FbtmBS1CKP3FgrylkvyqunXQ6CfF9gQIDAQAB',
            //远程日志服务配置
            'REMOTE_LOG_IP' => 'pmlog1.wxlc.org',
            'REMOTE_LOG_PORT' => '55001',
            'ALARM_EMAIL_LIST' => array('wangqunqiang@ucfgroup.com'), // 告警邮件列表
            'ALARM_MOBILE_LIST' => array('18611187809'), // 告警手机号列表
            'PAYMENT_ID' => 3, // 支付方式编号(先锋支付4|易宝支付3)
            'PAYMENT_NAME' => '易宝支付', // 支付方式名称
            'CREATE_ORDER_TIPS' => '易宝支付系统维护中,为您提供先锋支付',
            'QUICK_BANKLIST' => array(
                'ICBC' => '中国工商银行',
                'BOC' => '中国银行',
                'CCB' => '中国建设银行',
                'PSBC' => '中国邮政储蓄银行',
                'ECITIC' => '中信银行',
                'CEB' => '中国光大银行',
                'HX' => '华夏银行',
                'CMBCHINA' => '招商银行',
                'CIB' => '兴业银行',
                'SPDB' => '浦发银行',
                'SZPA' => '平安银行',
                'CMBC' => '中国民生银行',
                'GDB' => '广发银行',
                'BCCB' => '北京银行',
                'ABC' => '中国农业银行',
                'JSBC' => '江苏银行',
                'SHB' => '上海银行',
                'BOCO' => '交通银行',
            ),
            'WXLC_BANKLIST' => array( // 理财的银行短码与银行名称映射配置
                'ICBC' => '中国工商银行',
                'BOC' => '中国银行',
                'CCB' => '中国建设银行',
                'PSBC' => '中国邮政储蓄银行',
                'CNCB' => '中信银行',
                'CEB' => '中国光大银行',
                'HXB' => '华夏银行',
                'CMB' => '招商银行',
                'CIB' => '兴业银行',
                'SPDB' => '浦发银行',
                'PAB' => '平安银行',
                'CMBC' => '中国民生银行',
                'GDB' => '广发银行',
                'BCCB' => '北京银行',
                'ABC' => '中国农业银行',
                'BOS' => '上海银行',
                'BOCOM' => '交通银行',
            ),
        ),
        'bindBankCard' => array( // 绑定银行卡-4.1.1绑卡请求接口
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindcard/request',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'confirmBindBankCard' => array( // 绑定银行卡-4.1.2确定绑卡接口
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindcard/confirm',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'resendSmsBindBankCard' => array( // 绑定银行卡-4.1.3请求短验重发接口
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindcard/resendsms',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'payBindRequest' => array( // 支付接口-发送短验-4.2.1支付请求接口
            'url' => 'https://ok.yeepay.com/payapi/api/tzt/pay/bind/request',
            'callbackUrl' => $http.$notifyDomain.'/payment/yeepayChargeNotify',
            'formUrl' => $http . APP_HOST . '/payment/yeepayStartPayH5', // Form表单的链接地址
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'payValidatecodeSend' => array( // 支付接口-发送短验-4.2.2发送短信验证码接口
            'url' => 'https://ok.yeepay.com/payapi/api/tzt/pay/validatecode/send',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'payConfirmValidatecode' => array( // 支付接口-发送短验-4.2.3确认支付接口
            'url' => 'https://ok.yeepay.com/payapi/api/tzt/pay/confirm/validatecode',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'directBindPay' => array( // 支付接口-不发送短验-4.3支付请求接口
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindpay/direct',
            'callbackUrl' => $http.$notifyDomain.'/payment/yeepayChargeNotify',
            'formUrl' => 'http://openapi.firstp2p.com/payment/yeepayStartPay', // Form表单的链接地址
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'queryOrder' => array( // 支付接口-发送短验-4.4支付接口查询
            'url' => 'https://ok.yeepay.com/payapi/api/query/order',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'bindCardRecord' => array( // 支付接口-新投资通-4.6.1绑卡记录查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindcard/record',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'bindPayRecord' => array( // 支付接口-新投资通-4.6.2充值记录查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindpay/record',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'withdrawRecord' => array( // 支付接口-新投资通-4.6.3提现记录查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/withdraw/record',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'firstPayRecord' => array( // 支付接口-新投资通-4.6.4首次充值记录查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/firstpay/record',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'refundRecord' => array( // 支付接口-新投资通-4.6.5退款记录查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/refund/record',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'bankCardUnbind' => array( // 支付接口-发送短验-4.7解绑卡
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/unbind/request',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'bankCardAuthBindList' => array( // 支付接口-发送短验-4.8查询绑卡信息列表
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindcard/list',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'bankCardCheck' => array( // 支付接口-发送短验-4.9银行卡信息查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bankcard/check',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'drawValidAmount' => array( // 支付接口-发送短验-4.10可提现余额接口
            'url' => 'https://ok.yeepay.com/payapi/api/tzt/drawvalidamount',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'merchantQueryServerPaySingle' => array( // 支付接口-发送短验-5.1交易记录查询
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/bindpay/record',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'merchantQueryServerDirectRefund' => array( // 支付接口-发送短验-5.2退货退款接口
            'url' => 'https://jinrong.yeepay.com/tzt-api/api/refund/request',
            'requestMethod' => 'POST', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantno' => 1,
            ),
        ),
        'merchantQueryServerRefundSingle' => array( // 支付接口-发送短验-5.3退货退款记录查询
            'url' => 'https://ok.yeepay.com/merchant/query_server/refund_single',
            'requestMethod' => 'GET', // HTTP请求方式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'merchantQueryServerPayClearData' => array( // 支付接口-发送短验-5.4获取消费清算对账单记录
            'url' => 'https://ok.yeepay.com/merchant/query_server/pay_clear_data',
            'requestMethod' => 'GET', // HTTP请求方式
            'responseFormat' => 'string', // 接口返回数据格式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
        'merchantQueryServerRefundClearData' => array( // 支付接口-发送短验-5.5获取退款清算对账记录
            'url' => 'https://ok.yeepay.com/merchant/query_server/refund_clear_data',
            'requestMethod' => 'GET', // HTTP请求方式
            'responseFormat' => 'string', // 接口返回数据格式
            'params' => array( // 必填参数
                'merchantaccount' => 1,
            ),
        ),
    ),
    // 海口联合农商银行
    'unitebank' => array(
        //通用配置
        'common' => array(
            // 银行公钥
            'UB_PUBKEY' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyg/3nabRDNb4/q+JmSjsXaHGhh19jPZ0rQKihDFEfdamXCPpGmr4YC+JYzNyIBkDBDN0xgjkcAPRPfvBpZgph8kkpiHMpJnkzJuIdotPPtAw1V2KjUQQUyPBFDYHS8WfA82mq+8BckZHphsnlFb9d4o1HuVKCss+t8+3Zst6EFaYZl0X6P/3Pqqfck86Ke1zbh5ZwvMaRpEOaI5EQO6aTiPwvpD31pYi9Ayk9WeA6RPxFSC0b5mms/JpbAGhH0Srb6NZqDcdZswTP1h6+e4VuP8fC6s6EOPAyJgDpeO5MgdnnTyagLjjTMXqZ1u3t0K9fnh+bchsM/4G7KghfNTuawIDAQAB',
            // 银行私钥
            'UB_PRIKEY' => '',
            // 商户私钥
            'MERCHANT_PRIVKEY' => 'MIIEpQIBAAKCAQEAvl4pYCZu+7FXEHtePfmmToigHZegjyCSFtpPlXLyMRBaK+msgcMtglNZx2yxoBGe5oHSYxzhnyRkNa2e9Nej1Rsf9PdonS7pd+cNbplDuB0MjyvOrc9InZrtGSNYSdq5I6w49dmM2MH/PjW5ci2W8xW7bONJ7KYcB8axZNaXizPBGhIhSgPbYMvXEucoX3ZVeix/+NQC2GANv6t0yNN99gLVpuJvVzvz7R6SRW5YG0L8DN56a/208Jst4gtE0w+DR5fgH/k9GI2Al2BMX+BdK9pB1Z3sbTooA7oSsb4Q22m1ipnifhlc/GM8wElkY6KDqwwQ0ItAKZFEDgBsJLgdrwIDAQABAoIBAB6UA3NlWQhm2QRVvLKZykPtIEMAmxLCeZTgJk5sM0j8Rm+tTj9duY6oktA8vl9m1S5ThhbTic5FSy9wHwtXJALUI5L2tsAgy/GtlHPCfKUzTVQmBkHW/OQMAa+7BLCASKLZRCEBe+VJbBVzDcGwXwHW6M85xyMTH4eEO/Rln9wFAgZgtY40GWPkLuRWJNrjHePwDgd+9shRkI536U+einGizrZFOL50oVHDJPBYb1ahFHKgOwDRPiBKrmFUXKhIUo7+mVo1zFW/C7o1Rh4QBI8HewJFHI3iImweFLuC6ivg4oHZAKsrv/pdyFfvHugt/wSemmpVOsBYyaS4uK0SWvECgYEA7zY/rIx0PkvxnJdxjDIZn/FNIzkcLh3/ld+xVUF0qga588rClRCZeDN1XIRDO9CsTvqKFtcWk9lSRCsBj3NHIh3wFQnv0vtlqtP+q15BSqJ9fhKo4BSDwRMVHG3dYRQP3hnnQk8S6nsy/4i9ll1fzQ9P9KEJazOd1dJGsDZhxNkCgYEAy7pdm44G4Ym8tg5LDFU+utvr1qTMOjOxZyrXknHaK1rpcsV8T5iHsJu2tsa0M31UuP6p80DzHp8uStF0OTaDzjLkggnG0UJm9ECYzKyNYLgZ9gimoCdtOHYdrqhFz3JCad4w8oWKOaU1t8Ouj0aPlDs/n44jheM2bZaxHZ+FQccCgYEAgCf5LxFEiceYFwPP0oNY1Saq4+8J2O87aekhEYLy5NCbuS/s1X3CKvKusrUtbBNc7Scu6hOrxeQNPfYobNkex/lwEWV0df03t7DB5L+njTvGrc+DaCG1gLAfhE6b5xGfeqc4DX9dq//7D4oLwE4gMDU+6dmIuUU7Dz4LnwZTlOkCgYEAjFAKAoXaJWHo7/Z+J7taXfXzwzxzUC6kI2r1V+5EFZIisKJlUKi7454LRG0sVT4fqN30jQ4Ro+h8SJljk7gBJXYVvZ4gKaWzJMyMsIKzSIbjknk40Zr19WocXVuV4R9PsHyQd6gToEox6iPCyPkPEEeSNUD/JEpuBSJBUCa676cCgYEAwZhjOHhdMmNC69qGNGJDHdB98wehGQU2oySBfzVJIAlIlhkd2usiR9xwLMk7xyPwstGiTlB/o3t2xNtWNHfuDgfyX4kN9Ynhx+jcfUpAak4EdXERNNgUMk89rQ+7njaQicU9DiO3UOozj1Wjs2Pr7eh9Q22hOKVB8jouCafGwEE=',
            // 商户公钥
            'MERCHANT_PUBKEY' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvl4pYCZu+7FXEHtePfmmToigHZegjyCSFtpPlXLyMRBaK+msgcMtglNZx2yxoBGe5oHSYxzhnyRkNa2e9Nej1Rsf9PdonS7pd+cNbplDuB0MjyvOrc9InZrtGSNYSdq5I6w49dmM2MH/PjW5ci2W8xW7bONJ7KYcB8axZNaXizPBGhIhSgPbYMvXEucoX3ZVeix/+NQC2GANv6t0yNN99gLVpuJvVzvz7R6SRW5YG0L8DN56a/208Jst4gtE0w+DR5fgH/k9GI2Al2BMX+BdK9pB1Z3sbTooA7oSsb4Q22m1ipnifhlc/GM8wElkY6KDqwwQ0ItAKZFEDgBsJLgdrwIDAQAB',
            //远程日志服务配置
            'REMOTE_LOG_IP' => 'test01.firstp2plocal.com',
            'REMOTE_LOG_PORT' => '55001',
        ),
        // 2.1贷款账户开户
        'CreateLoanAcctPre' => array(
            'desc' => '贷款用户在银行申请开户接口',
            'url' => 'https://dbank.unitedbank.cn/portal/CreateLoanAcctPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => $http.$notifyDomain.'/payment/CreateLoanAccountNotify',
            ),
            'requiredFields' => array(
                'WJnlNo' => '网信注册申请流水',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'AcNo' => '绑定卡号',
                'MobilePhone' => '手机号',
                'BankName' => '开户银行',
                'WXUrl' => '回调地址',
            ),
            'signFields' => array(
                'WJnlNo' => '网信注册申请流水',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'AcNo' => '绑定卡号',
                'MobilePhone' => '手机号',
                'WXUrl' => '回调地址',
            ),
        ), // end CreateLoanAcctPre
        // 2.2贷款账户开户接口
        'CreateNewLoanAcctPre' => array(
            'desc' => '贷款账户开户并发起借款申请',
            'url' => 'https://dbank.unitedbank.cn/portal/CreateNewLoanAcctPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'PMethod' => '10',
                'WXUrl' => $http.$notifyDomain.'/payment/CreateLoanAccountNotify',
                'WXLoanUrl' => $http.$notifyDomain.'/payment/CreateLoanNotify',
                'WXGrantUrl' => $http.$notifyDomain.'/payment/LoanLendNotify',
            ),
            'requiredFields' => array(
                'WJnlNo' => '网信注册申请流水(用户ID)',
                'LWJnlNo' => '网信贷款申请流水号',
                'LAmount' => '贷款申请金额',
                'LTime' => '借款期限',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'BankId' => '银行Id',
                'AcNo' => '绑定卡号',
                'MobilePhone' => '手机号',
                'RegChannelId' => '渠道号',
                'BankName' => '开户银行',
                'WXUrl' => '回调地址',
                'WXLoanUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
            'signFields' => array(
                'WJnlNo' => '网信注册申请流水(用户ID)',
                'LWJnlNo' => '网信贷款申请流水号',
                'LAmount' => '贷款申请金额',
                'LTime' => '借款期限',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'BankId' => '银行Id',
                'AcNo' => '绑定卡号',
                'PMethod' => '还款方式',
                'MobilePhone' => '手机号',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXLoanUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
        ), // end CreateNewLoanAcctPre
        // 2.3贷款申请接口
        'LoanApplyPre' => array(
            'desc' => '贷款申请接口',
            'url' => 'https://dbank.unitedbank.cn/portal/LoanApplyPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'PMethod' => '10',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => $http.$notifyDomain.'/payment/CreateLoanNotify',
                'WXGrantUrl' => $http.$notifyDomain.'/payment/LoanLendNotify',
            ),
            'requiredFields' => array(
                'UserId' => '姓名',
                'LTime' => '借款期限',
                'PMethod' => '还款方式',
                'LAmount' => '借款金额',
                'IdNo' => '证件号',
                'BankId' => '银行Id',
                'MobilePhone' => '手机号',
                'WJnlNo' => '网信申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
            'signFields' => array(
                'UserId' => '姓名',
                'LTime' => '借款期限',
                'PMethod' => '还款方式',
                'LAmount' => '借款金额',
                'IdNo' => '证件号',
                'BankId' => '银行Id',
                'MobilePhone' => '手机号',
                'WJnlNo' => '网信申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
        ), // end LoanApplyPre
        // 2.4还款申请（用户发起)
        'LoanRepayEarlyPre' => array(
            'desc' => '还款申请（用户发起）',
            'url' => 'https://dbank.unitedbank.cn/portal/LoanRepayEarlyPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => $http.$notifyDomain.'/payment/LoanRepayAcceptNotify',
                'WXRepayUrl' => $http.$notifyDomain.'/payment/LoanRepayNotify',
            ),
            'requiredFields' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
            'signFields' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
        ), // end LoanRepayEarlyPre
        // 2.5还款申请（网信POST申请)
        'LoanRepayEarlyWX' => array(
            'desc' => '还款申请（网信POST申请)',
            'url' => 'https://dbank.unitedbank.cn/portal/LoanRepayEarlyWX.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => $http.$notifyDomain.'/payment/LoanRepayAcceptNotify',
                'WXRepayUrl' => $http.$notifyDomain.'/payment/LoanRepayNotify',
            ),
            'requiredFields' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
            'signFields' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'BankId' => '银行Id',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
        ), // end LoanRepayEarlyWX
    ), // end unitebank
);
