<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserEnum extends AbstractEnum {

    const USER_FIELDS = 'id,user_name,real_name,invite_code,email,idno,create_time,country_code,mobile_code,mobile,idcardpassed,user_purpose,group_id,id_type,user_type,supervision_user_id,payment_user_id,is_effect,mobilepassed,byear,bmonth,bday,force_new_passwd,is_dflh,sex,mobiletruepassed,is_delete';

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

    /*存管升级用户TAG*/
    const SV_UPGRADE_USER = 'SV_UPGRADE_USER';

    // 用户类型-普通用户
    const USER_TYPE_NORMAL = 0;

    //用户类型-企业用户
    const USER_TYPE_ENTERPRISE = 1;

    //用户类型名称-个人用户
    const USER_TYPE_NORMAL_NAME = '个人用户';

    //用户类型名称-企业用户
    const USER_TYPE_ENTERPRISE_NAME = '企业用户';

    // ------------------------ user 表中字段 -----------------------
    // user表中字段名称 - real_name
    const TABLE_FIELD_REAL_NAME = 'real_name';

    //user表中字段名称 - mobile
    const TABLE_FIELD_MOBILE = 'mobile';

    const MSG_FOR_USER_ACCOUNT_TITLE = ''; // 在短信中对个人用户的title（为适应企业会员的短信信息）

    const ACCOUNT_FREEZE_KEY = 'account_freeze_key_';   // 账户冻结的键值
    const ACCOUNT_FREEZE_TIME = 86400;                  // 账户冻结的时间

    const PHOTO_STATUS_DEFAULT = 0;
    const PHOTO_STATUS_PASS = 1;
    const PHOTO_STATUS_REJECT = 2;
    const PHOTO_STATUS_INIT = 3;

    const EMPTY_ERROR = 1; // 未填写的错误
    const FORMAT_ERROR = 2; // 格式错误
    const EXIST_ERROR = 3; // 已存在的错误
    const IDNO_ERROR = 4; // 身份号认证失败
    const IDNO_LINK_ERROR = 5; // 连接失败或其他错误

    const ACCOUNT_NO_EXIST_ERROR = 1; // 帐户不存在
    const ACCOUNT_PASSWORD_ERROR = 2; // 帐户密码错误
    const ACCOUNT_NO_VERIFY_ERROR = 3; // 帐户未激活

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

    /**
     * 企业用户-公司证件类型
     * @see conf/dictionary.conf.php里面的CREDENTIALS_TYPE
     * @var array
     */
    public static $credentialsType = array(
        1 => 'BLC', // 营业执照
        2 => 'ORC', // 组织机构代码证
        3 => 'USCC', // 统一社会信用代码/三证合一营业执照
        0 => 'RTC', // 其他企业证件
        'default' => 'BLC', // 默认
    );

    /**
     * 企业用户-法人证件类型
     * @see conf/dictionary.conf.php里面的ID_TYPE
     * @var array
    */
    public static $idCardType = array(
        1 => 'IDC', // 身份证
        4 => 'GAT', // 港澳居民来往内地通行证/港澳台身份证
        6 => 'GAT', // 台湾居民往来大陆通行证/港澳台身份证
        2 => 'PASS_PORT', // 护照
        3 => 'MILIARY', // 军官证
        'default' => 'IDC', // 默认
    );

    /**
     * 需要标记删除的log_info
     * @var array
    */
    public static $userLogMarkDelete = array(
        '智多鑫-转入本金解冻',
        '智多鑫-本金回款并冻结',
        '智多鑫-债权出让',
        '智多鑫-债权出让本金回款并冻结',
    );

    /**
     * 头像照片映射数组配置
     * @var array
    */
    public static $PHOTO_STATUS = array(
        self::PHOTO_STATUS_DEFAULT => '照片未上传',
        self::PHOTO_STATUS_INIT => '照片审核中',
        self::PHOTO_STATUS_PASS => '照片审核通过',
        self::PHOTO_STATUS_REJECT => '照片审核拒绝',
    );
}