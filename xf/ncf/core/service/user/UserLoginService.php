<?php
namespace core\service\user;

use libs\utils\Monitor;
use core\enum\UserEnum;
use core\enum\UserAccountEnum;
use core\service\BaseService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\user\UserTrackService;
use core\service\user\PassportService;
use core\service\user\UserBindService;

class UserLoginService extends BaseService {
    private static $_passKey = 'AEXJIEJSIFKELFDILEKFDI';
    const USER_FIELDS = 'id,user_name,real_name,user_pwd,create_time,email,idno,country_code,mobile_code,mobile,idcardpassed,user_purpose,group_id,id_type,user_type,supervision_user_id,payment_user_id,is_effect,mobilepassed,site_id,byear,bmonth,bday,force_new_passwd,is_dflh,sex,mobiletruepassed,is_delete';

    /**
     * 设置登录标识
     * @param int $userId
     * @return boolean
     */
    public static function setUserLogin($userId) {
        $GLOBALS['user_info'] = UserService::getUserById($userId, self::USER_FIELDS);
        if (empty($GLOBALS['user_info'])) {
            return false;
        }

        setcookie('PHPSESSID', session_id(), time() + 3600, '/');
        \es_session::set('user_info', $GLOBALS['user_info']);
        \es_cookie::set("user_name", $GLOBALS['user_info']['user_name'], 3600);
        \es_cookie::set("auto_login_name", $GLOBALS['user_info']['user_name'], 3600);
        \es_cookie::set("user_pwd", md5($GLOBALS['user_info']['user_pwd'].'_EASE_COOKIE'), 3600);
        return true;
    }

    /**
     * 退出登录
     * @return string
     */
    public static function setUserLogout() {
        $user_info = \es_session::get("user_info");
        if(empty($user_info)) {
            return true;
        }

        \es_session::delete("user_info");
        \es_session::delete("userInfo");
        \es_session::clear();
        \es_cookie::delete("user_name");
        \es_cookie::delete("auto_login_name");
        \es_cookie::delete("user_pwd");
        \es_cookie::clear();
        // 清空用户登录站点
        $userTrackService = new UserTrackService();
        $userTrackService->clearLoginSite($user_info['id']);
        return true;
    }

    /**
     * 返回企业联系人手机号列表
     *
     * @return array
     */
    public static function getEnterpriseMobileList($userId, $smsOnly = false, $userBankInfo = []) {
        // 获取企业用户信息
        $mobileInfo = array();
        $enterpriseContractInfo = UserService::getEnterpriseContactByUserId($userId);
        // receive_msg_mobile 单独处理
        if ($smsOnly) {
            if (empty($userBankInfo)) {
                $userBankInfo = BankService::getNewCardByUserId($userId);
            }
            if (!empty($userBankInfo)) {
                $mobileList = trim($enterpriseContractInfo['receive_msg_mobile'], ',');
                if (!empty($mobileList))
                {
                    $mobiles = explode(',', $mobileList);
                    $mobiles = array_unique($mobiles);
                }
                foreach ($mobiles as $k => $mobileItem)
                {
                    if (strpos($mobileItem, '-') !== false) {
                        list($countryCode, $mobile) = explode('-', $mobileItem);
                    }
                    $mobileInfo[] = array(
                        'code' => isset($countryCode) ? $countryCode : '86',
                        'mobile' => isset($mobile) ? $mobile : $mobileItem,
                    );
                }
            }else{
                $mobileInfo[] = array(
                    'code' => isset($enterpriseContractInfo['consignee_phone_code']) ? $enterpriseContractInfo['consignee_phone_code'] : '86',
                    'mobile' => isset($enterpriseContractInfo['consignee_phone']) ? $enterpriseContractInfo['consignee_phone'] : '',
                );
            }
            return $mobileInfo;
        }

        $enterpriseInfo = UserService::getEnterpriseInfo($userId);
        // 法人联系信息
        $legalbodyInfo = array();
        if (!empty($enterpriseInfo['legalbody_name'])) {
            $legalbodyInfo['name'] = $enterpriseInfo['legalbody_name'];
            $legalbodyInfo['code'] = $enterpriseInfo['legalbody_mobile_code'];
            $legalbodyInfo['mobile'] = $enterpriseInfo['legalbody_mobile'];
            $mobileInfo[] = $legalbodyInfo;
        }
        // 企业负责人联系信息
        $majorInfo = array();
        if (!empty($enterpriseContractInfo['major_name'])) {
            $majorInfo['name'] = $enterpriseContractInfo['major_name'];
            $majorInfo['code'] = $enterpriseContractInfo['major_mobile_code'];
            $majorInfo['mobile'] = $enterpriseContractInfo['major_mobile'];
            $mobileInfo[] = $majorInfo;
        }
        // 企业联系人2信息
        $contactInfo = array();
        if (!empty($enterpriseContractInfo['contact_name'])) {
            $contactInfo['name'] = $enterpriseContractInfo['contact_name'];
            $contactInfo['code'] = $enterpriseContractInfo['contact_mobile_code'];
            $contactInfo['mobile'] = $enterpriseContractInfo['contact_mobile'];
            $mobileInfo[] = $contactInfo;
        }
        // 经办人信息
        $employeeInfo = array();
        if (!empty($enterpriseContractInfo['employee_name'])) {
            $employeeInfo['name'] = $enterpriseContractInfo['employee_name'];
            $employeeInfo['code'] = $enterpriseContractInfo['employee_mobile_code'];
            $employeeInfo['mobile'] = $enterpriseContractInfo['employee_mobile'];
            $mobileInfo[] = $employeeInfo;
        }
        return $mobileInfo;
    }

    /**
     * 密码编译方法;
     * @access  public
     * @param   string      $pass       需要编译的原始密码
     * @return  string
     */
    public static function compilePassword($pass)
    {
        $md5pass = md5(md5(base64_encode(self::$_passKey . $pass)) . self::$_passKey);
        return $md5pass;
    }

    /**
     * 旧的登录
     * @param type $userInfo
     * @param type $jumpUrl
     * @return int|string
     * @throws \Exception
     */
    public static function doLoginOld($userInfo, $jumpUrl) {
        $isAjax = 0;
        $isOAuth = 1;
        $retResult = array('code' => -1, 'msg' => '', 'data' => null, 'isAjax' => $isAjax);

        // ====== do_login_user
        $result = UserService::login($userInfo['user_name'], $userInfo['password'], $isOAuth, false);
        if ($result['status']) {
            // 设置登录标识
            self::setUserLogin($userInfo['user_id']);

            $sessUserInfo = \es_session::get('user_info');
            //增加web端用户是否是卖家的标志
            $checkTag = UserService::checkUserTag('O2O_SELLER', $sessUserInfo['id']);
            if ($checkTag) {
                $isSeller = 1;
            } else {
                $isSeller = 0;
            }
            \es_session::set('isSeller', $isSeller);
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
        if ($result['data'] == UserEnum::ACCOUNT_NO_EXIST_ERROR) {
            $err = $GLOBALS['lang']['USER_NOT_EXIST'];
        } else if ($result['data'] == UserEnum::ACCOUNT_PASSWORD_ERROR) {
            $err = $GLOBALS['lang']['PASSWORD_ERROR'];
        } else if ($result['data'] == UserEnum::ACCOUNT_NO_VERIFY_ERROR) {
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

    /**
     * 鉴定用户名密码
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $country_code 国别码
     * @param bool $passport 网信通信证
     * @param bool $isFromEnterprise是否是企业入口
     * @return string
     */
    public static function authenticate($username, $password, $country_code = false, $passport = true, $isFromEnterprise = false) {
        $ret = array('code'=>'0', 'msg'=>'', 'isPassport'=>false);
        if (!$isFromEnterprise) {
            // 通行证鉴权通过，直接返回, 当前只开放大陆手机号登录用户使用
            if ($passport && is_mobile($username)) {
            }
        }

        $userinfo = UserService::getUserByName($username, '*');
        do {
            if ($userinfo) {
                $isEnterpriseUser = UserService::isEnterpriseUser($userinfo['id']);
                // 只有企业投资户和融资户才能登录企业app或者企业网站
                $isForEnterpriseChannel = $isEnterpriseUser &&
                ($userinfo['user_purpose'] == UserAccountEnum::ACCOUNT_INVESTMENT
                    || $userinfo['user_purpose'] == UserAccountEnum::ACCOUNT_FINANCE)
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
                    if ($isEnterpriseUser && self::isUserAccountFreeze($username)) {
                        $ret['code'] = '-12';
                        $ret['msg'] = '用户帐号已冻结';
                        break;
                    }

                    if (!empty($country_code) && $country_code != $userinfo['country_code']) {
                        $ret['code'] = '-3';
                        $ret['msg'] = '您输入的密码和用户名不匹配 ';
                    } else {
                        $password = self::compilePassword($password);
                        if ($password === $userinfo['user_pwd']) {
                            // 本地登录成功，通行证弹窗逻辑
                            if (!$isFromEnterprise && is_mobile($username)) {
                                // 验证是否需要二次验证
                                if (PassportService::needLocalVerify($username) && !$isEnterpriseUser) {
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
                            if ($userinfo['site_id'] > 1) { //注册来源是分站
                                // 校验是否需要引导用户修改密码
                                $bIs = UserBindService::isUserCanResetPwd($userinfo['id']);
                                $ret['code'] = $bIs ? "-33" : '-2';
                            } else {
                                $ret['code'] = '-2';
                            }
                            $ret['msg'] = '您输入的密码和用户名不匹配 ';
                        }
                    }
            } else {
                $ret['code'] = '-1';
                $ret['msg'] = '您输入的密码和用户名不匹配';
            }
        } while (false);

        setLog(array(
            'errno' => $ret['code'],
            'errmsg' => $ret['msg'],
            'uid' => isset($ret['user_id']) ? $ret['user_id'] : ''
        ));
        if ($ret['code'] != '0') {
            Monitor::add('LOGIN_FAIL');
        }
        return $ret;
    }

    /**
     * 密码验证，修改密码时验证输入的旧密码是否正确,如果传入手机号码，则验证是不是user表里的手机号
     * @param unknown $user_id
     * @param unknown $pwd_old
     */
    public static function verifyPwd($user_id, $pwd = '', $mobile = '') {
        $ret = array('code' => '0', 'msg' => '');
        $userinfo = UserService::getUserById($user_id, 'id,user_pwd,mobile');
        if($userinfo){
            if ($pwd) {
                $pwd_compile = self::compilePassword($pwd);
                if($pwd_compile != $userinfo['user_pwd']){
                    $ret['code'] = '3';
                    $ret['msg'] = '旧密码输入错误';
                }
                return $ret;
            }
            if ($mobile) {
                if ($mobile != $userinfo['mobile']) {
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

    public static function updateNewPwd($user_id, $pwd_new)
    {
        $ret = array('code' => '0', 'msg' => '修改失败');
        $userinfo = UserService::getUserById($user_id, 'id,user_pwd');
        if ($userinfo) {
            $pwd_new_compile = self::compilePassword($pwd_new);
            if ($pwd_new_compile == $userinfo['user_pwd']) {
                $ret['msg'] = '旧密码和新密码不能相同';
            }
            // 更新用户信息
            $userUpdateData = ['id'=>$user_id, 'user_pwd'=>$pwd_new_compile, 'force_new_passwd'=>0];
            $result = UserService::updateWxUserInfo($userUpdateData);
            if ($result) {
                self::_upPwdNext($user_id);
                $ret['code'] = '1';
                $ret['msg'] = '修改成功';
            }
        }else{
            $ret['msg'] = '用户不存在';
        }
        return $ret;
    }

    public static function updatePwd($user_id, $pwd_old, $pwd_new) {
        $ret = array('code' => '0', 'msg' => '修改失败');
        $userinfo = UserService::getUserById($user_id, 'id,user_pwd');
        if ($userinfo) {
            $pwd_old_compile = self::compilePassword($pwd_old);
            if ($pwd_old_compile === $userinfo['user_pwd']) {
                // 更新用户信息
                $userUpdateData = ['id'=>$user_id, 'user_pwd'=>self::compilePassword($pwd_new), 'force_new_passwd'=>0];
                $result = UserService::updateWxUserInfo($userUpdateData);
                if ($result) {
                    self::_upPwdNext($user_id);
                    $ret['code'] = '1';
                    $ret['msg'] = '修改成功';
                }
            } else {
                $ret['msg'] = '旧密码输入错误';
            }
        } else {
            $ret['msg'] = '用户不存在';
        }
        return $ret;
    }

    public static function resetPwd($phone, $pwd) {
        $resetResult = false;
        $user = UserService::getUserByMobile($phone, 'id');
        if (!empty($user)) {
            $userUpdateData = ['id'=>$user['id'], 'user_pwd'=>self::compilePassword($pwd)];
            $resetResult = UserService::updateWxUserInfo($userUpdateData);
            if ($resetResult) {
                self::_upPwdNext($user['id']);
            }
        }
        return $resetResult;
    }

    public static function resetPwdCompany($userId, $pwd) {
        $resetResult = false;
        $user = UserService::getUserById($userId, 'id');
        if (!empty($user)) {
            $userUpdateData = ['id'=>$userId, 'user_pwd'=>self::compilePassword($pwd)];
            $resetResult = UserService::updateWxUserInfo($userUpdateData);
            if ($resetResult) {
                self::_upPwdNext($userId);
            }
        }
        return $resetResult;
    }

    /**
     * 用户修改密码后自动操作
     * @return [type] [description]
     */
    private static function _upPwdNext($uid) {
        // 原有操作提出来
        PassportService::sessionDestroyByUserId($uid);
        self::kickoffToken($uid);
        // 新加删tag操作
        UserBindService::delUserCanResetPwdTag($uid);
    }

    /**
     *  用户踢下线（用于修改密码，忘记密码等操作)
     *
     */
    public static function kickoffToken($userId) {
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
     * 查询用户是否冻结
     * @param string $username 用户名
     * @return bool
     */
    public static function isUserAccountFreeze($username) {
        if (empty($username)) {
            return false;
        }
        return \SiteApp::init()->cache->get(UserEnum::ACCOUNT_FREEZE_KEY . $username) ? true : false;
    }
}
