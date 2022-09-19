<?php

namespace openapi\conf;

class ConstDefine {

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
}