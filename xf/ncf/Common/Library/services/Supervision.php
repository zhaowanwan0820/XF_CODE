<?php

namespace NCFGroup\Common\Library\services;

use NCFGroup\Common\Library\services\ServiceInterface;

class Supervision implements ServiceInterface
{
    //   接口响应状态码
    const RESPONSE_SUCCESS      = '00000';
    const RESPONSE_ORDER_EXIST  = '10005';

    const RESPONSE_CODE_SUCCESS = '00';
    const RESPONSE_CODE_FAILURE = '100001';
    const RESPONSE_CODE_PROCESSING = '100002';

    // 业务状态代码
    const STATUS_SUCCESS        = 'S';
    const STATUS_FAIL           = 'F';
    const STATUS_PROCESS        = 'I';

    // 业务常量定义
    const REQ_SOURCE_NORMAL     = 1; // 请求来源(1:PC)
    const REQ_SOURCE_MOBILE     = 2; // 请求来源(2:MOBILE)

    /**
     * 判断是否存在服务
     * @param string $key 服务名称
     * @return boolean
     */
    public function has($key)
    {
        if (isset($this->_services[$key]))
        {
            return true;
        }
        return false;
    }

    public function getServices()
    {
        return $this->_services;
    }

    /**
     *  读取服务信息配置
     * @param string $key 服务名称
     * @return mixed 返回对应的服务配置信息
     */
    public function get($key)
    {
        return $this->_services[$key];
    }

    /**
     *  读取服务公共配置信息
     * @param string $key
     * @return mixed 返回对应的公共配置数据
     */
    public function getConfig($key)
    {
        // 增加测试环境
        if (empty($this->_config[APP_ENV][$key]))
        {
            return null;
        }
        return $this->_config[APP_ENV][$key];
    }

    // 公用配置信息
    private $_config = array(
        'dev' => array(
            // 网关地址
            'gatewayUrl'            => 'http://sandbox.firstpay.com/hk-fsgw/gateway',
            // 默认版本号
            'version'               => '1.0.0',
            // 签名方法
            'method'                => 'RSA',
            // 签名方法
            'source'                => self::REQ_SOURCE_NORMAL,
            // 远程日志设置
            'logServerIp'           => '10.20.69.101',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDAxhVpmyO1jMqpKFjn3MMCxALj7J3RVj3isgX6EqQ6EW7FIYWyNfcjQZGffbWjF2jwwiwG9CnEgHdxLDFSH2B0QKy4089hdeSvJA7OXtLh5eaOVgJVMGek6ywYFyls3mh1lWvQn4cBaR/6QY088+D4CT6brSvI7TJCjI/9pVBxewIDAQAB',
            // 默认商户号
            'merchantId'            => 'M20000001101',
            // 商户私钥
            'merchantPrivateKey'    => 'MIICeQIBADANBgkqhkiG9w0BAQEFAASCAmMwggJfAgEAAoGBAIhxZbcl41vncCoHxRBnSFUDWIxCozAPCiEisxKddJKmNxpUXlg/22Y8TJmQoYtj5xnSLqDfjOX0Pkyx9mLYpuy31YOz/bh15falCtPumMNdyhRTMd6xJVPBmIs9LeMhz7+1Ai4mKCuXd0DJ6mUGKSto4H7MeH7Ac7fmjqd0MZGrAgMBAAECgYEAgdholALae3ukolsCjrm7fCvS+Kfx5KprWV1MTUrKxUSo68Wegx1CDekUfI/HLH/GTixXc4FK9QuaviId97N2JisMTYTHKXLbVB1fWYQwdQvrQs6Cj+8Vs8lhZYODdH0jdJJquQVwqcsxrc8sQzXXB4ox5vrfNoAMZ1afRqE1wQECQQDNDrNDwovhCiFbmJhQLTjltSjtQ//FDdXRPw5P92Nl+etpBrqoF1+Bno9QnKPj7WgI0PMJnQiFd6GYpo4NkQenAkEAqlbvXeOBdS8UfvT2g7cYNb++ojJPao7gdGZuMgDPVDvQqRyitj5QG8aYg5LEzYlmxNUoA7ftGsrM/7oBwYkmXQJBAKlIJHeg6MccDNPIEp3F533C44mUJFcyB70ZWCBt85HhExV+J6PSv9aK5nc/CRGGEOeOT8U07S75xt71SLosa2sCQQCGxziokk2pefH+rjara2D1jlz5G1OpHZnNoAqK+AcUQCvO00CPcGiUQaQFX0jm1FQDZCFAJ/SsoVBo+zVOfAVlAkEAgo9ee/rWwmy4HsAGxGoCEwusrs/IJ/tTt77Yx6s3k1oiKzJCKhBbnPm+/HWjVTywEUwZjhCxlibALFziC4yUyw==',
        ),
        'test' => array(
            // 网关地址
            'gatewayUrl'            => 'http://sandbox.firstpay.com/hk-fsgw/gateway',
            // 默认版本号
            'version'               => '1.0.0',
            // 签名方法
            'method'                => 'RSA',
            // 签名方法
            'source'                => 1,
            // 远程日志设置
            'logServerIp'           => '10.20.69.101',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDAxhVpmyO1jMqpKFjn3MMCxALj7J3RVj3isgX6EqQ6EW7FIYWyNfcjQZGffbWjF2jwwiwG9CnEgHdxLDFSH2B0QKy4089hdeSvJA7OXtLh5eaOVgJVMGek6ywYFyls3mh1lWvQn4cBaR/6QY088+D4CT6brSvI7TJCjI/9pVBxewIDAQAB',
            // 默认商户号
            'merchantId'            => 'M20000001101',
            // 商户私钥
            'merchantPrivateKey'    => 'MIICeQIBADANBgkqhkiG9w0BAQEFAASCAmMwggJfAgEAAoGBAIhxZbcl41vncCoHxRBnSFUDWIxCozAPCiEisxKddJKmNxpUXlg/22Y8TJmQoYtj5xnSLqDfjOX0Pkyx9mLYpuy31YOz/bh15falCtPumMNdyhRTMd6xJVPBmIs9LeMhz7+1Ai4mKCuXd0DJ6mUGKSto4H7MeH7Ac7fmjqd0MZGrAgMBAAECgYEAgdholALae3ukolsCjrm7fCvS+Kfx5KprWV1MTUrKxUSo68Wegx1CDekUfI/HLH/GTixXc4FK9QuaviId97N2JisMTYTHKXLbVB1fWYQwdQvrQs6Cj+8Vs8lhZYODdH0jdJJquQVwqcsxrc8sQzXXB4ox5vrfNoAMZ1afRqE1wQECQQDNDrNDwovhCiFbmJhQLTjltSjtQ//FDdXRPw5P92Nl+etpBrqoF1+Bno9QnKPj7WgI0PMJnQiFd6GYpo4NkQenAkEAqlbvXeOBdS8UfvT2g7cYNb++ojJPao7gdGZuMgDPVDvQqRyitj5QG8aYg5LEzYlmxNUoA7ftGsrM/7oBwYkmXQJBAKlIJHeg6MccDNPIEp3F533C44mUJFcyB70ZWCBt85HhExV+J6PSv9aK5nc/CRGGEOeOT8U07S75xt71SLosa2sCQQCGxziokk2pefH+rjara2D1jlz5G1OpHZnNoAqK+AcUQCvO00CPcGiUQaQFX0jm1FQDZCFAJ/SsoVBo+zVOfAVlAkEAgo9ee/rWwmy4HsAGxGoCEwusrs/IJ/tTt77Yx6s3k1oiKzJCKhBbnPm+/HWjVTywEUwZjhCxlibALFziC4yUyw==',
        ),
        'pdtest' => array(
             // 网关地址
            'gatewayUrl'            => 'http://sandbox.firstpay.com/hk-fsgw/gateway',
            // 默认版本号
            'version'               => '1.0.0',
            // 签名方法
            'method'                => 'RSA',
            // 签名方法
            'source'                => 1,
            // 远程日志设置
            'logServerIp'           => 'pmlog1.wxlc.org',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCIj7mvAexsn+0v7JfTn2X6ALmjV1RD+G1SP6C3pYI2hsn0XoZYTBud5lNUjCW9iBuMJTlpmpITpc5pJU9PK8+ZHPO8LF5CmR644FtWq/aUXV+JkVpAd+PsFavNvhqnUBIvAwaZ5CpC9cS9zzIxI/T1hnQD5f0o7zXNGa66w6QqpQIDAQAB',
            // 默认商户号
            'merchantId'            => 'M20000002377',
            // 商户私钥
            'merchantPrivateKey'    => 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBANeiOwspNwIJnqQrckH2oUy8xdzAWUH9eE9/zYiIQa84//lo03DmYP5bGgnB1jjYRqf48F1dDS6cgiaM5IZGF3yTXq9yTJMG0XrQAmLyS4CLXD2GUXxjYeTiTKNPzWMdMhm6y+76xdzYr1iuiHv5keay3vD5aKRQ2GSXtN/NglYHAgMBAAECgYB5Ke5VWiZPnconY0ZDbGq8LMJdRTOiUeO9gAmkczO9WqDyqwVMRhcwNU6PNvzBWj1xev7M51FV5Jl5QefSzyW4UM9kUyw1tlXjE8yDc3jlcTTXepNi/yTzzQBszV7B6VWC/bCEd/FNKnXYMDrDJaPCuxg4Te4XBda7vXCY3OFV+QJBAPdL00z4dmYCqd6WXmkGqjuMoSVelIVlhqH5zufITcpwgHj+7lpKQ7tlzzPxvX8thakbj5MPJuHpsCYEOeIOu1MCQQDfOR8oQGW3MEG12tWpCwwlUdH7KyKF0QYqU5T1bCmg+12oaarGosjeMxlcF3YAtyUEbnfqZzhYyuuASx1wTlf9AkEAvKpVr4BTW+omTNHtfzT9hOb6PjdVGhxlxYd/KefwKUUBTs43bB0CZaL7nIaOasuBEI4dUDWcFXii0a4htuxETwJAB294qTeT68kwtyUF7u6ORgP2sZ4bNUfkI67LDG3A6TrWQNDcPmeXt0cOdjHV3Wo8Umx3lBhCGTsRIyHdZitF5QJABSX9XmV0/Mk4Q0EzQV0DrDTTCeOjoswbkFw5SuxuQfIFSIRhI7ssBxiAY3Ec8ol5x2oA4/aPSiqO/rqBZZwgjw==',
        ),
        'product' => array(
            // 网关地址
            'gatewayUrl'            => 'https://cgpt.unitedbank.cn/gateway',
            // 默认版本号
            'version'               => '1.0.0',
            // 签名方法
            'method'                => 'RSA',
            // 签名方法
            'source'                => 1,
            // 远程日志设置
            'logServerIp'           => 'pmlog1.wxlc.org',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDMJ1xe6cNaWbDKD8tbE6sk/c0zUK0p5da3lyK15QaaTEl9OgbNKnVHwUtuKlK5onJ/CSc0vO5+7BYPzEjpMEKskoxBriGGKdeQ0ZhUrOfVUTUyJ6qWyv9u8KzRoRIlG4VWtrYJv1LFsd1gJa1JoJre6lB5WRXAyaCxyBZe5Ia+BQIDAQAB',
            // 默认主商户号
            'merchantId'            => 'M20002646207',
            // 商户私钥
            'merchantPrivateKey'    => 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMtn4r6BALAtLsKcl//F7C4zICSVOU9gGMRBjWaHfl8M59bse7dSG8ovoCCd302Yi3rIet5r0Ek57CS+L3Sf7N9dAmBmEFHDEOUqJ+S28IcONZheTf/ZpdSgVNTiuzu+V51CnckzZM6nquiwpmPOAul+N7i+MLB2SAJ7LcryMEmTAgMBAAECgYBiE4VRNgKO8DpLvBXOTjDDVgN5oDox+7P1bWYwucRFMIPZLc25Zu3fX3dmQrkZQSR/34rfFD0qEbO7Q7i+Ex6y92Il21oAUdYjgZ1AMLtBcgcmS66lUmKAVk0se4+iPPV0qmDMvB78ZqBUfvc/d8Oq4PTBaQ32kqafMTYPXaxKAQJBAP4Ko/hPJZ/ukXzyNQ2Tq8aWAq4KrkkvOAe0TMkxzqxvLotjsF8jWCDabs3qxQsvDwQFkbPfWWFrdo6V9g+A8DECQQDM+VBYf31xX1WwDiMraKJmA7a4AAmd4WWQ3roP+fRKnoko3BCO8YxB4gOtflKNZgstVYUIYOXJ1Yg7OuOhx8kDAkEAgxXIgEOO3ZTDu95bI9NUKteTMG1Qe3EDD26oxJSP2YhRUxmwk5bwTZuI23ZOELRKoj+hbFhOjade+LpGyr80sQJAVQrvyfgDXOF4FNaYwu7jyj2qNsdVNhsJX8T5H53OPJNwRvKfMB+J2N/kNLxflekLpCCCVIqXbFla73Asd7gDbwJBAKx6AJ0qd3looPqqOTbidfEZL2hvvkdtrl36VSmGhpKSuGGHcttv1xT8A4EIo0i+uamdk0a3amrieYklC1/RaTE=',
        ),
    );

    //  服务配置信息
    private $_services = array(
        // 账户类型修改
        'biztypeModify' => array(
            'service' => 'p2p.member.biztype.modify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '账户类型',
            ),
        ),
        // 海口银行快速注册页面
        'memberQuickRegister' => array(
            'service' => 'h5.p2p.member.account.createindividualsimple',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/registerNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // standardRegister
        'memberStandardRegister' => array(
            'service' => 'web.member.account.create',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/registerStandardNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // h5 standardRegister
        'h5MemberStandardRegister' => array(
            'service' => 'h5.p2p.member.account.create',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/registerStandardNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // 个人用户注册 - 页面跳转
        'memberRegister' => array(
            'service' => 'web.p2p.member.account.createindividual',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/registerNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // h5个人用户注册 - 页面跳转
        'h5MemberRegister' => array(
            'service' => 'h5.p2p.member.account.createindividual',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/registerNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // 个人用户开户接口
        'memberRegisterApi' => array(
            'service' => 'p2p.member.account.createindividual',
            //'noticeUrl' => '/supervision/registerNotify',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '用户类型',
                'realName' => '姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'regionCode' => '国家区域码',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'IDC', // IDC-身份证(目前只支持身份证认证，其他证件类型暂不支持)
            ),
        ),
        // 港澳台开户页面
        'foreignMemberRegister' => array(
            'service' => 'web.p2p.member.account.createother',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/foreignMemberRegisterNotify',
            'required' => array(
                'userId' => '用户Id',
                'bizType' => '用户类型',
                'realName' => '真实姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'regionCode' => '国家区域码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'GAT', // 证件类型(港澳台身份证:GAT 军官证:MILITARY 护照:PASS_PORT)
            ),
        ),
        // H5港澳台开户页面
        'h5ForeignMemberRegister' => array(
            'service' => 'h5.p2p.member.account.createother',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/foreignMemberRegisterNotify',
            'required' => array(
                'userId' => '用户Id',
                'bizType' => '用户类型',
                'realName' => '真实姓名',
                'certType' => '证件类型',
                'certNo' => '证件号码',
                'phone' => '手机号码',
                'regionCode' => '国家区域码',
                'bankCode' => '银行编码',
                'bankCardNo' => '银行卡号',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资用户 02-借款用户 06-借款/投资混合用户)
                'certType' => 'GAT', // 证件类型(港澳台身份证:GAT 军官证:MILITARY 护照:PASS_PORT)
            ),
        ),
        // 企业用户开户 - 页面跳转
        'enterpriseRegister' => array(
            'service' => 'web.p2p.member.account.createenterprise',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/enterpriseRegisterNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '业务类型',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资户|02-借款户|03-担保户|04-咨询户|05-平台户|06-借贷混合户|08-平台营销户|10-平台收费户|11-代偿户|12-第三方营销账户|13-垫资户)
            ),
        ),
        // H5企业用户开户 - 页面跳转
        'h5EnterpriseRegister' => array(
            'service' => 'h5.p2p.member.account.createenterprise',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/enterpriseRegisterNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'bizType' => '业务类型',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'bizType' => '01', // 业务类型(01-投资户|02-借款户|03-担保户|04-咨询户|05-平台户|06-借贷混合户|08-平台营销户|10-平台收费户|11-代偿户|12-第三方营销账户|13-垫资户)
            ),
        ),
        // 用户余额查询 - 接口
        'memberBalanceSearch' => array(
            'service' => 'p2p.trade.balance.search',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 会员资料查询接口 - 接口
        'memberSearch' => array(
            'service' => 'p2p.member.info.search',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 会员绑卡查询 - 接口
        'memberCardSearch' => array(
            'service' => 'p2p.member.card.search',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // web用户授权页面 - 页面
        'memberAuthorizationCreate' => array(
            'service' => 'web.p2p.member.authorization.create',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/memberAuthorizationCreateNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'grantList' => '用户授权列表',
                'callbackUrl' => '异步回调地址',
            )
        ),
        // h5用户授权页面 - 页面
        'h5MemberAuthorizationCreate' => array(
            'service' => 'h5.p2p.member.authorization.create',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/memberAuthorizationCreateNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'grantList' => '用户授权列表',
                'callbackUrl' => '异步回调地址',
            )
        ),
        // 取消用户授权 - 接口
        'memberAuthorizationCancel' => array(
            'service' => 'p2p.member.authorization.cancel',
            'required' => array(
                'userId' => 'P2P用户ID',
                'grantList' => '用户授权列表',
            )
        ),
        // 取消用户授权 - Web页面
        'webMemberAuthorizationCancel' => array(
            'service' => 'web.p2p.member.authorization.cancel',
            'required' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // 取消用户授权 - H5页面
        'h5MemberAuthorizationCancel' => array(
            'service' => 'h5.p2p.member.authorization.cancel',
            'required' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // 用户授权查询 - 接口
        'memberAuthorizationSearch' => array(
            'service' => 'p2p.member.authorization.search',
            'required' => array(
                'userId' => 'P2P用户ID',
            )
        ),
        // 存管账户绑卡 - 页面跳转
        'memberBindcard' => array(
            'service' => 'web.p2p.member.card.create',
            'returnUrl' => '/account/',
            //'noticeUrl' => '/supervision/memberBindcardNotify',
            'required' => array(
                'userId' => 'P2P用户Id',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 会员换卡接口
        // 会员修改手机号接口
        // pc充值收银台 - 页面跳转
        'webCharge' => array(
            'service' => 'web.p2p.trade.account.recharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/chargeNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // APP H5充值 -- 页面跳转
        'h5Charge' => array(
            'service' => 'h5.p2p.trade.account.quickrecharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/chargeNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 自动扣款充值代扣 - 接口
        'autoRecharge' => array(
            'service' => 'p2p.trade.account.autoRecharge',
            //'noticeUrl' => '/supervision/chargeNotify',
            'retry' => true,
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额,单位分',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 限额订阅(API) - 接口
        'chargeLimitSubscription' => array(
            'service' => 'p2p.service.subscription.factory',
            'noticeUrl' => '/supervision/chargeLimitNotify',
            'retry' => true,
            'required' => array(
                'outOrderId' => '订单号',
                'callbackUrl' => '订阅通知地址',
            ),
            'defaults' => array(
                'type' => 'BCL', // 订阅类型(BCL-银行卡限额信息)
            ),
        ),
        // 标的报备 - 接口
        'dealCreate' => array(
            'service' => 'p2p.trade.bid.create',
            //'noticeUrl' => '/supervision/dealCreateNotify',
            'retry' => true,
            'required' => array(
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
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 标的更新 - 接口
        'dealUpdate' => array(
            'service' => 'p2p.trade.bid.update',
            //'noticeUrl' => '/supervision/dealUpdateNotify',
            'retry' => true,
            'required' => array(
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
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 标的查询 - 接口
        'dealSearch' => array(
            'service' => 'p2p.trade.bid.search',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
            ),
        ),
        // 标的投资验密 -  页面
        'investCreateSecret' => array(
            'service' => 'web.p2p.trade.invest.create',
            //'noticeUrl' => '/supervision/investCreateNotify',
            'required' => array(
                'orderId' => '外部流水号',
                'userId' => '用户ID',
                'totalAmount' => '投资总金额',
                'accAmount' => '使用账户金额',
                'bidId' => '标的Id',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 标的投资验密 -  h5页面
        'h5InvestCreateSecret' => array(
            'service' => 'h5.p2p.trade.invest.create',
            'returnUrl' => '/',
            //'noticeUrl' => '/supervision/investCreateNotify',
            'required' => array(
                'orderId' => '外部流水号',
                'userId' => '用户ID',
                'totalAmount' => '投资总金额',
                'accAmount' => '使用账户金额',
                'bidId' => '标的Id',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'expireTime' => '5', // 页面失效时间
            ),
        ),
        // 标的投资无密 - 接口
        'investCreate' => array(
            'service' => 'p2p.trade.invest.create',
            //'noticeUrl' => '/supervision/InvestCreateNotifyLogOnly',
            'retry' => true,
            'required' => array(
                'orderId' => '外部流水号',
                'userId' => '用户ID',
                'totalAmount' => '投资总金额',
                'accAmount' => '使用账户金额',
                'bidId' => '标的Id',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'expireTime' => '5', // 页面失效时间
            ),
        ),
        // 投资取消 - 接口
        'investCancel' => array(
            'service' => 'p2p.trade.invest.cancel',
            'retry' => true,
            'required' => array(
                'origOrderId' => '原投资订单号',
                'rpDirect' => '红包流向',
            ),
        ),
        // 放款接口
        'dealGrant' => array(
            'service' => 'p2p.trade.bid.grant',
            //'noticeUrl' => '/supervision/dealGrantNotify',
            'retry' => true,
            'required' => array(
                'orderId' => '订单号',
                'bidId' => '标的Id',
                'userId' => '借款人P2P用户ID',
                'totalNum' => '放款总笔数',
                'totalAmount' => '放款总金额',
                'grantAmount' => '放款人实际收款金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 还款接口
        'dealRepay' => array(
            'service' => 'p2p.trade.bid.repay',
            //'noticeUrl' => '/supervision/orderSplitNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
                'orderId' => '外部订单号',
                'payUserId' => '还款人P2P用户ID',
                'totalNum' => '该批次总条数',
                'totalAmount' => '该批次总金额',
                'currency' => '币种(默认为CNY)',
                'repayOrderList' => '还款订单集合',
                'callbackUrl' => '异步回调地址',
            ),
            'orderSplit' => array( // 订单拆分配置
                'count' => 500, // 单笔请求笔数
                'specialCount' => 100, // 特殊列表拆分笔数
                'orderIdField' => 'orderId', // 主订单的[交易流水号]字段
                'listField' => 'repayOrderList', // 需要拆分的[列表]字段
                'specialListField' => 'chargeOrderList', // 需要拆分的[特殊列表]字段
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
            //'noticeUrl' => '/supervision/dealCancelNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的ID',
                'rpDirect' => '红包流向',//01-返还到红包账户 02-留在投资人账户
                'callbackUrl' => '异步回调地址',
            ),
        ),
        //  批量转账  - 接口
        'batchTransfer' => array(
            'service' => 'p2p.trade.batchtransfer.create',
            //'noticeUrl' => '/supervision/batchTransferNotify',
            'retry' => true,
            'required' => array(
                'orderId' => '转账批次单号',
                'subOrderList' => '明细列表',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 转账充值订单信息查询  - 接口
        'offlineRechargeSearch' => array(
            'service' => 'p2p.trade.offlinerecharge.search',
            'retry' => true,
            'required' => array(
                'userId' => '用户ID',
                'startDate' => '起始日期',
                'endDate' => '终止日期',
                'page' => '页码',
            ),
        ),
        // 专户资金流水查询  - 接口
        'coreAccountLogSearch' => array(
            'service' => 'p2p.clear.coreaccountlog.search',
            'retry' => true,
            'required' => array(
                'bankCardNo' => '对手方账户',
                'startDate' => '起始日期',
                'endDate' => '终止日期',
                'page' => '页码',
            ),
        ),
        // 单笔订单查询接口
        'orderSearch' => array(
            'service' => 'p2p.trade.order.search',
            'required' => array(
                'orderId' => '外部订单号',
            ),
        ),
        // 订单批量查询接口
        'batchSearch' => array(
            'service' => 'p2p.trade.order.batchsearch',
            'required' => array(
                'orderIds' => '外部订单号',
            ),
        ),
        // 存管行资金记录 - 页面跳转
        'memberInfo' => array(
            'service' => 'web.p2p.member.info.manage',
            'required' => array(
                'userId' => 'P2P用户Id',
            ),
        ),
        // h5存管行资金记录 - 页面跳转
        'h5MemberInfo' => array(
            'service' => 'h5.p2p.member.info.manage',
            'required' => array(
                'userId' => 'P2P用户Id',
            ),
        ),
        // 提现
        'withdraw' => array(
            'service' => 'p2p.trade.account.withdraw',
            //'noticeUrl' => '/supervision/withdrawNotify',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型',
                'efficType' => '提现时效类型',
                'amount' => '提现金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 提现-掌众专用
        'bankpayupWithdraw' => array(
            'service' => 'p2p.trade.bankpayup.withdraw',
            //'noticeUrl' => '/supervision/withdrawNotify',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'amount' => '提现总金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 免密提现至超级账户-接口
        'accountSuperWithdraw' => array(
            'service' => 'p2p.trade.account.superwithdraw',
            //'noticeUrl' => '/supervision/superWithdrawNotify',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户Id',
                'amount' => '提现金额',
                'orderId' => '订单Id',
                'superUserId' => '超级账户用户ID',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 提现至银信通电子账户-接口
        'bidElecWithdraw' => array(
            'service' => 'p2p.trade.bid.elecwithdraw',
            //'noticeUrl' => '/supervision/bidElecwithdrawNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'totalAmount' => '总金额,单位：分',
                'repayAmount' => '还款金额,单位：分，总金额-还款金额=解冻金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'bizType' => '04', // 银信通
            ),
        ),
        // 速贷提现到银信通电子账户接口
        'creditLoanWithdraw' => array(
            'service' => 'p2p.trade.bid.elecwithdraw',
            //'noticeUrl' => '/creditloan/SupervisionWithdrawNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'totalAmount' => '总金额,单位：分',
                'repayAmount' => '还款金额,单位：分，总金额-还款金额=解冻金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'bizType' => '05', //速贷
            ),
        ),

        // web/pc验密提现至银行卡-页面
        'secretWithdraw' => array(
            'service' => 'web.p2p.trade.account.withdraw',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/withdrawNotify',
            'required' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型(NW:普通提现,FW:放款提现)',
                'efficType' => '提现时效类型(T1:T+1提现,D0:D+0提现)',
                'amount' => '金额,单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'expireTime' => '5', //页面失效时间
                'advanceType' => '02', // 提现使用垫资 01  提现不使用垫资 02
            ),
        ),
        // H5验密提现至银行卡-页面
        'h5SecretWithdraw' => array(
            'service' => 'h5.p2p.trade.account.withdraw',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/withdrawNotify',
            'required' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型(NW:普通提现,FW:放款提现)',
                'efficType' => '提现时效类型(T1:T+1提现,D0:D+0提现)',
                'amount' => '金额,单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'expireTime' => '5', //页面失效时间
                'advanceType' => '02', // 提现使用垫资 01  提现不使用垫资 02
            ),
        ),
        // 快速验密提现PC端-页面-新
        'pcSecretWithdrawFast' => array(
            'service' => 'web.p2p.trade.account.fastwithdraw',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/withdrawNotify',
            'required' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'amount' => '提现金额（含手续费）,单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'expireTime' => '5', // 页面失效时间（单位为分）
            ),
        ),
        // 快速验密提现H5端-页面-新
        'h5SecretWithdrawFast' => array(
            'service' => 'h5.p2p.trade.account.fastwithdraw.apply',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/withdrawNotify',
            'required' => array(
                'userId' => 'P2P用户Id',
                'orderId' => '外部订单号',
                'bizType' => '提现业务类型(01-普通提现 02-放款提现)',
                'efficType' => '提现时效类型(T1:T+1提现,D0:D+0提现)',
                'amount' => '提现金额,单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种,默认为CNY
                'expireTime' => '5', // 页面失效时间（单位为分）
            ),
        ),
        // H5确认投资-页面
        'h5InvestConfirm' => array(
            'service' => 'h5.p2p.trade.invest.confirm',
            'required' => array(
                'reqId' => '请求ID',
                'password' => '密码',
                'type' => '类型',
            ),
        ),
        // 账户注销 -- 接口
        'memberCancel' => array(
            'service' => 'p2p.member.account.cancel',
            'retry' => true,   //是否重试
            'required' => array(
                'userId' => 'P2P用户ID',
            ),
        ),
        // 修改手机号 -- 接口
        'memberPhoneUpdate' => array(
            'service' => 'p2p.member.phone.update',
            'retry' => true,   //是否重试
            'required' => array(
                'userId' => 'P2P用户ID',
                'phone' => '新注册手机号',
            ),
        ),
        // 绑定银行卡 - 接口
        'memberCardBind' => array(
            'service' => 'p2p.member.card.bind',
            'retry' => true,
            'required' => array(
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
            'required' => array(
                'userId' => 'P2P用户ID',
                'bankCardNo' => '银行卡号',
            ),
        ),
        // 修改/更换银行卡 - 接口
        'memberCardUpdate' => array(
            'service' => 'p2p.member.card.update',
            'retry' => true,
            'required' => array(
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
            'required' => array(
                'userId' => 'P2P用户ID',
                'returnUrl' => '返回商户的地址',
            ),
        ),
        // 个人及港澳台信息修改 -- 接口
        'memberInfoModify' => array(
            'service' => 'p2p.member.info.modify',
            'retry' => true,   //是否重试
            //'noticeUrl' => '/supervision/memberInfoModifyNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'orderId' => '外部单号',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 企业会员修改 - 接口
        'enterpriseUpdateApi' => array(
            'service' => 'p2p.member.account.updateenterprise',
            //'noticeUrl' => '/supervision/enterpriseUpdateNotify',
            'retry' => true,
            'required' => array(
                'userId' => 'P2P用户ID',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // pc企业会员修改 - 页面
        'enterpriseUpdate' => array(
            'service' => 'web.p2p.member.account.updateenterprise',
            //'noticeUrl' => '/supervision/enterpriseUpdateNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 从超级账户充值到网贷账户 - 接口
        'superRecharge' => array(
            'service' => 'p2p.trade.account.superrecharge',
            //'noticeUrl' => '/supervision/superRechargeNotify',
            'retry' => true,
            'required' => array(
                'orderId' => '外部单号',
                'userId' => 'P2P用户ID',
                'amount' => '金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            )
        ),
        // PC验密提现至超级账户 - 页面
        'superWithdrawSecret' => array(
            'service' => 'web.p2p.trade.account.superwithdraw',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/superWithdrawNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'amount' => '金额',
                'orderId' => '外部单号',
                'superUserId' => '超级账户用户ID',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            )
        ),
        // H5验密提现至超级账户 - 页面
        'h5SuperWithdrawSecret' => array(
            'service' => 'h5.p2p.trade.account.superwithdraw',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/superWithdrawNotify',
            'required' => array(
                'userId' => 'P2P用户ID',
                'amount' => '金额',
                'orderId' => '外部单号',
                'superUserId' => '超级账户用户ID',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            )
        ),
        // 收费接口
        'gainFees' => array(
            'service' => 'p2p.trade.account.gainfees',
            //'noticeUrl' => '/supervision/gainFeesNotify',
            'retry' => true,
            'required' => array(
                'orderId' => '外部单号',
                'bidId' => '标的Id',
                'payUserId' => '收费付款方P2P用户Id',
                'totalNum' => '该批次总条数',
                'totalAmount' => '该批次总金额',
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            )
        ),
        // 代偿 - 接口
        'dealReplaceRepay' => array(
            'service' => 'p2p.trade.bid.replacerepay',
            //'noticeUrl' => '/supervision/orderSplitNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
                'orderId' => '外部单号',
                'payUserId' => '代偿人P2P用户ID',
                'totalNum' => '该批次总条数',
                'totalAmount' => '该批次总金额',
                'originalPayUserId' => '原始借款人ID',
                'bizType' => '代偿方式', // D：直接代偿 I:代垫，间接代偿 默认D
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
            'orderSplit' => array( // 订单拆分配置
                'count' => 500, // 单笔请求笔数
                'specialCount' => 100, // 特殊列表拆分笔数
                'orderIdField' => 'orderId', // 主订单的[交易流水号]字段
                'listField' => 'repayOrderList', // 需要拆分的[列表]字段
                'specialListField' => 'chargeOrderList', // 需要拆分的[特殊列表]字段
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
            //'noticeUrl' => '/supervision/returnRepayNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
                'orderId' => '外部单号',
                'payUserId' => '还代偿人P2P用户Id',
                'totalAmount' => '该批次总金额',
                'totalNum' => '该批次总条数',
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            )
        ),
        // 受托提现
        'entrustedWithdraw' => array(
            'service' => 'p2p.trade.bid.entrustedwithdraw',
            //'noticeUrl' => '/supervision/entrustedWithdrawNotify',
            'retry' => true,
            'required' => array(
                'grandOrderId' => '原放款单号',
                'orderId' => '外部订单号',
                'bizType' => '业务类型',
                'efficType' => '时效类型',
                'amount' => '批次总金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            )
        ),
        // 快速受托提现-新
        'entrustedWithdrawFast' => array(
            'service' => 'p2p.trade.bid.entrustedfastwithdraw',
            'noticeUrl' => '/supervision/entrustedWithdrawNotify',
            'retry' => true,
            'required' => array(
                'grandOrderId' => '原放款单号',
                'orderId' => '外部订单号',
                'amount' => '提现金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 批次订单查询
        'batchOrderSearch' => array(
            'service' => 'p2p.trade.batchorder.search',
            'required' => array(
                'batchId' => '批次单号',
                'orderType' => '订单类型', //5000 = 放款、7000 = 还款、3100 = 流标、8000 = 返利红包收费
            ),
        ),
        // 智多鑫存管相关接口
        // 1.1.1预约冻结（PC端WEB）-页面
        'webBookfreezeCreate' => array(
            'service' => 'web.p2p.trade.bookfreeze.create',
            //'noticeUrl' => '/supervision/bookfreezeCreateNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'freezeType' => '冻结类型(01-预约投资)',
                'freezeSumAmount' => '预约冻结总金额，单位（分）',
                'freezeAccountAmount' => '预约投资冻结使用账户金额，单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'freezeType' => '01',// 冻结类型(01-预约投资)
                'expireTime' => '5', // 页面失效时间

            ),
        ),
        // 1.1.2预约冻结（手机端H5）-页面
        'h5BookfreezeCreate' => array(
            'service' => 'h5.p2p.trade.bookfreeze.create',
            //'noticeUrl' => '/supervision/bookfreezeCreateNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'freezeType' => '冻结类型(01-预约投资)',
                'freezeSumAmount' => '预约冻结总金额，单位（分）',
                'freezeAccountAmount' => '预约投资冻结使用账户金额，单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'freezeType' => '01',// 冻结类型(01-预约投资)
                'expireTime' => '5', // 页面失效时间
            ),
        ),
        // 1.1.3预约冻结(API)-接口
        'bookfreezeCreate' => array(
            'service' => 'p2p.trade.bookfreeze.create',
            //'noticeUrl' => '/supervision/bookfreezeCreateNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'freezeType' => '冻结类型(01-预约投资)',
                'freezeSumAmount' => '预约冻结总金额，单位（分）',
                'freezeAccountAmount' => '预约投资冻结使用账户金额，单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'freezeType' => '01',// 冻结类型(01-预约投资)
            ),
        ),
        // 1.1.4取消预约冻结(API)-接口
        'bookfreezeCancel' => array(
            'service' => 'p2p.trade.bookfreeze.cancel',
            //'noticeUrl' => '/supervision/bookfreezeCancelNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'unFreezeType' => '冻结类型(01-预约投资)',
                'amount' => '解冻总金额，包含平台收费金额，单位（分）',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'unFreezeType' => '01',// 冻结类型(01-预约投资)
            ),
        ),
        // 1.1.5预约批量投资（API）-接口
        'bookInvestBatchCreate' => array(
            'service' => 'p2p.trade.bookInvestBatch.create',
            //'noticeUrl' => '/supervision/bookInvestBatchCreateNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'currency' => '币种，默认为CNY,以下为可选值,CNY (人民币)',
                'totalAmount' => '批量投资总金额，单位（分）',
                'subInvestOrderList' => '批量投资子单集合',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种，默认为CNY,以下为可选值,CNY (人民币)
            ),
        ),
        // 1.1.6取消投资（API）-接口
        'bookInvestCancel' => array(
            'service' => 'p2p.trade.bookInvest.cancel',
            'required' => array(
                'origOrderId' => '原投资单号',
            ),
        ),
        // 1.1.7批量债权转让投资(API)-接口
        'bookCreditBatch' => array(
            'service' => 'p2p.trade.bookcredit.batch',
            //'noticeUrl' => '/supervision/bookCreditBatchNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => 'P2P用户Id',
                'currency' => '币种，默认为CNY,以下为可选值,CNY (人民币)',
                'totalAmount' => '投资总金额，单位（分）',
                'subInvestOrderList' => '批量投资债转集合',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 1.1.8取消债权转让(API)-接口
        'bookCreditCancel' => array(
            'service' => 'p2p.trade.bookcredit.cancel',
            'required' => array(
                'origOrderId' => '债权转让订单号',
            ),
        ),
        // 1.1.9批量标的债权转让(API)-接口
        'creditAssignmentBatchGrant' => array(
            'service' => 'p2p.trade.creditAssignment.batchgrant',
            //'noticeUrl' => '/supervision/creditAssignBatchGrantNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'totalAmount' => '转让本金总金额 单位分',
                'dealTotalAmount' => '成交总金额 单位分',
                'totalNum' => '该批次总条数',
                'currency' => '币种，默认为CNY,以下为可选值,CNY (人民币)',
                'creditOrderList' => '转让列表',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',// 币种，默认为CNY,以下为可选值,CNY (人民币)
            ),
        ),
        // 1.1.10取消债转投资(API)-接口
        'creditAssignmentCancel' => array(
            'service' => 'p2p.trade.creditAssignment.cancel',
            'required' => array(
                'origOrderId' => '债权转让订单号',
            ),
        ),
        // 批量余额查询
        'memberBatchBalanceSearch' => array(
            'service' => 'p2p.batch.balance.search',
            'required' => array(
                'userIds' => '用户Id',
            ),
        ),
        // 初始化用户授权
        'memberInitAuth' => array(
            'service' => 'p2p.member.account.setauth',
            'required' => array(
                'userId' => '用户Id',
            ),
        ),
        // web超级账户余额划转到网贷账户
       'superRechargeSecret' => array(
            'service' => 'web.p2p.trade.account.superrecharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/superRechargeSecretNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值用户网贷账户ID',
                'amount' => '金额 单位分',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
                'expireTime' => '5', // 页面失效时间
            ),
       ),
       // h5超级账户余额划转到网贷账户
       'h5SuperRechargeSecret' => array(
            'service' => 'h5.p2p.trade.account.superrecharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/superRechargeSecretNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值用户网贷账户ID',
                'amount' => '金额 单位分',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
                'expireTime' => '5', // 页面失效时间
            ),
       ),
        // 标的信息迁移
        'dealImport' => array(
            'service' => 'p2p.trade.bid.import',
            'required' => array(
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
            'required' => array(
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
            'required' => array(
                'userId' => '投资人ID',
                'bidId' => '标的ID',
                'sumAmount' => '投资债权总本金',
                'leftAmount' => '待还本金',
            ),
        ),
        // 账户流水查询
        'accountLogPage' => array(
            'service' => 'web.p2p.trade.accountlog.search',
            'required' => array(
                'userId' => '用户id',
            ),
        ),
        // 多银行卡充值
        'multiCardRecharge' => array(
            'service' => 'p2p.trade.multiCard.recharge',
            //'noticeUrl' => '/supervision/multicardChargeNotify',
            'required' => array(
                'orderId'=> '交易流水号',
                'userId' => '用户id',
                'amount' => '充值金额,单位分',
                'realName' => '真实姓名',
                'certNo' => '证件号',
                'bankCardNo' => '银行卡号',
                'callbackUrl' => '异步回调地址',
            ),
        ),
        // 代充值还款
        'dealReplaceRechargeRepay' => array(
            'service' => 'p2p.trade.bid.replacerechargerepay',
            //'noticeUrl' => '/supervision/orderSplitNotify',
            'retry' => true,
            'required' => array(
                'bidId' => '标的Id',
                'orderId' => '外部单号',
                'payUserId' => '代偿用户Id',
                'totalAmount' => '该批次总金额',
                'totalNum' => '该批次总条数',
                'originalPayUserId' => '原始借款人ID',
                'currency' => '币种',
                'repayOrderList' => '还款订单集合',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
            'orderSplit' => array( // 订单拆分配置
                'count' => 500, // 单笔请求笔数
                'specialCount' => 100, // 特殊列表拆分笔数
                'orderIdField' => 'orderId', // 主订单的[交易流水号]字段
                'listField' => 'repayOrderList', // 需要拆分的[列表]字段
                'specialListField' => 'chargeOrderList', // 需要拆分的[特殊列表]字段
                'userIdField' => 'payUserId', // 主订单的[用户ID]字段
                'dealIdField' => 'bidId', // 主订单的[标的ID]字段
                'subAmountField' => 'amount', // 子订单里面的[金额]字段
                'totalNumField' => 'totalNum', // 拆分后需要替换的字段-该批次总条数
                'totalAmountField' => 'totalAmount', // 拆分后需要替换的字段-该批次总金额
            ),
        ),
        // pc网贷大额充值 - 页面跳转
        'webOfflineCharge' => array(
            'service' => 'web.p2p.trade.account.offlineRecharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/offlineChargeNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // H5网贷大额充值 -- 页面跳转
        'h5OfflineCharge' => array(
            'service' => 'h5.p2p.trade.account.offlineRecharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/offlineChargeNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // H5企业用户充值 -- 页面跳转
        'h5EnterpriseCharge' => array(
            'service' => 'h5.p2p.trade.account.enterpriserecharge',
            'returnUrl' => '',
            //'noticeUrl' => '/supervision/enterpriseChargeNotify',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '充值P2P用户',
                'amount' => '充值金额',
                'callbackUrl' => '异步回调地址',
            ),
            'defaults' => array(
                'currency' => 'CNY',
            ),
        ),
        // 对账结果确认(API)
        'checkResultNotice' => array(
            'service' => 'p2p.merchant.check.result.notice',
            'required' => array(
                'billDate' => '账单日期',
                'bizType' => '账单类型',
                'billCheckResult' => '确认结果',
            ),
            'default' => array(
            ),
        ),
        //非绑定银行卡签约申请
        'noBindCardSign' =>array(
            'service' => 'p2p.trade.notbindsign.apply',
            'retry' => true,
            'required' => array(
                'userId' => '用户ID',
                'orderId' => '签约申请订单号',
                'realName' => '姓名',
                'certNo' => '身份证号',
                'bankCardNo' => '银行卡号',
                'mobile' => '银行预留手机号',
            ),
        ),
        //签约重发短信
        'signResendMessage' =>array(
            'service' => 'p2p.trade.sign.resend',
            'required' => array(
                'orderId' => '签约申请订单号',
            ),
        ),
        //签约确认
        'signConfirm' =>array(
            'service' => 'p2p.trade.sign.confirm',
            'retry' => true,
            'required' => array(
                'orderId' => '签约申请订单号',
                'smsCode' => '短信验证码',
            ),
        ),
        //非绑定银行卡信息签约查询
        'notBindSignQuery' =>array(
            'service' => 'p2p.trade.notbindsign.query',
            'retry' => true,
            'required' => array(
                'bankCardNo' => '银行卡号',
                'realName' => '真实姓名',
                'certNo' => '用户证件号',
                'mobileNo' => '银行预留手机号',
                'contractChannelId' => '签约渠道',
            ),
        ),
        // 放款后收费接口(API)
        'chargeFeeAfterGrant' => array(
            'service' => 'p2p.trade.bid.charge',
            'required' => array(
                'orderId' => '外部订单号',
                'userId' => '借款人账户id',
                'chargeOrderList' => '收费明细列表',
                'bidId' => '收费标的编号',
                'amount' => '收费金额',
                'totalNum' => '收费明细条数',
                'expireTime' => '关单时间',
            ),
            'default' => array(
            ),
       ),
    );
}
