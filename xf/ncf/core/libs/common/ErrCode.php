<?php

/**
 * ErrConf class file
 */

namespace libs\common;

class ErrCode {

    /**
     * 获取错误码和错误信息
     * @param string $key
     * @return array()
     */
    public static function get($key) {
        return isset(self::$_err_arr[$key]) ? self::$_err_arr[$key] : self::$_err_arr['ERR_UNKNOWN'];
    }

    /**
     * 获取错误码
     * @param type $key
     * @return int
     */
    public static function getCode($key) {
        return isset(self::$_err_arr[$key]) ? self::$_err_arr[$key]['errno'] : self::$_err_arr['ERR_UNKNOWN']['errno'];
    }

    /**
     * 获取错误信息
     * @param type $key
     * @return string
     */
    public static function getMsg($key) {
        return isset(self::$_err_arr[$key]) ? sprintf('%s (%s)', self::$_err_arr[$key]['errmsg'], self::$_err_arr[$key]['errno'])
            : sprintf('%s(%s)', self::$_err_arr['ERR_UNKNOWN']['errmsg'], self::$_err_arr['ERR_UNKNOWN']['errno']);
    }

    private static $_err_arr = array(
        'ERR_UNKNOWN' => array('errno' => 100, 'errmsg' => '未知错误'),

        //存管相关错误码
        'ERR_SYSTEM' => array('errno' => 1000, 'errmsg' => '系统异常'),
        'ERR_REQUEST_TIMEOUT' => array('errno' => 1001, 'errmsg' => '请求过时，请重新发起'),
        'ERR_PARAM' => array('errno' => 1002, 'errmsg' => '接口参数错误'),
        'ERR_CONFIG' => array('errno' => 1003, 'errmsg' => '接口配置错误'),
        'ERR_REQUEST_FREQUENCY_TOO_FAST' => array('errno' => 1005, 'errmsg' => '请求频率太快'),
        'ERR_SERVICE' => array('errno' => 1006, 'errmsg' => '服务错误'),
        'ERR_SIGNATURE' => array('errno' => 1007, 'errmsg' => '签名校验失败'),
        'ERR_ORDER_NOT_EXIST' => array('errno' => 1008, 'errmsg' => '订单不存在'),
        'ERR_ORDER_NUM_MAX_LIMIT' => array('errno' => 1009, 'errmsg' => '订单个数超过最大限制'),
        'ERR_USER_NOEXIST' => array('errno' => 1010, 'errmsg' => '用户不存在'),
        'ERR_PARAM_LOSE' => array('errno' => 1011, 'errmsg' => '参数缺失或不能为空'),
        'ERR_OPEN_ACCOUNT_FAILED' => array('errno' => 1012, 'errmsg' => '存管账户开户失败，请重试'),
        'ERR_MEMBER_SEARCH' => array('errno' => 1013, 'errmsg' => '查询用户信息失败，请重试'),
        'ERR_BALANCE_SEARCH' => array('errno' => 1014, 'errmsg' => '查询用户余额失败，请重试'),
        'ERR_MEMBER_CARD_SEARCH' => array('errno' => 1015, 'errmsg' => '查询用户银行卡失败，请重试'),
        'ERR_DEAL_CREATE' => array('errno' => 1016, 'errmsg' => '标的报备失败，请重试'),
        'ERR_DEAL_UPDATE' => array('errno' => 1017, 'errmsg' => '标的更新失败，请重试'),
        'ERR_PARAM_PLATFORM' => array('errno' => 1018, 'errmsg' => '平台参数错误'),
        'ERR_INVEST_CREATE' => array('errno' => 1019, 'errmsg' => '免密投资失败'),
        'ERR_INVEST_CANCEL' => array('errno' => 1020, 'errmsg' => '投资取消失败'),
        'ERR_DEAL_REPAY' => array('errno' => 1021, 'errmsg' => '标的还款失败，请重试'),
        'ERR_AUTHORIZATION_CANCEL' => array('errno' => 1022, 'errmsg' => '用户授权取消失败，请重试'),
        'ERR_AUTHORIZATION_SEARCH' => array('errno' => 1023, 'errmsg' => '用户授权查询失败，请重试'),
        'ERR_DEALGRANT' => array('errno' => 1024, 'errmsg' => '放款失败'),
        'ERR_SUPERRECHARGE' => array('errno' => 1025, 'errmsg' => '从超级账户充值到网贷账户失败'),
        'ERR_AVOID_ACCOUNT_SUPERWITHDRAW' => array('errno' => 1026, 'errmsg' => '免密提现至超级账户失败'),
        'ERR_MEMBER_CARD_UPDATE' => array('errno' => 1027, 'errmsg' => '更换用户银行卡失败，请重试'),
        'ERR_MEMBER_CARD_BIND' => array('errno' => 1027, 'errmsg' => '用户绑定银行卡失败，请重试'),
        'ERR_RETURN_FAILURE' => array('errno' => 1028, 'errmsg' => '业务操作失败'),
        'ERR_DEAL_SEARCH' => array('errno' => 1029, 'errmsg' => '查询标的失败，请重试'),
        'ERR_MEMBER_PHONE_UPDATE' => array('errno' => 1030, 'errmsg' => '修改手机号失败，请重试'),
        'ERR_MEMBER_CANCEL' => array('errno' => 1032, 'errmsg' => '账户注销失败，请重试'),
        'ERR_GET_FORM' => array('errno' => 1033, 'errmsg' => '表单生成失败，请重试'),
        'ERR_PARAM_USERID' => array('errno' => 1034, 'errmsg' => 'userId参数错误'),
        'ERR_ORDER_SEARCH' => array('errno' => 1035, 'errmsg' => '单笔订单查询失败'),
        'ERR_BATCH_SEARCH' => array('errno' => 1036, 'errmsg' => '批量订单查询失败'),
        'ERR_CALLBACK_STATUS' => array('errno' => 1037, 'errmsg' => '回调状态有误'),
        'ERR_RESPONSE_STATUS' => array('errno' => 1038, 'errmsg' => '错误的响应状态'),
        'ERR_MEMBER_CARD_UNBIND' => array('errno' => 1039, 'errmsg' => '解绑银行卡失败，请重试'),
        'ERR_DEAL_REPLACE_REPAY' => array('errno' => 1040, 'errmsg' => '标的代偿失败，请重试'),
        'ERR_DEAL_RETURN_REPAY' => array('errno' => 1041, 'errmsg' => '标的还代偿款失败，请重试'),
        'ERR_ENTRUSTED_WITHDRAW' => array('errno' => 1042, 'errmsg' => '受托提现失败，请重试'),
        'ERR_SUPERACCOUNT_MONEY_NOT_ENOUGH' => array('errno' => 1043, 'errmsg' => '网信账户可用余额不足'),
        'ERR_GTM_EXCEPTION' => array('errno' => 1044, 'errmsg' => 'GTM异常，请重试'),
        'ERR_USER_NOLOGIN' => array('errno' => 1045, 'errmsg' => '您尚未登录，请登录后重试'),
        'ERR_USER_INVALID' => array('errno' => 1046, 'errmsg' => '用户已被注销'),
        'ERR_MONEY_NOT_ENOUGH' => array('errno' => 1047, 'errmsg' => '余额不足'),
        'ERR_AUTOCHARGE_TIMEOUT' => array('errno' => 1048, 'errmsg' => '交易超时'),
        'ERR_DEAL_REPLACE_RECHARGE_REPAY' => array('errno' => 1049, 'errmsg' => '标的代充值还款失败，请重试'),
        'ERR_DEALCANCEL' => array('errno' => 1050, 'errmsg' => '标的流标失败，请重试'),
        'ERR_ACCOUNT_NOEXIST' => array('errno' => 1051, 'errmsg' => '账户不存在'),
        'ERR_SV_MONEY_NOT_ENOUGH' => array('errno' => 1052, 'errmsg' => '存管账户可用余额不足'),

        'ERR_BALANCE_NOT_ENOUGHT' => array('errno' => 20002, 'errmsg' => '正在与银行系统同步您的账户信息，请过段时间再操作'),
        'ERR_NOT_BANDCARD' => array('errno' => 20003, 'errmsg' => '用户未绑卡'),
        'ERR_CARRY_ORDER_CREATE' => array('errno' => 20004, 'errmsg' => '提现订单生成失败'),
        'ERR_ASYNC_ADD_SUPERVISION_ORDER' => array('errno' => 20005, 'errmsg' => '异步添加存管订单失败'),
        'ERR_ASYNC_UPDATE_SUPERVISION_ORDER' => array('errno' => 20006, 'errmsg' => '异步更新存管订单失败'),
        'ERR_NOT_OPEN_ACCOUNT' => array('errno' => 20007, 'errmsg' => '用户未在存管开户'),
        'ERR_OUT_ORDER_NOT_EXIST' => array('errno' => 20008, 'errmsg' => '外部订单号不存在'),
        'ERR_SUPERVISION_OPEN_ACCOUNT' => array('errno'=>20013, 'errmsg' => '用户已经在存管系统开户'),
        'ERR_USER_NOT_REALNAME' => array('errno'=>20014, 'errmsg' => '用户尚未进行实名认证'),
        'ERR_CARD_NOT_VERIFY' => array('errno'=>20015, 'errmsg' => '用户尚未验证银行卡'),
        'ERR_ASSET_NOTZERO' => array('errno'=>20016, 'errmsg' => '您的资产总额不为0，无法注销'),
        'ERR_HAVE_NO_PRIVILEGES' => array('errno' => 20017, 'errmsg' => '权限不足'),
        'ERR_ADD_SUPERVISION_ORDER' => array('errno' => 20019, 'errmsg' => '添加存管订单失败'),
        'ERR_DEAL_EXIST' => array('errno' => 20020, 'errmsg' => '标的已存在'),
        'ERR_ENTRUSTED_WITHDRAW_EXIST' => array('errno' => 20021, 'errmsg' => '原放款单已经存在受托支付'),
        'ERR_RESERVE_NOCANCEL' => array('errno' => 20022, 'errmsg' => '您有预约中的随心约记录，暂时无法取消授权'),
        'ERR_YXT_NOCANCEL' => array('errno' => 20023, 'errmsg' => '您有还款中的银信通记录，暂时无法取消授权'),
        'ERR_ENTERPRISE_NOBRANCHNO' => array('errno' => 20024, 'errmsg' => '对公账户没有联行号'),
        'ERR_USER_NOOPEN_SUPERVISION' => array('errno' => 20025, 'errmsg' => '该用户的账户类型不需要开通存管账户'),
        'ERR_USER_SIGN_WXFREEPAYMENT' => array('errno' => 20026, 'errmsg' => '签署网信超级账户免密协议失败'),
        'ERR_ENTERPRISE_UPDATE_FAILED' => array('errno' => 20026, 'errmsg' => '企业用户信息修改失败'),
        'ERR_BANKCARD_NOT_EXIST' => array('errno' => 20027, 'errmsg' => '银行卡不存在'),
        'ERR_ENTERPRISE_AUDITING' => array('errno' => 20028, 'errmsg' => '该企业用户正在审核中，不允许再次提交'),
        'ERR_ASYNC_ORDERSPLIT_JOB' => array('errno' => 20029, 'errmsg' => '添加订单拆分异步请求Jobs失败'),
        'ERR_ADD_ORDER_SPLIT' => array('errno' => 20030, 'errmsg' => '新增存管订单拆分数据失败'),
        'ERR_ORDER_SPLIT_NOCONFIG' => array('errno' => 20031, 'errmsg' => '订单拆分尚未配置'),
        'ERR_ORDER_SPLIT_SUPERVISION' => array('errno' => 20032, 'errmsg' => '订单拆分请求存管接口失败'),
        'ERR_ORDER_SPLIT_NOEXIST' => array('errno' => 20033, 'errmsg' => '该交易流水号数据不存在'),
        'ERR_ORDER_SPLIT_UPDATE' => array('errno' => 20034, 'errmsg' => '订单更新失败'),
        'ERR_ORDER_SPLIT_SREM' => array('errno' => 20035, 'errmsg' => '该交易流水号清理失败'),
        'ERR_ORDER_SPLIT_NOTIFY' => array('errno' => 20036, 'errmsg' => '该业务的回调不接受失败状态'),
        'ERR_DT_BID_LOCK_ERROR' => array('errno' => 20037,'errmsg'=>'投资进行中，请稍后再试'),


        /**
         * 通用错误
         * 预留范围 30000 ~ 30999
         */
        'ERR_RESEND_MESSAGE' => array('errno'=>30000, 'errmsg' => '短信重发失败'),

        /**
         * 账户相关错误
         * 预留范围 31000 ~ 31999
         */
        'ERR_UCFPAY_NOTOPEN' => array('errno'=>31000, 'errmsg' => '用户尚未开通超级账户'),
        'ERR_MEMBERCARD_UNBIND_NOTZERO' => array('errno'=>31001, 'errmsg' => '您的资产总额不为0，无法解绑银行卡'),
        'ERR_AUTHORIZATION_SEARCH_REQUEST' => array('errno' => 31002, 'errmsg' => '用户授权查询请求失败，请重试'),
        'ERR_MEMBER_INFO_MODIFY_FAILED' => array('errno' => 31003, 'errmsg' => '用户实名信息修改失败，请重试'),
        'ERR_AUTHORIZATION_CANCEL_NOT_CAN' => array('errno' => 31004, 'errmsg' => '用户授权不能取消'),
        'ERR_BANK_CARD_SIGN_APPLY' => array('errno' => 31005, 'errmsg' => '用户银行卡签约申请失败'),


        /**
         * 充值相关错误
         * 预留范围 32000 ~ 32999
         */
        'ERR_CHARGE_AMOUNT' => array('errno' => 32000, 'errmsg' => '充值金额不一致'),
        'ERR_CHARGE_FAILED' => array('errno' => 32001, 'errmsg' => '处理交易记录失败'),
        'ERR_ORDER_PAID' => array('errno' => 32002, 'errmsg' => '充值订单已完成'),
        'ERR_CHARGE_ORDER_CREATE' => array('errno' => 32003, 'errmsg' => '充值订单生成失败'),
        'ERR_CHARGE_DISACCORD' => array('errno' => 32004, 'errmsg' => '充值单状态不一致'),
        'ERR_CREATE_CHARGE_FAILED' => array('errno' => 32005, 'errmsg' => '创建充值单失败'),
        'ERR_AUTOCHARGE_FAILED' => array('errno' => 32006, 'errmsg' => '自动扣款充值失败'),
        'ERR_AUTOCHARGE_NOTIFY_FAILED' => array('errno' => 32007, 'errmsg' => '代扣还款回调失败'),
        'ERR_CHARGE_ORDER_NOT_EXSIT' => array('errno' => 32008, 'errmsg' => '存管充值订单不存在'),
        'ERR_CHARGE_ORDER_HAS_EXSIT' => array('errno' => 32009, 'errmsg' => '存管充值订单已存在'),
        'ERR_SV_SERVER_BUSY' => array('errno' => 32010, 'errmsg' => '存管服务器繁忙，请稍后再试'),


        /**
         * 提现相关错误
         * 预留范围 33000 ~ 33999
         */
        'ERR_WITHDRAW' => array('errno' => 33000, 'errmsg' => '免密提现至银行卡失败，请重试'),
        'ERR_CARRY_ORDER_NOT_EXIST' => array('errno' => 33001, 'errmsg' => '提现订单不存在'),
        'ERR_CARRY_AMOUNT_WRONG' => array('errno' => 33002, 'errmsg' => '提现金额错误'),
        'ERR_CARRY_FAILED' => array('errno' => 33003, 'errmsg' => '提现失败'),
        'ERR_BID_ELEC_WITHDRAW_STATUS' => array('errno' => 33004, 'errmsg' => '提现返回状态不一致'),
        'ERR_WITHDRAW_STATUS' => array('errno' => 33005, 'errmsg' => '提现状态错误'),
        'ERR_WITHDRAW_BANKPAYUP' => array('errno' => 33006, 'errmsg' => '提现至银行卡失败，请重试'),
        'ERR_WITHDRAW_LIMIT' => array('errno' => 33007, 'errmsg' => '您的账户暂时无法使用，请拨打95782与客服联系。'),
        'ERR_WITHDRAW_ENTRUSTED_FAST' => array('errno' => 33008, 'errmsg' => '快速受托提现失败，请重试'),
        'ERR_WITHDRAW_AUDIT_FAILED' => array('errno' => 33009, 'errmsg' => '提现审批失败'),
        'ERR_WITHDRAW_AUDIT_CREATE_FAILED' => array('errno' => 33010, 'errmsg' => '创建提现审批记录失败'),

        /**
         * 标的相关错误
         * 预留范围 34000 ~ 34999
         */


        /**
         * 投资相关错误
         * 预留范围 35000 ~ 35999
         */
        'ERR_INVEST_NO_EXIST' => array('errno' => 35000, 'errmsg' => '订单不存在'),
        'ERR_INVEST_SUBORDER_EXIST' => array('errno' => 35001, 'errmsg' => '投资红包子单已存在'),
        'ERR_INVEST_ORDER_SEARCH' => array('errno' => 35002, 'errmsg' => '投资查单失败'),


        /**
         * 订单相关错误
         * 预留范围 36000 ~ 36999
         */
        'ERR_ERR_SUPERVISION_ORDER_UPDATE_FAILED' => array('errno' => 36000, 'errmsg' => '存管订单更新失败'),


        /**
         * 放款相关错误
         * 预留范围 37000 ~ 37999
         */


        /**
         * 还款相关错误
         * 预留范围 38000 ~ 38999
         */
        'ERR_REPAYORDER_NO_EXIST' => array('errno' => 38000, 'errmsg' => '第三方标的订单号关系数据不存在'),
        'ERR_REPAYMONEY_LOANMONEY' => array('errno' => 38001, 'errmsg' => '还款金额跟回款金额不一致'),
        'ERR_REPAYDATA_NO_EXIST' => array('errno' => 38002, 'errmsg' => '第三方还款申请记录不存在'),
        'ERR_REPAYDATA_CLOSE' => array('errno' => 38003, 'errmsg' => '第三方还款申请记录已终态'),
        'ERR_REPAYDATA_STATUS' => array('errno' => 38004, 'errmsg' => '第三方还款申请状态不一致'),
        'ERR_REPAYORDER_NOIDEN' => array('errno' => 38004, 'errmsg' => '订单号跟用户标的信息不一致'),


        /**
         * 代偿相关错误
         * 预留范围 39000 ~ 39999
         */


        /**
         * 收费相关错误
         * 预留范围 40000 ~ 40999
         */

        /**
         * 划转相关
         * 预留范围 41000 ~ 41999
         */
        'ERR_TRANSFER_ORDER_FAILED' => array('errno' => 41000, 'errmsg' => '划转失败'),
        'ERR_TRANSFER_ORDER_NOT_EXSIT' => array('errno' => 41001, 'errmsg' => '划转订单不存在'),
        'ERR_TRANSFER_STATUS_NOT_ALLOWED' => array('errno' => 41002, 'errmsg' => '审核状态错误'),
        'ERR_TRANSFER_ORDER_UPDATE' => array('errno' => 41003, 'errmsg' => '划转订单审批失败'),
        'ERR_TRANSFER_AMOUNT' => array('errno' => 41004,'errmsg'=>'划转金额错误'),
        'ERR_TRANSFER_FAILED' => array('errno' => 41005,'errmsg'=>'划转失败'),

        /**
         * 其他错误消息
         * 预留范围 42000 ~ 44999
         */
        'ERR_GTM_EXCEPTION' => array('errno' => 42000, 'errmsg' => '系统操作失败'),
        'ERR_IDWORKER' => array('errno' => 42001, 'errmsg' => '获取GID失败'),

        /**
         * 智多鑫相关
         * 预留范围 45000 ~ 46999
         */
        'ERR_DT_BOOKCREATE_FAILED' => array('errno' => 45000, 'errmsg' => '预约冻结失败'),
        'ERR_DT_BOOKCREATENOTIFY_FAILED' => array('errno' => 45001, 'errmsg' => '预约冻结处理失败'),
        'ERR_DT_BOOKCANCEL_FAILED' => array('errno' => 45002, 'errmsg' => '取消预约冻结失败'),
        'ERR_DT_BOOKINVESTBATCHCREATE_FAILED' => array('errno' => 45003, 'errmsg' => '预约批量投资失败'),
        'ERR_DT_BOOKINVESTCANCEL_FAILED' => array('errno' => 45004, 'errmsg' => '取消投资失败'),
        'ERR_DT_BOOKCREDITBATCH_FAILED' => array('errno' => 45005, 'errmsg' => '批量债权转让投资失败'),
        'ERR_DT_BOOKCREDITCANCEL_FAILED' => array('errno' => 45005, 'errmsg' => '取消预约债转失败'),
        'ERR_DT_CREDITASSIGNGRANT_FAILED' => array('errno' => 45006, 'errmsg' => '批量标的债权转让失败'),
        'ERR_DT_CREDITASSIGNCANCEL_FAILED' => array('errno' => 45007, 'errmsg' => '取消债转投资失败'),

        /**
         * 转账相关
         * 预留范围 47000 ~ 47999
         */
        'ERR_BATCH_TRANSFER' => array('errno' => 47000, 'errmsg' => '批量转账失败'),
    );
}
