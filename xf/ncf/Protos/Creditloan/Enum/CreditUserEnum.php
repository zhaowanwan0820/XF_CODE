<?php
namespace NCFGroup\Protos\Creditloan\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CreditUserEnum extends AbstractEnum {


    // 账户状态
    const ACCOUNT_STATUS_NORMAL = 1; // 正常
    const ACCOUNT_STATUS_DISABLE = 2; // 停用

    //账户开通状态
    const CREDIT_STATUS_SIGNED = 1; //已签署服务协议
    const CREDIT_STATUS_PROCESSING = 2; //授信审批中
    const CREDIT_STATUS_SUCCESS = 3; //授信通过
    const CREDIT_STATUS_FAILURE = 4; //授信失败

    //账户状态描述映射
    public static $accountStatusDesMap = [
        self::ACCOUNT_STATUS_NORMAL => '正常',
        self::ACCOUNT_STATUS_DISABLE => '停用',
    ];

    //即富状态映射
    public static $creditStatusJfpayMap = [
        'PROCESSING' => self::CREDIT_STATUS_PROCESSING,
        'SUC' => self::CREDIT_STATUS_SUCCESS,
        'FAIL' => self::CREDIT_STATUS_FAILURE,
    ];

    //账户开通状态描述映射
    public static $creditStatusDesMap = [
        self::CREDIT_STATUS_SIGNED => '已签署服务协议',
        self::CREDIT_STATUS_PROCESSING => '授信审批中',
        self::CREDIT_STATUS_SUCCESS => '授信通过',
        self::CREDIT_STATUS_FAILURE => '授信失败',
    ];

}
