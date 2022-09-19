<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class PaymentConfigEnum extends AbstractEnum {
    // {{{ common 通用配置
    const COMMON_VERSION = '1.0.0';
    // }}}

    // {{{ ucfpay 先锋支付配置
    const UCFPAY_REQUEST_SOURCE_APPWAP = 'APP';
    const UCFPAY_REQUEST_SOURCE_APP_IOS = 11;
    const UCFPAY_REQUEST_SOURCE_APP_ANDROID = 12;

    const UCFPAY_REQUEST_SOURCE_PC= 'PC';
    const UCFPAY_REQUEST_SOURCE_WAP_IOS = 21;
    const UCFPAY_REQUEST_SOURCE_WAP_ANDROID = 22;

    // }}}

}
