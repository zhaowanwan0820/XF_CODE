<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserCarryEnum extends AbstractEnum {
    // 放款状态
    const STATUS_ACCOUNTANT_PASS = 3; // 会计通过

    const WITHDRAW_STATUS_CREATE = 0; // 未处理
    const WITHDRAW_STATUS_SUCCESS = 1; // 提现成功
    const WITHDRAW_STATUS_FAILED = 2; //提现失败
    const WITHDRAW_STATUS_PROCESS = 3; // 处理中
    const WITHDRAW_STATUS_PAY_PROCESS = 4; // 支付已处理

    static $withdrawDesc = array(
        self::WITHDRAW_STATUS_CREATE => '未处理',
        self::WITHDRAW_STATUS_PROCESS => '处理中',
        self::WITHDRAW_STATUS_SUCCESS => '提现成功',
        self::WITHDRAW_STATUS_FAILED => '提现失败',
        self::WITHDRAW_STATUS_PAY_PROCESS => '银行处理中'
    );

    // todo 这个小额提现警告信息已经废弃 下个版本可以移除
    // 小额提现警告信息和对应值
    const WARNING_SAME_CARRY = 2;
    const WARNING_TWO_CARRY = 4;
    const WARNING_NO_CHARGE = 8;
    const WARNING_NAME_INCONSISTENT = 16;
    const WARNING_MONEY_OVER_LIMIT = 32;
    const WARNING_NO_DEAL = 64;

    static $warningMap = array (
        self::WARNING_SAME_CARRY  => '上次提现与本次提现金额一致',
        self::WARNING_TWO_CARRY  => '过去24小时内出现两次提现',
        self::WARNING_NO_CHARGE  => '交易记录中，无充值，直接提现',
        self::WARNING_NAME_INCONSISTENT => '提现人姓名，银行卡户名与身份证信息不一致',
        self::WARNING_MONEY_OVER_LIMIT => '金额大于%d',
        self::WARNING_NO_DEAL => '第一次充值后无投资，直接提现',
    );

    /**
     * 提现是否被风控延迟处理-正常
     * @var int
    */
    const WITHDRAW_IS_NORMAL = 0;
    /**
     * 提现是否被风控延迟处理-延迟
     * @var int
     */
    const WITHDRAW_IS_DELAY = 1;

    /**
     * 提现延迟配置
     * @var array
     */
    public static $withdrawDelayConfig = array(
        'payTime' => 86400, // 24小时内有充值记录
        'withdrawDelayTime' => 86400, // 符合风控规则，24小时后再发起提现请求
        'withdrawMoney' => 500, // 单笔提现金额大于等于500元
    );
}