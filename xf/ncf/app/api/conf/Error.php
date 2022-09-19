<?php

/**
 * ErrConf class file
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\conf;

class Error {

    /**
     * 获取错误码和错误信息
     * @param string $key
     * @return array()
     */
    public static function get($key) {
        if (!is_string($key) || !isset(self::$_err_arr[$key])) {
            return self::$_err_arr['ERR_SYSTEM'];
        }

        return self::$_err_arr[$key];
    }

    /**
     * 获取错误码
     * @param type $key
     * @return int
     */
    public static function getCode($key) {
        if (!is_string($key) || !isset(self::$_err_arr[$key])) {
            return self::$_err_arr['ERR_SYSTEM']['errno'];
        }

        return self::$_err_arr[$key]['errno'];
    }

    /**
     * 获取错误信息
     * @param type $key
     * @return string
     */
    public static function getMsg($key) {
        if (!is_string($key) || !isset(self::$_err_arr[$key])) {
            return self::$_err_arr['ERR_SYSTEM']['errmsg'];
        }

        return self::$_err_arr[$key]['errmsg'];
    }

    private static $_err_arr = array(
        // 1开头代表系统错误
        'ERR_SYSTEM' => array("errno" => 1001, "errmsg" => "系统错误"),
        'ERR_SYSTEM_TIME' => array("errno" => 1002, "errmsg" => "请您调准手机时间后重试"),
        'ERR_SYSTEM_VERIFY' => array("errno" => 1003, "errmsg" => "系统鉴权失败"),
        'ERR_PLAYBACK' => array("errno" => 1004, "errmsg" => "请求回放错误"),
        'ERR_VERSION' => array("errno" => 1005, "errmsg" => "您的当前版本过低，请更新至网信最新版本后重试"),
        'ERR_IP_LIMIT' => array("errno" => 1006, "errmsg" => "频繁登录，请稍后重试"),

        // 2开头代表业务错误
        'ERR_PARAMS_ERROR' => array("errno" => 20001, "errmsg" => "请求参数不正确"),
        'ERR_PARAMS_VERIFY_FAIL' => array("errno" => 20002, "errmsg" => "参数校验失败"),
        'ERR_MONEY_FORMAT' => array("errno" => 20003, "errmsg" => "请输入正确的投资金额"),
        'ERR_IDENTITY_NO_VERIFY' => array("errno" => 20004, "errmsg" => "身份未认证"),
        'ERR_MANUAL_REASON' => array("errno" => 20005, "errmsg" => "自定义错误"),
        'ERR_MONEY_LIMIT' => array("errno" => 20006, "errmsg" => "提现或投资金额超过账号限制"),
        'ERR_ENTERPRISE_ABANDON' => array("errno" => 20007, "errmsg" => "暂仅支持个人会员登录，企业会员请通过PC端登录您的账户"),
        'ERR_LOGIN_FAILED' => array("errno" => 20008, "errmsg" => "登录失败"),
        'ERR_FAILED_RESETPWD' => array("errno" => 20009, "errmsg" => "登录/注册失败，请修改密码"),
        'ERR_ENQUIRY_ACCOUNT_FAIL' => array("errorCode" => 20010, "errorMsg" => "账户信息查询异常"),
        'ERR_INVESTMENT_USER_CAN_BID' => array("errorCode" => 20011, "errorMsg" => "非投资账户不允许投资"),
        'ERR_IDENTITY_NOT_VERIFY' => array("errno" => 20012, "errmsg" => "请先完成实名认证"),

        // 21开头代表deal相关
        'ERR_DEAL_NOT_EXIST' => array("errno" => 21003, "errmsg" => "投资不存在"),
        'ERR_DEAL_FORBID_BID' => array("errno" => 21004, "errmsg" => "您的账户暂时无法使用，请拨打95782与客服联系"),
        'ERR_UNFINISHED_RISK_ASSESSMENT' => array("errno" => 21005, "errmsg" => "请您投资前先完成风险承受能力评估"),
        'ERR_SYSTEM_CALL_CUSTOMER' => array("errno" => 21007, "errmsg" => "系统繁忙，如有疑问，请拨打客服电话：95782"),
        'ERR_BEYOND_INVEST_LIMITS' => array("errno" => 21006, "errmsg" => "超出单笔最高投资额度"),
        'ERR_BEYOND_REDEEM_LIMITS' => array("errno" => 21008, "errmsg" => "超出转让限额"),
        'ERR_USER_MONEY_FAILED' => array("errno" => 21009, "errmsg" => "余额不足，请先进行充值"),
        // 22开头代表coupon相关
        'ERR_COUPON_ERROR' => array("errno" => 22001, "errmsg" => "优惠码输入错误，请重试"),
        'ERR_COUPON_EXPIRE' => array("errno" => 22002, "errmsg" => "优惠码不在有效期内"),
        'ERR_COUPON_APP_ERROR' => array('errno' => 22003, "errmsg" => "该优惠邀请码无效，请检查"),
        'ERR_COUPON_EFFECT' => array("errno" => 22004, "errmsg" => "您的优惠码不适应此项目"),
        'ERR_COUPON_DISABLE' => array("errno" => 22005, "errmsg" => "邀请码无效"),
        // 3开头代表其他错误
        'ERR_USERNAME_ILLEGAL' => array("errno"=>30001, "errmsg"=>"用户名不符合规则"),
        'ERR_PASSWORD_ILLEGAL' => array("errno"=>30002, "errmsg"=>"密码不符合规则"),
        'ERR_VERIFY_ILLEGAL' => array("errno"=>30003, "errmsg"=>"验证码有误"),
        'ERR_VERIFY' => array("errno"=>30004, "errmsg"=>"请输入验证码"),
        'ERR_VERIFY_EMPTY' => array("errno"=>30005, "errmsg"=>"验证码不可为空"),
        'ERR_ADVID_EMPTY' => array("errno"=>30006, "errmsg"=>"广告位空"),
        'ERR_SPLASH_EMPTY' => array("errno"=>30007, "errmsg"=>"获取闪屏信息失败"),
        'ERR_PASS_RULE' => array("errno"=>30008, "errmsg"=>"密码不符合规则"),
        'ERR_PASS_BLACKLIST' => array("errno"=>30009, "errmsg"=>"密码不符合规则"),
        'ERR_EMAIL_HAS_SET' => array("errno"=>30010, "errmsg"=>"邮箱已经存在"),
        'ERR_EMAIL_REPEAT' => array("errno"=>30011, "errmsg"=>"新邮箱不能与老邮箱一样"),
        'ERR_PARAM_IDNO_ILLEGAL' => array("errno" => 30012, "errmsg" => "身份证号格式不正确"),
        'ERR_RISK_DEVICE_BLACKLIST' => array("errno" => 30013, "errmsg" => "设备命中风控黑名单"),
        'ERR_SHOOT_VERIFY' => array("errno" => 30014, "errmsg" => "您需要投篮验证"),
        'ERR_FACE_VERIFY' => array("errno" => 30015, "errmsg" => "您需要人脸识别验证"),
        'ERR_SMS_VERIFY' => array("errno" => 30016, "errmsg" => "您需要短信验证"),
        'ERR_SHOOT_VERIFY_FAIL' => array("errno" => 30017, "errmsg" => "投篮验证失败"),
        'ERR_FACE_VERIFY_FAIL' => array("errno" => 30018, "errmsg" => "人脸识别验证失败"),
        'ERR_VERIFY_EXPIRED' => array('errno' => 30019, 'errmsg' => '验证码已超时 请重新获取'),
        // 4开头代表oauth相关
        'ERR_AUTH_FAIL' => array("errno" => 40001, "errmsg" => "用户名或密码错误"),
        'ERR_GET_USER_FAIL' => array("errno" => 40002, "errmsg" => "登录过期了，为了您的账户安全，请重新登录"),
        'ERR_LOGIN_FAIL' => array("errno" => 40003, "errmsg" => "登录失败"),
        'ERR_TOKEN_ERROR' => array("errno" => 40004, "errmsg" => "token不正确"),
        'ERR_SIGNUP_UNIQUE' => array("errno" => 41001, "errmsg" => "校验用户名、邮箱、手机号的唯一性失败"),
        'ERR_SIGNUP_SEND_CODE' => array("errno" => 41002, "errmsg" => "发送手机验证码失败"),
        'ERR_SIGNUP' => array("errno" => 41003, "errmsg" => "用户注册失败"),
        'ERR_SIGNUP_PARAM_USERNAME' => array("errno" => 41011, "errmsg" => "用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母"),
        'ERR_SIGNUP_PARAM_PASSWORD' => array("errno" => 41012, "errmsg" => "密码格式不正确，请输入6-20个字符"),
        'ERR_SIGNUP_PARAM_PHONE' => array("errno" => 41013, "errmsg" => "手机号格式不正确"),
        'ERR_SIGNUP_PARAM_EMAIL' => array("errno" => 41014, "errmsg" => "邮箱格式不正确"),
        'ERR_SIGNUP_PARAM_CODE' => array("errno" => 41015, "errmsg" => "手机验证码格式不正确"),
        'ERR_SIGNUP_USERNAME_UNIQUE' => array("errno" => 41031, "errmsg" => "用户名被占用"),
        'ERR_SIGNUP_PHONE_UNIQUE' => array("errno" => 41032, "errmsg" => "该手机号已经注册，如有疑问请联系客服"),
        'ERR_SIGNUP_EMAIL_UNIQUE' => array("errno" => 41033, "errmsg" => "电子邮箱被占用"),
        'ERR_SIGNUP_CODE' => array("errno" => 41034, "errmsg" => "手机验证码不正确"),
        // 5 开头代表与支付端错误相关
        'ERR_SIGNATURE_NULL' => array("errno" => 50001, "errmsg" => "签名数据不能为空"),
        'ERR_SIGNATURE_FAIL' => array("errno" => 50002, "errmsg" => "签名数据不正确"),
        // 6开头代表基金相关
        'ERR_FUND_NOT_EXIST' => array("errno" => 60001, "errmsg" => "基金产品不存在"),
        'ERR_FUND_STATUS_FAIL' => array("errno" => 60002, "errmsg" => "基金产品状态不合法"),
        'ERR_FUND_SUB_COMMENT_FAIL' => array("errno" => 60003, "errmsg" => "备注的长度不合法"),
        'ERR_FUND_LOG_NOT_EXIST' => array("errno" => 60004, "errmsg" => "记录不存在"),
        'ERR_FUND_LOG_DEAL_FAIL' => array("errno" => 60005, "errmsg" => "记录处理失败"),
        // 7开头代表随心约相关
        'ERR_RESERVE_SUPERVISION_NOACCOUNT' => array('errno'=>70001, 'errmsg'=>'您尚未开通网贷P2P账户，无法进行预约'),
        'ERR_RESERVE_QUICK_BID' => array('errno'=>70002, 'errmsg'=>'您尚未开通快捷投资服务，无法进行预约'),
        'ERR_RESERVE_QUICK_BID_OPEN' => array('errno'=>70003, 'errmsg'=>'您已经开通快捷投资服务'),
        // 8开头代速贷相关
        'ERR_SPEEDLOAN_REPAY_APPLY_FAIL' => array('errno'=>80001, 'errmsg'=>'还款申请失败，请重新发起申请'),
        'ERR_SPEEDLOAN_REPAY_NOTIN_SERVICE_TIME' => array('errno'=>80002, 'errmsg'=>'还款申请失败，请在服务时间内发起还款'),
        'ERR_SPEEDLOAN_REPAY_BALANCE_NOT_ENOUGTH' => array('errno'=>80003, 'errmsg'=>'账户余额不足，请充值后再次发起还款'),
        'ERR_SPEEDLOAN_REPAY_HAS_APPLIED' => array('errno'=>80004, 'errmsg'=>'您已经完成还款申请，请勿重复操作'),

        'ERR_SPEEDLOAN_APPLY_FAIL' => array('errno'=>80005, 'errmsg'=>'审核申请失败，请稍后重试'),
        'ERR_SPEEDLOAN_WITHDRAW_AMOUNT_ERROR' => array('errno'=>80006, 'errmsg'=>'借款金额不能低于500元，并且为100的整数倍'),
        'ERR_SPEEDLOAN_CLOSE' => array('errno'=>80007, 'errmsg'=>'速贷服务维护中，请稍后再试'),
        'ERR_SPEEDLOAN_ACCOUNT_DISABLE' => array( 'errno' => 80008, 'errmsg' => '您的账户暂时无法使用'),

        // 授权相关
        'ERR_REMOVE_PRIVILIEGES' => array('errno' => 90001, 'errmsg' => '取消授权失败'),

        // 网信生活相关
        'ERR_LIFE_NETWORK_FAILED' => array('errno'=>100001, 'errmsg' => '网络请求超时或受理失败'),

        // 众汇管家
        'ERR_DARKMOON_SIGNED' => array('errno'=>200001, 'errmsg' => '合同已签署'),
        'ERR_DARKMOON_DEAL_NOT_EXIST' => array('errno'=>200002, 'errmsg' => '信息不存在'),
        'ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL' => array('errno'=>200003, 'errmsg' => '系统繁忙，请稍后重试'),
        'ERR_DARKMOON_CANNOT_SIGNED' => array('errno'=>200004, 'errmsg' => '请等待投资人签完之后，您才可以签署合同，谢谢！'),
        'ERR_DARKMOON_UPDATE_EMAIL_FAIL' => array('errno'=>200005, 'errmsg' => '更新邮箱失败，请稍后重试'),
    );
}
