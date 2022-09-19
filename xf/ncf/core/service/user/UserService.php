<?php

namespace core\service\user;

use core\service\BaseService;
use core\enum\UserEnum;

class UserService extends BaseService {
    private static $funcMap = array(
        'getUserIdByRealName' => array('name'),         // 获取指定真实用户的id
        'getUserById' => array('userId', 'fields'),     // 通过userId获取用户信息
        'getUserByMobile' => array('mobile', 'fields'), // 通过手机号获取用户信息
        'getUserByIdno' => array('idno', 'exclude'),    // 通过身份证号获取用户信息
        'getUserByName' => array('username', 'fields'), // 通过用户名获取用户信息
        'getUserByNameMobile' => array('username'), // 通过用户名称或手机号，获取用户信息
        'getUserByCondition' => array('cond', 'fields'),// 获取指定条件的用户
        'getFormatUserName' => array('userId'),         // 获取格式化的用户名
        'getUserRealName' => array('userId'),           // 取真实姓名。普通用户获取真实姓名，企业用户获得企业名称
        'getUserInfoByIds' => array('userIds', 'needUserTypeName'), // 批量获取用户的基本信息
        'getUserGroupList' => array('cond', 'fields'),  // 获取用户组列表
        'getUserServicesFee' => array('userId'),        // 获取用户的服务费率
        'getUserCompanyInfo' => array('userId', 'fields'), // 获取用户的公司信息
        'getUserTypeName' => array('userId', 'flag'),   // 根据用户的id，获取用户类型对应的名称
        'checkUserTag' => array('tagName', 'userId'),   // 判断用户是否有该标签
        'getUserTags' => array('userId'), // 获取用户所有标签
        'delUserTagsByConstName' => array('userId', 'constNames'), // 根据const_name删除用户标签
        'getDealUserInfo' => array('userId', 'needRegion', 'needWorkInfo'), //获取借款人信息
        'paymentRegister' => array('userId', 'regData'),
        'isIdCardExist' => array('idNo'), // 检查该身份证是否已存在
        'isEmailExist' => array('email'), // 检查该邮箱是否已存在
        // 重置初始密码
        'forceResetInitPwd' => array('userId', 'newPwd'),
        // 用户签署网信超级账户免密协议
        'signWxFreepayment' => array('userId'),
        'resetPwd' => array('mobile', 'pwd'), // 修改用户密码
        'getUserInfoForContractByUserId' => array('userId'),    // 获取合同需要的的用户信息

        'isEnterprise' => array('userId'),              // 是否为企业用户
        'getEnterpriseInfo' => array('userId'),         // 获取企业用户信息
        'getUserIdByCCU' => array('credentialsNo','companyName','userName'), //根据公司证件号、公司名称和用户名获取用户ids
        'getEnterpriseRegisterInfo' => array('userId'),         // 获取企业用户注册信息
        'getEnterpriseByCompanyName' => array('name', 'userId', 'purpose'),  // 根据公司名称获取企业用户信息
        'getEnterpriseByCredentialsNo' => array('credentialsNo', 'userId', 'purpose'), // 根据公司证件号获取企业用户信息
        'getEnterpriseContactByMobile' => array('mobile'), // 根据联系人手机号获取企业联系人信息
        'getEnterpriseContactByUserId' => array('userId'), // 根据联系人用户ID获取企业联系人信息
        'makeUserBidTag' => array('userId', 'money', 'couponId', 'dealLoadId', 'isRedeem', 'bidMore') ,//投标给用户打tag
        // 更新用户实名信息
        'updateUserIdentityInfo' => array('userId', 'realname', 'idType', 'idno'),
        // 更新用户邮箱信息
        'updateUserEmail' => array('userId', 'email'),
        // 修改超级账户的会员基本信息同步接口
        'modifyUserInfo' => array('userId', 'newInfo'),
        // 修改网信用户基本信息
        'updateWxUserInfo' => array('userData'),
        // 更新超级账户手机号
        'updateUcfpayMobile' => array('userId', 'mobile', 'mobileCode'),
        // 判断手机号是否已存在
        'checkUserMobile' => array('phone'),
        // 实名认证
        'doIdValidate' => array('userId', 'data', 'isUpdateIdcard'),
        // 实名认证并开通超级账户
        'doIdValidateRegister' => array('userId', 'data', 'isUpdateIdcard'),

        // 登陆
        'login' => array('account', 'password', 'country_code', 'loginFrom'),
        // 获取用户的token
        'getApiToken' => array('userId', 'tokenExpireTime'),
        'allowAccountLoan' => array('userPurpose'),
        // 获取账户详情
        'accountSummary' => array('userId', 'siteId'),
        //获取用户的快捷银行卡信息
        'limitBankInfo' => array('userId'),
        //获取用户角色
        'getUserRole' => array('userInfo'),
        //是否在黑名单里
        'checkBwList' => array('typeKey', 'value'),
        // 用户首页汇总信息
        'userCount' => array('userId'),
        // 获取用户信息接口
        'userInfo' => array('userId'),
        // 我的优惠码接口
        'accountCouponInvite' => array('userId', 'siteId', 'from', 'siteDomain', 'siteCoupon', 'siteLogo', 'euidLevel', 'euid'),
        // 判断用户是否是港澳台、军官证、护照用户
        'hasPassport' => array('userId'),
        // 用户注册接口
        'userRegister' => array('params'),
        // 用户注册校验接口
        'userCheckRegister' => array('params'),
        // 获取用户未读的消息个数
        'messageCount' => array('userId'),
        // 用户迁移到经讯时代确认
        'updateUserToJXSD' => array('userId'),
        // 更新用户存管id
        'updateSupervisionUserId' => array('userId'),
        // 获取用户认证信息
        'getUserCreditFile' => array('userId'),
    );

    /**
     * 当前登录用户信息
     */
    private static $loginUserInfo = array();

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError($name.' method not exist', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }

        // 处理特殊的ncfph的api接口
        $ncfphApiArr = array('login', 'getApiToken', 'limitBankInfo',
            'accountSummary', 'getUserRole', 'checkBwList', 'userCount',
            'userInfo', 'accountCouponInvite', 'userRegister', 'userCheckRegister', 'messageCount',
            'getUserCreditFile',
        );

        // 用户中心的api接口
        $userCenterApiArr = array('getUserById', 'isEnterprise', 'getUserIdByRealName',
            'getUserByMobile', 'getUserByCondition', 'getFormatUserName', 'getDealUserInfo',
            'getUserInfoForContractByUserId', 'checkUserTag', 'getUserTags', 'getUserByName',
            'getEnterpriseByCompanyName', 'getEnterpriseByCredentialsNo', 'getUserInfoByIds',
            'getUserGroupList', 'getUserCompanyInfo', 'getEnterpriseInfo', 'getUserByNameMobile',
            'getUserRealName', 'isIdCardExist', 'isEmailExist', 'getUserIdByCCU','getEnterpriseRegisterInfo',
            'getEnterpriseContactByMobile', 'getEnterpriseContactByUserId', 'checkUserMobile',
        );

        // 后台单个接口查询可能超时
        if ('getUserIdByRealName' == $name) {
            return self::rpc('user', 'user/'.$name, $args, false, 60);
        }

        if (in_array($name, $userCenterApiArr)) {
            return self::rpc('user', 'user/'.$name, $args);
        } else if (in_array($name, $ncfphApiArr)) {
            return self::rpc('ncfwx', 'ncfph/'.$name, $args);
        }

        return self::rpc('ncfwx', 'user/'.$name, $args);
    }

    /**
     * 获取登陆的用户信息
     * lica-mobile的backend用的是userInfo，为了区别，这里用ncfphUserInfo，
     * 因为两边存储的数据不一样，暂时无法共享
     */
    public static function getLoginUser() {
        if (!empty(self::$loginUserInfo)) {
            return self::$loginUserInfo;
        }

        // 已经在ncfph的wap登录了
        if (!empty($_SESSION['ncfphUserInfo'])) {
            return (array) $_SESSION['ncfphUserInfo'];
        }

        // 在ncfwx的wap没有登录
        if (empty($_SESSION['userInfo'])) {
            return array();
        }

        // 在ncfwx的wap已经登录
        $userInfo = (array) $_SESSION['userInfo'];
        // 需要修正成ncfph的session数据
        $tokenInfo = UserService::getUserByCode($userInfo['token']);
        // 这里拿不到user信息
        if (!empty($tokenInfo['code'])) {
            return array();
        }

        $ncfphUserInfo = $tokenInfo['user'];
        // 这里补充token信息
        $ncfphUserInfo['token'] = $userInfo['token'];
        self::setLoginUser($ncfphUserInfo);
        return $ncfphUserInfo;
    }

    /**
     * 设置登陆的用户信息
     * lica-mobile的backend用的是userInfo，为了区别，这里用ncfphUserInfo，
     * 因为两边存储的数据不一样，暂时无法共享
     */
    public static function setLoginUser($userInfo) {
        $_SESSION['ncfphUserInfo'] = $userInfo;
        self::$loginUserInfo = $userInfo;
    }

    // 账号退出
    // lica-mobile的backend用的是userInfo，为了区别，这里用ncfphUserInfo，
    // 因为两边存储的数据不一样，暂时无法共享
    public static function userLogout() {
        unset($_SESSION['userInfo']);
        unset($_SESSION['ncfphUserInfo']);
    }

    /**
     * 通过token获取用户的信息
     *      {"code":306,"reason":"获取参数出错"}
     *      {"code":307,"reason":"clientid or secret error"}
     *      {"code":308,"reason":"authorization code error"}
     *      {"code":309,"reason":"invalid oauth grant type"}
     *
     * @param $code string 用户的token码
     * @return array
     */
    public static function getUserByCode($code) {
        if (empty($code)){
            return array('code' => 'ERR_GET_USER_FAIL', 'reason' => '获取参数出错');
        }

        $tokenKey = md5($code);
        // APP免登录的Redis采用高可用的Redis
        $userJson = \SiteApp::init()->dataCache->getRedisInstance()->get($tokenKey);
        if (empty($userJson)) {
            return array('code' => 'ERR_GET_USER_FAIL', 'reason' => 'authorization code error');
        }

        $userArr = json_decode($userJson, true);
        if (empty($userArr['uid'])){
            return array('code' => 'ERR_GET_USER_FAIL', 'reason' => 'authorization code error');
        }

        $userInfo = self::getUserById($userArr['uid'], UserEnum::USER_FIELDS);
        if (empty($userInfo)) {
            return array('code' => 'ERR_SYSTEM_CALL_CUSTOMER','reason' => '系统繁忙，获取用户信息失败');
        }

        if ($userInfo['is_effect'] == 0 || $userInfo['is_delete'] == 1) {
            return array('code' => 'ERR_SYSTEM_CALL_CUSTOMER', 'reason' => '无效用户，禁止登录');
        }

        // 登陆来源
        $isFromWxlc = false;
        // 处理上线前已经登陆的用户
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'wxph') !== false) {
            $isFromWxlc = false;
        } elseif (isset($_SERVER['HTTP_PLATFORM']) && $_SERVER['HTTP_PLATFORM'] == 'wxapp') {
            $isFromWxlc = true;
        } elseif (isset($_SERVER['HTTP_CLIENT']) && ($_SERVER['HTTP_CLIENT'] == 'app')) {
            $isFromWxlc = false;
        } else {
            if (empty($userArr['loginFrom'])) {
                $userTrackService = new UserTrackService();
                $loginSite = $userTrackService->getLoginSite($userArr['uid']);
                $isFromWxlc = ($loginSite == 1) ? true : false;
            } else {
                // 1为网信app，2为网信wap
                $wxLoginFromTypes = array(1, 2);
                $isFromWxlc = in_array($userArr['loginFrom'], $wxLoginFromTypes) ? true : false;
            }
        }

        // 是否来自网信
        $userInfo['isFromWxlc'] = $isFromWxlc;
        // 是否可以使用红包，优惠券
        $userInfo['canUseBonus'] = true;

        if (!$isFromWxlc && self::checkBwList('USE_BONUS_BLACK', $userArr['uid'])) {
            $userInfo['canUseBonus'] = false;
        }

        // 这里去掉了原来是否是o2o商家的判断，因为普惠用不到
        $ret = array();
        $ret['user'] = $userInfo;
        $ret['status'] = 1;
        return $ret;
    }
}
