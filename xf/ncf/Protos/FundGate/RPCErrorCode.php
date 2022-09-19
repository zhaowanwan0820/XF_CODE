<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractErrorCodeBase;
use NCFGroup\Common\Extensions\Enum\EnumExceptionLevel;

class RPCErrorCode extends AbstractErrorCodeBase
{
    //维护期间异常
    const MAINTENANCE_EXCEPTION = 800;

    //model保存异常
    const MODEL_SAVE_EXCEPTION = 900;
    const DUPLICATE_SUBMIT = 901;

    // 业务工程请从1000开始，[0, 1000) 保留给框架层。
    const USER_NOT_EXIST = 1200;
    const USER_GET_PURCHASED_LIST_ERROR      = 1201;
    const USER_GET_HISTORY_ORDERS_LIST_ERROR = 1202;
    const USER_TRADE_ACCOUNT_NO_CREATE = 1203;
    const USER_NOT_CERTIFIED = 1204;
    const USER_LOGIN_FAILED = 1205;
    const USER_LOGIN_TIMES_OVER_LIMIT = 1206;
    const USER_FUND_TRADE_ACCOUNT_NOT_EXIST = 1207;//基金账号不存在或交易账号不存在或已销户

    //fund error code begin with 1300
    const FUND_NOT_EXIST    = 1300;
    const FUND_REDEEM_ERROR = 1301;
    const FUND_MIN_SHARE = 1302;
    const FUND_LEAST_REDEEM = 1303;
    const FUND_MOST_REDEEM = 1304;
    const FUND_USABLE_LESS_LEAST_REDEEM = 1305;

    // Order error code, begin with 1400
    const ORDER_PURCHASE_DB_ERROR        = 1401;
    const ORDER_NOT_EXIST_OR_ALREAD_DONE = 1402;
    const ORDER_RISK_CONFIRM_FAILED      = 1403;
    const ORDER_LEAST_PURCHASE_ERROR     = 1404;
    const ORDER_MOST_PURCHASE_ERROR      = 1405;
    const ORDER_SUPPLY_PURCHASE_ERROR    = 1406;
    const ORDER_FUND_NOT_ONLINE          = 1407;

    // UCFPAY ERROR code
    const NCFPAY_QUERY_FUND_USERINFO_ERROR = 1501;
    const UCFPAY_PWD_NOT_VERIFIED = 1502;

    // RPC
    const RPC_P2P_LOCK_MONEY_FAILED = 1601;
    const RPC_P2P_MONEY_CALL_FAILED = 1602;
    const RPC_UNION_FUND_CALL_FAILED = 1603;

    //基金开户成功的提示
    const FUND_ACCOUNT_SUCCESSFULL = 50000;
    //用户风险等级不匹配的提示
    const FUND_USER_RISK_NOT_MATCH = 50001;
    //未签署合格投资者认定协议
    const FUND_USER_NOT_SIGN_QUALIFIED_INVESTMENT = 50002;
    //用户风险等级已过期的提示
    const FUND_USER_RISK_ABILITY_EXPIRE = 50003;
   public static function initErrorInfo()
    {
        self::addToMapping(static::MAINTENANCE_EXCEPTION, array(EnumExceptionLevel::NONE, "系统正在维护"));

        self::addToMapping(static::MODEL_SAVE_EXCEPTION, array(EnumExceptionLevel::ERROR, "model save error！"));
        self::addToMapping(static::DUPLICATE_SUBMIT, array(EnumExceptionLevel::WARNING, "请求已经受理，请勿重复提交"));

        self::addToMapping(static::USER_NOT_EXIST, array(EnumExceptionLevel::ERROR, "该用户不存在！"));
        self::addToMapping(static::USER_GET_PURCHASED_LIST_ERROR, array(EnumExceptionLevel::ERROR, "获取用户购买基金列表失败！"));
        self::addToMapping(static::USER_GET_HISTORY_ORDERS_LIST_ERROR, array(EnumExceptionLevel::ERROR, "获取用户交易记录列表失败！"));
        self::addToMapping(static::USER_TRADE_ACCOUNT_NO_CREATE, array(EnumExceptionLevel::ERROR, "基金账户未被创建！"));
        self::addToMapping(static::USER_NOT_CERTIFIED, array(EnumExceptionLevel::WARNING, "账户未通过身份证验证"));
        self::addToMapping(static::USER_LOGIN_FAILED, array(EnumExceptionLevel::ERROR, "用户登陆失败"));
        self::addToMapping(static::USER_LOGIN_TIMES_OVER_LIMIT, array(EnumExceptionLevel::WARNING, "用户尝试登陆次数超限"));
        self::addToMapping(static::USER_FUND_TRADE_ACCOUNT_NOT_EXIST,array(EnumExceptionLevel::WARNING,"基金账号不存在或已销户"));

        self::addToMapping(static::FUND_NOT_EXIST, array(EnumExceptionLevel::ERROR, "基金不存在！"));
        self::addToMapping(static::FUND_REDEEM_ERROR, array(EnumExceptionLevel::ERROR, "赎回错误！"));
        self::addToMapping(static::FUND_MIN_SHARE, array(EnumExceptionLevel::ERROR, "您赎回后剩余份额低于最小可持有份额%s份，请全部赎回。"));
        self::addToMapping(static::FUND_LEAST_REDEEM, array(EnumExceptionLevel::ERROR, "最小赎回份额为%s份"));
        self::addToMapping(static::FUND_MOST_REDEEM, array(EnumExceptionLevel::ERROR, "单笔赎回限额为%s份"));
        self::addToMapping(static::FUND_USABLE_LESS_LEAST_REDEEM, array(EnumExceptionLevel::ERROR, "可赎回份额低于单笔赎回限额%s份，请全部赎回。"));

        self::addToMapping(static::ORDER_NOT_EXIST_OR_ALREAD_DONE, array(EnumExceptionLevel::WARNING, "订单(%s)不存在或已处理"));
        self::addToMapping(static::ORDER_PURCHASE_DB_ERROR, array(EnumExceptionLevel::ERROR, "订单保存失败"));

        self::addToMapping(static::ORDER_RISK_CONFIRM_FAILED, array(EnumExceptionLevel::WARNING, "用户风险等级不匹配，请确认！"));
        self::addToMapping(static::ORDER_LEAST_PURCHASE_ERROR , array(EnumExceptionLevel::WARNING, "购买必须大于起投份额"));
        self::addToMapping(static::ORDER_MOST_PURCHASE_ERROR , array(EnumExceptionLevel::WARNING, "购买必须小于最大允许份额"));
        self::addToMapping(static::ORDER_SUPPLY_PURCHASE_ERROR , array(EnumExceptionLevel::WARNING, "追回份额不符合要求"));
        self::addToMapping(static::ORDER_FUND_NOT_ONLINE , array(EnumExceptionLevel::WARNING, "基金暂时未开放购买"));

        self::addToMapping(static::RPC_P2P_LOCK_MONEY_FAILED , array(EnumExceptionLevel::ERROR, "扣款失败或余额不足"));
        self::addToMapping(static::RPC_P2P_MONEY_CALL_FAILED , array(EnumExceptionLevel::ERROR, "P2P余额服务调用异常"));
        self::addToMapping(static::RPC_UNION_FUND_CALL_FAILED , array(EnumExceptionLevel::ERROR, "联合基金服务调用异常"));

        self::addToMapping(static::NCFPAY_QUERY_FUND_USERINFO_ERROR, array(EnumExceptionLevel::ERROR, "查询先锋支付接口获取开户信息失败"));
        self::addToMapping(static::UCFPAY_PWD_NOT_VERIFIED, array(EnumExceptionLevel::ERROR, "非法操作，您没有验证支付密码"));

        self::addToMapping(static::FUND_ACCOUNT_SUCCESSFULL, array(EnumExceptionLevel::WARNING, "用户开户成功！"));
        self::addToMapping(static::FUND_USER_RISK_NOT_MATCH, array(EnumExceptionLevel::WARNING, "用户风险等级不匹配，请确认！"));
        self::addToMapping(static::FUND_USER_NOT_SIGN_QUALIFIED_INVESTMENT, array(EnumExceptionLevel::WARNING, "用户未签署合格投资者认定！"));
        self::addToMapping(static::FUND_USER_RISK_ABILITY_EXPIRE, array(EnumExceptionLevel::WARNING,"用户风险问卷过期！"));

    }
}
