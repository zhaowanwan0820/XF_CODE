<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class SupervisionEnum extends AbstractEnum
{
    const RESPONSE_CODE_SUCCESS = '00';
    const RESPONSE_CODE_FAILURE = '01';
    const RESPONSE_CODE_PROCESSING = '02';

    const RESPONSE_SUCCESS = 'S';
    const RESPONSE_FAILURE = 'F';
    const RESPONSE_PROCESSING = 'I';

    const NOTICE_SUCCESS = 'S';
    const NOTICE_FAILURE = 'F';
    const NOTICE_PROCESSING = 'I';
    const NOTICE_CANCEL = 'C'; //取消状态，对账使用

    const CHARGE_PENDING = 'I';
    const CHARGE_FAILURE = 'F';
    const CHARGE_SUCCESS = 'S';

    const WITHDRAW_PROCESSING = 'AS';
    const WITHDRAW_FAILURE = 'F';
    const WITHDRAW_SUCCESS = 'S';

    const BATCHORDER_TYPE_GRANT = '5000'; // 放款
    const BATCHORDER_TYPE_REPAY = '7000'; // 还款
    const BATCHORDER_TYPE_DEALCANCEL = '3100'; // 流标
    const BATCHORDER_TYPE_BENIFIT = '8000'; // 返利红包收费

    // 批量转账接口业务类型
    const BATCHTRANSFER_BENIFIT = 8001; // 返利
    const BATCHTRANSFER_CHARGE  = 8002; // 收费

    const BATCHTRANSFER_PROCESS = 'I';
    const BATCHTRANSFER_SUCCESS = 'S';
    const BATCHTRANSFER_FAILURE = 'F';
    const BATCHTRANSFER_CANCEL  = 'C';

    const BATCHTRANSFER_STATUS_INIT = 0;
    const BATCHTRANSFER_STATUS_PROCESS = 1;
    const BATCHTRANSFER_STATUS_SUCCESS = 2;
    const BATCHTRANSFER_STATUS_FAILURE = 3;
    const BATCHTRANSFER_STATUS_CANCEL  = 4;

    static $batchTransferStatusMap = [
        self::BATCHTRANSFER_STATUS_INIT     => '未处理',
        self::BATCHTRANSFER_STATUS_PROCESS  => '处理中',
        self::BATCHTRANSFER_STATUS_SUCCESS  => '成功',
        self::BATCHTRANSFER_STATUS_FAILURE  => '失败',
        self::BATCHTRANSFER_STATUS_CANCEL   => '取消',
    ];

    static $batchTransferMap = [
        self::BATCHTRANSFER_PROCESS => self::BATCHTRANSFER_STATUS_PROCESS,
        self::BATCHTRANSFER_SUCCESS => self::BATCHTRANSFER_STATUS_SUCCESS,
        self::BATCHTRANSFER_FAILURE => self::BATCHTRANSFER_STATUS_FAILURE,
        self::BATCHTRANSFER_CANCEL  => self::BATCHTRANSFER_STATUS_CANCEL,
    ];

    // 订单号已存在
    const CODE_ORDER_EXIST = '200103';

    // 服务器繁忙，请稍后再试
    const CODE_SV_SERVER_BUSY = '500000';

    // 支付通道-网贷账户
    const CHARGE_TYPE_NCFPH = 'BCL';
    // 支付通道-先锋支付
    const CHARGE_TYPE_UCFPAY = 'XFZF';
    // 支付通道-易宝支付
    const CHARGE_TYPE_YEEPAY = 'YEEPAY';
    // 支付通道映射配置
    public static $chargeTypeConfig = [
        self::CHARGE_QUICK_CHANNEL => self::CHARGE_TYPE_UCFPAY,
        self::CHARGE_YEEPAY_CHANNEL => self::CHARGE_TYPE_YEEPAY,
        self::CHARGE_NCFPH_CHANNEL => self::CHARGE_TYPE_NCFPH,
    ];

    // 支付通道-网贷限额
    const CHARGE_NCFPH_CHANNEL = 'UCF_PAY';
    // 支付通道-先锋支付
    const CHARGE_QUICK_CHANNEL = 'XFZF_PAY';
    // 支付通道-易宝支付
    const CHARGE_YEEPAY_CHANNEL = 'YEEPAY_PAY';
    // 支付通道映射配置
    public static $chargeChannelConfig = [
        self::CHARGE_QUICK_CHANNEL => '先锋支付',
        self::CHARGE_YEEPAY_CHANNEL => '易宝支付',
        self::CHARGE_NCFPH_CHANNEL => '网贷限额',
    ];

    const CARD_TYPE_DEBIT = 3;//银行卡类型 3借记卡
    const CARD_FLAG_PUB = 1;//银行卡标识 对公
    const CARD_FLAG_PRI = 2;//银行卡标识 对私

    // 提现状态
    const WITHDRAW_STATUS_NORMAL = 0; // 未处理
    const WITHDRAW_STATUS_SUCCESS = 1; // 成功
    const WITHDRAW_STATUS_FAILED = 2; // 失败
    const WITHDRAW_STATUS_PROCESS = 3; // 处理中
    const WITHDRAW_STATUS_INQUEUE = 4; // 自动处理队列

    //终态状态集合
    public static $finalStatus = [self::WITHDRAW_STATUS_SUCCESS, self::WITHDRAW_STATUS_FAILED];

    //提现业务类型
    const TYPE_TO_BANKCARD = 0; //提现至银行卡
    const TYPE_TO_CREDIT_ELEC_ACCOUNT = 1; //提现至银信通电子账户
    const TYPE_ENTRUSTED = 2; //受托提现
    const TYPE_LOCKMONEY = 3; //需要冻结用户资金的提现
    const TYPE_LIMIT_WITHDRAW = 4; //  可提现额度限制提现的提现单
    const TYPE_LIMIT_WITHDRAW_BLACKLIST = 5; //  投资户限制提现的提现单

    public static $withdrawDesc = [
        self::WITHDRAW_STATUS_NORMAL => '未处理',
        self::WITHDRAW_STATUS_PROCESS => '处理中',
        self::WITHDRAW_STATUS_SUCCESS => '提现成功',
        self::WITHDRAW_STATUS_FAILED => '提现失败',
        //self::WITHDRAW_STATUS_INQUEUE => '自动队列',
    ];

    // 支付状态
    const PAY_STATUS_NORMAL = 0; // 未处理
    const PAY_STATUS_SUCCESS = 1; // 成功
    const PAY_STATUS_FAILURE = 2; // 失败
    const PAY_STATUS_PROCESS = 3; // 处理中

    static $statusMap = [
        'I' => self::PAY_STATUS_NORMAL,
        'S' => self::PAY_STATUS_SUCCESS,
        'F' => self::PAY_STATUS_FAILURE,
        'AS' => self::PAY_STATUS_PROCESS,
    ];

    //还代偿款相关
    const RETURNREPAY_PROCESS = 'I';
    const RETURNREPAY_SUCCESS = 'S';
    const RETURNREPAY_FAILURE = 'F';
    const RETURNREPAY_CANCEL  = 'C';

    const RETURNREPAY_STATUS_INIT = 0;
    const RETURNREPAY_STATUS_PROCESS = 1;
    const RETURNREPAY_STATUS_SUCCESS = 2;
    const RETURNREPAY_STATUS_FAILURE = 3;
    const RETURNREPAY_STATUS_CANCEL  = 4;

    static $returnRepayStatusMap = [
        self::RETURNREPAY_STATUS_INIT     => '未处理',
        self::RETURNREPAY_STATUS_PROCESS  => '处理中',
        self::RETURNREPAY_STATUS_SUCCESS  => '成功',
        self::RETURNREPAY_STATUS_FAILURE  => '失败',
        self::RETURNREPAY_STATUS_CANCEL   => '取消',
    ];

    static $returnRepayMap = [
        self::RETURNREPAY_PROCESS => self::RETURNREPAY_STATUS_PROCESS,
        self::RETURNREPAY_SUCCESS => self::RETURNREPAY_STATUS_SUCCESS,
        self::RETURNREPAY_FAILURE => self::RETURNREPAY_STATUS_FAILURE,
        self::RETURNREPAY_CANCEL  => self::RETURNREPAY_STATUS_CANCEL,
    ];

    // 审核状态
    const STATUS_NOT_AUDIT = 0; // A角色待审核
    const STATUS_A_PASS = 1; // B角色待审核
    const STATUS_B_PASS = 2; // B角色通过
    const STATUS_B_REFUND = 3; // B角色拒绝
    const STATUS_SYS_AUTO = 4; // 系统自动处理

    public static $auditStatusDesc = [
        self::STATUS_NOT_AUDIT => 'A角色待审核',
        self::STATUS_A_PASS => 'B角色待审核',
        self::STATUS_B_PASS => 'B角色审核通过',
        self::STATUS_B_REFUND => 'B角色拒绝',
        self::STATUS_SYS_AUTO => '系统自动处理',
    ];

    // 大额充值-订单状态
    public static $offlineOrderStatusMap = [
        'I' => '处理中',
        'S' => '成功',
        'F' => '失败',
    ];

    // 大额充值-流水状态
    public static $offlineRecordsStatusMap = [
        'I'  => '未匹配',
        'S'  => '匹配成功',
        'RS' => '退款',
    ];
}
