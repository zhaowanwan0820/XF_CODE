<?php

namespace NCFGroup\Protos\Gold\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ErrorCode extends AbstractEnum {

    /** 系统相关 */
    const SUCCESS = '0';
    const MISS_PARAMETERS = '1001';
    const PARAMETERS_ERROR = '1002';
    const DB_UNKNOW_ERROR = '1003';
    const SERVICE_BUSY = '1004';

    /** 项目相关 */
    const PROJECT_NO_EXISTS = '1101';
    const PROJECT_NAME_EXISTS   = '1102';
    const PROJECT_ID_EMPTY = '1103';
    const PROJECT_IS_CLEANING = '1104';
    const PROJECT_SINGLE_MAX_LOAN_BEYOND = '1105'; //个人单笔最高金额
    const PROJECT_SINGLE_MIN_LOAN_BEYOND = '1106'; //个人单笔最低金额
    const PROJECT_LOAN_COUNT_BEYOND = '1107';      //个人笔数
    const PROJECT_SINGLE_ENTERPRISE_MAX_LOAN_BEYOND = '1108'; //企业单笔最高金额
    const PROJECT_SINGLE_ENTERPRISE_MIN_LOAN_BEYOND = '1109'; //企业单笔最低金额
    const PROJECT_ENTERPRISE_LOAN_COUNT_BEYOND = '1110';      //企业单笔


    // 标的相关
    const DEAL_NOT_EXISTS = '1501';
    const DEAL_BID_CLOSE = '1502';
    const DEAL_TIME_BEYOND = '1503';
    const DEAL_DAY_MONEY_BEYOND = '1504';
    const DEAL_BID_MIN_BEYOND = '1505';
    const DEAL_USER_MAX_BID_BEYOND = '1506';
    const DEAL_DAY_MONEY_LOCK_BEYOND = '1507';
    const DEAL_BID_UNIT_BEYOND = '1508';
    const DEAL_BID_MONEY_ERROR = '1509';
    const DEAL_UPDATE_ERROR = '1510';


    // 投资、赎回相关
    const DEAL_LOAN_NOT_EXISTS = '1901';
    const LOAN_USER_ID_NOT_MATCH = '1902';
    const REDEEM_TIME_BEYOND = '1903';
    const REDEEM_NOT_BEGININTEREST = '1904';
    const LOAN_STATUS_NOT_ALLOW_REDEEM = '1905';
    const DEAL_LOAN_TOKEN_NOT_FUOND = '1906';
    const REDEEM_MONEY_BEYOND = '1907';
    const INVEST_TIME_BEYOND = '1908';

    // 匹配相关
    const LOAN_MONEY_MUST_LESS_THAN_MAPPING = '1601';
    const SYNC_P2P_DEAL_EXISTS = '1602'; // 预约投标同步多投 标的已存在
    const MAPPING_IS_BUISY = '1603' ; // 匹配进行中
    const SYNC_P2P_DEAL_MONEY_INVALID = '1604' ; // 预约投标同步多投 标的金额无效

    //P2P还款相关
    const UPDATE_STATUS_REPAY_FAIL = '1701';//更新p2p标为还款状态失败
    const REPAY_ADD_JOBS_FAIL = '1702';//添加还款jobs失败
    const JOB_MOVE_GRAGMENTS_FAIL = '1703';//还款jobs移动还款数据到待匹配表失败
    const JOB_UPDATE_STATUS_REPAY_FAIL = '1704';//还款jobs更新还款数据为已还款状态失败

    //变现相关
    const WITHDRAW_NOT_EXISTS = '1801';
    const WITHDRAW_GOLD_NOT_ENOUGH = '1802';
    const WITHDRAW_MAX_GOLD_LIMIT = '1803';
    const WITHDRAW_APPLY_FAIL = '1804';
    const WITHDRAW_PROCESS_FAIL = '1805';
    const WITHDRAW_UPDATE_STATUS_FAIL = '1806';
    const WITHDRAW_SERVICE_BUSY = '1807';
    const WITHDRAW_GOLD_PRICE_OUT_RANGE = '1808';

    // 查询订单相关
    const ORDER_BIZ_SUBTYPE_ERROR = '2001'; // 业务子类型不存在
    const ORDER_TRANSFER_ERROR = '2002'; // 查询订单类型错误
    const ORDER_NOT_EXISTS = '2003'; // 订单不存在
    const ORDER_BIZ_SUBTYPE_TRANSFER_ERROR = '2004'; // 业务子类型和转账类型不匹配

    public static $errMsg = array(
        self::SUCCESS => 'success',
        self::MISS_PARAMETERS => 'Miss parameters %s!',
        self::DB_UNKNOW_ERROR => 'Database unknow error',
        self::PARAMETERS_ERROR => 'Parameters error',
        self::SERVICE_BUSY => '当前购买人数过多，请您稍后重试',

        self::PROJECT_NAME_EXISTS => '项目名称已存在',
        self::PROJECT_IS_CLEANING => '项目清盘中不允许该操作',
        self::PROJECT_SINGLE_MAX_LOAN_BEYOND => '超出项目单笔投资限额',
        self::PROJECT_SINGLE_MIN_LOAN_BEYOND => '低于项目单笔投资限额',
        self::PROJECT_LOAN_COUNT_BEYOND => '超出个人投资笔数限制',
        self::PROJECT_SINGLE_ENTERPRISE_MAX_LOAN_BEYOND => '超出项目单笔投资限额',
        self::PROJECT_SINGLE_ENTERPRISE_MIN_LOAN_BEYOND => '低于项目单笔投资限额',
        self::PROJECT_ENTERPRISE_LOAN_COUNT_BEYOND => '超出企业投资笔数限制',

        self::DEAL_NOT_EXISTS => '标的%s信息不存在',
        self::DEAL_BID_CLOSE => '该标不在投标状态',
        self::DEAL_TIME_BEYOND => '标的%s不在投标时间范围内',
        self::DEAL_DAY_MONEY_BEYOND => '标的%s每日加入限额达到最大值:%s',
        self::DEAL_DAY_MONEY_LOCK_BEYOND => '投资金额超出项目可投金额,剩余可投金额为%s元',
        self::DEAL_BID_MIN_BEYOND => '投资金额%s不能小于最小投资金额%s',
        self::DEAL_BID_UNIT_BEYOND => '投资金额必须是%s元的整数倍',
        self::DEAL_USER_MAX_BID_BEYOND => '超出单账户投资限额',
        self::DEAL_BID_MONEY_ERROR => '投资金额不合法',

        self::REDEEM_NOT_BEGININTEREST => '投资尚未计息不能发起转让',
        self::DEAL_LOAN_NOT_EXISTS => '投资记录不存在',
        self::LOAN_USER_ID_NOT_MATCH => '投资记录与用户不匹配',
        self::REDEEM_TIME_BEYOND => '请您在每日%s-%s进行转让。谢谢您的谅解!',
        self::LOAN_STATUS_NOT_ALLOW_REDEEM => '当前状态不允许发起转让',
        self::DEAL_LOAN_TOKEN_NOT_FUOND => '当前token不存在token:%s',
        self::REDEEM_MONEY_BEYOND => '超出当日转让限额',
        self::INVEST_TIME_BEYOND => '请您在每日%s-%s进行投资。谢谢您的谅解!',

        self::LOAN_MONEY_MUST_LESS_THAN_MAPPING => '未匹配金额不得大于投资金额',
        self::SYNC_P2P_DEAL_EXISTS => '预约投标标的已存在',
        self::DEAL_UPDATE_ERROR => '标的更新失败',
        self::MAPPING_IS_BUISY => '当前不允许进行此操作',
        self::SYNC_P2P_DEAL_MONEY_INVALID => '预约投标标的金额无效',

        self::UPDATE_STATUS_REPAY_FAIL => '更新p2p标为还款状态失败',
        self::REPAY_ADD_JOBS_FAIL => '添加还款jobs失败',
        self::JOB_MOVE_GRAGMENTS_FAIL => '还款jobs移动还款数据到待匹配表失败',
        self::JOB_UPDATE_STATUS_REPAY_FAIL => '还款jobs更新还款数据为已还款状态失败',

        self::WITHDRAW_NOT_EXISTS => '变现单不存在',
        self::WITHDRAW_GOLD_NOT_ENOUGH => '可变现黄金不足',
        self::WITHDRAW_MAX_GOLD_LIMIT => '超出当日最大变现克重',
        self::WITHDRAW_APPLY_FAIL => '申请变现失败',
        self::WITHDRAW_PROCESS_FAIL => '变现处理失败',
        self::WITHDRAW_UPDATE_STATUS_FAIL => '变现更新状态失败',
        self::WITHDRAW_SERVICE_BUSY => '交易暂停中，请稍后再试',
        self::WITHDRAW_GOLD_PRICE_OUT_RANGE => '黄金价格超过波动范围，购买失败',
        self::ORDER_BIZ_SUBTYPE_ERROR => '订单业务子类型错误',
        self::ORDER_TRANSFER_ERROR => '查询订单业类型错误',
        self::ORDER_NOT_EXISTS => '订单不存在',
        self::ORDER_BIZ_SUBTYPE_TRANSFER_ERROR => '业务子类型和转账类型不匹配',

    );
}
