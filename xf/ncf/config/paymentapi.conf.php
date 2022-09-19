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
if (isP2PRc()) {
    $notifyDomain = 'prenotify.ncfwx.com';
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
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/register',
            'retry' => true,   //是否重试
            'bizType' => 'q001zc',
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        //注册开户绑卡合并接口
        'register_bindbank' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/register_bindbank',
            'bizType' => 'q001zc',
            'retry' => true,   //是否重试
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        // 企业用户开户接口
        'compregister' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/compregister',
            'params' => array(
                'userId' => 1,
            ),
        ),
        //银行卡四要素验证接口（跳转）
        'authCard' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/r/authCard',
            'redirect' => true,
            'callbackUrl' => $http.$notifyDomain.'/payment/authCardNotify',
            'returnUrl' => $http.$domainName.'/account/editbank',
            'params' => array(
                'userId' => 1,
            )
        ),
        //银行卡四要素验证接口（appH5跳转)
        'h5authCard' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/h5/authCard',
            'redirect' => true,
            'callbackUrl' =>  $http.$notifyDomain.'/payment/authCardNotify',
            'params' => array(
                    'userId' => 1,
            )
        ),
        // 支付h5四要素绑卡页面(跳转)
        'h5AuthBindCard' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/r/authBindCard',
            'redirect' => true,
            'callbackUrl' => $http.$notifyDomain.'/payment/bindCardNotify',
            'params' => array(
                'userId' => 1,
            )
        ),
        //银行卡绑定
        'bindbankcard' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/bindbankcard',
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        //用户绑定银行卡信息查询
        'searchbankcards' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/searchbankcards',
            'params' => array(
                'userId' => 1, //必填项
            ),
        ),
        //解绑银行卡
        'unbindCard' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/u/unbindCard',
            'retry' => true,
            'params' => array(
                'userId' => 1, //必填项
                'cardNo' => 1, //必填项
            ),
        ),
        //手机号码修改接口
        'phoneupdate' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/phoneupdate',
            'bizType' => 'h005sjhmxg',
            'params' => array(
                'userId' => 1,
            ),
        ),
        //银行卡提现接口
        'towithdrawal' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/towithdrawal',
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
            'url' => 'https://cgw.unitedbank.cn/u-trade/trade/b/pretransfer',
            'retry' => true,   //是否重试
            'bizType' => 'q007tx',
        ),
        //转账接口
        'transfer' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/trade/b/transfer',
            'retry' => true,   //是否重试
            'bizType' => 'q007tx',
        ),
        //对账信息批量查询接口
        'searchtrades' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/trade/b/searchtrades',
            'bizType' => 'q007tx',
        ),
        //单笔交易查询接口
        'searchonetrade' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/trade/b/searchonetrade',
            'retry' => true,   //是否重试
            'bizType' => 'q007tx',
        ),
        //用户余额查询接口
        'searchuserbalance' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/searchBalance',
            //'bizType' => 'q007tx',
            'retry' => true,   //是否重试
            'params' => array(
                'userId' => 1,
            ),
        ),
        //批量用户余额查询
        'searchBalances' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/searchBalances',
            'retry' => true,   //是否重试
        ),
        //用户信息查询接口
        'searchuserinfo' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/searchUserInfo',
            //'bizType' => 'q007tx',
            'retry' => true,   //是否重试
            'params' => array(
                'userId' => 1,
            ),
        ),
        //记录投资项目信息 (接口待定)
        'saveprojectinfo' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/trade/b/saveProjectInfo',
            //'bizType' => 'q007tx',
            'params' => array(
                'userId' => 1,
            ),
        ),
        //支付密码修改接口 (跳转)
        'pwdupdate' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/f/pwdupdate',
            'bizType' => 'q003zfmmxg',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        //支付密码找回接口 (跳转)
        'pwdfind' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/f/pwdfind',
            'bizType' => 'q004zfmmzh',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        //支付 pc 绑卡接口(跳转)
        'bindCard' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/r/bindCard',
            'redirect' => true,
            'callbackUrl' => $http.$notifyDomain.'/payment/bindCardNotify',
            'params' => array(
                'userId' => 1,
            ),
        ),

        //账户充值接口 (跳转)
        'torecharge' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/f/torecharge',
            'callbackUrl' => $http.$notifyDomain.'/payment/chargeNotify',
            'bizType' => 'h005sjhmxg',
            'redirect' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // 查询卡bin接口
        'searchcardbin' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/serchcardbin',
            'params' => array(
                'source' => 'p2p_fix_script',
            ),
        ),
        // 第三方平台网信理财投资接口
        'invest' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/trade/deal/invest',
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
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/newcompregister',
            'retry' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // 企业会员修改接口
        'newcompupdate' => array(
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/newcompupdate',
            'retry' => true,
            'params' => array(
                'userId' => 1,
            ),
        ),
        // 个人用户基本资料同步接口
        'modifyuserinfo' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/modifyuserinfo',
            'callbackUrl' => $http.$notifyDomain.'/payment/modifyUserInfoNotify',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 个人用户银行卡修改接口
        'modifybankinfo' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/modifybankinfo',
            'callbackUrl' => $http.$notifyDomain.'/payment/modifyBankInfoNotify',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 个人用户注销接口
        'canceluser' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/canceluser',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 托管银行资金划拨
        'towithdrawaltrustbank' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/towithdrawaltrustbank',
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
            'url' => 'https://cgw.unitedbank.cn/u-trade/superAccount/queryUserInfo',
            'returnUrl' => $http.$domainName.'/account',
            'retry' => true,   //是否重试
            'params' => [
                'userId' => 1,
            ],
        ],
        //用户信息管理接口
        'h5QueryUserInfo' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/superAccount/queryUserInfoH5',
            'returnUrl' => $http.$domainName.'/account',
            'retry' => true,   //是否重试
            'params' => [
                'userId' => 1,
            ],
        ],
        // 静态白名单用户同步验卡方式
        'staticWhitelist' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/authBindCard',
            'retry' => true,
            'params' => [
                'userId' => 1,
            ],
        ],
        // 掌众暗绑接口
        'quickAuthBindCard' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/quickAuthBindCard',
            'params' => [
                'userId' => 1,
            ],
        ],
        // 四要素换卡接口
        'quickAuthChangeCard' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/quickAuthChangeCard',
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
        // 后台四要素认证页面
        'factorAuth' => [
            'url' => 'https://riskconfig.firstpay.com/risk-config-web/cardAuth/CardAuthController/toList',
        ],
        // 用户存管预开户
        'moveUser' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/moveUsers',
            'retry' => true,
        ],
        // 取消网信白名单
        'cancelAuth' => [
            'url' => 'https://cgw.unitedbank.cn/u-trade/member/b/cancelAuth',
            'retry' => true,
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
                'POST' => '中国邮政储蓄银行',
                'ECITIC' => '中信银行',
                'CEB' => '中国光大银行',
                'HXB' => '华夏银行',
                'CMBCHINA' => '招商银行',
                'CIB' => '兴业银行',
                'SPDB' => '浦发银行',
                'PINGAN' => '平安银行',
                'GDB' => '广发银行',
                'CMBC' => '中国民生银行',
                'ABC' => '中国农业银行',
                'BOCO' => '交通银行',
                'BCCB' => '北京银行',
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
                'GDB' => '广发银行',
                'CMBC' => '中国民生银行',
                'ABC' => '中国农业银行',
                'BOCOM' => '交通银行',
                'BCCB' => '北京银行',
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
    // 存管
    'supervision' => array(
        'common' => array(
            // 支付网关地址
            'GATEWAY_URL' => 'https://cgpt.unitedbank.cn/gateway',
            //签名字段计算Salt
            'SIGNATURE_SALT' => '10f770777c951ed04ed913eb3048ff51',
            // 签名方法
            'METHOD' => 'RSA',
            //平台商ID
            'MERCHANT_ID' => 'M20002646207',
            // 请求来源
            'SOURCE' => 1,
            // 服务版本
            'VERSION' => '1.0.0',
            // 平台公钥
            'SUPERVISION_PUBKEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDMJ1xe6cNaWbDKD8tbE6sk/c0zUK0p5da3lyK15QaaTEl9OgbNKnVHwUtuKlK5onJ/CSc0vO5+7BYPzEjpMEKskoxBriGGKdeQ0ZhUrOfVUTUyJ6qWyv9u8KzRoRIlG4VWtrYJv1LFsd1gJa1JoJre6lB5WRXAyaCxyBZe5Ia+BQIDAQAB',
            // 平台私钥
            'SUPERVISION_PRIVKEY' => '',
            // 商户私钥
            'MERCHANT_PRIVKEY' => 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMtn4r6BALAtLsKcl//F7C4zICSVOU9gGMRBjWaHfl8M59bse7dSG8ovoCCd302Yi3rIet5r0Ek57CS+L3Sf7N9dAmBmEFHDEOUqJ+S28IcONZheTf/ZpdSgVNTiuzu+V51CnckzZM6nquiwpmPOAul+N7i+MLB2SAJ7LcryMEmTAgMBAAECgYBiE4VRNgKO8DpLvBXOTjDDVgN5oDox+7P1bWYwucRFMIPZLc25Zu3fX3dmQrkZQSR/34rfFD0qEbO7Q7i+Ex6y92Il21oAUdYjgZ1AMLtBcgcmS66lUmKAVk0se4+iPPV0qmDMvB78ZqBUfvc/d8Oq4PTBaQ32kqafMTYPXaxKAQJBAP4Ko/hPJZ/ukXzyNQ2Tq8aWAq4KrkkvOAe0TMkxzqxvLotjsF8jWCDabs3qxQsvDwQFkbPfWWFrdo6V9g+A8DECQQDM+VBYf31xX1WwDiMraKJmA7a4AAmd4WWQ3roP+fRKnoko3BCO8YxB4gOtflKNZgstVYUIYOXJ1Yg7OuOhx8kDAkEAgxXIgEOO3ZTDu95bI9NUKteTMG1Qe3EDD26oxJSP2YhRUxmwk5bwTZuI23ZOELRKoj+hbFhOjade+LpGyr80sQJAVQrvyfgDXOF4FNaYwu7jyj2qNsdVNhsJX8T5H53OPJNwRvKfMB+J2N/kNLxflekLpCCCVIqXbFla73Asd7gDbwJBAKx6AJ0qd3looPqqOTbidfEZL2hvvkdtrl36VSmGhpKSuGGHcttv1xT8A4EIo0i+uamdk0a3amrieYklC1/RaTE=',
            // 商户公钥
            'MERCHANT_PUBKEY' => '',
            //远程日志服务配置
            'REMOTE_LOG_IP' => 'test01.firstp2plocal.com',
            'REMOTE_LOG_PORT' => '55001',
            'PAYMENT_ID' => 5, // 支付方式编号(先锋支付4|易宝支付3,海口银行存管账户5)
            'PAYMENT_NAME' => '海口银行存管账户', // 支付方式名称
            'CREATE_ORDER_TIPS' => '海口银行存管系统维护中',
        ),
        // 账户类型修改
        'biztypeModify' => array(
            'service' => 'p2p.member.biztype.modify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '账户类型',
            ),
        ),
        // 海口银行快速注册页面
        'memberQuickRegister' => array(
            'service' => 'h5.p2p.member.account.createindividualsimple',
            'returnUrl' => '',//$http.$domainName.'/supervision/registerReturn?pf=h5',
            'callbackUrl' => $http.$notifyDomain.'/supervision/registerNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // standardRegister
        'memberStandardRegister' => array(
            'service' => 'web.member.account.create',
            'returnUrl' => $http.'www.firstp2p.cn/',
            'callbackUrl' => $http.$notifyDomain.'/supervision/registerStandardNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // h5 standardRegister
        'h5MemberStandardRegister' => array(
            'service' => 'h5.p2p.member.account.create',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/registerStandardNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // 个人用户注册 - 页面跳转
        'memberRegister' => array(
            'service' => 'web.p2p.member.account.createindividual',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/registerReturn',
            'callbackUrl' => $http.$notifyDomain.'/supervision/registerNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // h5个人用户注册 - 页面跳转
        'h5MemberRegister' => array(
            'service' => 'h5.p2p.member.account.createindividual',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/registerReturn?pf=h5',
            'callbackUrl' => $http.$notifyDomain.'/supervision/registerNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),

        // 个人用户开户接口
        'memberRegisterApi' => array(
            'service' => 'p2p.member.account.createindividual',
            'callbackUrl' => $http.$notifyDomain.'/supervision/registerNotify',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),

        // 港澳台开户页面
        'foreignMemberRegister' => array(
            'service' => 'web.p2p.member.account.createother',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/foreignMemberRegisterReturn',
            'callbackUrl' => $http.$notifyDomain.'/supervision/foreignMemberRegisterNotify',
            'requiredFields' => array(
                'userId' => '用户Id',
                'bizType' => '用户类型',
                'realName' => '真实姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'regionCode' => '国家区域码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'GAT', // 证件类型(港澳台身份证:GAT 军官证:MILITARY 护照:PASS_PORT)
            ),
        ),
        // H5港澳台开户页面
        'h5ForeignMemberRegister' => array(
            'service' => 'h5.p2p.member.account.createother',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/foreignMemberRegisterReturn?pf=h5',
            'callbackUrl' => $http.$notifyDomain.'/supervision/foreignMemberRegisterNotify',
            'requiredFields' => array(
                'userId' => '用户Id',
                'bizType' => '用户类型',
                'realName' => '真实姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'regionCode' => '国家区域码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'GAT', // 证件类型(港澳台身份证:GAT 军官证:MILITARY 护照:PASS_PORT)
            ),
        ),
        // 企业用户开户 - 页面跳转
        'enterpriseRegister' => array(
            'service' => 'web.p2p.member.account.createenterprise',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/enterpriseRegisterReturn',
            'callbackUrl' => $http.$notifyDomain.'/supervision/enterpriseRegisterNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '业务类型',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资户|02-借款户|03-担保户|04-咨询户|05-平台户|06-借贷混合户|08-平台营销户|10-平台收费户|11-代偿户|12-第三方营销账户|13-垫资户)
            ),
        ),
        // H5企业用户开户 - 页面跳转
        'h5EnterpriseRegister' => array(
            'service' => 'h5.p2p.member.account.createenterprise',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/enterpriseRegisterReturn',
            'callbackUrl' => $http.$notifyDomain.'/supervision/enterpriseRegisterNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '业务类型',
            ),
            'default' => array(
                'bizType' => '01', // 业务类型(01-投资户|02-借款户|03-担保户|04-咨询户|05-平台户|06-借贷混合户|08-平台营销户|10-平台收费户|11-代偿户|12-第三方营销账户|13-垫资户)
            ),
        ),
        // 用户余额查询 - 接口
        'memberBalanceSearch' => array(
            'service' => 'p2p.trade.balance.search',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 会员资料查询接口 - 接口
        'memberSearch' => array(
            'service' => 'p2p.member.info.search',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 会员绑卡查询 - 接口
        'memberCardSearch' => array(
            'service' => 'p2p.member.card.search',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // web用户授权页面 - 页面
        'memberAuthorizationCreate' => array(
            'service' => 'web.p2p.member.authorization.create',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/memberAuthorizationCreateNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'grantList' => '用户授权列表',
            )
        ),
        // h5用户授权页面 - 页面
        'h5MemberAuthorizationCreate' => array(
            'service' => 'h5.p2p.member.authorization.create',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/memberAuthorizationCreateNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'grantList' => '用户授权列表',
            )
        ),
        // 取消用户授权 - 接口
        'memberAuthorizationCancel' => array(
            'service' => 'p2p.member.authorization.cancel',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'grantList' => '用户授权列表',
            )
        ),
        // 取消用户授权 - Web页面
        'webMemberAuthorizationCancel' => array(
            'service' => 'web.p2p.member.authorization.cancel',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // 取消用户授权 - H5页面
        'h5MemberAuthorizationCancel' => array(
            'service' => 'h5.p2p.member.authorization.cancel',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // 用户授权查询 - 接口
        'memberAuthorizationSearch' => array(
            'service' => 'p2p.member.authorization.search',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // 存管账户绑卡 - 页面跳转
        'memberBindcard' => array(
            'service' => 'web.p2p.member.card.create',
            'returnUrl' => $http.$domainName.'/account/',
            'callbackUrl' => $http.$notifyDomain.'/supervision/memberBindcardNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
            ),
        ),
        // 会员换卡接口
        // 会员修改手机号接口
        // pc充值收银台 - 页面跳转
        'webCharge' => array(
            'service' => 'web.p2p.trade.account.recharge',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/chargeResult',
            'callbackUrl' => $http.$notifyDomain.'/supervision/chargeNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // APP H5充值 -- 页面跳转
        'h5Charge' => array(
            'service' => 'h5.p2p.trade.account.quickrecharge',
            'returnUrl' => '',//$http.'www.wangxinlicai.com/supervision/h5ChargeResult',
            'callbackUrl' => $http.$notifyDomain.'/supervision/chargeNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // 自动扣款充值代扣 - 接口
        'autoRecharge' => array(
            'service' => 'p2p.trade.account.autoRecharge',
            'callbackUrl' => $http.$notifyDomain.'/supervision/chargeNotify',
            'retry' => true,
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额,单位分',
            ),
        ),
        // 标的报备 - 接口
        'dealCreate' => array(
            'service' => 'p2p.trade.bid.create',
            'callbackUrl' => $http.$notifyDomain.'/supervision/dealCreateNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'name' => '标的名称',
                'amount' => '标的金额，单位（分）',
                'userId' => '借款人P2P用户ID',
                'bidRate' => '标的年利率',
                'bidType' => '标的类型',
                'cycle' => '借款周期',
                'repaymentType' => '还款方式',
                'borrPurpose' => '借款用途',
                'productType' => '标的产品类型',
                'borrName' => '借款方名称',
                'borrUserType' => '借款人用户类型',
                'borrCertType' => '借款方证件类型',
                'borrCertNo' => '借款方证件号码',
            ),
        ),
        // 标的更新 - 接口
        'dealUpdate' => array(
            'service' => 'p2p.trade.bid.update',
            'callbackUrl' => $http.$notifyDomain.'/supervision/dealUpdateNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'name' => '标的名称',
                'amount' => '标的金额',
                'userId' => '借款人P2P用户ID',
                'bidRate' => '标的年利率',
                'bidType' => '标的类型',
                'beginTime' => '标的开始时间',
                'cycle' => '借款期限',
                'repaymentType' => '还款方式',
                'borrPurpose' => '借款用途',
            ),
        ),
        // 标的查询 - 接口
        'dealSearch' => array(
            'service' => 'p2p.trade.bid.search',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
            ),
        ),
        // 标的投资验密 -  页面
        'investCreateSecret' => array(
            'service' => 'web.p2p.trade.invest.create',
            //'returnUrl' => $http.$domainName.'/',
            'callbackUrl' => $http.$notifyDomain.'/supervision/investCreateNotify',
            'requiredFields' => array(
                'orderId' => '外部流水号',
                'userId' => '用户ID',
                'totalAmount' => '投资总金额',
                'accAmount' => '使用账户金额',
                'bidId' => '标的Id',
            ),
        ),
        // 标的投资验密 -  h5页面
        'h5InvestCreateSecret' => array(
            'service' => 'h5.p2p.trade.invest.create',
            'returnUrl' => $http.$domainName.'/',
            'callbackUrl' => $http.$notifyDomain.'/supervision/investCreateNotify',
            'requiredFields' => array(
                'orderId' => '外部流水号',
                'userId' => '用户ID',
                'totalAmount' => '投资总金额',
                'accAmount' => '使用账户金额',
                'bidId' => '标的Id',
            ),
            'default' => array(
                'expireTime' => '5', // 页面失效时间
            ),
        ),
        // 标的投资无密 - 接口
        'investCreate' => array(
            'service' => 'p2p.trade.invest.create',
            'callbackUrl' => $http.$notifyDomain.'/supervision/InvestCreateNotifyLogOnly',
            'retry' => true,
            'requiredFields' => array(
                'orderId' => '外部流水号',
                'userId' => '用户ID',
                'totalAmount' => '投资总金额',
                'accAmount' => '使用账户金额',
                'bidId' => '标的Id',
            ),
            'default' => array(
                'expireTime' => '5', // 页面失效时间
            ),
        ),
        // 投资取消 - 接口
        'investCancel' => array(
            'service' => 'p2p.trade.invest.cancel',
            'retry' => true,
            'requiredFields' => array(
                'origOrderId' => '原投资订单号',
                'rpDirect' => '红包流向',
            ),
        ),
        // 放款接口
        'dealGrant' => array(
            'service' => 'p2p.trade.bid.grant',
            'callbackUrl' => $http.$notifyDomain.'/supervision/dealGrantNotify',
            'retry' => true,
            'requiredFields' => array(
                'orderId' => '订单号',
                'bidId' => '标的Id',
                'userId' => '借款人P2P用户ID',
                'totalNum' => '放款总笔数',
                'totalAmount' => '放款总金额',
                'grantAmount' => '放款人实际收款金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // 还款接口
        'dealRepay' => array(
            'service' => 'p2p.trade.bid.repay',
            'callbackUrl' => $http.$notifyDomain.'/supervision/orderSplitNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'orderId' => '外部订单号',
                'payUserId' => '还款人P2P用户ID',
                'totalNum' => '该批次总条数',
                'totalAmount' => '该批次总金额',
                'currency' => '币种(默认为CNY)',
                'repayOrderList' => '还款订单集合',
            ),
            'orderSplit' => array( // 订单拆分配置
                'count' => 500, // 单笔请求笔数
                'orderIdField' => 'orderId', // 主订单的[交易流水号]字段
                'listField' => 'repayOrderList', // 需要拆分的[列表]字段
                'userIdField' => 'payUserId', // 主订单的[用户ID]字段
                'dealIdField' => 'bidId', // 主订单的[标的ID]字段
                'subAmountField' => 'amount', // 子订单里面的[金额]字段
                'totalNumField' => 'totalNum', // 拆分后需要替换的字段-该批次总条数
                'totalAmountField' => 'totalAmount', // 拆分后需要替换的字段-该批次总金额
            ),
        ),
        // 标的流标 -接口
        'dealCancel' => array(
            'service' => 'p2p.trade.bid.cancel',
            'callbackUrl' => $http.$notifyDomain.'/supervision/dealCancelNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的ID',
                'rpDirect' => '红包流向',//01-返还到红包账户 02-留在投资人账户
            ),
        ),
        //  批量转账  - 接口
        'batchTransfer' => array(
            'service' => 'p2p.trade.batchtransfer.create',
            'callbackUrl' => $http.$notifyDomain.'/supervision/batchTransferNotify',
            'retry' => true,
            'requiredFields' => array(
                'orderId' => '转账批次单号',
                'subOrderList' => '明细列表',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // 单笔订单查询接口
        'orderSearch' => array(
            'service' => 'p2p.trade.order.search',
            'requiredFields' => array(
                'orderId' => '外部订单号',
            ),
        ),
        // 订单批量查询接口
        'batchSearch' => array(
            'service' => 'p2p.trade.order.batchsearch',
            'requiredFields' => array(
                'orderIds' => '外部订单号',
            ),
        ),
        // 存管行资金记录 - 页面跳转
        'memberInfo' => array(
            'service' => 'web.p2p.member.info.manage',
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
            ),
        ),
        // h5存管行资金记录 - 页面跳转
        'h5MemberInfo' => array(
            'service' => 'h5.p2p.member.info.manage',
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
            ),
        ),
        // 提现
        'withdraw' => array(
            'service' => 'p2p.trade.account.withdraw',
            'callbackUrl' => $http.$notifyDomain.'/supervision/withdrawNotify',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型',
                'efficType' => '提现时效类型',
                'amount' => '提现金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // 提现-掌众专用
        'bankpayupWithdraw' => array(
            'service' => 'p2p.trade.bankpayup.withdraw',
            'callbackUrl' => $http.$notifyDomain.'/supervision/withdrawNotify',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'amount' => '提现总金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // 免密提现至超级账户-接口
        'accountSuperWithdraw' => array(
            'service' => 'p2p.trade.account.superwithdraw',
            'callbackUrl' => $http.$notifyDomain.'/supervision/superWithdrawNotify',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
                'amount' => '提现金额',
                'orderId' => '订单Id',
                'superUserId' => '超级账户用户ID',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
        ),
        // 提现至银信通电子账户-接口
        'bidElecWithdraw' => array(
            'service' => 'p2p.trade.bid.elecwithdraw',
            'domain' => $http.$notifyDomain,
            'callbackUrl' => $http.$notifyDomain.'/supervision/bidElecwithdrawNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'totalAmount' => '总金额,单位：分',
                'repayAmount' => '还款金额,单位：分，总金额-还款金额=解冻金额',
            ),
            'default' => array(
                'currency' => 'CNY',// 币种,默认为CNY
            ),
        ),
        // 速贷提现到银信通电子账户接口
        'creditLoanWithdraw' => array(
            'service' => 'p2p.trade.bid.elecwithdraw',
            'domain' => $http.$notifyDomain,
            'callbackUrl' => $http.$notifyDomain.'/creditloan/SupervisionWithdrawNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'totalAmount' => '总金额,单位：分',
                'repayAmount' => '还款金额,单位：分，总金额-还款金额=解冻金额',
            ),
            'default' => array(
                'currency' => 'CNY',// 币种,默认为CNY
            ),
        ),

        // web/pc验密提现至银行卡-页面
        'secretWithdraw' => array(
            'service' => 'web.p2p.trade.account.withdraw',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/withdrawNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型(NW:普通提现,FW:放款提现)',
                'efficType' => '提现时效类型(T1:T+1提现,D0:D+0提现)',
                'amount' => '金额,单位（分）',
            ),
            'default' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'expireTime' => '5', //页面失效时间
            ),
        ),
        // H5验密提现至银行卡-页面
        'h5SecretWithdraw' => array(
            'service' => 'h5.p2p.trade.account.withdraw',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/withdrawNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型(NW:普通提现,FW:放款提现)',
                'efficType' => '提现时效类型(T1:T+1提现,D0:D+0提现)',
                'amount' => '金额,单位（分）',
            ),
            'default' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'expireTime' => '5', //页面失效时间
            ),
        ),
        // H5确认投资-页面
        'h5InvestConfirm' => array(
            'service' => 'h5.p2p.trade.invest.confirm',
            'requiredFields' => array(
                'reqId' => '请求ID',
                'password' => '密码',
                'type' => '类型',
            ),
        ),
        // 账户注销 -- 接口
        'memberCancel' => array(
            'service' => 'p2p.member.account.cancel',
            'retry' => true,   //是否重试
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 修改手机号 -- 接口
        'memberPhoneUpdate' => array(
            'service' => 'p2p.member.phone.update',
            'retry' => true,   //是否重试
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'phone' => '新注册手机号',
            ),
        ),
        // 绑定银行卡 - 接口
        'memberCardBind' => array(
            'service' => 'p2p.member.card.bind',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bankCardNo' => '银行卡号',
                'bankName' => '银行名称',
                'cardType' => '银行卡类型',
                'bankCode' => '开户行ID',
                'cardFlag' => '银行卡标志',
                'cardCertType' => '认证类型',
            ),
        ),
        // 解绑银行卡 - 接口
        'memberCardUnbind' => array(
            'service' => 'p2p.member.card.unbind',
            'retry' => true,   //是否重试
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bankCardNo' => '银行卡号',
            ),
        ),
        // 修改/更换银行卡 - 接口
        'memberCardUpdate' => array(
            'service' => 'p2p.member.card.update',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'bankCardNo' => '银行卡号',
                'bankName' => '银行名称',
                'cardFlag' => '银行卡标志',
                'bankCode' => '银行编码',
            ),
        ),
        // H5修改/更换银行卡 - 页面
        'h5MemberCardChange' => array(
            'service' => 'h5.p2p.member.card.change',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'returnUrl' => '返回商户的地址',
            ),
        ),
        // 个人及港澳台信息修改 -- 接口
        'memberInfoModify' => array(
            'service' => 'p2p.member.info.modify',
            'retry' => true,   //是否重试
            'callbackUrl' => $http.$notifyDomain.'/supervision/memberInfoModifyNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'orderId' => '外部单号',
            ),
        ),
        // 企业会员修改 - 接口
        'enterpriseUpdateApi' => array(
            'service' => 'p2p.member.account.updateenterprise',
            'callbackUrl' => $http.$notifyDomain.'/supervision/enterpriseUpdateNotify',
            'retry' => true,
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // pc企业会员修改 - 页面
        'enterpriseUpdate' => array(
            'service' => 'web.p2p.member.account.updateenterprise',
            'callbackUrl' => $http.$notifyDomain.'/supervision/enterpriseUpdateNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 从超级账户充值到网贷账户 - 接口
        'superRecharge' => array(
            'service' => 'p2p.trade.account.superrecharge',
            'callbackUrl' => $http.$notifyDomain.'/supervision/superRechargeNotify',
            'retry' => true,
            'requiredFields' => array(
                'orderId' => '外部单号',
                'userId' => 'P2P用户ID',
                'amount' => '金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            )
        ),
        // PC验密提现至超级账户 - 页面
        'superWithdrawSecret' => array(
            'service' => 'web.p2p.trade.account.superwithdraw',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/superWithdrawNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'amount' => '金额',
                'orderId' => '外部单号',
                'superUserId' => '超级账户用户ID',
            ),
            'default' => array(
                'currency' => 'CNY',
            )
        ),
        // H5验密提现至超级账户 - 页面
        'h5SuperWithdrawSecret' => array(
            'service' => 'h5.p2p.trade.account.superwithdraw',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/superWithdrawNotify',
            'requiredFields' => array(
                'userId' => 'P2P用户ID',
                'amount' => '金额',
                'orderId' => '外部单号',
                'superUserId' => '超级账户用户ID',
            ),
            'default' => array(
                'currency' => 'CNY',
            )
        ),
        // 收费接口
        'gainFees' => array(
            'service' => 'p2p.trade.account.gainfees',
            'callbackUrl' => $http.$notifyDomain.'/supervision/gainFeesNotify',
            'retry' => true,
            'requiredFields' => array(
                'orderId' => '外部单号',
                'bidId' => '标的Id',
                'payUserId' => '收费付款方P2P用户Id',
                'totalNum' => '该批次总条数',
                'totalAmount' => '该批次总金额',
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
            ),
            'default' => array(
                'currency' => 'CNY',
            )
        ),
        // 代偿 - 接口
        'dealReplaceRepay' => array(
            'service' => 'p2p.trade.bid.replacerepay',
            'callbackUrl' => $http.$notifyDomain.'/supervision/orderSplitNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'orderId' => '外部单号',
                'payUserId' => '代偿人P2P用户ID',
                'totalNum' => '该批次总条数',
                'totalAmount' => '该批次总金额',
                'originalPayUserId' => '原始借款人ID',
                'bizType' => '代偿方式', // D：直接代偿 I:代垫，间接代偿 默认D
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
            'orderSplit' => array( // 订单拆分配置
                'count' => 500, // 单笔请求笔数
                'orderIdField' => 'orderId', // 主订单的[交易流水号]字段
                'listField' => 'repayOrderList', // 需要拆分的[列表]字段
                'userIdField' => 'payUserId', // 主订单的[用户ID]字段
                'dealIdField' => 'bidId', // 主订单的[标的ID]字段
                'subAmountField' => 'amount', // 子订单里面的[金额]字段
                'totalNumField' => 'totalNum', // 拆分后需要替换的字段-该批次总条数
                'totalAmountField' => 'totalAmount', // 拆分后需要替换的字段-该批次总金额
            ),
        ),
        // 还代偿款 - 接口
        'dealReturnRepay' => array(
            'service' => 'p2p.trade.bid.returnrepay',
            'callbackUrl' => $http.$notifyDomain.'/supervision/returnRepayNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'orderId' => '外部单号',
                'payUserId' => '还代偿人P2P用户Id',
                'totalAmount' => '该批次总金额',
                'totalNum' => '该批次总条数',
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
            ),
            'default' => array(
                'currency' => 'CNY',
            )
        ),
        // 受托提现
        'entrustedWithdraw' => array(
            'service' => 'p2p.trade.bid.entrustedwithdraw',
            'callbackUrl' => $http.$notifyDomain.'/supervision/entrustedWithdrawNotify',
            'retry' => true,
            'requiredFields' => array(
                'grandOrderId' => '原放款单号',
                'orderId' => '外部订单号',
                'bizType' => '业务类型',
                'efficType' => '时效类型',
                'amount' => '批次总金额',
            ),
            'default' => array(
                'currency' => 'CNY',
            )
        ),
        // 批次订单查询
        'batchOrderSearch' => array(
            'service' => 'p2p.trade.batchorder.search',
            'requiredFields' => array(
                'batchId' => '批次单号',
                'orderType' => '订单类型', //5000 = 放款、7000 = 还款、3100 = 流标、8000 = 返利红包收费
            ),
        ),
        // 智多鑫存管相关接口
        // 1.1.1预约冻结（PC端WEB）-页面
        'webBookfreezeCreate' => array(
            'service' => 'web.p2p.trade.bookfreeze.create',
            //'returnUrl' => $http.$domainName.'/supervision/bookfreezeCreateReturn',
            'callbackUrl' => $http.$notifyDomain.'/supervision/bookfreezeCreateNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'freezeType' => '冻结类型(01-预约投资)',
                'freezeSumAmount' => '预约冻结总金额，单位（分）',
                'freezeAccountAmount' => '预约投资冻结使用账户金额，单位（分）',
            ),
            'default' => array(
                'freezeType' => '01',// 冻结类型(01-预约投资)
                'expireTime' => '5', // 页面失效时间

            ),
        ),
        // 1.1.2预约冻结（手机端H5）-页面
        'h5BookfreezeCreate' => array(
            'service' => 'h5.p2p.trade.bookfreeze.create',
            //'returnUrl' => $http.$domainName.'/supervision/bookfreezeCreateReturn',
            'callbackUrl' => $http.$notifyDomain.'/supervision/bookfreezeCreateNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'freezeType' => '冻结类型(01-预约投资)',
                'freezeSumAmount' => '预约冻结总金额，单位（分）',
                'freezeAccountAmount' => '预约投资冻结使用账户金额，单位（分）',
            ),
            'default' => array(
                'freezeType' => '01',// 冻结类型(01-预约投资)
                'expireTime' => '5', // 页面失效时间
            ),
        ),
        // 1.1.3预约冻结(API)-接口
        'bookfreezeCreate' => array(
            'service' => 'p2p.trade.bookfreeze.create',
            'callbackUrl' => $http.$notifyDomain.'/supervision/bookfreezeCreateNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'freezeType' => '冻结类型(01-预约投资)',
                'freezeSumAmount' => '预约冻结总金额，单位（分）',
                'freezeAccountAmount' => '预约投资冻结使用账户金额，单位（分）',
            ),
            'default' => array(
                'freezeType' => '01',// 冻结类型(01-预约投资)
            ),
        ),
        // 1.1.4取消预约冻结(API)-接口
        'bookfreezeCancel' => array(
            'service' => 'p2p.trade.bookfreeze.cancel',
            'callbackUrl' => $http.$notifyDomain.'/supervision/bookfreezeCancelNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'unFreezeType' => '冻结类型(01-预约投资)',
                'amount' => '解冻总金额，包含平台收费金额，单位（分）',
            ),
            'default' => array(
                'unFreezeType' => '01',// 冻结类型(01-预约投资)
            ),
        ),
        // 1.1.5预约批量投资（API）-接口
        'bookInvestBatchCreate' => array(
            'service' => 'p2p.trade.bookInvestBatch.create',
            'callbackUrl' => $http.$notifyDomain.'/supervision/bookInvestBatchCreateNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'currency' => '币种，默认为CNY,以下为可选值,CNY (人民币)',
                'totalAmount' => '批量投资总金额，单位（分）',
                'subInvestOrderList' => '批量投资子单集合',
            ),
            'default' => array(
                'currency' => 'CNY',// 币种，默认为CNY,以下为可选值,CNY (人民币)
            ),
        ),
        // 1.1.6取消投资（API）-接口
        'bookInvestCancel' => array(
            'service' => 'p2p.trade.bookInvest.cancel',
            'requiredFields' => array(
                'origOrderId' => '原投资单号',
            ),
        ),
        // 1.1.7批量债权转让投资(API)-接口
        'bookCreditBatch' => array(
            'service' => 'p2p.trade.bookcredit.batch',
            'callbackUrl' => $http.$notifyDomain.'/supervision/bookCreditBatchNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'currency' => '币种，默认为CNY,以下为可选值,CNY (人民币)',
                'totalAmount' => '投资总金额，单位（分）',
                'subInvestOrderList' => '批量投资债转集合',
            ),
        ),
        // 1.1.8取消债权转让(API)-接口
        'bookCreditCancel' => array(
            'service' => 'p2p.trade.bookcredit.cancel',
            'requiredFields' => array(
                'origOrderId' => '债权转让订单号',
            ),
        ),
        // 1.1.9批量标的债权转让(API)-接口
        'creditAssignmentBatchGrant' => array(
            'service' => 'p2p.trade.creditAssignment.batchgrant',
            'callbackUrl' => $http.$notifyDomain.'/supervision/creditAssignBatchGrantNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'totalAmount' => '转让本金总金额 单位分',
                'dealTotalAmount' => '成交总金额 单位分',
                'totalNum' => '该批次总条数',
                'currency' => '币种，默认为CNY,以下为可选值,CNY (人民币)',
                'creditOrderList' => '转让列表',
            ),
            'default' => array(
                'currency' => 'CNY',// 币种，默认为CNY,以下为可选值,CNY (人民币)
            ),
        ),
        // 1.1.10取消债转投资(API)-接口
        'creditAssignmentCancel' => array(
            'service' => 'p2p.trade.creditAssignment.cancel',
            'requiredFields' => array(
                'origOrderId' => '债权转让订单号',
            ),
        ),
        // 批量余额查询
        'memberBatchBalanceSearch' => array(
            'service' => 'p2p.batch.balance.search',
            'requiredFields' => array(
                'userIds' => '用户Id',
            ),
        ),
        // 初始化用户授权
        'memberInitAuth' => array(
            'service' => 'p2p.member.account.setauth',
            'requiredFields' => array(
                'userId' => '用户Id',
            ),
        ),
        // web超级账户余额划转到网贷账户
       'superRechargeSecret' => array(
            'service' => 'web.p2p.trade.account.superrecharge',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/superRechargeSecretNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => '充值用户网贷账户ID',
                'amount' => '金额 单位分',
            ),
            'default' => array(
                'currency' => 'CNY',
                'expireTime' => '5', // 页面失效时间
            ),
       ),
       // h5超级账户余额划转到网贷账户
       'h5SuperRechargeSecret' => array(
            'service' => 'h5.p2p.trade.account.superrecharge',
            'returnUrl' => '',
            'callbackUrl' => $http.$notifyDomain.'/supervision/superRechargeSecretNotify',
            'requiredFields' => array(
                'orderId' => '外部订单号',
                'userId' => '充值用户网贷账户ID',
                'amount' => '金额 单位分',
            ),
            'default' => array(
                'currency' => 'CNY',
                'expireTime' => '5', // 页面失效时间
            ),
       ),
        // 标的信息迁移
        'dealImport' => array(
            'service' => 'p2p.trade.bid.import',
            'requiredFields' => array(
                'orderId' => '迁移单号',
                'bidId' => '标的ID',
                'name' => '标的名称',
                'amount' => '标的金额',
                'userId' => '借款人ID',
                'bidRate' => '标的年利率',
                'bidType' => '标的类型', // 01 信用 02 抵押 03 债权转让 04-99 其他
                'cycle' => '借款周期', // 单位:天
                'repaymentType' => '还款方式', // 01 一次性还本付息 02 等额本金 03 等额本息 04 按期付息到期还本 99其他
                //'borrPurpose' => '借款用途',
                'productType' => '标的产品类型', // 01房贷类 02车贷类 03 收益权转让类 04 信用贷款类 05 股票配资类 06 银行承兑汇票 07 产业承兑汇票 08 消费贷款类 09 供应链类 99 其他
                'borrName' => '借款方名称',
                'borrUserType' => '借款人用户类型', // 1 个人  2企业
                'borrCertType' => '借款方证件类型', // IDC 身份证 GAT 港澳台居民来往内地通行证 MILITARY 军官证 PASS_PORT 护照 BLC 营业执照 USCC 统一社会信用代码
                'borrCertNo' => '借款方证件号码', // 借款企业营业执照编号（借款方类型为企业时）
            ),
        ),
        // 投资单信息迁移
        'dealOrderImport' => array(
            'service' => 'p2p.trade.investOrder.import',
            'requiredFields' => array(
                'userId' => '投资人ID',
                'orderId' => '原投资订单单号',
                'bidId' => '标的ID',
                'amount' => '原投资总金额，单位分',
                'orgAmount' => '待还本金金额,单位分',
            ),
        ),
        // 债权信息迁移
        'dealCreditImport' => array(
            'service' => 'p2p.trade.credit.import',
            'requiredFields' => array(
                'userId' => '投资人ID',
                'bidId' => '标的ID',
                'sumAmount' => '投资债权总本金',
                'leftAmount' => '待还本金',
            ),
        ),
        // 账户流水查询
        'accountLogPage' => array(
            'service' => 'web.p2p.trade.accountlog.search',
            'requiredFields' => array(
                'userId' => '用户id',
            ),
        ),
        // 多银行卡充值
        'multiCardRecharge' => array(
            'service' => 'p2p.trade.multiCard.recharge',
            'callbackUrl' => $http.$notifyDomain.'/supervision/multicardChargeNotify',
            'requiredFields' => array(
                'orderId'=> '交易流水号',
                'userId' => '用户id',
                'amount' => '充值金额,单位分',
                'realName' => '真实姓名',
                'certNo' => '证件号',
                'bankCardNo' => '银行卡号',
            ),
        ),
        // 代充值还款
        'dealReplaceRechargeRepay' => array(
            'service' => 'p2p.trade.bid.replacerechargerepay',
            'callbackUrl' => $http.$notifyDomain.'/supervision/orderSplitNotify',
            'retry' => true,
            'requiredFields' => array(
                'bidId' => '标的Id',
                'orderId' => '外部单号',
                'payUserId' => '代偿用户Id',
                'totalAmount' => '该批次总金额',
                'totalNum' => '该批次总条数',
                'originalPayUserId' => '原始借款人ID',
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
            ),
            'default' => array(
                'currency' => 'CNY',
            ),
            'orderSplit' => array( // 订单拆分配置
                'count' => 500, // 单笔请求笔数
                'orderIdField' => 'orderId', // 主订单的[交易流水号]字段
                'listField' => 'repayOrderList', // 需要拆分的[列表]字段
                'userIdField' => 'payUserId', // 主订单的[用户ID]字段
                'dealIdField' => 'bidId', // 主订单的[标的ID]字段
                'subAmountField' => 'amount', // 子订单里面的[金额]字段
                'totalNumField' => 'totalNum', // 拆分后需要替换的字段-该批次总条数
                'totalAmountField' => 'totalAmount', // 拆分后需要替换的字段-该批次总金额
            ),
        ),
    ), // end supervision
);
