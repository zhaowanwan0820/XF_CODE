<?php

/**
 * ErrConf class file
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace openapi\conf;

class Error {

    /**
     * 获取错误码和错误信息
     * @param string $key
     * @return array()
     */
    public static function get($key) {
        return isset(self::$_err_arr[$key]) ? self::$_err_arr[$key] : self::$_err_arr['ERR_SYSTEM'];
    }

    private static $_err_arr = array(
        // 1开头代表系统错误
        'ERR_SYSTEM' => array("errorCode" => 1001, "errorMsg" => "系统错误"),
        'ERR_SYSTEM_CLIENTID' => array("errorCode" => 1002, "errorMsg" => "client ID 无效"),
        'ERR_SYSTEM_TIME' => array("errorCode" => 1003, "errorMsg" => "timestamp无效"),
        'ERR_SYSTEM_SIGN_NULL' => array("errorCode" => 1004, "errorMsg" => "缺少sign参数"),
        'ERR_SYSTEM_SIGN' => array("errorCode" => 1005, "errorMsg" => "sign参数不正确"),
        'ERR_SYSTEM_MAINTENANCE' => array("errorCode" => 1006, "errorMsg" => "系统维护中"),
        'ERR_SYSTEM_ACTION_PERMISSION' => array("errorCode" => 1007, "errorMsg" => "无权限访问此接口"),
        'ERR_SYSTEM_ACTION_CLOSE' => array("errorCode" => 1008, "errorMsg" => "此接口已关闭"),
        // 2开头代表业务错误
        'ERR_PARAMS_ERROR' => array("errorCode" => 20001, "errorMsg" => "请求参数不正确"),
        'ERR_PARAMS_VERIFY_FAIL' => array("errorCode" => 20002, "errorMsg" => "参数校验失败"),
        'ERR_MONEY_FORMAT' => array("errorCode" => 20003, "errorMsg" => "请输入正确的投资金额"),
        'ERR_IDENTITY_NO_VERIFY' => array("errorCode" => 20004, "errorMsg" => "身份未认证"),
        'ERR_MANUAL_REASON' => array("errorCode" => 20005, "errorMsg" => "自定义错误"),
        'ERR_IDENTITY_VERIFY' => array("errorCode" => 20006, "errorMsg" => "身份正在审核"),
        'ERR_ENTERPRISE_ABANDON' => array("errorCode" => 20007, "errorMsg" => "暂仅支持个人会员登录，企业会员请通过PC端登录您的账户"),
        'ERR_SITE_ID_ERROR' => array("errorCode" => 20008, "errorMsg" => "该client没有设置SiteId"),
        'ERR_OAUTH_ERROR' => array("errorCode" => 20009, "errorMsg" => "clientId错误或者auth_token过期"),
        'ERR_ENQUIRY_ACCOUNT_FAIL' => array("errorCode" => 20010, "errorMsg" => "账户信息查询异常"),
        'ERR_INVESTMENT_USER_CAN_BID' => array("errorCode" => 20011, "errorMsg" => "非投资账户不允许投资"),
        'ERR_USER_BANKCARD_FAIL' => array("errorCode" => 20012, "errorMsg" => "账户银行卡信息异常"),

        // 21开头代表deal相关
        'ERR_DEAL_NOT_EXIST' => array("errorCode" => 21003, "errorMsg" => "投资不存在"),
        'ERR_UNFINISHED_RISK_ASSESSMENT' => array("errorCode" => 21004, "errorMsg" => "请您投资前先完成风险承受能力评估"),
        'ERR_BEYOND_INVEST_LIMITS' => array("errorCode" => 21005, "errorMsg" => "超出单笔最高投资额度"),
        'ERR_RESERVE_CLOSE' => array("errorCode" => 21006, "errorMsg" => "随鑫预关闭"),
        'ERR_RESERVE_OFTER' => array("errorCode" => 21007, "errorMsg" => "刷新过于频繁，请稍后再试"),
        'ERR_RESERVE_SUPERVISION_NOACCOUNT' => array('errorCode'=>21008, 'errorMsg'=>'您尚未开通网贷P2P账户，无法进行预约'),
        'ERR_RESERVE_QUICK_BID' => array('errorCode'=>21009, 'errorMsg'=>'您尚未开通快捷投资服务，无法进行预约'),
        'ERR_DEAL_REPAY_TYPE' => array('errorCode'=>21010,'errorMsg' =>'还款方式错误'),
        'ERR_DEAL_FIND_NULL' => array('errorCode'=>21011,'errorMsg' =>'未查询到标的'),
        'ERR_REPAY_DEAL_STATUS' => array('errorCode'=>21012,'errorMsg' =>'还款标的状态异常'),
        'ERR_REPAY_TRIAL_ERR' => array('errorCode'=>21013,'errorMsg' =>'还款试算请求异常'),
        'ERR_REPAY_DEAL_REPAYING' => array('errorCode'=>21014,'errorMsg' => '标的正在还款中'),
        'ERR_DEAL_TYPE_ID' => array('errorCode'=>21015,'errorMsg' => '标的类型错误'),
        'ERR_INTEREST_ACCOUNT_BALANCE' => array('errorCode'=>21016,'errorMsg' => '利息账户余额不足'),
        'ERR_GR_ACCOUNT_BALANCE' => array('errorCode'=>21017,'errorMsg' => '代充值账户余额不足'),
        'ERR_DEAL_REPAY_INFO' => array('errorCode'=>21018,'errorMsg' => '标的还款计划不存在'),
        'ERR_SEARCH_DK_STATUS' => array('errorCode'=>21019,'errorMsg' => '查询代扣状态失败'),
        'ERR_DK_REQUEST' => array('errorCode'=>21020,'errorMsg' => '发送代扣请求失败'),
        'ERR_DK_REPAY_WAITTING' => array('errorCode'=>21021,'errorMsg' => '代扣成功,等待还款中'),
        'ERR_DSD_INTEREST_ACCOUNT' => array('errorCode'=>21022,'errorMsg' => '代扣利息账户不存在'),
        'ERR_DSD_GR_ACCOUNT' => array('errorCode'=>21023,'errorMsg' => '代充值账户不存在'),
        'ERR_DEAL_REPAY_CALC' => array('errorCode'=>21024,'errorMsg' => '标的还款试算信息获取错误'),
        'ERR_DEAL_REPAY_GR' => array('errorCode'=>21025,'errorMsg' => '标的代充值还款异常'),
        'ERR_DEAL_REPAY_NOT_IN_TIME' => array('errorCode'=>21026,'errorMsg' => '还款失败，该标的不在可调用的时间范围内'),
        'ERR_DEAL_REPAY_GRANT_TIME' => array('errorCode'=>21027,'errorMsg' => '还款失败，该标的不可在放款日发起提前还款'),
        'ERR_DK_REPAY_REPAYERR' => array('errorCode'=>21028,'errorMsg' => '代扣成功、还款失败'),
        'ERR_USER_PURPOSE' => array('errorCode'=>21029,'errorMsg' => '用户账户类型不符合'),
        'ERR_DEAL_REPAY_ID' => array('errorCode'=>21030,'errorMsg' => '标的还款ID异常'),
        'ERR_DK_SEARCH' => array('errorCode'=>21031,'errorMsg' => '代扣查询异常'),
        'ERR_OUTER_ID_SAVE' => array('errorCode'=>21032,'errorMsg' => '第三方订单保存失败'),
        'ERR_CHANGE_DEAL_STATUS' => array('errorCode'=>21033,'errorMsg' => '更改标的状态失败'),
        'ERR_DK_MONEY' => array('errorCode'=>21034,'errorMsg' => '代扣金额错误'),
        'ERR_OUTER_ID' => array('errorCode'=>21035,'errorMsg' => '未查询到外部订单号'),
        'ERR_REPAYED' => array('errorCode'=>21036,'errorMsg' => '本期还款已还清'),
        'ERR_OUTER_ID_USED' => array('errorCode'=>21037,'errorMsg' => '外部订单号已被使用'),
        'ERR_DK_SUCCESSED' => array('errorCode'=>21038,'errorMsg' => '此笔还款已代扣成功,交易未受理'),
        'ERR_NO_DEAL_PROJECT' => array('errorCode'=>21039,'errorMsg' => '该项目不存在'),
        'ERR_UPDATE_POST_LOAN_MESSAGE' => array('errorCode'=>21040,'errorMsg' => '贷后信息更新失败'),
        'ERR_IS_DURING_LOAN' => array('errorCode'=>21041,'errorMsg' => '正在放款'),
        'ERR_NOT_ENOUGH_MONEY' => array('errorCode'=>21042,'errorMsg' => '余额不足'),
        'ERR_USED_ORDER' => array('errorCode'=>21043,'errorMsg' => '已有其他业务使用该订单号'),

        // 22开头代表coupon相关
        'ERR_COUPON_ERROR' => array("errorCode" => 22001, "errorMsg" => "优惠码输入错误，请重试"),
        'ERR_COUPON_EXPIRE' => array("errorCode" => 22002, "errorMsg" => "优惠码不在有效期内"),
        'ERR_COUPON_APP_ERROR' => array('errorCode' => 22003, "errorMsg" => "您的优惠码无效，注册成功后无法获得返利，是否继续？"),
        'ERR_COUPON_EFFECT' => array("errorCode" => 22004, "errorMsg" => "您的优惠码不适应此项目"),
        'ERR_JRGC_USER' => array("errorCode" => 22005, "errorMsg" => "已注册金融工场账户(www.9888.cn),暂不参与此次活动"),
        // 23 开头代表合同相关
        'ERR_CONTRACT_EMPTY' => array("errorCode" => 23005, "errorMsg" => "合同不存在"),
        'ERR_CONTRACT_ILLEGAL' => array("errorCode" => 23006, "errorMsg" => "合同不存在"),
        'ERR_CONTRACT_TYPE' => array("errorCode" => 23007, "errorMsg" => "合同类型错误"),
        'ERR_CONTRACT_MONEY' => array("errorCode" => 23008, "errorMsg" => "合同金额错误"),
        'ERR_CONTRACT_SIGN_FAILED' => array("errorCode" => 23009, "errorMsg" => "合同签署失败"),
        'ERR_CONTRACT_SIGNED' => array("errorCode" => 23010, "errorMsg" => "合同已经签署"),
        'ERR_SUPERVISION_NOACCOUNT' => array("errorCode" => 23011, "errorMsg" => "该用户尚未开通存管账户"),
        'ERR_CONTRACT_NOT_SIGNED' => array("errorCode" => 23012, "errorMsg" => "已请求或已落库，但是未签署"),
        'ERR_REPAY_PERIOD_TYPE_ILLEGAL' => array("errorCode" => 23013, "errorMsg" => "借款期限类型不正确"),
        'ERR_BANK_NOT_FOUND' => array("errorCode" => 23014, "errorMsg" => "受托方开户行不存在"),
        'ERR_USER_NOT_FOUND' => array("errorCode" => 23015, "errorMsg" => "wxOpenId 不正确"),

        // 24 提现相关
        "ERR_CASHOUT_ERROR" => array("errorCode" => 24001, "errorMsg" => "提现错误"),
        "ERR_CASHOUT_AMOUNT" => array("errorCode" => 24002, "errorMsg" => "您的银行卡仅支持单笔500万元交易，请分多次提现。"),
        "ERR_CASHOUT_NOT_ENOUGH_MONEY" => array("errorCode" => 24003, "errorMsg" => "您的可用余额不足"),
        // 25 充值相关
        "ERR_USER_DIFF" => array("errorCode" => 25001, "errorMsg" => "当前访问发送问题，请稍后再试"),
        "ERR_PAYMENT_CODE" => array("errorCode" => 25002, "errorMsg" => "当前访问发送问题，请稍后再试"),
        "ERR_CHARGE_PENDING" => array("errorCode" => 25003, "errorMsg" => "操作无效,充值处理中"),
        "ERR_CHARGE_DONE" => array("errorCode" => 25004, "errorMsg" => "操作无效,已充值"),
        "ERR_CHARGE_FAILED" => array("errorCode" => 25005, "errorMsg" => "操作无效,充值失败"),
        "ERR_PAYMENT_ID_NULL" => array("errorCode" => 25006, "errorMsg" => "支付订单编号不能为空"),
        "ERR_CHARGE" => array("errorCode" => 25007, "errorMsg" => "当前访问发送问题，请稍后再试"),

        // 3开头代表其他错误
        'ERR_USERNAME_ILLEGAL' => array("errorCode" => 30001, "errorMsg" => "用户名不合法"),
        'ERR_PASSWORD_ILLEGAL' => array("errorCode" => 30002, "errorMsg" => "密码不符合规则"),
        'ERR_VERIFY_ILLEGAL' => array("errorCode" => 30003, "errorMsg" => "验证码输入错误"),
        'ERR_VERIFY' => array("errorCode" => 30004, "errorMsg" => "请输入验证码"),
        'ERR_VERIFY_EMPTY' => array("errorCode" => 30005, "errorMsg" => "验证码不可为空"),
        'ERR_ADVID_EMPTY' => array("errorCode" => 30006, "errorMsg" => "广告位空"),
        // 4开头代表oauth相关
        'ERR_AUTH_FAIL' => array("errorCode" => 40001, "errorMsg" => "用户名密码不匹配"),
        'ERR_GET_USER_FAIL' => array("errorCode" => 40002, "errorMsg" => "登录过期了，为了您的账户安全，请重新登录"),
        'ERR_LOGIN_FAIL' => array("errorCode" => 40003, "errorMsg" => "登录失败"),
        'ERR_TOKEN_ERROR' => array("errorCode" => 40004, "errorMsg" => "token不正确"),
        'ERR_SIGNUP_UNIQUE' => array("errorCode" => 41001, "errorMsg" => "校验用户名、邮箱、手机号的唯一性失败"),
        'ERR_SIGNUP_SEND_CODE' => array("errorCode" => 41002, "errorMsg" => "发送手机验证码失败"),
        'ERR_SIGNUP' => array("errorCode" => 41003, "errorMsg" => "用户注册失败"),
        'ERR_SIGNUP_PARAM_USERNAME' => array("errorCode" => 41011, "errorMsg" => "用户名格式不正确，请输入4-16个字符，支持英文或英文与数字组合"),
        'ERR_SIGNUP_PARAM_PASSWORD' => array("errorCode" => 41012, "errorMsg" => "密码格式不正确，请输入6-20个字符"),
        'ERR_SIGNUP_PARAM_PHONE' => array("errorCode" => 41013, "errorMsg" => "手机号格式不正确"),
        'ERR_SIGNUP_PARAM_EMAIL' => array("errorCode" => 41014, "errorMsg" => "邮箱格式不正确"),
        'ERR_SIGNUP_PARAM_CODE' => array("errorCode" => 41015, "errorMsg" => "手机验证码格式不正确"),
        'ERR_SIGNUP_USERNAME_UNIQUE' => array("errorCode" => 41031, "errorMsg" => "用户名被占用"),
        'ERR_SIGNUP_PHONE_UNIQUE' => array("errorCode" => 41032, "errorMsg" => "该手机号已经注册，如有疑问请联系客服"),
        'ERR_SIGNUP_EMAIL_UNIQUE' => array("errorCode" => 41033, "errorMsg" => "电子邮箱被占用"),
        'ERR_SIGNUP_CODE' => array("errorCode" => 41034, "errorMsg" => "手机验证码不正确"),
        'ERR_OPEN_ID' => array("errorCode" => 41035, "errorMsg" => "OPEN ID 不正确"),
        'ERR_EMAIL_SUBSCRIBE' => array("errorCode" => 41036, "errorMsg" => "邮件订阅失败"),
        'ERR_EMAIL_SET' => array("errorCode" => 41037,"errorMsg" => "邮箱设置失败"),
        'ERR_MIANMI_SET' => array("errorCode" => 41038,"errorMsg" => "未开通免密授权"),
        // 5 开头代表与支付端错误相关
        'ERR_SIGNATURE_NULL' => array("errorCode" => 50001, "errorMsg" => "签名数据不能为空"),
        'ERR_SIGNATURE_FAIL' => array("errorCode" => 50002, "errorMsg" => "签名数据不正确"),
        // 6开头代表基金相关
        'ERR_FUND_NOT_EXIST' => array("errorCode" => 60001, "errorMsg" => "基金产品不存在"),
        'ERR_FUND_STATUS_FAIL' => array("errorCode" => 60002, "errorMsg" => "基金产品状态不合法"),
        'ERR_FUND_SUB_COMMENT_FAIL' => array("errorCode" => 60003, "errorMsg" => "备注的长度不合法"),
        // 7开头代表用户余额相关
        'ERR_USER_MONEY_SUCCESS' => array("errorCode" => 70001, "errorMsg" => "用户余额操作成功"),
        'ERR_USER_MONEY_FAILED' => array("errorCode" => 70002, "errorMsg" => "用户余额操作失败"),
        'ERR_USER_PARAM_SET_FAILED' => array("errorCode" => 70003, "errorMsg" => "参数设置失败"),
        // 8开头代表礼券相关
        'ERR_COUPON_FAILED' => array('errorCode' => 80001, 'errorMsg' => '发券失败'),
    );

}
