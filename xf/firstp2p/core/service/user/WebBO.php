<?php
/**
 * 网站用户业务对象
 * 实现了网站的登录的退出
 *
 *
 */
namespace core\service\user;

require_once APP_ROOT_PATH."system/utils/es_session.php";
require_once APP_ROOT_PATH."system/libs/user.php";

use core\service\CouponService;
use core\service\UserLoanRepayStatisticsService;
use libs\utils\Logger;
use core\service\user\BOBase;
use core\service\user\BOInterface;
use core\dao\UserModel;
use core\dao\UserGroupModel;
use core\dao\EnterpriseModel;
use core\service\BonusService;
use core\service\UserTagService;
use core\service\UserTrackService;
use libs\utils\Monitor;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\O2OService;
use core\service\CouponBindService;
// for tianmai
use core\service\curlHook\ThirdPartyHookService;
// for userProfile
use core\service\UserProfileService;
use core\service\PassportService;
use core\service\UserBindService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use core\dao\WangxinPassportModel;
use core\service\BwlistService;
use core\service\UserTokenService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use NCFGroup\Common\Library\Msgbus;
use core\service\risk\RiskService;

class WebBO extends BOBase implements BOInterface
{

    const USER_REG_REPORT_TOPIC = 'user_reg_report';

    // 是否是API平台登陆, API平台包括api, openpai
    private $_isApiPlatform = false;
    // 各个分站退出时的回调地址白名单
    private $_callback_url = array(
            '12' => array('www.cnpawn.cn','/'),
            '11' => array('www.chedai.com','/'),
            '2' => array('www.diyifangdai.com','/'),
            '31' => array('www.esp2p.com','/'),
            '29' => array('www.creditzj.com','/'),
            '15' => array('www.cnp2p.com','/'),
            '42' => array('www.yijinrong.com','/'),
            '13' => array('www.tianjinp2p.com','/'),
            '51' => array('www.shtcapital.cn','/'),
            );

    public function __construct($platform = 'web')
    {
        if ($platform !== 'web')
        {
            $this->_isApiPlatform = true;
        }
        // oauth开关状态位
        $this->conf = array(
                'oauthSwitch' => $GLOBALS['sys_config']['NEW_OAUTH_SWITCH'],
                );
    }

    public function genOAuthLoginUri($url) {
        return  $GLOBALS['sys_config']['NEW_OAUTH_AUTH_URL'] . 'oauthserver_firstp2p/firstp2p/login/get.do?response_type=code&client_id=' . $GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"] . '&redirect_uri=' . urlencode($GLOBALS['sys_config']["OAUTH_REDIRECT_URI"] . '?state=' . urlencode(urlencode($url)));
    }

    /**
     * 鉴定用户名密码
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $country_code 国别码
     * @param bool $passport 网信通信证
     * @param bool $loginFrom 登陆来源
     * @return string
     */
    public function authenticate($username, $password, $country_code = false, $passport = true, $loginFrom = '') {
        $ret = array('code'=>'0', 'msg'=>'', 'isPassport'=>false);
        $extra = array('user_name' => $username);
        if (is_mobile($username)) {
            $extra['mobile'] = $username;
        }

        if (!RiskService::check('LOGIN', $extra)) {
            $ret['code'] = -13;
            $ret['msg'] = '操作失败，请稍后再试 ';
            RiskService::report('LOGIN', RiskService::STATUS_FAIL, $extra);
            return $ret;
        }

        $passportService = new PassportService();
        $isFromEnterprise = false;
        $qiyeLoginFromTypes = array(UserTokenService::LOGIN_FROM_QIYE_APP, UserTokenService::LOGIN_FROM_QIYE_PC);
        if ($loginFrom && in_array($loginFrom, $qiyeLoginFromTypes)) {
            $isFromEnterprise = true;
        }

        // 通行证鉴权通过，直接返回, 当前只开放大陆手机号登录用户使用
        if (!$isFromEnterprise && $passport && is_mobile($username)) {
            $authRes = $passportService->authenticate($username, $password);
            if ($authRes['authPass']) {
                if (!empty($country_code) && $country_code != $authRes['userInfo']['country_code']) {
                    // 这里需要检查country_code，如果不匹配，可以提前退出
                    $ret['code'] = '-3';
                    $ret['msg'] = '您输入的密码和用户名不匹配';
                    $ret['user_id'] = $authRes['userInfo']['id'];
                } else {
                    // 增加登录埋点
                    try {
                        $message = [
                            'userId' => $authRes['userInfo']['id'],
                            'loginTime' => time(),
                            'device' => $loginFrom
                        ];
                        Msgbus::instance()->produce('login', $message);
                        Logger::info('login produce msgbus success:'.json_encode($message));
                    } catch (\Exception $e) {
                        Logger::error('login produce msgbus error:'.json_encode($message)."|msg:".$e->getMessage());
                    }
                    // passport登陆成功
                    $ret['isPassport'] = true;
                    $ret['data'] = $authRes;
                    $ret['user_id'] = $authRes['userInfo']['id'];
                    $ret['user_name'] = $authRes['userInfo']['user_name'];
                    user_last_time_stack($ret['user_id'], get_gmtime());
                }

                setLog(array('errno' => $ret['code'], 'errmsg' => $ret['msg'], 'uid' => $ret['user_id']));
                return $ret;
            }
        }

        $userinfo = UserModel::instance()->getUserinfoByUsername($username);
        do {
            if ($userinfo) {
                $isEnterpriseUser = (new \core\service\UserService())->checkEnterpriseUser($userinfo['id']);
                // 只有企业投资户和融资户才能登录企业app或者企业网站
                $allowUserPoupose = array(
                    UserAccountEnum::ACCOUNT_INVESTMENT,
                    UserAccountEnum::ACCOUNT_FINANCE,
                    UserAccountEnum::ACCOUNT_RECHARGE,
                    UserAccountEnum::ACCOUNT_REPLACEPAY,
                    UserAccountEnum::ACCOUNT_GUARANTEE
                );

                $isForEnterpriseChannel = $isEnterpriseUser && in_array($userinfo['user_purpose'], $allowUserPoupose)
                    ? true
                    : false;

                // 非企业会员用户或其他用户类型用户使用企业投资入口登录
                if ($isFromEnterprise && !$isForEnterpriseChannel) {
                    $ret['code']= '-10';
                    $ret['msg'] = '非企业投资户请使用个人用户入口登录';
                    break;
                }

                // 企业会员用户且用户类型为投资户 或者 企业融资户但是不是个人会员列表中的用户
                if (!$isFromEnterprise && $isForEnterpriseChannel && is_wxlc()) {
                    $ret['code']= '-11';
                    $ret['msg'] = '企业会员请在企业端登录您的账户';
                    break;
                }

                // 现在只对企业用户登录做冻结判断
                if ($isEnterpriseUser && UserModel::instance()->isUserAccountFreeze($username)) {
                    $ret['code'] = '-12';
                    $ret['msg'] = '用户帐号已冻结';
                    break;
                }

                if (!empty($country_code) && $country_code != $userinfo['country_code']) {
                    $ret['code'] = '-3';
                    $ret['msg'] = '您输入的密码和用户名不匹配 ';
                } else {
                    $password = $this->compilePassword($password);
                    if ($password === $userinfo['user_pwd']) {
                        // 本地登录成功，通行证弹窗逻辑
                        if (!$isFromEnterprise && is_mobile($username)) {
                            if ($passportService->needLocalVerify($username) && !$isEnterpriseUser) {
                                $ret['code'] = '-20';
                                $ret['msg'] = '本地账户因通行证修改密码需二次认证';
                                setLog(array('errno' => $ret['code'], 'errmsg' => $ret['msg'], 'uid' => $userinfo['id']));
                                return $ret;
                            }
                        }
                        // END
                        $ret['code'] = '0';
                        $ret['msg'] = '登录成功';
                        $ret['user_id'] = $userinfo['id'];
                        $ret['user_name'] = $userinfo['user_name'];

                        user_last_time_stack($userinfo['id'], get_gmtime());
                    } else {
                        if($userinfo['site_id'] > 1){ //注册来源是分站
                            //校验是否需要引导用户修改密码
                            $oUserBindService = new UserBindService();
                            $bIs = $oUserBindService->isUserCanResetPwd($userinfo['id']);
                            $ret['code'] = $bIs ? "-33" : '-2';
                        }else{
                            $ret['code'] = '-2';
                        }
                        $ret['msg'] = '您输入的密码和用户名不匹配 ';

                        Monitor::add('LOGIN_USER_PWD_WRONG');
                    }
                }
            } else {
                // 用户不存在
                $ret['code'] = '-2';
                $ret['msg'] = '您输入的密码和用户名不匹配';

                Monitor::add('LOGIN_USER_NOT_EXISTS');
            }
        } while (false);

        $userId = isset($ret['user_id']) ? $ret['user_id'] : '';
        setLog(array(
            'errno' => $ret['code'],
            'errmsg' => $ret['msg'],
            'uid' => $userId
        ));

        $extra['user_id'] = intval($userId);
        if ($ret['code'] != '0') {
            Monitor::add('LOGIN_FAIL');
            RiskService::report('LOGIN', RiskService::STATUS_FAIL, $extra);
        } else {
            RiskService::report('LOGIN', RiskService::STATUS_SUCCESS, $extra);
            // 增加登录埋点
            try {
                $message = [
                    'userId' => $userId,
                    'loginTime' => time(),
                    'device' => $loginFrom
                ];
                Msgbus::instance()->produce('login', $message);
                Logger::info('login produce msgbus success:'.json_encode($message));
            } catch (\Exception $e) {
                Logger::error('login produce msgbus error:'.json_encode($message)."|msg:".$e->getMessage());
            }
        }

        return $ret;
    }
    /**
     * 密码验证，修改密码时验证输入的旧密码是否正确,如果传入手机号码，则验证是不是user表里的手机号
     * @param unknown $user_id
     * @param unknown $pwd_old
     */
    public function verifyPwd($user_id,$pwd = '',$mobile = '') {
        $ret = array('code' => '0', 'msg' => '');
        $userinfo = UserModel::instance()->find($user_id, 'id,user_pwd,mobile');
        if($userinfo){
            if ($pwd) {
                $pwd_compile = $this->compilePassword($pwd);
                if($pwd_compile != $userinfo->user_pwd){
                    $ret['code'] = '3';
                    $ret['msg'] = '旧密码输入错误';
                }
                return $ret;
            }
            if ($mobile) {
                if ($mobile != $userinfo->mobile) {
                    $ret['code'] = '4';
                    $ret['msg'] = '请输入现在绑定的手机号';
                }
                return $ret;
            }
        } else {
            $ret['code'] = '-2';
            $ret['msg'] = '用户不存在';
            return $ret;
        }
        return $ret;
    }
    public function updateNewPwd($user_id,$pwd_new)
    {
        $ret = array('code' => '0', 'msg' => '修改失败');
        $userinfo = UserModel::instance()->find($user_id, 'id,user_pwd');
        if($userinfo){
            $pwd_old_compile = $this->compilePassword($pwd_new);
            $userData = array(
                    'user_pwd'=> $this->compilePassword($pwd_new),
            );
            $userData['force_new_passwd'] = 0;
            if ($pwd_old_compile == $userinfo->user_pwd){
                $ret['msg'] = '旧密码和新密码不能相同';
            } elseif ($userinfo->update($userData)){
                $this->upPwdNext($user_id);
                $ret['code'] = '1';
                $ret['msg'] = '修改成功';
            }
        }else{
            $ret['msg'] = '用户不存在';
        }
        return $ret;
    }
    public function updatePwd($user_id, $pwd_old, $pwd_new){
        $ret = array('code' => '0', 'msg' => '修改失败');
        $userinfo = UserModel::instance()->find($user_id, 'id,user_pwd');
        if($userinfo){
            $pwd_old_compile = $this->compilePassword($pwd_old);
            if($pwd_old_compile === $userinfo['user_pwd']){
                $userData = array(
                        'user_pwd'=> $this->compilePassword($pwd_new),
                        );
                $userData['force_new_passwd'] = 0;
                if($userinfo->update($userData)){
                    $this->upPwdNext($user_id);
                    $ret['code'] = '1';
                    $ret['msg'] = '修改成功';
                }
            }else{
                $ret['msg'] = '旧密码输入错误';
            }
        }else{
            $ret['msg'] = '用户不存在';
        }
        return $ret;
    }

    public function resetPwd($phone, $pwd){
        $resetResult =  UserModel::instance()->editPasswordByPhone($phone, $this->compilePassword($pwd));
        if($resetResult){
            $user = UserModel::instance()->getUserByMobile($phone, 'id');
            $this->upPwdNext($user['id']);
        }

        return $resetResult;

    }

    public function resetPwdCompany($userId, $pwd){
        $resetResult =  UserModel::instance()->editPasswordByUserId($userId, $this->compilePassword($pwd));
        if($resetResult){
            $this->upPwdNext($userId);
        }

        return $resetResult;

    }

    /**
     * [upPwdNext 用户修改密码后自动操作
     * @return [type] [description]
     */
    private function upPwdNext($uid){
        //原有操作提出来
        $passportService = new PassportService();
        $passportService->sessionDestroyByUserId($uid);
        $this->kickoffToken($uid);
        //新加删tag操作
        $oUserBindService = new UserBindService();
        $oUserBindService->delUserCanResetPwdTag($uid);
    }

    /**
     *  用户踢下线（用于修改密码，忘记密码等操作)
     *
     */

    public function kickoffToken($userId){

        //将所有token踢下线
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userTokenMapKey = 'API_TOKEN_SET'.$userId;
        $tokens = $redis->hKeys($userTokenMapKey);

        if($tokens){
            foreach($tokens as $tk){
                \SiteApp::init()->cache->delete($tk);
                //双写了session，所以这也要删掉sentinel里的token
                $redis->del($tk);
            }
            $redis->del($userTokenMapKey);
        }
        return;

    }

    /**
     * 新的登录
     * @param type $userInfo
     * @param type $jumpUrl
     * @return int|string
     * @throws \Exception
     */
    public function doLogin($userInfo, $jumpUrl) {
        $isAjax = 0;
        $isOAuth = 1;
        $retResult = array('code' => -1, 'msg' => '', 'data' => null, 'isAjax' => $isAjax);

        // ====== do_login_user
        $result = UserModel::instance()->doLogin($userInfo['user_name'], $userInfo['password'], $isOAuth, FALSE);
        if ($result['status']) {
            $sessUserInfo = \es_session::get('user_info');

            if (!empty($userInfo['auto_login'])) {
                $userData = $sessUserInfo;
                \es_cookie::set("user_name",$userData['user_name'],3600*24*30);
                \es_cookie::set("user_pwd",md5($userData['user_pwd']."_EASE_COOKIE"),3600*24*30);
            }
            //增加web端用户是否是卖家的标志
            $tagService = new \core\service\UserTagService;
            if ($tagService->getTagByConstNameUserId('O2O_SELLER', $sessUserInfo['id'])){
                $isSeller = 1;
            } else {
                $isSeller = 0;
            }
            \es_session::set('isSeller', $isSeller);

            // 通行证登录，且不是本地账号，跳过强制修改密码逻辑
            $passportService = new PassportService();
            if (\es_session::get('ppId') && $passportService->isThirdPassport($GLOBALS['user_info']['mobile'], false)) {
                $GLOBALS['user_info']['force_new_passwd'] = 0;
                \es_session::set("user_info", $GLOBALS['user_info']);
            }

            if ($isAjax == 0 && trim(app_conf("INTEGRATE_CODE")) == '') {
                $retResult['code'] = 0;
                return $retResult;
            }

            $jumpUrl = $jumpUrl  ? $jumpUrl  : get_gopreview();
            $return['status'] = 1;
            $return['info'] = $GLOBALS['lang']['LOGIN_SUCCESS'];
            $return['data'] = $result['msg'];
            $return['jump'] = $jumpUrl;
            $retResult['code'] = 0;
            $retResult['data'] = $return;
            return $retResult;
        }

        // 处理错误
        if ($result['data'] == ACCOUNT_NO_EXIST_ERROR) {
            $err = $GLOBALS['lang']['USER_NOT_EXIST'];
        } else if ($result['data'] == ACCOUNT_PASSWORD_ERROR) {
            $err = $GLOBALS['lang']['PASSWORD_ERROR'];
        } else if ($result['data'] == ACCOUNT_NO_VERIFY_ERROR) {
            $err = $GLOBALS['lang']['USER_NOT_VERIFY'];
        }

        Logger::wLog(array(
            'result' => $result,
            'url' =>'/user/dologin',
            'info' => $userInfo,
            'path' =>  __FILE__,
            'function' => 'doNewLogin',
            'msg' => '用户登录失败.',
            'time' => time(),
        ));

        $retResult['code'] = '-1';
        $retResult['msg'] = $err;
        return $retResult;
    }

    private function _verifyCallback($callback){
        $callback = urldecode($callback);
        $site_id = app_conf('TEMPLATE_ID');
        if(array_key_exists($site_id,$this->_callback_url) === true){
            $url = parse_url($callback);
            if($url['host'] == $this->_callback_url[$site_id][0]
                    && $url['path'] == $this->_callback_url[$site_id][1]){
                return $callback;
            }
        }
        return null;
    }

    public function doLogout() {
        // $result = loginout_user();
        $user_info = \es_session::get("user_info");
        if(!$user_info)
        {
            return app_redirect(url("index"));
        }

        // $sessUserInfo = \es_session::get("user_info");
        \es_session::delete("user_info");
        \es_cookie::delete("user_name");
        \es_session::set('before_login','');
        $jumpUrl = $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:get_http().$_SERVER["HTTP_HOST"].url("index");
        $this->oAuthLogout($jumpUrl);
    }
    public function doNewLogout($callback=null) {
        // 验证回调地址
        if(!empty($callback)){
            $url = $this->_verifyCallback($callback);
            if(!empty($url)){
                session_destroy();
                return app_redirect($url);
            }
        }
        $user_info = \es_session::get("user_info");
        if(!$user_info)
        {
            return app_redirect(url("index"));
            //exit;
        }
        // 清空用户登录站点
        $userTrackService = new UserTrackService();
        $userTrackService->clearLoginSite($user_info['id']);

        //生产用户访问日志
        UserAccessLogService::produceLog($user_info['id'], UserAccessLogEnum::TYPE_LOGOUT, '退出成功', '', '', DeviceEnum::DEVICE_WEB);

        session_destroy();
        return app_redirect(url("index"));
    }

    public function updateInfo($userInfo) {
        // print_r($userInfo);
        $dataToWrite = array();
        $userDao = UserModel::instance();
        // ========save user====== 数据验证开始 ，走oauth 不需要验证身份证，电话和邮箱
        // 用户保存方式检测
        $updateMode = $userDao->isUserExists($userInfo['passport_id']) ? 'update' : 'insert';
        // $updateMode = 'insert';
        // 如果为update模式，获取用户在数据库中的记录主键
        if ($updateMode == 'update') {
            $dataToWrite['id'] = $userDao->getUserIdByPassportId($userInfo['passport_id']);
            if (!$dataToWrite['id']) {
                throw new \Exception('系统运行时错误');
            }
            // 为更新数据做一些通用字段设置
            $dataToWrite['update_time'] = get_gmtime();
            $code = $userDao->getCodeByPk($dataToWrite['id']);
            $dataToWrite['code'] = $code ? $code : '';
        } else {
            // 为插入数据初始化一些基础字段
            $dataToWrite['create_time'] = get_gmtime();
            $userGroupDao = UserGroupModel::instance();
            $defaultGroupId =  $userGroupDao->getDefaultGroupId();
            $dataToWrite['group_id'] = $defaultGroupId !== false ? $defaultGroupId : 0;
            $dataToWrite['is_effect'] = app_conf('USER_VERIFY');
            $dataToWrite['code'] = '';
            $dataToWrite['site_id'] = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
        }
        // ========save user====== 数据验证开始结束
        // ========save user====== 数据整理开始，oauth中没有的数据临时注释掉
        // 用户名称检测
        $dataToWrite['user_name'] = trim($userInfo['user_login_name']);
        if (empty($dataToWrite['user_name'])) {
            throw new \Exception('用户名称不可以为空');
        }
        $dataToWrite['email'] = $userInfo['user_email'];
        $dataToWrite['mobile'] = $userInfo['mobile'];
        $dataToWrite['passport_id'] = $userInfo['passport_id'];
        $dataToWrite['mobilepassed'] = 1;

        // 第三方整合和扩展字段 跳过
        $userDao->updateInfo($dataToWrite, $updateMode);
        return true;
    }

    public function genUsername($prefix='m'){
        $userDao = UserModel::instance();
        for($i=0;$i<10;$i++){
            $username = $prefix.mt_rand(10000000000, 99999999999);
            if($userDao->isUserExistsByUsername($username) === false){
                break;
            }
        }
        return $username;
    }

    /**
     * 用户注册底层代码，需要重构，逻辑比较混乱
     */
    public function insertInfo($userInfo, $isH5 = false) {
        $user_type = isset($userInfo['usertype']) ? intval($userInfo['usertype']) : 0;

        // 风控注册阻拦判断
        $extraData = array();
        $extraData['mobile'] = $userInfo['mobile'];
        $extraData['user_type'] = $user_type;
        $extraData['invite_code'] = $userInfo['invite_code'];
        if (!RiskService::check('REG', $extraData)) {
            Monitor::add('REGISTER_FAIL');
            RiskService::report('REG', RiskService::STATUS_FAIL, $extraData);
            return array('status'=>-1, 'data'=>array('mobile'=>'操作失败，请稍后再试'));
        }

        $userDao = UserModel::instance();
        // 用户默认投资户
        $user_purpose = EnterpriseModel::COMPANY_PURPOSE_INVESTMENT;
        if (isset($userInfo['user_purpose'])) {
            $user_purpose = intval($userInfo['user_purpose']);
        } else if (\es_cookie::get('user_purpose')) {
            $user_purpose = intval(\es_cookie::get('user_purpose'));
        }

        if (empty($userInfo['username'])){
            $userInfo['username'] = $this->genUsername();
        }

        $ret = array();
        if($userDao->isUserExistsByUsername($userInfo['username']) == true) {
            $ret = array('status'=>-1,'data'=>array('username'=>'用户名已存在'));
        }

        if(!empty($userInfo['email'])){
            if($userDao->isUserExistsByEmail($userInfo['email']) == true) {
                $ret = array('status'=>-1,'data'=>array('email'=>'邮箱已存在'));
            }
        }

        // 非企业用户才需要检查mobile参数
        $registerEnterprisePurpose = array(
            EnterpriseModel::COMPANY_PURPOSE_INVESTMENT,
            EnterpriseModel::COMPANY_PURPOSE_FINANCE
        );

        if ($user_type != UserModel::USER_TYPE_ENTERPRISE || !in_array($user_purpose, $registerEnterprisePurpose)) {
            if ($userDao->isUserExistsByMobile($userInfo['mobile']) == true) {
                //校验是否需要引导用户修改密码
                $oUserBindService = new UserBindService();
                $bIs = $oUserBindService->isUserCanResetPwdByMobile($userInfo['mobile']);
                if($bIs){
                    $ret = array('status'=>-33,'data'=>array('mobile'=>'该手机号已经注册，如有疑问请联系客服'));
                }else{
                    $ret = array('status'=>-1,'data'=>array('mobile'=>'该手机号已经注册，如有疑问请联系客服'));
                }
            }
        }

        if (!preg_match("/^[a-zA-Z0-9]\w+/", $userInfo['username'])) {
            $ret = array('status'=>-1,'data'=>array('username'=>'只支持英文或英文与数字组合'));
        }

        if(!empty($ret)) {
            Monitor::add('REGISTER_FAIL');
            return $ret;
        }

        $dataToWrite = array();
        // 为插入数据初始化一些基础字段
        $dataToWrite['create_time'] = get_gmtime();
        $dataToWrite['user_name'] = $userInfo['username'];
        if (!empty($userInfo['password'])) {
            $dataToWrite['user_pwd'] = $this->compilePassword($userInfo['password']);
        }
        $dataToWrite['site_id'] = empty($userInfo['site_id']) ? $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] : $userInfo['site_id'];

        //5个分站用户注册未填邀请码，则后端自动添加默认邀请码
        if(empty($userInfo['invite_code']) && isset($GLOBALS['sys_config']['DEFAULT_INVITE_CODE'][$dataToWrite['site_id']])){
            $userInfo['invite_code'] = $GLOBALS['sys_config']['DEFAULT_INVITE_CODE'][$dataToWrite['site_id']];
        }

        if(!empty($userInfo['invite_code'])) {
            $coupon_service = new CouponService();
            $coupon = $coupon_service->checkCoupon($userInfo['invite_code']);
            if ($coupon !== FALSE && empty($coupon['coupon_disable'])) {
                $dataToWrite['invite_code'] = strtoupper($userInfo['invite_code']);
                $dataToWrite['refer_user_id'] = $coupon['refer_user_id'];
            } else {
                // 记录错误日志
                $log_info = array(__CLASS__, __FUNCTION__, $userInfo['referer']);
                Logger::info(implode(" | ", array_merge($log_info, array('register invite_code ' . $userInfo['invite_code']))));
            }
        }

        //注册初始化话服务等级
        $dataToWrite['new_coupon_level_id'] = 1;
        // 支持手机端注册用户设置到不同的会员组
        $dataToWrite['group_id'] = null;
        if (!isset($userInfo['group_id'])) {
            $userGroupDao = UserGroupModel::instance();
            $defaultGroupId = $userGroupDao->getDefaultGroupId();
            $dataToWrite['group_id'] = $defaultGroupId !== false ? $defaultGroupId : 0;
        }
        else {
            $dataToWrite['group_id'] = $userInfo['group_id'];
        }
        if (!empty($userInfo['country_code'])){
            $dataToWrite['country_code'] = $userInfo['country_code'];
            // 当 mobile_code 并未传进来时，通过映射表来获取country_code 对应的 mobile_code
            $dataToWrite['mobile_code'] = empty($userInfo['mobile_code']) ? $GLOBALS['dict']['MOBILE_CODE'][$userInfo['country_code']]['code'] : $userInfo['mobile_code'] ;
        }

        $dataToWrite['is_effect'] = app_conf('USER_VERIFY');
        $dataToWrite['email'] = $userInfo['email'];
        $dataToWrite['mobile'] = $userInfo['mobile'];
        $dataToWrite['mobilepassed'] = 1;
        $dataToWrite['referer'] = $userInfo['referer'];
        $dataToWrite['user_type'] = $user_type;
        $dataToWrite['user_purpose'] = $user_purpose;

        $GLOBALS['db']->startTrans();
        try {
            $userid = $userDao->updateInfo($dataToWrite,'insert');
            if (!$userid) {
                throw new \Exception('用户注册失败');
            }
            // 初始化第三方账户余额
            //\core\dao\UserThirdBalanceModel::instance()->initBalance($userid, $user_purpose);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $userid = 0;
            Logger::error($e->getMessage() . ', userId:'.$userid);
        }

        if ($userid) {
            $user_tag_service = new UserTagService();
            // 只有企业投资户才需要在注册的时候，打这个tag
            if ($user_type == UserModel::USER_TYPE_ENTERPRISE && $user_purpose == EnterpriseModel::COMPANY_PURPOSE_INVESTMENT) {
                $user_tag_service->addUserTagsByConstName($userid, 'QY_NOMEDAL');
            }

            //分站优惠购活动
            //if (!empty($userInfo['open_ticket'])) {
            //    $openService = new \core\service\OpenService();
            //    $tagInfo = $openService->getDiscountBuyTag($userInfo['open_ticket']);
            //    $user_tag_service->autoAddUserTag($userid, $tagInfo['name'], $tagInfo['desc']);
            //}

            // 添加注册来源
            $user_tag_service->autoAddUserTag($userid, 'FROM_SITE_' . $dataToWrite['site_id'], '注册自'. \libs\utils\Site::getTitleById($dataToWrite['site_id']));
            // 添加注册时间
            $user_tag = array('REG_Y_'.date('Y'),'REG_M_'.date('m'));
            $user_tag_service->addUserTagsByConstName($userid, $user_tag);
            $bonus_service = new BonusService;
            $bonus_service->bind($userid, $userInfo['mobile']);

            try{
                UserLoanRepayStatisticsService::initRegUserAssets($userid);
            }catch (\Exception $ex) {
                Logger::error('用户注册初始化资产表失败 user_id:'.$userid);
            }
            setLog(array('errno' => 0,'uid'=>$userid));
        }

        //注册绑码
        if($userid){
            $couponBindService = new CouponBindService();
            $couponBindService ->init($userid);
        }

        if(!empty($userid) && !empty($dataToWrite['invite_code']))
        {
            $this->sendInviteMsg($userid);
            //邀请返利 记录到couponlog中
            $coupon_service = new CouponService();
            $coupon_service->regCoupon($userid, $dataToWrite['invite_code']);
        }

        try {
            $triggerExtra = array();
            if (isset($dataToWrite['invite_code'])) {
                $triggerExtra['invite_code'] = $dataToWrite['invite_code'];
            }

            if (isset($dataToWrite['refer_user_id'])) {
                $triggerExtra['refer_user_id'] = $dataToWrite['refer_user_id'];
            }

            O2OService::triggerO2OOrder(
                $userid, // 用户id
                CouponGroupEnum::TRIGGER_REGISTER, // 触发动作
                0, // 交易id
                $dataToWrite['site_id'], // 分站site_id
                0, // 投资金额
                0, // 投资年化
                0, // 交易类型
                0, // 触发类型
                $triggerExtra // 额外参数
            );

            // 注册回调
            new \core\service\DigService('register', array(
                'id' => $userid,
                'cn' => $dataToWrite['invite_code'],
            ));
        } catch (\Exception $e) {
            Logger::error('O2O注册落单失败|' .$userid);
        }

        $event = new \core\event\BonusEvent('register', $userid, $userInfo['invite_code']);
        // $tagEvent = new \core\event\UserMobileAreaEvent($userid,$userInfo['mobile']);
        $task_obj = new GTaskService();
        $task_id = $task_obj->doBackground($event, 20);
        // $task_id_tag = $task_obj->doBackground($tagEvent, 5);
        if (!$task_id) {
            Logger::wLog('注册添加返利失败|' .$userid. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
        }
        // if(!$task_id_tag) {
        //     Logger::wLog('注册添加地域tag失败 user_id:'.$userid .' mobile:'.$userInfo['mobile']);
        // }

        if(!empty($userid) && !empty($dataToWrite['invite_code'])){
            //用户userProfile埋点
            $userProfileService = new UserProfileService();
            $userProfileService->updateCouponProfile($userid);
            //第三方渠道发红包业务
            //$openService = new \core\service\OpenService();
            //$openService->registSendBouns($userInfo['mobile'], $dataToWrite['invite_code'], $userid);
        }

        Monitor::add('REGISTER_SUCCESS');

        // 新用户注册后，自动加入黑名单
        //\core\service\BwlistService::addToList('USE_BONUS_BLACK', $userid);

        // 只做一次交付, 不能使用在强一致性的业务
        $content = array_merge($userInfo, $dataToWrite);
        $content['user_id'] = $userid;
        $this->saveAdunionDeal($content);

        $extraData['user_id'] = intval($userid);
        RiskService::report('REG', RiskService::STATUS_SUCCESS, $extraData);

        return array(
            'status'=>0,
            'user_id'=>$userid,
            'data'=>array(
                'mobile' => $userInfo['mobile'],
                'username'=>$userInfo['username']
            )
        );
    }

    public function saveAdunionDeal($data) {
        unset($data['password'], $data['user_pwd'], $data['email']);
        Logger::info(sprintf("保存数据到广告联盟, 数据: %s", json_encode($data)));
        if (empty($data['invite_code']) && empty($data['euid'])) {
            return true;
        }

        try {
            $objAdunionDealService = new \core\service\AdunionDealService();
            $objAdunionDealService->addAdRecord($data);
        } catch (\Exception $e) {
            Logger::error(sprintf("保存数据到广告联盟, 数据: %s, 原因: %s", json_encode($data), $e->getMessage()));
        }
    }

    public function sendInviteMsg($userid) {
        $title = '成为投资人，获得返利';
        $content = '成为投资人还需完成身份验证、绑定银行卡，成功后即可获得返利，<a href="/account/addbank">立即成为投资人</a>。';
        send_user_msg($title,$content,0,$userid,get_gmtime(),0,true,1);
    }
}
