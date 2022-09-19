<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserBankCardEnum extends AbstractEnum {
    const STATUS_UNBIND = 0; // 未绑卡
    const STATUS_BINDED = 1; // 已绑卡

    const VERIFY_STATUS_UNVALIDATE = 0; //未验卡
    const VERIFY_STATUS_VALIDATED = 1; // 已验卡
    const VERIFY_STATUS_VALIDATING = 2; // 验卡中

    const CARD_TYPE_PERSONAL = 0; // 对私卡
    const CARD_TYPE_BUSINESS = 1; // 对公卡


    public static $cert_status_map = array(
        'EXTERNAL_CERT'     => 1, //IVR语音认证
        'FASTPAY_CERT'      => 2, //快捷认证(四要素认证)
        'TRANSFER_CERT'     => 3, //转账认证
        'WHITELIST_CERT'    => 4, //白名单
        'REMIT_CERT'        => 5, //打款认证
        'ONLY_CARD'         => 6, //卡密认证
        'AUDIT_CERT'        => 7, //人工认证
        'NO_CERT'           => 8, //未认证
        'MER_WHIT_CERT'     => 9, // 商户白名单认证
        'INSIDE_CERT'       => 10, // 内部认证
    );
}
