<?php

namespace openapi\conf;

class ConstDefine {

    static $_OPEN_LOGIN_TPL_CONF = array(
        '7b9bd46617b3f47950687351' => array(
            'register' => 'openapi/views/user/register.html',
            'login' => 'openapi/views/user/login.html',
        ),
    );

    static $_CARD_TYPES = array('对私'=> 0,'对公'=>1);

    const REGISTER_DEFAULT = 'openapi/views/user/register.html';
    const LOGIN_DEFAULT = 'openapi/views/user/login.html';
    const SIGNATURE_KEY = 'signature';
    const XFZF_BANK_ID = 'CMBCHINA';
    const XFZF_BUSINESS_TYPE = 16;
    const RESULT_SUCCESS = '00';
    const RESULT_FAILURE = '01';

    const LOAN_LIMIT_PER = 200000; //个人借款上限20w
    const LOAN_LIMIT_PER_TOTAL = 1000000; //个人跨平台借款上限100w

    const LOAN_LIMIT_ENT = 1000000; //企业借款上限100w
    const LOAN_LIMIT_ENT_TOTAL = 5000000; //企业跨平台借款上限500w

    //const XFZF_SEC_KEY = '/trBxSVzokD9vJSY/Fcj6w';
    //const XFZF_AES_KEY = '/trBxSVzokD9vJSY/Fcj6w';
    //const XFZF_PAY_CALLBACK = "http://api.firstp2p.com/account/payNotify";
    //const XFZF_PAY_CREATE = "http://10.20.15.170/ucfpay/p2pOperate/p2pCreateOrder";
    //const XFZF_PAY_CHECK = "http://10.20.15.170/ucfpay/p2pOperate/p2pQueryRechargeResult";
}
