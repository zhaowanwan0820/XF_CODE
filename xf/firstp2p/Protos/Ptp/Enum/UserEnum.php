<?php
namespace NCFGroup\Protos\Ptp\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserEnum extends AbstractEnum {

    /**
     * 增加余额
     * @var int
     */
    const TYPE_MONEY = 0;

    /**
     * 冻结金额，增加冻结资金同时减少余额
     * @var int
     */
    const TYPE_LOCK_MONEY = 1;

    /**
     * 减少冻结金额
     * @var int
     */
    const TYPE_DEDUCT_LOCK_MONEY = 2;

    //通用-错误码
    /**
     * 通用-业务受理成功
     * @var string
     */
    const ERROR_COMMON_SUCCESS = '00';

    /**
     * 通用-业务受理失败
     * @var string
     */
    const ERROR_COMMON_FAILED = '01';

    //异步通知-错误码
    /**
     * 异步通知-付款单状态 N（初始状态）
     * @var string
     */
    const ERROR_ASYNC_NOTIFY_N = 'N';

    /**
     * 异步通知-付款单状态 I（推送中）
     * @var string
     */
    const ERROR_ASYNC_NOTIFY_ING = 'I';

    //异步通知-错误码
    /**
     * 异步通知-付款单状态S(成功)
     * @var string
     */
    const ERROR_ASYNC_NOTIFY_SUCC = 'S';

    //异步通知-错误码
    /**
     * 异步通知-付款单状态F(失败)
     * @var string
     */
    const ERROR_ASYNC_NOTIFY_FAILED = 'F';

    //第三方交互项目-错误码
    /**
     * 第三方交互项目-商户ID参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_MERCHANTID = '02';

    /**
     * 第三方交互项目-用户ID参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_USERID = '03';

    /**
     * 第三方交互项目-第三方项目发起人ID参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_RECEIVERID = '04';

    /**
     * 第三方交互项目-用户认筹金额参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_AMOUNT = '05';

    /**
     * 第三方交互项目-第三方付款单号参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_OUTORDERID = '06';

    /**
     * 第三方交互项目-交易事由参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_CASE = '07';

    /**
     * 第三方交互项目-投资失败
     * @var string
     */
    const ERROR_THIRD_PARTY_INVEST_FAILED = '08';

    /**
     * 第三方交互项目-同一付款单号不能重复投资
     * @var string
     */
    const ERROR_THIRD_PARTY_INVEST_REPEAT = '09';

    /**
     * 第三方交互项目-投资异常
     * @var string
     */
    const ERROR_THIRD_PARTY_EXCEPTION = '10';

    /**
     * 第三方交互项目-录入投资数据失败
     * @var string
     */
    const ERROR_THIRD_PARTY_INVEST_INSERT_FAILED = '11';

    /**
     * 第三方交互项目-用户ID不能与项目发起人ID相同
     * @var string
     */
    const ERROR_THIRD_PARTY_USER_SAME = '20';

    /**
     * 第三方交互项目-录入第三方消费数据失败
     * @var string
     */
    const ERROR_THIRD_PARTY_TRANSFERLOG_FAILED = '21';

    /**
     * 第三方交互项目-商户编号参数错误
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_MERCHANTNO = '22';

    /**
     * 第三方交互项目-第三方订单号、开始结束时间戳参数不能为空
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_EMPTY = '23';

    /**
     * 第三方交互项目-第三方订单号、开始结束时间戳参数不能同时为空
     * @var string
     */
    const ERROR_THIRD_PARTY_PARAM_EMPTY_SAME = '24';

    //资金记录-错误码
    /**
     * 资金记录服务化-用户余额更新成功
     * @var string
     */
    const ERROR_USER_MONEY_SUCCESS = '12';

    /**
     * 资金记录服务化-用户余额更新失败
     * @var string
     */
    const ERROR_USER_MONEY_FAILED = '13';

    /**
     * 资金记录服务化-记录用户资金日志成功
     * @var string
     */
    const ERROR_USER_MONEY_LOG_SUCCESS = '14';

    /**
     * 资金记录服务化-记录用户资金日志失败
     * @var string
     */
    const ERROR_USER_MONEY_LOG_FAILED = '15';

    /**
     * 资金记录服务化-冻结用户余额成功
     * @var string
     */
    const ERROR_LOCK_MONEY_SUCCESS = '16';

    /**
     * 资金记录服务化-冻结用户余额失败
     * @var string
     */
    const ERROR_LOCK_MONEY_FAILED = '17';

    /**
     * 资金记录服务化-解冻用户余额失败
     * @var string
     */
    const ERROR_UNLOCK_MONEY_SUCCESS = '18';

    /**
     * 资金记录服务化-解冻用户余额失败
     * @var string
     */
    const ERROR_UNLOCK_MONEY_FAILED = '19';

    // RPC-错误码
    const RPC_P2P_LOCK_MONEY_FAILED = 1601;
    const RPC_P2P_MONEY_CALL_FAILED = 1602;

    //存管未激活用户Tag
    const SV_UNACTIVATED_USER = 'SV_UNACTIVATED_USER';

    // 错误信息
    public static $ERROR_MSG = array(
        self::ERROR_COMMON_SUCCESS => '业务受理成功',
        self::ERROR_COMMON_FAILED => '业务受理失败',
        self::ERROR_THIRD_PARTY_PARAM_MERCHANTID => '商户ID参数错误',
        self::ERROR_THIRD_PARTY_PARAM_USERID => '用户ID参数错误',
        self::ERROR_THIRD_PARTY_PARAM_RECEIVERID => '第三方项目发起人ID参数错误',
        self::ERROR_THIRD_PARTY_PARAM_AMOUNT => '用户认筹金额参数错误',
        self::ERROR_THIRD_PARTY_PARAM_OUTORDERID => '第三方付款单号参数错误',
        self::ERROR_THIRD_PARTY_PARAM_CASE => '交易事由参数错误',
        self::ERROR_THIRD_PARTY_INVEST_FAILED => '投资失败',
        self::ERROR_THIRD_PARTY_INVEST_REPEAT => '同一付款单号不能重复投资',
        self::ERROR_THIRD_PARTY_EXCEPTION => '系统繁忙,请稍后重试',
        self::ERROR_THIRD_PARTY_INVEST_INSERT_FAILED => '录入投资数据失败',
        self::ERROR_THIRD_PARTY_USER_SAME => '用户ID不能与项目发起人ID相同',
        self::ERROR_THIRD_PARTY_TRANSFERLOG_FAILED => '录入第三方消费数据失败',
        self::ERROR_THIRD_PARTY_PARAM_MERCHANTNO => '商户编号参数错误',
        self::ERROR_THIRD_PARTY_PARAM_EMPTY => '%s参数不能为空或为0',
        self::ERROR_THIRD_PARTY_PARAM_EMPTY_SAME => '%s参数不能同时为空或为0',
        self::ERROR_USER_MONEY_SUCCESS => '用户余额更新成功',
        self::ERROR_USER_MONEY_FAILED => '用户余额更新失败',
        self::ERROR_USER_MONEY_LOG_SUCCESS => '记录用户资金日志成功',
        self::ERROR_USER_MONEY_LOG_FAILED => '记录用户资金日志失败',
        self::ERROR_LOCK_MONEY_SUCCESS => '冻结用户余额成功',
        self::ERROR_LOCK_MONEY_FAILED => '冻结用户余额失败',
        self::ERROR_UNLOCK_MONEY_SUCCESS => '解冻用户余额成功',
        self::ERROR_UNLOCK_MONEY_FAILED => '解冻用户余额失败',
        self::RPC_P2P_LOCK_MONEY_FAILED => 'P2P冻结余额失败！',
        self::RPC_P2P_MONEY_CALL_FAILED => 'P2P调用余额失败！',
    );


}
