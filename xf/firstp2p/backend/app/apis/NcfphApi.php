<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Common\Library\ApiBackend;
use core\service\UserService;
use core\service\UserTokenService;
use core\service\UserBankcardService;
use core\service\BankService;
use core\service\AttachmentService;
use core\service\BonusService;
use core\service\CouponService;
use core\service\O2OService;
use core\service\AccountService;
use core\service\BwlistService;
use core\service\OpenService;
use core\service\CouponLogService;
use core\service\LogRegLoginService;
use core\service\AdunionDealService;
use core\service\marketing\RecommendService;
use libs\web\Open;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Finance;
use libs\utils\Monitor;
use libs\lock\LockFactory;
use libs\rpc\Rpc;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use core\dao\OtoAcquireLogModel;
use core\dao\UserModel;

/**
 * 专供网信普惠的api
 * 为了兼容以前的代码，这里把错误处理进行了规整统一
 * 保证这里获取的数据是网信特有的数据
 */
class NcfphApi extends ApiBackend {
    // 错误汇总
    private static $_err_arr = array(
        // 1开头代表系统错误
        'ERR_SYSTEM' => array("errno" => 1001, "errmsg" => "系统错误"),
        'ERR_SYSTEM_TIME' => array("errno" => 1002, "errmsg" => "请您调准手机时间后重试"),
        'ERR_SYSTEM_VERIFY' => array("errno" => 1003, "errmsg" => "系统鉴权失败"),
        'ERR_PLAYBACK' => array("errno" => 1004, "errmsg" => "请求回放错误"),
        'ERR_VERSION' => array("errno" => 1005, "errmsg" => "您的当前版本过低，请更新至网信最新版本后重试"),

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

        // 7开头代表随鑫约相关
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

    // 为了兼容rpc的写法
    private $rpc;

    /**
     * 获取错误码和错误信息
     * @param string $key
     * @return array()
     */
    private static function get($key) {
        if (!is_string($key) || !isset(self::$_err_arr[$key])) {
            return self::$_err_arr['ERR_SYSTEM'];
        }

        return self::$_err_arr[$key];
    }

    /**
     * 如果出错，允许设置错误，为了兼容原有代码
     */
    private function setErr($err, $error = "") {
        if (!is_string($err) || !isset(self::$_err_arr[$err])) {
            $err = 'ERR_SYSTEM';
        }

        $arr = self::get($err);
        return $this->formatResult($err, $arr['errno'], empty($error) ? $arr["errmsg"] : $error);
    }

    public function __construct() {
        parent::__construct();
        // 这里实例化rpc，是为了兼容
        $this->rpc = new Rpc();
    }

    /**
     * 处理用户的登陆user/login
     * 用户普惠的登陆处理
     */
    public function login() {
        // 账号
        $account = $this->getParam('account');
        // 密码
        $password = $this->getParam('password');
        // 国别号
        $country_code = $this->getParam('country_code', 'cn');
        // 登陆来源
        $loginFrom = $this->getParam('loginFrom', '');

        $userService = new UserService();

        // 用户登陆
        $result = $userService->apiNewLogin($account, $password, false, $loginFrom, $country_code);
        if (!isset($result['success']) || $result['success'] !== true) {
            if (empty($result['code'])) {
                return $this->setErr('ERR_AUTH_FAIL');
            }

            if ($result['code'] == '20007') {
                return $this->setErr('ERR_ENTERPRISE_ABANDON');
            }

            if ($result['code'] == '20006') {
                return $this->setErr('ERR_LOGIN_FAILED', $result['reason']);
            }

            if ($result['code'] == '-33') {
                return $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
            }

            if ($result['code'] == '20003' || $result['code'] == '20004') {
                // 如果超过限制，则提示需要填写验证码
                return $this->setErr('ERR_VERIFY', "用户名或密码错误");
            }

            // 未超过限制泽提示登录失败
            return $this->setErr('ERR_AUTH_FAIL', isset($result['reason']) ? $result['reason'] : '');
        }

        $token = $result['code'];
        // 调用oauth接口获取用户信息
        $info = $userService->getUserByCode($token);
        if (isset($info['code']) && $info['code']) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        if ($info['status'] == 0) {
            return $this->setErr('ERR_LOGIN_FAIL');
        }

        $data = array(
            "token"=>$token,
            'tokenExpireTime' => time() + UserTokenService::API_TOKEN_EXPIRE
        );

        $jsonData = array_merge($data, $this->getRetUserInfo($info['user']));
        $jsonData['isBid'] = false;
        return $this->formatResult($jsonData);
    }

    /**
     * 获取Api域名
     */
    private function getHost() {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }

    /**
     * 账户总览
     */
    public function accountSummary() {
        $userId = $this->getParam('userId');
        $site_id = $this->getParam('siteId', 100);

        // 默认返回的字段
        $fields = 'id,user_name,real_name,mobile_code,mobile,idno,is_effect,user_purpose,
            group_id,user_type,country_code,idcardpassed,photo_passed,email,email_sub';

        $condition = "id = ':userId'";
        $user = UserModel::instance()->findBy($condition, $fields, array(':userId'=>$userId), true);
        $user_info = $user ? $user->getRow() : array();

        $result['mobile'] = $user_info['mobile'] ? moblieFormat($user_info['mobile'], $user_info['mobile_code']) : "无";
        $result['name'] = $user_info['real_name'] ? $user_info['real_name'] : "无";
        $result['country_code'] = $user_info['country_code'] ? $user_info['country_code'] : "cn";
        $result['email'] = $user_info['email'] ? mailFormat($user_info['email']) : "无";
        $result['email_sub'] = $user_info['email_sub'] ? $user_info['email_sub'] : "无";
        $result['idno'] = $user_info['idno'];
        $result["idcard_passed"] = $user_info['idcardpassed'];
        $result["photo_passed"] = $user_info['photo_passed'];
        $result['user_purpose'] = $user_info['user_purpose'];

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userId));
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:' . $GLOBALS['sys_config']['STATIC_HOST'] . '/' . $attachment['attachment'];
            $bind_bank = $bankcard['verify_status'];
        } else {
            $bank_no = '无';
            $bank_name = '';
            $bank_icon = '';
            $bind_bank = 0;
        }

        $result['cardVerifyStatus'] = $bind_bank;
        if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            $bonus = $this->rpc->local('BonusService\get_useable_money', array($userId));
        } else {
            $bonus['money'] = 0;
        }

        $result['card'] = $bankcard['bankcard'] ? formatBankcard($bankcard['bankcard']) : "无";
        $result["bank_no"] = $bank_no;
        $result["bank"] = $bank_name;
        $result["bank_icon"] = $bank_icon;
        $result['bonus'] = format_price($bonus['money'], false);

        // 判断是否是企业用户
        $isEnterpriseUser = $this->rpc->local('UserService\checkEnterpriseUser', array($userId));
        $result['isEnterpriseUser'] = $isEnterpriseUser ? 1 : 0;
        if ($isEnterpriseUser) {
            $result['verify_status'] = $this->rpc->local('EnterpriseService\getVerifyStatus', array($userId));
        }

        $result['bind_bank'] = $bind_bank;
        $bind_coupon = $this->rpc->local('CouponService\getCouponLatest', array($userId));
        // 绑定邀请码
        $result['bindCoupon'] = $bind_coupon['short_alias'] ? $bind_coupon['short_alias'] : "";
        $result['canBindCoupon'] = 0;
        if (empty($result['bindCoupon'])) {
            $cpRes = $this->rpc->local('CouponBindService\getByUserId', [$userId]);
            if(empty($cpRes['is_fixed']) && empty($cpRes['short_alias'])) {
                $result['canBindCoupon'] = 1;
            }
        }

        // 获取用户微信头像
        $avatar = $this->rpc->local('UserImageService\getUserImageInfo', array($userId));
        $result['avatar'] = '';
        // 记录用户头像来源
        $avatarFrom = '';
        if ($avatar && !empty($avatar['attachment'])) {
            $avatarFrom = 'UserImageService本地用户头像';
            if (stripos($avatar['attachment'], 'http') === 0) {
                $result['avatar'] = $avatar['attachment'];
            } else {
                $result['avatar'] = 'http:' . (isset($GLOBALS['sys_config']['STATIC_HOST']) ? $GLOBALS['sys_config']['STATIC_HOST'] : '//static.firstp2p.com') . '/' . $avatar['attachment'];
            }
        } else {
            $avatar = $this->rpc->local('UserProfileService\getUserHeadImg', array($user_info['mobile']));
            if (!empty($avatar['headimgurl']) && stripos($avatar['headimgurl'], 'http') === 0) {
                $avatarFrom = 'UserProfileService调用的微信用户头像';
                $result['avatar'] = $avatar['headimgurl'];
            }
        }
        $result['bad_avatar_md5'] = app_conf('WEIXIN_DEFAULT_IMG_MD5');

        // 会员编号
        $result['userNum'] = numTo32($userId, 0);

        //会员信息
        $result['isShowVip'] = 0;
        if ($this->rpc->local("VipService\isShowVip", array($userId), VipEnum::VIP_SERVICE_DIR)) {
            $result['isShowVip'] = 1;
            $vipInfo = $this->rpc->local("VipService\getVipInfoForSummary", array($userId), VipEnum::VIP_SERVICE_DIR);
            $result['isUpgrade'] = $vipInfo['isUpgrade'];
            $result['vipGradeName'] = $vipInfo['vipGradeName'];
            $result['upgradeCondition'] = $vipInfo['upgradeCondition'];
        }

        //分享相关
        $result['euid'] = $this->rpc->local("OpenService\getEuid", array(array('userId' => $userId)));
        return $this->formatResult($result);
    }

    private function getRetUserInfo($userInfo) {
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userInfo['id']));
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            $bind_bank = 1;
        } else {
            $bank_no = '无';
            $bank_name = '';
            $bank_icon = '';
            $bind_bank = 0;
        }

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($userInfo['id']));
        return array(
            "uid" => $userInfo['id'],
            "username" => $userInfo['user_name'],
            "name" => $userInfo['real_name'] ? $userInfo['real_name'] : "无",
            "money" => number_format($userInfo['money'], 2),
            "idno" => $userInfo['idno'],
            "idcard_passed" => $userInfo['idcardpassed'],
            "photo_passed" => $userInfo['photo_passed'],
            "mobile" => !empty($userInfo['mobile']) ? moblieFormat($userInfo['mobile']) : '无',
            "email" => !empty($userInfo['email']) ? mailFormat($userInfo['email']) : '无',
            "bank_no" => $bank_no,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            'bonus' => format_price($bonus['money'], false),
            'force_new_password' => $userInfo['force_new_passwd'],
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $userInfo['isSeller'],
            'couponUrl' => $userInfo['couponUrl'],
            'isO2oUser' => $userInfo['isO2oUser'],
            'showO2O' => $userInfo['showO2O'],
            // } END

            'bind_bank' => $bind_bank,
        );
    }

    /**
     * 获取api token
     * @param $userId int 用户id
     * @param $tokenExpireTime int token的过期时间
     */
    public function getApiToken() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult('');
        }

        $tokenExpireTime = $this->getParam('tokenExpireTime');
        $userTokenService = new UserTokenService();
        $token = $userTokenService->getApiToken($userId, $tokenExpireTime);
        return $this->formatResult($token ? $token: '');
    }

    /**
     * 通过token获取用户信息
     * @param $token string token码
     */
    public function getUserByCode() {
        $token = $this->getParam('token');

        $userService = new UserService();
        $userInfo = $userService->getUserByCode($token);
        // 处理user的值
        if (!empty($userInfo['user'])) {
            $user = $userInfo['user'];
            $userInfo['user'] = $user->getRow();
        }

        return $this->formatResult($userInfo);
    }

    /**
     * 获取用户信息
     * @param $userId int 用户id
     */
   public function userInfo() {
        $userId = $this->getParam('userId');

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id"=>$userId));
        $cardVerifyStatus = 0;
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id'=>$bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            $bind_bank = 1;
            $cardVerifyStatus = $bankcard['verify_status'];
        } else {
            $bank_no = '无';
            $bind_bank = 0;
            $bank_name = '';
            $bank_icon = '';
        }

        if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            $bonus = $this->rpc->local('BonusService\get_useable_money', array($userId));
        } else {
            $bonus['money'] = 0;
        }

        $result = array(
            "bank_no" => $bank_no,
            "bind_bank" => $bind_bank,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            "cardVerifyStatus" => $cardVerifyStatus,
            'bonus' => format_price($bonus['money'], false),
        );

        return $this->formatResult($result);
    }

    // 获取用户的快捷银行卡信息
    public function limitBankInfo() {
        $bankService = new BankService();
        $bankInfo = $bankService ->getFastPayBanks();
        if ($bankInfo['status'] != '') {
            return $this->setErr('ERR_SYSTEM' ,$bankInfo['msg']);
        }

        $userId = $this->getParam('userId');

        $userBankcardService = new UserBankcardService();
        $bankcard = $userBankcardService->getBankcard($userId);
        $bankList = isset($bankInfo['data']) ? $bankInfo['data'] : array();
        $bankId = isset($bankcard['bank_id']) ? $bankcard['bank_id'] : '';
        $bankLimit = null;
        foreach ($bankList as $item) {
            if ($item['bank_id'] == $bankId ) {
                $bankLimit = $item;
                break;
            }
        }

        return $this->formatResult($bankLimit);
    }

    /**
     * 红包首页
     */
    public function bonusGet() {
        $userId = $this->getParam('userId');
        $page = 0;
        $pageSize = 10;

        $result = array();
        $response = $this->rpc->local('BonusService\getBonusLogList', [$userId, $page + 1, $pageSize]);
        $list = $response['list'];
        $result['all'] = ['list' => $list, 'count' => $response['page']['total']];

        $response = $this->rpc->local('BonusService\getUserBonusInfo', [$userId]);
        $result['userInfo'] = $response;

        $shareCount = $this->rpc->local('BonusService\getUnsendCount', [$userId]);
        $result['shareCount'] = $shareCount;

        $result['sendUrl'] = urlencode($this->getHost(). '/bonus/send?wxb=true');

        // 清除红包的状态
        $this->rpc->local('WXBonusService\delIncomeStatus', [$userId]);

        return $this->formatResult($result);
    }

    //分享红包相关
    public function bonusSend() {
        $page = $this->getParam('page');
        $pageSize = $this->getParam('pageSize');
        $siteId = $this->getParam('siteId');
        $userId = $this->getParam('userId');

        $bonusService = new BonusService();
        $groupList = $bonusService->get_group_list($userId, true, $page, $pageSize);
        $list = array();
        $time = time();
        $host = app_conf('API_BONUS_SHARE_HOST');
        $couponService = new CouponService();
        $senderUserCoupon = \SiteApp::init()->dataCache->call($couponService, 'getOneUserCoupon', array($userId), 10);
        $bonusTemplete = \SiteApp::init()->dataCache->call($bonusService, 'getBonusTempleteBySiteId', array($siteId), 10);
        if (!empty($bonusTemplete)) {
            $share_icon    = $bonusTemplete['share_icon'];
            $share_title   = $bonusTemplete['share_title'];
            $share_content = $bonusTemplete['share_content'];
        } else {
            $share_icon    = get_config_db('API_BONUS_SHARE_FACE',$siteId);
            $share_title   = get_config_db('API_BONUS_SHARE_TITLE', $siteId);
            $share_content = get_config_db('API_BONUS_SHARE_CONTENT', $siteId);
        }

        foreach ($groupList['list'] as $item) {
            $tmp = array();
            $tmp['id'] = $item['id'];
            $tmp['isNew'] = $item['isNew'] ?: 0;
            $tmp['createdAt'] = format_date($item['created_at'], 'Y-m-d H:i:s');
            $tmp['expiredAt'] = format_date($item['expired_at'] - 1, 'Y-m-d H:i:s');
            $tmp['loanId'] = $item['deal_load_id'];
            $tmp['count'] = $item['count'];
            $tmp['usedNum'] = $item['use_num'];
            $tmp['sendNum'] = $item['send_num'];
            $tmp['leftNum'] = $item['count'] - $item['send_num'];
            if ($tmp['leftNum'] <= 0) { // 发光了
                $tmp['flag'] = 0;
            } elseif ($item['expired_at'] < $time) { // 过期
                $tmp['flag'] = 2;
            } else { // 可以发
                $tmp['flag'] = 1;
                if (isset($item['link'])) {
                    $tmp['url'] = urlencode($host . $item['link'] . '?sn='.$item['sn']); // web端提供
                } else {
                    $tmp['url'] = urlencode($host.'/hongbao/GetHongbao?sn='.$item['id_encrypt']); // web端提供
                }
                $tmp['shareContent'] = str_replace('{$BONUS_TTL}', $item['count'], $share_content);
                $tmp['shareContent'] = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $tmp['shareContent']));
            }
            $list[] = $tmp;
        }
        // 分享链接扩展信息
        $face = urlencode($share_icon);
        $title = urlencode(str_replace('{$COUPON}', $senderUserCoupon['short_alias'], $share_title));

        $result = array(
            'count' => $groupList['count'],
            'list' => $list,
            'face' => $face,
            'title' => $title,
        );

        return $this->formatResult($result);
    }

    /* 获取用户角色
     * @array userInfo array('id' => $userId, 'user_name' => $userName)
     */
    public function getUserRole() {
        $userInfo = $this->getParam('userInfo');

        //判断用户角色，包括 担保公司用户、普通用户（借款人、出借人）
        $userService = new UserService();
        $user_agency_info = $userService->getUserAgencyInfoNew($userInfo);
        $user_advisory_info = $userService->getUserAdvisoryInfo($userInfo);
        $user_entrust_info = $userService->getUserEntrustInfo($userInfo);

        $result = array(
            'user_agency_info' => $user_agency_info,
            'user_advisory_info' => $user_advisory_info,
            'user_entrust_info' => $user_entrust_info,
         );
        return $this->formatResult($result);
    }

    /**
     * 是否在黑名单里
     * 查询value是否存在黑白名单里
     * @param $userId
     * @return bool
     */
    public function checkBwList(){
        $typeKey = $this->getParam('typeKey');
        $value = $this->getParam('value');

        $bwlistService = new BwlistService();
        $res = $bwlistService->inList($typeKey, $value);
        return $this->formatResult($res);
    }

    /**
     * 闪屏接口
     */
    public function getSplashInfo() {
        $service = new \core\service\SplashService();

        $os = $this->getParam('os');
        $width = $this->getParam('width');
        $height = $this->getParam('height');
        $siteId = $this->getParam('siteId');
        $result = $service->getSplashInfo($os, $width, $height, $siteId);
        return $this->formatResult($result);
    }

    //用户偏好的投资
    public function getDealRecommend() {
        $userId = $this->getParam('userId');

        $recommendService = new RecommendService();
        $res = $recommendService->getDealRecommend($userId);
        return $this->formatResult($res);
    }

    /**
     * 我的页面中，未使用红包、礼券、投资券和风险评估的信息
     */
    public function userCount() {
        $userId = $this->getParam('userId');

        $usableInfo = $this->rpc->local('BonusService\get_useable_money', array($userId));
        $data = array();
        $data['giftValidCount']     = intval($this->rpc->local('O2OService\getUnpickCount', array($userId)));
        $data['bonusValidCount']    = intval($this->rpc->local('BonusService\getUnSendCount', array($userId)));
        $data['bonusValidMoney']    = $usableInfo['money'];
        $data['discountValidCount'] = intval($this->rpc->local('O2OService\getUserUnusedDiscountCount', array($userId)));

        //获取24小时内将过期的红包金额
        $args = array(
            'userId' => $userId,
            'status' => 1,
            'endExpireTime' => (time() + 24*3600)
        );
        $data['willExpireBonusMoney']    = intval($this->rpc->local('BonusService\getUserSumMoney', array($args)));
        $data['willExpireDsicountCount'] = intval($this->rpc->local('O2OService\getUserWillExpireDiscountCount', array($userId)));//24小时即将过期的投资券数量
        $data['willExpireCouponCount']   = intval($this->rpc->local('O2OService\getUserWillExpireCouponCount', array($userId)));//24小时即将过期的礼券数量

        $riskData = array();
        $riskRes = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array($userId));
        $riskData['levelName'] = empty($riskRes['last_level_name']) ? '' : $riskRes['last_level_name'];
        if (isset($riskRes['remaining_assess_num'])) {
            $riskData['remainingNum'] = intval($riskRes['remaining_assess_num']);
        } else {
            $riskData['remainingNum'] = !empty($riskRes['ques']) ? 1 : 0;
        }

        $riskData['limitType'] = !empty($riskRes['ques']) ? $riskRes['ques']['limit_type'] : 0;
        $riskData['status'] = !empty($riskRes['ques']) ? 1 : 0;
        $data['riskData'] = $riskData;

        return $this->formatResult($data);
    }

    public function discountMine() {
        $user_id = $this->getParam('userId');
        if (empty($user_id)) {
            return $this->formatResult(array('total' => 0, 'totalPage' => 0, 'list' => array()));
        }
        $page = $this->getParam('page');

        //过滤黄金券
        $type = $this->getParam('discountType');
        $siteId = 100;
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        $useStatus = $this->getParam('useStatus', 1);

        $rpcParams = array($user_id, 0, $page, 10, $type, 1, $useStatus);
        $couponList = $this->rpc->local('O2OService\getUserDiscountList', $rpcParams);
        if ($couponList === false) {
            $couponList = array('total' => 0, 'totalPage' => 0, 'list' => array());
        }

        /*微信分享信息Start*/
        $couponInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getOneUserCoupon', array($user_id)), 10);
        $wxDiscountTemplate = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DiscountService\getTemplateInfoBySiteId', array($siteId)), 10);
        $shareIcon    = urlencode($wxDiscountTemplate['shareIcon']);
        $shareTitle   = $wxDiscountTemplate['shareTitle'];
        $shareContent = $wxDiscountTemplate['shareContent'];

        $shareHost = app_conf('API_BONUS_SHARE_HOST');
        foreach ($couponList['list'] as &$item) {
            //格式化信息Start
            $goodsPrice = $item['goodsPrice'];
            if ($item['type'] == 1 && ceil($item['goodsPrice']) == $item['goodsPrice']) {
                $goodsPrice = intval($goodsPrice);
            }
            $goodsDesc = "金额满".number_format($item['bidAmount'])."元";
            if ($item['bidDayLimit']) {
                $goodsDesc .= "，期限满{$item['bidDayLimit']}天";
            }
            $goodsDesc .= '可用';
            if ($item['type'] == 1) {
                $goodsType = '返现券';
                $goodsPrice = $goodsPrice."元";
            } else {
                $goodsType = '加息券';
                $goodsPrice = $goodsPrice."%";
            }
            //格式化信息End
            $item['shareUrl']     = urlencode(sprintf('%s/discount/GetDiscount?sn=%s&cn=%s', $shareHost, $this->rpc->local('DiscountService\generateSN', array($item['id'])), $couponInfo['short_alias']));
            $item['shareContent'] = urlencode(str_replace('{COUPON_DESC}', $goodsDesc, $shareContent));
            $item['shareTitle'] = urlencode(str_replace(array('{COUPON_PRICE}', '{COUPON_TYPE}'), array($goodsPrice, $goodsType), $shareTitle));
        }
        $result = array();
        $result['shareIcon'] = $shareIcon;
        /*微信分享信息End*/
        $this->rpc->local('O2OService\clearUserMoments', array($user_id));//清除投资券的状态
        $result['couponList'] = $couponList;
        $result['discountListNum'] = is_array($couponList['list']) ? count($couponList['list']) : 0;
        $result['o2oDiscountSwitch'] = $o2oDiscountSwitch;
        $result['siteId'] = $siteId;
        $result['discountCenterUrl'] = (new \core\service\ApiConfService())->getDiscountCenterUrl(1);

        return $this->formatResult($result);
    }

    /**
     * 我的优惠码接口
     */
    public function accountCouponInvite() {
        $userId = $this->getParam('userId');
        $site_id = $this->getParam('siteId');
        $from = $this->getParam('from');
        $siteDomain = $this->getParam('siteDomain');
        $siteCoupon = $this->getParam('siteCoupon');
        $siteLogo = $this->getParam('siteLogo');
        $euidLevel= $this->getParam('euidLevel');
        $euid= $this->getParam('euid');

        $GLOBALS['sys_config']['TPL_SITE_DIR'] = $GLOBALS['sys_config']['TPL_SITE_LIST'][$site_id];

        $result = array();

        $isO2O = 0;
        if ($this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_HY_USER', $userId))
            || $this->rpc->local('UserTagService\getTagByConstNameUserId', array('O2O_SELLER', $userId))) {
            $isO2O = 1;
        }

        $adv_id = $isO2O ? "O2O我的邀请码说明" : "我的邀请码说明";
        $adv_content = $this->rpc->local('AdvService\getAdv', array($adv_id));
        $invite_text = $this->completeH5($adv_content);
        $result['inviteTextH5'] = $invite_text;

        $result['isRealAuth'] = 1;
        $is_used_code = $this->rpc->local('CouponService\isCouponUsed', array($userId));
        // 没有通过身份认证   并且没有使用过
        if (($user['idcardpassed'] != 1) && !$is_used_code) {
            $result['isRealAuth'] = 0;
            return $this->formatResult($result);
        }

        $coupons = $this->rpc->local('CouponService\getUserCoupons', array($userId));
        if ($this->rpc->local('BonusService\isCashBonusSender', array($userId, $site_id))) {//现金红包分享
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
        } else {
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG", $site_id);
        }

        // 返利文案
        $referer_rebate_info = $this->rpc->local('CouponService\getRebateInfo', array($userId));
        if($referer_rebate_info["rebate_effect_days"] || !$referer_rebate_info["basic_group_id"]){
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG",site_id);
        }else{
            $referer_rebate_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_REFERER_REBATE_MSG_NO_LIMIT",site_id);
        }

        //根据site_id，然后配置对应站点的域名
        if ($site_id) {
            if (intval($site_id) != 0 && intval($site_id) != 1){
               $template_list = $GLOBALS['sys_config']['TEMPLATE_LIST'];
               $site_name = array_search($site_id,$template_list);
               if($site_name){
                 $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN']["$site_name"];
                 if ($site_domain) {
                    $this->shareUrlPre = str_replace(app_conf('WXLC_DOMAIN').'/hongbao/CashGet?cn=%s',$site_domain.'/user/register?type=h5&cn=%s',$this->shareUrlPre);
                    //$shareRegUrl = str_replace(app_conf('WXLC_DOMAIN'), $site_domain, $shareRegUrl);
                 }
               }
            }
        }

        $wxBonusSrv = new \core\service\WXBonusService();
        $inviteSwitch = $wxBonusSrv->isInviter($userId, $site_id);
        if ($inviteSwitch) {
            $share_msg = get_config_db("COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS", $site_id);
        }

        //4.6版本后的分享url
        $shareRegUrl = get_config_db('APP_COUPON_INVITE_CARD_URL', $site_id);
        $coupon_list = array();
        foreach ($coupons as $k => $val){
            $c = $val;
            $c['couponId'] = $k;
            $c['rebate_ratio'] = sprintf("%.2f", $val['rebate_ratio']);
            $c['referer_rebate_ratio'] = sprintf("%.2f", $val['referer_rebate_ratio']);
            $c['shareMsg'] = urlencode(str_replace('{$COUPON}', $val['short_alias'], $share_msg));
            if(!empty($referer_rebate_msg)){
                $c['referer_rebate_msg'] = str_replace('{$referer_rebate_ratio}', $c['referer_rebate_ratio'], $referer_rebate_msg);
            }
            $c['shareUrl'] = sprintf($this->shareUrlPre, $val['short_alias']);
            if ($inviteSwitch) {
                $c['shareRegUrl'] = $wxBonusSrv->getShareUrl($val['short_alias']);
            } else {
                $c['shareRegUrl'] = str_replace('{$COUPON}', $val['short_alias'], $shareRegUrl);
            }
            $coupon_list[] = $c;
        }

        $TotalRefererRebateAmount = $this->rpc->local('CouponLogService\getTotalRefererRebateAmount', array($userId));
        $result['couponLog']['referer_rebate_amount'] = number_format($TotalRefererRebateAmount['referer_rebate_amount'],2);
        $result['couponLog']['referer_rebate_amount_no'] = number_format($TotalRefererRebateAmount['referer_rebate_amount_no'],2);
        $TotalInviteNumber = $this->rpc->local('CouponLogService\getTotalInviteNumber', array($userId));
        $result['couponLog']['consume_user_count'] = $TotalInviteNumber;
        $result['coupons'] = $coupon_list;
        $result['shareMsg'] = $shareMsg;
        $result['siteid'] = $site_id;
        $result['isO2O'] = $isO2O;
        $result['shareTitle'] = get_config_db('APP_COUPON_INVITE_CARD_TITLE', $site_id);
        $result['shareDescribe'] = get_config_db('APP_COUPON_INVITE_CARD_DESCRIBE', $site_id);
        $result['shareImg'] = get_config_db('APP_COUPON_INVITE_CARD_IMG', $site_id);

        if ($inviteSwitch) {
            $result['shareTitle'] = get_config_db('CASH_BONUS_SHARE_TITLE', $site_id);
            $result['shareDescribe'] = get_config_db('COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS', $site_id);
        }

        $couponModelTypes = CouponLogService::getModelTypes();
        foreach($couponModelTypes as $modelKey => $modelName){
            if ($site_id == 100 && $modelKey == CouponLogService::MODULE_TYPE_P2P) {
                $modelName = '投资奖励';
            }
            $result['types'][] = array('typeid' => $modelKey,'typename' => $modelName);
        }

        if ($from == 'wap') {
            $data = array();
            $data['userId'] = $userId;
            $data['site_domain'] = $siteDomain;
            $data['site_coupon'] = $siteCoupon;
            $data['site_logo'] = $siteLogo;
            $data['euid_level'] = $euidLevel;
            $data['euid'] = $euid;
            if (!$inviteSwitch) {
                $result = $this->wapShare($data, $result);
            }
        }

        return $this->formatResult($result);
    }

    private function completeH5($content) {
        $html5 = <<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="format-detection" content="telephone=no" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <title>邀请规则</title>
</head>
<body>
{$content}
</body>
</html>
HTML;
        return $html5;
    }


    private function wapZhuZhanShare($data, $result) {
        $openSrv = new OpenService();
        $appInfo = $openSrv->getAppInfoByUid($data['userId']);
        if(empty($appInfo)) {
            return $result;
        }

        $setParams = (array) json_decode($appInfo['setParams'], true);
        if ($appInfo['inviteCode'] && $setParams['showSiteCoupon']) {
            $data['couponId'] = $appInfo['inviteCode'];
            $data['showSiteCoupon'] = 1;
        } else{
            $data['couponId'] = $result['coupons'][0]['couponId'];
            $data['showSiteCoupon'] = 0;
        }

        $data['site_domain'] = $appInfo['usedWapDomain'];
        $data['site_logo']   = $appInfo['appLogo'];
        $data['euid_level']  = $setParams['euidLevel'] > 1 ? intval($setParams['euidLevel']) : 1;
        return $this->getFenZhanShareReturn($data, $result);
    }

    private function getFenZhanShareUrl($data) {
        $openSrv = new OpenService();
        $shareEuid = $openSrv->getEuid($data);
        if ($shareEuid) {
            $shareEuid = 'euid=' . $shareEuid;
        }

        return 'http://' . $data['site_domain'] . '/user/register?cn=' . $data['couponId'] . '&' . $shareEuid; // & => %26
    }

    private function getFenZhanShareMsg($data) {
        $shareMsg = "100元开启财富之旅！历史平均年化收益8％~12％，0手续费，期限灵活。任务勋章、投资券、加息券等，你想要的玩法全都有！邀请码:%s。%s";
        return sprintf($shareMsg, $data['couponId'], $data['share_url']);
    }

    private function getFenZhanShareReturn($data, $result) {
        $data['share_url'] = $this->getFenZhanShareUrl($data);
        return [
                'showSiteCoupon' => $data['showSiteCoupon'],
                'shareImg'       => $data['site_logo'],
                'couponLog'      => ($data['showSiteCoupon'] == 1) ? [] : $result['couponLog'],
                'types'          => ($data['showSiteCoupon'] == 1) ? [] : $result['types'],
                'coupons'        => [[
                   'couponId'    => $data['couponId'],
                   'shareRegUrl' => urlencode($data['share_url']),
                   'shareMsg'    => urlencode($this->getFenZhanShareMsg($data)),
                ]],
        ];
    }

    private function WapFenZhanShare($data, $result) {
        if ($data['site_coupon']) {
            $data['couponId'] = $data['site_coupon'];
            $data['showSiteCoupon'] = 1;
        }else{
            $data['couponId'] = $result['coupons'][0]['couponId'];
            $data['showSiteCoupon'] = 0;
        }

        return $this->getFenZhanShareReturn($data, $result);
    }

    private function wapShare($data, $result) {
        return $data['site_id'] > 1 ? $this->WapFenZhanShare($data, $result) : $this->wapZhuZhanShare($data, $result);
    }

    public function giftMineDetail() {
        $couponId = $this->getParam('couponId');
        $user_id = $this->getParam('userId');
        $storeId = $this->getParam('storeId');
        $useRules = $this->getParam('useRules');
        $address_id = $this->getParam('address_id');
        $rpcParams = array($couponId, $user_id);
        $couponDetail = $this->rpc->local('O2OService\getCouponInfo', $rpcParams);

        if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
            $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($couponDetail));
            $resultJson['gameUrl'] = $gameUrl;
        } else if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {

            // 领取成功，直接玩游戏
            // 获取游戏内容详情
            $error = '';
            $eventId = intval($couponDetail['useFormId']);
            $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
            $event = $this->rpc->local('GameService\getEventDetail', array($loginUser['id'], $eventId, false));
            if ($event === false) {
                $error = $this->rpc->local('GameService\getErrorMsg');
                $event = GameEnum::$DEFAULT_EVENT_DETAIL;
            }
            $response['eventId'] = $eventEncodeId;
            $response['event'] = $event;
            $response['errors'] = $error;
        } else {
            if (in_array($couponDetail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
                $storeId = $couponDetail['storeId'];
                $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $couponDetail['useRules']));
                $response['formConfig'] = $formConfig['form'];
                $response['storeName'] = $formConfig['storeName'];
                $response['titleName'] = $formConfig['titleName'];
            }
            if ($couponDetail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REPORT || $couponDetail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REALTIME) {
                $returnUrl = '/gift/mineDetail';
                $address = $this->rpc->local('AddressService\getAddress', array($loginUser['id'],$address_id));
                if (!empty($address_id)) {
                    $response['address_id'] = $address_id;
                }
                $response['address'] = $address;
                $response['returnUrl'] = $returnUrl;
            }
            $response['coupon'] = $couponDetail;
        }

        return $this->formatResult($response);
    }

    public function getUnpickList() {
        $userId = $this->getParam('userId');
        $page = $this->getParam('page', 1);
        $pageSize = $this->getParam('pageSize', 10);
        $status = $this->getParam('status', OtoAcquireLogModel::UNPICK_ALL);

        $rpcParams = array($userId, $page, $pageSize, $status);
        $unPickList = $this->rpc->local('O2OService\getUnpickList', $rpcParams);

        return $this->formatResult($unPickList);
    }

    public function giftAcquireDetail()
    {
        // get params
        $couponGroupId = $this->getParam('couponGroupId');
        $userId = $this->getParam('userId');
        $mobile = $this->getParam('mobile');
        $action = $this->getParam('action');
        $loadId = $this->getParam('loadId');
        $address_id = $this->getParam('addressId');

        $rpcParams = array($couponGroupId, $userId, $action, $loadId);
        $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', $rpcParams);

        $resultJson = array();
        if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME || $gift_detail['useRules'] ==  CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
            // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
            $isNeedExchange = 1;// 新版接口，需要完成兑换操作
            $gameParams = array($couponGroupId, $userId, $action, $loadId, $mobile, array(), array(), $isNeedExchange);
            PaymentApi::log('礼券详情 - 兑换游戏活动次数 - 请求参数' . var_export($gameParams, true));
            $gift = $this->rpc->local('O2OService\acquireExchange', $gameParams);
            if (empty($gift)) {
                // 领取错误展示
                $msg = $this->rpc->local('O2OService\getErrorMsg');
                $resultJson['errMsg'] = $msg;
                $resultJson['flag'] = 'acquireExchange';
            } else {
                if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                    $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail));
                    $resultJson['gameUrl'] = $gameUrl;
                } else {
                    // 领取成功，直接玩游戏
                    $error = '';
                    $eventId = intval($gift_detail['useFormId']);
                    $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
                    $event = $this->rpc->local('GameService\getEventDetail', array($userId, $eventId, false));
                    if ($event === false) {
                        $error = $this->rpc->local('GameService\getErrorMsg');
                        $event = GameEnum::$DEFAULT_EVENT_DETAIL;
                    }

                    $resultJson['eventId'] = $eventEncodeId;
                    $resultJson['gameHost'] = app_conf('ACTIVITY_WEIXIN_HOST');
                    $resultJson['event'] = $event;
                    $resultJson['errors'] = $error;
                }
            }
        } else {
            if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
                $storeId = $gift_detail['storeId'];
                $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $gift_detail['useRules']));
                $resultJson['formConfig'] = $formConfig['form'];
                $resultJson['storeName'] = $formConfig['storeName'];
                $resultJson['titleName'] = $formConfig['titleName'];
            }
            if($gift_detail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REPORT || $gift_detail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REALTIME) {
                $returnUrl = '/gift/acquireDetail';
                $address = $this->rpc->local('AddressService\getAddress', array($loginUser['id'],$address_id));
                if (!empty($address_id)) {
                    $resultJson['address_id'] = $address_id;
                }
                $resultJson['address'] = $address;
                $resultJson['returnUrl'] = $returnUrl;
            }
            $resultJson['coupon'] = $gift_detail;
        }

        return $this->formatResult($resultJson);
    }

    /**
     * 用户注册功能
     * @param $params array 用户注册相关参数
     * @return array
     */
    public function userRegister() {
        $params = $this->getParam('params');

        // 注册来源平台(1:Web|2:App)
        $from_platform = (int)$params['from_platform'];
        // 邀请码判断
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert($params['phone'], '', 3, 0, $from_platform, $params['invite']);
        if (!empty($params['invite'])) {
            $params['invite'] = trim($params['invite']);
            $ret = $this->rpc->local('CouponService\checkCoupon', array($params['invite']));
            //  如果验证码不正确
            if ($ret === false || $ret['coupon_disable'] ) {
                $err = $ret['coupon_disable'] == 0 ? 'ERR_COUPON_EFFECT' : 'ERR_COUPON_DISABLE';
                $logRegLoginService->insert($params['phone'], '', 2, 0, $from_platform, $params['invite'], $err);
                return $this->setErr($err);
            }

            $params['invite'] = str_replace(' ', '', $params['invite']);
        }

        // app 3.5版本 增加弱密码校验
        $mobile = $params['phone'];
        $password = $params['password'];
        // 获取密码黑名单
        \FP::import("libs.common.dict");
        $blacklist = \dict::get("PASSWORD_BLACKLIST");
        // 基本规则判断
        $base_rule_result = login_pwd_base_rule(strlen($password), $mobile, $password);
        if ($base_rule_result) {
            Monitor::add('REGISTER_FAIL');
            $logRegLoginService->insert($params['phone'], '', 2, 0, $from_platform, $params['invite'], $base_rule_result['errorMsg']);
            return $this->setErr('ERR_PASS_RULE', $base_rule_result['errorMsg']);
        }

        // 黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
        if ($forbid_black_result) {
            Monitor::add('REGISTER_FAIL');
            $logRegLoginService->insert($params['phone'], '', 2, 0, $from_platform, $params['invite'], $forbid_black_result['errorMsg']);
            return $this->setErr('ERR_PASS_BLACKLIST', $forbid_black_result['errorMsg']);
        }
        //密码校验结束

        // 加锁，防用户注册重复提交
        $lockKey = "register-user-".$params['phone'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey)) {
            Monitor::add('REGISTER_FAIL');
            return $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
        }

        // 网信普惠的site_id默认是100
        $userInfoExtra = array('site_id' => 100);
        if (isset($params['euid'])) {
            $userInfoExtra['euid'] = $params['euid'];
        }

        // 国别号
        $country_code = !empty($params['country_code']) ? $params['country_code'] : "cn";
        $use_mobile_code = isset($params['use_mobile_code']) ? $params['use_mobile_code'] : false;
        $result = $this->rpc->local('UserService\Newsignup', array(
            $params['username'],
            $params['password'],
            $params['email'],
            $params['phone'],
            $params['code'],
            $params['invite'],
            $userInfoExtra,
            $use_mobile_code,
            $country_code
        ));

        $lock->releaseLock($lockKey);

        if (empty($result) || isset($result['code'])) {
            Monitor::add('REGISTER_FAIL');
            $logRegLoginService->insert($params['phone'], '', 2, 0, $from_platform, $params['invite']);
            switch ($result['code']) {
            case '303':
                $errResult = $this->setErr('ERR_SIGNUP_USERNAME_UNIQUE');
                break;
            case '304':
                $errResult = $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
                break;
            case '305':
                $errResult = $this->setErr('ERR_SIGNUP_EMAIL_UNIQUE');
                break;
            case '319':
                $errResult = $this->setErr('ERR_SIGNUP_CODE');
                break;
            case '320':
                $errResult = $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
                break;
            default:
                $errResult = $this->setErr('ERR_SIGNUP', $result['reason']);
            }

            return $errResult;
        }

        $user_id = $result['user_id'];
        $logRegLoginService->insert($params['phone'], $user_id, 1, 0, $from_platform, $params['invite']);

        // 调用oauth接口进行登录验证
        $username = $params['phone'];
        $password = $params['password'];
        $loginResult = $this->rpc->local("UserService\apiNewLogin", array(
            $username,
            $password,
            false,
            $from_platform,
            $country_code
        ));

        if ($loginResult['success'] !== true) {
            $logRegLoginService->insert($params['username'], $user_id, 1, 0, $from_platform, $params['invite']);
            // 记录日志
            PaymentApi::log(sprintf('%s|%s, userId:%d, userName:%s, phone:%s, 注册成功,登录失败', __CLASS__, __FUNCTION__, $user_id, $params['username'], $username), Logger::INFO);
            // 登录失败则向频次险种中插入记录
            if (\libs\utils\Block::check('LOGIN_USERNAME', $username) === false) {
                // 如果超过限制，则提示需要填写验证码
                return $this->setErr('ERR_VERIFY', "登录认证失败");
            } else {
                // 未超过限制泽提示登录失败
                return $this->setErr('ERR_AUTH_FAIL');
            }
        }

        $token = $loginResult['code'];
        // 调用oauth接口获取用户信息
        $info = $this->rpc->local("UserService\getUserByCode", array($token));
        if ($info['code']) {
            // 获取oauth用户信息失败
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        if ($info['status'] == 0) {
            // 获取本地用户数据失败
            return $this->setErr('ERR_LOGIN_FAIL');
        }

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $info['user']['id']));
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:' . $GLOBALS['sys_config']['STATIC_HOST'] . '/' . $attachment['attachment'];
            $bind_bank = 1;
        } else {
            $bank_no = '无';
            $bank_name = '';
            $bank_icon = '';
            $bind_bank = 0;
        }

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($info['user']['id']));
        $jsonResult = array(
            "token" => $token,
            "uid" => $info['user']['id'],
            "username" => $info['user']['user_name'],
            "name" => $info['user']['real_name'] ? $info['user']['real_name'] : "无",
            "money" => number_format($info['user']['money'], 2),
            "idno" => $info['user']['idno'],
            "idcard_passed" => $info['user']['idcardpassed'],
            "photo_passed" => $info['user']['photo_passed'],
            "mobile" => !empty($info['user']['mobile']) ? moblieFormat($info['user']['mobile']) : '无',
            "email" => !empty($info['user']['email']) ? mailFormat($info['user']['email']) : '无',
            "bank_no" => $bank_no,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            'bonus' => format_price($bonus['money'], false),
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $info['user']['isSeller'],
            'couponUrl' => $info['user']['couponUrl'],
            'isO2oUser' => $info['user']['isO2oUser'],
            'showO2O' => $info['user']['showO2O'],
            // } END
            'bind_bank' => $bind_bank,
            'tokenExpireTime' => (time() + UserTokenService::API_TOKEN_EXPIRE),
            'isBid' => false, //是否首投
        );

        return $this->formatResult($jsonResult);
    }

    /**
     * 用户注册前的参数检查
     * @param $params array 用户注册相关参数
     * @return array
     */
    public function userCheckRegister() {
        $params = $this->getParam('params');

        // 校验邀请码是否有效
        if (!empty($params['invite'])) {
            $params['invite'] = trim($params['invite']);
            $ret = $this->rpc->local('CouponService\checkCoupon', array($params['invite']));
            if ($ret === false || $ret['coupon_disable'] ) {
                return $this->setErr($ret['coupon_disable'] == 0 ? 'ERR_COUPON_EFFECT' : 'ERR_COUPON_DISABLE');
            }
        }

        $result = $this->rpc->local('UserService\checkUserMobile', array($params['phone']));
        if (empty($result) || isset($result['code'])) {
            if ($result['code'] == 320) {
                return $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
            }

            return $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
        }

        return $this->formatResult($result);
    }

    /**
     * 未读的消息个数
     */
    public function messageCount() {
        $userId = $this->getParam('userId');
        try {
            $result = $this->rpc->local('MsgBoxService\getUnreadCount', array($userId));
            $notice = $this->rpc->local('NoticeService\getUserNoticeTips', array($userId));
            $bonusStatus = 0;
            $bonusStatus = 0;
            if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
                $bonusStatus = $this->rpc->local('WXBonusService\getIncomeStatus', array($userId));
                $discountStatus = $this->rpc->local('O2OService\checkUserMoments', array($userId));
            }
        } catch (\Exception $e) {
            Logger::error('MessageError:'.$e->getMessage());
            $result = $notice = 0;
        }

        $result = array(
            'noticeCount' => $notice,
            'unreadCount' => $result,
            'bonusStatus' => $bonusStatus ? 1 : 0,
            'discountStatus' => $discountStatus ? 1 : 0,
        );

        return $this->formatResult($result);
    }

    public function giftAcquireExchange()
    {
        // map
        $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
        $needForm = array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME, CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME, CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT);

        // get params
        $userId = $this->getParam('userId');
        $mobile = $this->getParam('mobile');
        $addressId = $this->getParam('addressId');
        $storeId = $this->getParam('storeId');
        $useRules = $this->getParam('useRules');
        $couponGroupId = $this->getParam('couponGroupId');
        $loadId = intval($this->getParam('loadId'));
        $dealType = $this->getParam('dealType', CouponGroupEnum::CONSUME_TYPE_P2P);
        $action = intval($this->getParam('action'));

        if($storeId && in_array($useRules, $needForm)) {
            //增加错误处理，防止获取表单配置时接口失败导致页面白页
            $formConfig = $this->rpc->local('O2OService\getExchangeForm',array($storeId,$useRules));
            if(false === $formConfig) {
                $result = array(
                    'errMsg' => $this->rpc->local('O2OService\getErrorMsg'),
                    'flag' => 'acquireExchange',
                );
                return $this->formatResult($result);
            }
        }

        // execute
        //根据load_id信息获取触发券组列表校验groupId，防止前端篡改groupId
        $triggerParams = array($userId, $action, $loadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $triggerParams);

        $result = array();
        if (empty($couponGroupList) || !isset($couponGroupList[$couponGroupId])) {
            //非法操作
            $msg = '抢光了！下次要尽早哦！';
            // 控制器标志
            $result['flag'] = 'acquireExchange';
            $result['errMsg'] = $msg;
            return $this->formatResult($result);
        }

        // 根据地址ID获取收货人地址信息
        if(!empty($addressId)) {
            $address = $this->rpc->local('AddressService\getOne', array($userId,$addressId));
            $receiverParam['receiverName'] = $address['consignee'];
            $receiverParam['receiverPhone'] = $address['mobile'];
            $receiverParam['receiverArea'] = $address['area'];
            $receiverParam['receiverAddress'] = $address['address'];
        } else { //根据receiverInfoMap信息获取表单数据
            foreach ($receiverInfoMap as $val) {
                $receiverParam[$val] = self::getFormData($data, $val);
            }
        }

        if (isset($formConfig['form']) && !empty($formConfig['form'])) {
            foreach($formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($data, $k);
            }
        }

        $isNeedExchange = 1;//新版接口，需要完成兑换操作
        //新版接口的领取即兑换需三方标志的操作，前端页面没phone参数，需要专门处理
        if($useRules == CouponGroupEnum::ONLINE_COUPON_ATONCE_REPORT) {
            $extraParam['phone'] = $mobile;
        }

        $rpcParams = array($couponGroupId, $userId, $action, $loadId, $mobile, $receiverParam,
            $extraParam, $isNeedExchange, $dealType);

        $gift = $this->rpc->local('O2OService\acquireExchange', $rpcParams);

        if (empty($gift)) {
            $msg = $this->rpc->local('O2OService\getErrorMsg');
            $result['errMsg'] = $msg;
            $result['flag'] = 'acquireExchange';
        } else {
            $result['receiverParam'] = $receiverParam;
            $result['extraParam'] = $extraParam;
            $result['coupon'] = $gift;
        }

        return $this->formatResult($result);
    }

    private static function getFormData($formData, $name) {
        return isset($formData[$name]) ? $formData[$name] : '';
    }

    public function giftPickList()
    {
        // get params
        $userId = $this->getParam('userId');
        $action = $this->getParam('action');
        $dealLoadId = $this->getParam('dealLoadId');
        $dealType = $this->getParam('dealType', CouponGroupEnum::CONSUME_TYPE_P2P);

        $rpcParams = array($userId, $action, $dealLoadId, $dealType);
        $couponGroupList = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        if ($couponGroupList === false) {
            $couponGroupList = array();
        }

        // return result
        $result = array();
        if (count($couponGroupList) == 1) {
            //只有一个奖品时，进入领取详情页
            $groupInfo = array_pop($couponGroupList);
            $couponGroupId = $groupInfo['id'];
            $rpcParams = array($couponGroupId, $userId, $action, $dealLoadId);
            $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', $rpcParams);
            if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME_CENTER) {
                $gameUrl = $this->rpc->local('O2OService\getGameLinkUrl', array($gift_detail));
                $result['gameUrl'] = $gameUrl;
            } else if ($gift_detail['useRules'] == CouponGroupEnum::ONLINE_COUPON_ATONCE_GAME) {
                // 如果礼券类型是游戏活动，直接调用兑换接口并且跳转到游戏页面
                $isNeedExchange = 1;// 新版接口，需要完成兑换操作
                $gameParams = array($couponGroupId, $userId, $data['action'], $dealLoadId, $loginUser['mobile'],
                    array(), array(), $isNeedExchange, $dealType);

                $gift = $this->rpc->local('O2OService\acquireExchange', $gameParams);
                if (empty($gift)) {
                    // 领取错误展示
                    $msg = $this->rpc->local('O2OService\getErrorMsg');
                    $result['errMsg'] = $msg;
                    $result['flag'] = 'acquireExchange';
                } else {
                    // 领取成功，直接玩游戏
                    // 获取游戏内容详情
                    $error = '';
                    $eventId = intval($gift_detail['useFormId']);
                    $eventEncodeId = $this->rpc->local('GameService\encodeEventId', array($eventId));
                    $event = $this->rpc->local('GameService\getEventDetail', array($userId, $eventId, false));
                    if ($event === false) {
                        $error = $this->rpc->local('GameService\getErrorMsg');
                        $event = GameEnum::$DEFAULT_EVENT_DETAIL;
                    }

                    $result['eventId'] = $eventEncodeId;
                    $result['event'] = $event;
                    $result['error'] = $error;
                }

                return $this->formatResult($result);
            }

            if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_FORM_RULES)) {
                $storeId = $gift_detail['storeId'];
                $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($storeId, $gift_detail['useRules']));
                $result['formConfig'] = $formConfig['form'];
                $result['storeName'] = $formConfig['storeName'];
                $result['titleName'] = $formConfig['titleName'];
            }
            if($gift_detail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REPORT || $gift_detail['useRules'] == CouponGroupEnum::ONLINE_GOODS_REALTIME) {
                $returnUrl = '/gift/acquireDetail';
                $address = $this->rpc->local('AddressService\getAddress', array($userId,$addressId));
                if (!empty($addressId)) {
                    $result['address_id'] = $addressId;
                }
                $result['address'] = $address;
                $result['returnUrl'] = $returnUrl;
            }
            $result['coupon'] = $gift_detail;
        } else {
            $result['couponGroupList'] = $couponGroupList;
            $result['countList'] = count($couponGroupList);
        }

        return $this->formatResult($result);
    }

    public function getUnpickCount()
    {
        $userId = $this->getParam('userId');
        $status = $this->getParam('status', OtoAcquireLogModel::UNPICK_UNEXPIRED);
        $rpcParams = array($userId, $status);
        $count = $this->rpc->local('O2OService\getUnpickCount', $rpcParams);

        return $this->formatResult($count);
    }

    public function giftAcquireForm()
    {
        $couponId = $this->getParam('couponId');
        $userId = $this->getParam('userId');
        $couponGroupId = $this->getParam('couponGroupId', 0);
        $extraInfo = $this->getParam('extraInfo');

        if ($couponId) {
            $rpcParams = array($couponId, $userId);
            PaymentApi::log('webo2o - 进入领取兑换详情页面 - 请求参数'.json_encode($rpcParams, JSON_UNESCAPED_UNICODE));
            $gift_detail = $this->rpc->local('O2OService\getCouponInfo', $rpcParams);
        } else {
            PaymentApi::log('webo2o - 进入领取兑换详情页面 - 请求参数'.json_encode($couponGroupId, JSON_UNESCAPED_UNICODE));
            $gift_detail = $this->rpc->local('O2OService\getCouponGroupInfo', array($couponGroupId, $userId,
                $extraInfo['action'], $extraInfo['loadId'], $extraInfo['dealType']));
        }
        $result = $gift_detail;

        if (in_array($gift_detail['useRules'], CouponGroupEnum::$ONLINE_ORDER_USE_RULES)) {
            $useFormId = $gift_detail['storeId'];
            $formConfig = $this->rpc->local('O2OService\getExchangeForm', array($useFormId, $gift_detail['useRules']));
            $result['formConfig'] = $formConfig['form'];
            $result['storeName'] = $formConfig['storeName'];
            $result['titleName'] = $formConfig['titleName'];
        }

        return $this->formatResult($result);
    }

    public function giftExchangeCoupon()
    {

        $receiverInfoMap = array('receiverName', 'receiverPhone', 'receiverCode', 'receiverArea', 'receiverAddress');
        // get params
        $extraInfo = $this->getParam('extraInfo');
        $storeId = $this->getParam('storeId');
        $useRules = $this->getParam('useRules');
        $userId = $this->getParam('userId');
        $couponId = $this->getParam('couponId');


        $receiverParam = array();
        $extraParam = array();
        //根据receiverInfoMap信息获取表单数据
        foreach($receiverInfoMap as $val) {
            $receiverParam[$val] = self::getFormData($extraInfo, $val);
        }
        $formConfig = $this->rpc->local('O2OService\getExchangeForm',array($storeId,$useRules));
        if (isset($formConfig['form']) && !empty($formConfig['form'])) {
            foreach($formConfig['form'] as $k => $v) {
                $extraParam[$k] = self::getFormData($extraInfo, $k);
            }
        }
        $msgConf = $formConfig['msgConf'];
        $storeName = $this->formConfig['storeName'];
        $msgConf['storeName'] = $storeName;

        $rpcParams = array($userId, $receiverParam, $extraParam);
        PaymentApi::log('webo2o- 兑换优惠券 - 请求参数'.json_encode($rpcParams,JSON_UNESCAPED_UNICODE));
        $couponInfo = $this->rpc->local('O2OService\exchangeCoupon', array($couponId, $userId, $storeId, $receiverParam, $extraParam, $msgConf));
        PaymentApi::log('webo2o - 兑换优惠券 - 请求结果'.json_encode($couponInfo, JSON_UNESCAPED_UNICODE));
        $coupon['useRules'] = $couponInfo['coupon']['useRules'];

        $response = array();
        if (is_array($couponInfo)) {
            $couponExtra = array();
            $coupon['productName'] = $couponInfo['product']['productName'];
            $coupon['updateTime'] = $couponInfo['coupon']['updateTime'];
            $coupon['useEndTime'] = $couponInfo['coupon']['useEndTime'];
            $coupon['couponDesc'] = $couponInfo['couponGroup']['couponDesc'];
            if (in_array($useRules, array(CouponGroupEnum::ONLINE_GOODS_REPORT, CouponGroupEnum::ONLINE_GOODS_REALTIME))) {
                $coupon['receiverName'] = $receiverParam['receiverName'];
                $coupon['receiverPhone'] = $receiverParam['receiverPhone'];
                $coupon['receiverCode'] = $receiverParam['receiverCode'];
                $coupon['receiverAddress'] = $receiverParam['receiverAddress'];
                $response['receiverParam'] = $receiverParam;
                $response['extraParam'] = $extraParam;
            } elseif(in_array($useRules, array(CouponGroupEnum::ONLINE_COUPON_REPORT, CouponGroupEnum::ONLINE_COUPON_REALTIME))) {
                $coupon['storeName'] = $storeName;
                $coupon['titleName'] = $titleName;
                if (isset($formConfig['form']) && !empty($formConfig['form'])){
                    foreach($formConfig['form'] as $k=>$v) {
                        $couponExtra[$k] = array('displayName' => $v['displayName'], 'value' => $extraParam[$k]);
                    }
                }
                $response['formConfig'] = $couponExtra;
                $response['receiverParam'] = $receiverParam;
                $response['extraParam'] = $extraParam;
            }
            $coupon['id'] = $couponId;
            $response['coupon'] = $coupon;
        }

        return $this->formatResult($response);
    }

    const DEFAULT_APP_LOGO = "//event.firstp2p.com/upload/image/20171017/18-18-_20171017181738.png";
    public function openSiteConf(){
        $domain  = $this->getParam('domain');

        if (!$siteId = Open::getSiteIdByDomain($domain)) {
            $returnUrl = $this->rpc->local('OpenService\getNewFzUrl', array($domain));
            if(!empty($returnUrl)){
                return $this->formatResult(array('redirectUrl' => $returnUrl));

            }else{
                return $this->setErr(-1, 'domain not found!');
            }
        }
        //找不到app信息
        if (!$appInfo = Open::getAppBySiteId($siteId)) {
            return $this->setErr(-1, 'app info not found!');
        }

        //检查app状态
        if(!(2 & intval($appInfo['onlineStatus']))) { // 2 表示 wap 端在线
            return $this->setErr(-1, 'error app status!');
        }

        $GLOBALS['sys_config']['APP_SITE'] = $appInfo['appShortName'];
        $appAdvs = (array) Open::getSiteAdvBySiteId($siteId);

        $appConf = Open::getSiteConfBySiteId($siteId);

        if (false === $appConf) {
            return $this->setErr(-1, 'get app conf fail!');
        }

        $response['id'] = $appInfo['id'];
        $response['appName'] = $appInfo['appName'];
        $response['appShortName'] = $appInfo['appShortName'];
        $response['setParams'] = (array)json_decode($appInfo['setParams'], true);
        $response['appLogo'] = empty($appInfo['appLogo']) ? self::DEFAULT_APP_LOGO : $appInfo['appLogo'];
        $response['appDesc'] = $appInfo['appDesc'];
        $response['usedWebDomain'] = $appInfo['usedWebDomain'];
        $response['usedWapDomain'] = $appInfo['usedWapDomain'];
        $response['siteName'] = $appInfo['appName'];
        $response['onlineStatus'] = $appInfo['onlineStatus'];
        $response['inviteCode'] = $appInfo['inviteCode'];
        $response['confInfo'] = Open::getWapTplData($appConf['confInfo'], array('advs' => $appAdvs));

        return $this->formatResult($response);
    }

    /**
     * 获取用户认证信息
     */
    public function getUserCreditFile()
    {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }
        $userCreditFile = get_user_credit_file($userId);

        return $this->formatResult($userCreditFile);
    }

    /**
     * 记录广告联盟相关信息
     */
    public function triggerAdRecord() {
        return $this->formatResult([]);
/*
        $uid = $this->getParam('uid');
        $type = $this->getParam('type');
        $deal_id = $this->getParam('deal_id', 0);
        $load_id = $this->getParam('load_id', 0);
        $money = $this->getParam('money', 0.00);
        $order_channel = $this->getParam('order_channel', 0);
        $coupon = $this->getParam('coupon', '');
        $ceuid = $this->getParam('ceuid', '');
        $ctrack_id = $this->getParam('ctrack_id', 0);

        $adunionDealService = new \core\service\AdunionDealService();
        $res = $adunionDealService->triggerAdRecord(
            $uid,
            $type,
            $deal_id,
            $load_id,
            $money,
            $order_channel,
            $coupon,
            $ceuid,
            $ctrack_id
        );

        return $this->formatResult($res);
*/
    }
}

