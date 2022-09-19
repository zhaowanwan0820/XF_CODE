<?php
/**
 * UserService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\AgencyUserModel;
use core\dao\RegionConfModel;
use core\dao\UserLogModel;
use core\dao\DealTagModel;
use core\dao\UserWorkModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use system\libs\oauth;
use core\dao\UserModel;
use core\service\CouponService;
use core\service\CouponBindService;
use core\service\user\WebBO;
use core\service\user\BOFactory;
use core\dao\FinanceQueueModel;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseContactModel;
use core\dao\UserBankcardModel;
use core\dao\UserBankcardAuditModel;
use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Aes;
use libs\utils\Block;
use libs\utils\XDateTime;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\db\Db;
use core\service\DealLoadService;
use core\service\UserTagService;
use core\service\UserLogService;
use core\service\UserBankcardService;
use core\service\BonusService;
use core\service\UserBindService;
use core\service\UserGroupService;
use libs\utils\PaymentApi;
// for gearman
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\TestExampleEvent;
use core\dao\BonusConfModel;
use core\dao\DealLoadModel;
use core\service\O2OService;
use core\service\rank\RankService;
use core\service\duotou\DtInvestNumService;
use core\dao\UserCompanyModel;
use core\dao\ChangeGroupLevelLogModel;

use libs\payment\supervision\Supervision;
use core\service\SupervisionBaseService;
use core\service\PassportService;
use core\service\UserTokenService;

use core\dao\DealModel;
use core\dao\UserThirdBalanceModel;
use core\dao\UserLoanRepayStatisticsModel;
use core\dao\UserIdentityModifyLogModel;

use core\service\BwlistService;

// for tianmai
use core\service\curlHook\ThirdPartyHookService;
use core\service\RemoteTagService;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
require_once APP_ROOT_PATH.'system/libs/CryptRc4.php';
require_once APP_ROOT_PATH.'system/libs/msgcenter.php';

/**
 * Class UserService
 * @package core\service
 */
class UserService extends BaseService {

    /** @var _userObject UserService 绑定的特定用户 */
    private $_userObject;

    /**
     * 用户状态验证-尚未登录
     * @var int
     */
    const STATUS_BINDCARD_UNLOGIN = 1000;

    /**
     * 用户状态验证-尚未开户
     * @var int
     */
    const STATUS_BINDCARD_PAYMENTUSERID = 1001;

    /**
     * 用户的状态验证-尚未实名认证
     * @var int
     */
    const STATUS_BINDCARD_IDCARD = 1002;

    /**
     * 用户的状态验证-尚未绑定手机号
     * @var int
     */
    const STATUS_BINDCARD_MOBILE = 1003;

    /**
     * 用户的状态验证-尚未绑定银行卡
     */
    const STATUS_BINDCARD_UNBIND = 1004;

    /**
     * 用户状态验证-尚未验证银行卡
     */
    const STATUS_BINDCARD_UNVALID = 1005;

    public function __construct($userObject = null)
    {
        if ($userObject instanceof UserModel)
        {
            $this->_userObject = $userObject;
        }
        else if (is_numeric($userObject) && $userObject == intval($userObject))
        {
            $user = UserModel::instance()->find(intval($userObject), '*' , true);
            if ($user->id)
            {
                $this->_userObject = $user;
            }
        }
    }

    const FIRSTP2P_LOGIN_VALUE = 3; // 开关是3的话启用自己的登录，不用oauth

    /**
     * 获取用户信息(走从库)
     */
    public function getUserViaSlave($id, $need_region = false, $need_workinfo = false)
    {
        return $this->getUser($id, $need_workinfo, $need_workinfo, true);
    }

    public function getUserIdByMobile($phone, $is_slave = false) {
        if(empty($phone)) {
            return false;
        }
        //此处返回userId by liguizhi 20171018
        $user = UserModel::instance()->getUserIdByMobile($phone, $is_slave);
        if(empty($user)) {
            return false;
        }
        return $user;
    }

    /**
     * 为投资提供查看用户可用金额的方法，包括红包余额
     * @param object $user
     * @param object $deal
     * @param float $load_money
     * @param bool $hasBank 是否查询存管余额，减少存管接口查询
     * @return array('ret'=>bool, 'money'=>array('lc'=>float, 'cg'=>float, 'bonus'=>float))
     */
    public function getMoneyInfo($user,$bidMoney,$orderId=false, $hasBank = true) {
        if(empty($user)){
            return false;
        }

        $bonusInfo = (new \core\service\BonusService())->getUsableBonus($user['id'], true, $bidMoney,$orderId);

        // 用户限制金额 仅针对网信理财
        $limitMoney = (new \core\service\UserCarryService())->getLimitAmountByUserId($user['id']);

        // 账户余额
        $balance = bcsub($user['money'],$limitMoney,2);
        // 如果用户当前可用减去限制金额小于0的时候, 用户的可用余额为0
        if ($balance < 0 )
        {
            $balance = 0;
        }

        $bonusMoney = $bonusInfo['money']; // 红包余额
        $bankMoney = 0; // 银行余额

        //是否查询存管余额，减少存管接口查询
        if ($hasBank) {
            $superAccountService = new \core\service\SupervisionAccountService();
            $isSuperUser = $superAccountService->isSupervisionUser($user);

            // 存管降级开关关闭不读取存管余额
            if($isSuperUser && Supervision::isServiceDown() === false){
                $res = $superAccountService->balanceSearch($user['id']);
                if($res['status'] == SupervisionBaseService::RESPONSE_FAILURE){
                    $bankMoney = 0;
                    Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"获取存管系统余额失败 errMsg:".$res['respMsg'])));
                }else{
                    $bankMoney = bcdiv($res['data']['availableBalance'],100,2);
                }
            }
        }

        $moneyInfo = array('lc' => $balance,'bonus'=>$bonusMoney, 'bank' => $bankMoney, 'limit' => $limitMoney, 'bonusInfo'=>$bonusInfo);
        Logger::info(implode(" | ", array(__CLASS__,__FUNCTION__,"获取余额信息 moneyInfo:".json_encode($moneyInfo))));
        return $moneyInfo;
    }

    /**
     * 获取用户信息
     *
     * @param $id
     * @param $need_workinfo 默认false
     * @return \libs\db\Model
     */
    public function getUser($id, $need_region=false, $need_workinfo=false, $slave = false) {
        if (empty($id)) {
            return false;
        }

        $user_model = new UserModel();
        $user = $user_model->find($id, '*', $slave);
        if (empty($user)) {
            return false;
        }
        $user['age'] = ($user['byear']) ? (to_date(get_gmtime(), "Y") - $user['byear']) : 0;

        if ($need_region == true) {
            // 处理地区信息
            if ($user['city_id']) {
                $user['region'] = $user['region_city'] = RegionConfModel::instance()->getRegionName($user['city_id']);
            }
            if ($user['province_id']){
                $user['region_province'] = RegionConfModel::instance()->getRegionName($user['province_id']);
                if(!$user['region']) {
                    $user['region'] = $user['region_province'];
                }
            }
        }

        // 处理工作信息
        if ($need_workinfo == true) {
            $work_info = UserWorkModel::instance()->findByViaSlave("`user_id`='{$id}'");
            $user['workinfo'] = $work_info;
            if ($work_info['province_id']) {
                $user['work_province'] = RegionConfModel::instance()->getRegionName($work_info['province_id']);
            }
            if ($work_info['city_id']) {
                $user['work_city'] = RegionConfModel::instance()->getRegionName($work_info['city_id']);
            }
        }

        // 判断用户是否是企业用户
        $user['is_enterprise_user'] = 0;
        if ((!empty($user['mobile']) && substr($user['mobile'], 0, 1) == 6) || (isset($user['user_type']) && $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)) {
            $user['is_enterprise_user'] = 1;
        }
        if((int)app_conf('USER_JXSD_TRANSFER_SWITCH') !== 1) {
            $user['is_dflh'] = 0;
        }

        //合规用户黑名单
        $user['isCompliantUser'] = intval(BwlistService::inList('COMPLIANCE_BLACK', $id));

        return $user;
    }

    /**
     * 判断用户是否是企业用户
     * @param  int $id 用户id
     * @return boolean
     */
    public function checkEnterpriseUser($id, $slave = true) {
        if (empty($id)) {
            return false;
        }
        $user_model = new UserModel();
        $user = $user_model->find($id, '*', $slave);
        if (empty($user)) {
            return false;
        }
        // 判断用户是否是企业用户:1.用户类型为企业用户;2.手机号国别为86且手机号首位为6
        if ((!empty($user['mobile']) && substr($user['mobile'], 0, 1) == 6 && $user['mobile_code'] == '86') || (isset($user['user_type']) && $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 冻结企业用户
     * @param string $username 用户名
     * @return bool
     */
    public function freezeUserAccount($username) {
        return UserModel::instance()->freezeUserAccount($username);
    }

    /**
     * 工作认证是否过期
     *
     * @param $user
     * @return array
     */
    public function getExpire($user){
        $time = get_gmtime();
        $expire_time = 6 * 30 * 24 * 3600;
        if ($user['workpassed'] == 1) {
            if (($time - $user['workpassed_time']) > $expire_time) {
                $user['workpassed_expire'] = 1;
            }
        }
        if ($user['incomepassed'] == 1) {
            if (($time - $user['incomepassed_time']) > $expire_time) {
                $user['incomepassed_expire'] = 1;
            }
        }
        if ($user['creditpassed'] == 1) {
            if (($time - $user['creditpassed_time']) > $expire_time) {
                $user['creditpassed_expire'] = 1;
            }
        }
        if ($user['residencepassed'] == 1) {
            if (($time - $user['residencepassed_time']) > $expire_time) {
                $user['residencepassed_expire'] = 1;
            }
        }
        return $user;
    }

    /**
     * // TODO 过滤返回参数，不然数据太大
     * firstp2p 的登录
     * @param string @username
     * @param string @password
     * @return 返回结果:
     *      {"code":alfjasdfjkaslf,"success":true}
     *  错误编码:
     *      {"code":0001,"reason":"认证失败"}
     *      {"code":20010,"reason":"响应方式不正确"}
     *      {"code":20001,"reason":"用户名不能为空"}
     *      {"code":20002,"reason":"密码不能为空"}
     *      {"code":20003,"reason":"用户名不存在"}
     *      {"code":20004,"reason":"用户密码错误"}
     *
     */
    public function apiNewLogin(
        $username,                                          // 账号
        $password,                                          // 密码
        $isPassport = false,                                // 是否使用通信证
        $loginFrom = '',                                    // 登陆来源
        $country_code = "cn"                                // 国别码
    ) {
        if (empty($username)){
            return array('code' => 20001,'reason' => "用户名不能为空");
        }

        if (empty($password)) {
            return array('code' => 20002, 'reason' => "密码不能为空");
        }

        $webBo = BOFactory::instance('app');
        $result = $webBo->authenticate($username, $password, $country_code, $isPassport, $loginFrom);

        $ppID = false;
        if (true === $result['isPassport']) {
            $ppID = $result['data']['ppUserInfo']['ppId'];
            if ($result['data']['showAuth'] == true) {
                return array('code' => '10001', 'ppID' => $ppID);
            }

            if ($result['data']['needVerify'] == true) {
                return array('code' => '10002', 'ppID' => $ppID);
            }
        }

        // 通行证密码已经修改，做二次验证
        if (!empty($result['code']) && $result['code'] == -20) {
            return array('code' => 10003, 'reason' => $result['msg']);
        }

        if (!empty($result['code']) && $result['code'] == -4) {
            return array('code' => 20007, 'reason' => $result['msg']);
        }

        if (!empty($result['code']) && $result['code'] == -1) {
            return array('code' => 20003, 'reason' => "用户名和密码不匹配");
        }

        if (!empty($result['code']) && $result['code'] == -2) {
            return array('code' => 20004, 'reason' => "用户名和密码不匹配");
        }

        if (!empty($result['code']) && in_array($result['code'], array(-10, -11, -12))) {
            return array('code' => 20006, 'reason' => $result['msg']);
        }

        if ($result['code'] != 0) {
            return array('code' => $result['code'], 'reason' => $result['msg']);
        }

        $userModel = UserModel::instance();
        $userInfo = $userModel->doLogin($username,$password,1);
        if (isset($userInfo['user']) && ($userInfo['user']['is_effect'] == 0 || $userInfo['user']['is_delete'] == 1)) {
            return array('code' => 20005, 'reason' => '无效用户，禁止登录');
        }

        $userTokenService = new UserTokenService();
        PaymentApi::log(var_export($userInfo,true));
        $code = $userTokenService->genAppToken($userInfo['user']['id'], $ppID, $loginFrom);
        return array(
            'code' => $code,
            'user_id' => $result['user_id'],
            'user_name' => $result['user_name'],
            'ppID' => $ppID,
            'success' => true
        );
    }

    /**
     * 即付宝用户注册
     */
    public function signupForJF($mobile)
    {
        $inviteCode = app_conf('AGENCY_COUPON_JF');
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];

        $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id, site_id');

        if (!empty($userInfo)) {
            //幂等处理
            if ($userInfo['site_id'] == $siteId) {
                return $userInfo['id'];
            } else {
                throw new \Exception('手机号已注册', 1);
            }
        }

        $password = substr(md5($mobile.mt_rand(1000000, 9999999)), 0, 10);

        $userInfoExtra = array(
            'site_id' => $siteId,
            'group_id' => $GLOBALS['sys_config']['SITE_USER_GROUP']['jifubao'],
        );
        $result = $this->Newsignup('', $password, '', $mobile, '', $inviteCode, $userInfoExtra, false);

        if (!$result || $result['status'] != 0) {
            throw new \Exception('注册失败:'.$result['reason']);
        }

        //打tag
        $tagService = new UserTagService();
        $tagService->addUserTagsByConstName($result['user_id'], array('JIFU_USER'));

        return $result['user_id'];
    }

    /**
     * 注册添加用户信息
     *
     * @param $username 用户名
     * @param $password 密码
     * @param $email 邮箱
     * @param $phone 手机号
     * @param $code 手机验证码
     * @param $invite_code 邀请码
     * @param $country_code 国别号
     * @return Null
     */
    public function signup($username, $password, $email, $phone, $code,$invite_code='') {

          return $this->Newsignup($username, $password, $email, $phone, $code,$invite_code);

    }
    /**
     * firstp2p 注册添加用户信息
     * @param $username 用户名
     * @param $password 密码
     * @param $email 邮箱
     * @param $phone 手机号
     * @param $code 手机验证码
     * @return 返回结果:
     *      {"createTime":"2013-07-11T13:30:51+08:00","deleted":1,"email":"zp.q@163.com","id":221,"idcard":11111,"loginTime":"2013-07-17T10:44:03+08:00","nickName":"qzp","pwd":"96E79218965EB72C92A549DD5A330112","sex":0,"state":0,"truename":111,"updateTime":"2013-07-17T10:44:03+08:00","username":"qzp","usertype":1，“passportid”：12 }
     *  必返回：passportid
     *  错误编码:
     *      {"code":500,"reason":"服务端内部出错"}
     *      {"code":303,"reason":"用户名被占用"}
     *      {"code":304,"reason":"电话号码被占用"}
     *      {"code":319,"reason":"验证码错误"}
     */
    public function Newsignup($username, $password, $email, $phone, $code,$invite_code='', $userInfoExtra = array(), $useMobileCode = true, $country_code="cn", $isPc = 0) {
        if ($useMobileCode) {
            $mobileCodeServiceObj = new MobileCodeService();
            $vcode = $mobileCodeServiceObj->getMobilePhoneTimeVcode($phone, 60, $isPc);
            if($vcode != $code) {
                Monitor::add('REGISTER_FAIL');
                return array('code' => 319, 'reason' => '验证码错误');
            }
        }

        $webboObj = new WebBO('web');
        $userInfo = array(
            'username' => $username,
            'email' => $email,
            'mobile' => $phone,
            'password' => $password,
            'invite_code' => $invite_code,
            'country_code'=> $country_code
        );

        $userInfo['referer'] = DeviceEnum::DEVICE_UNKNOWN;
        if (isset($_SERVER['HTTP_OS']) && stripos($_SERVER['HTTP_OS'], 'Android') !== false) {
            $userInfo['referer'] = DeviceEnum::DEVICE_ANDROID;//Android
        } elseif (isset($_SERVER['HTTP_OS']) && stripos($_SERVER['HTTP_OS'], 'iOS') !== false) {
            $userInfo['referer'] = DeviceEnum::DEVICE_IOS;//iOS
        }

        $userInfo = array_merge($userInfo, $userInfoExtra);

        // 优惠码正确需要记录
        // 注册添加用户
        $ret = $webboObj->insertInfo($userInfo, false);
        if (!empty($ret) && $ret['status'] === -1 && !empty($ret['data']['username'])) {
            // 这两种情况 ，一种用户名已存在，一种用户名格式错误
            return array('code' => 303,'reason' => $ret['data']['username']);
        }

        if (!empty($ret) && $ret['status'] === -1 && !empty($ret['data']['email'])) {
            return array('code' => 305,'reason' => '邮箱被占用');
        }

        if (!empty($ret) && $ret['status'] === -1 && !empty($ret['data']['mobile'])) {
            return array('code' => 304,'reason' => '该手机号已经注册，如有疑问请联系客服');
        }

        if (!empty($ret) && $ret['status'] === -33 && !empty($ret['data']['mobile'])) {
            return array('code' => 320,'reason' => '该手机号已经注册，如有疑问请联系客服');
        }

        if (!empty($ret) && $ret['status'] === 0) {
            return $ret;
        }

        return false;
    }
    /**
     * 发送用户注册手机验证码
     *
     * @param $mobile 手机号
     * @return \system\libs\json
     */
    public function sendVerifyCode($mobile, $type = 1, $idno = null, $isEnterprise = false) {
        return $this->NewSendVerifyCode($mobile,$type, $idno, $isEnterprise);
    }

    /**
     * firstp2p 发送用户注册手机验证码
     * @param int $mobile
     * 返回结果:
     *      {"result":true}   true : 发送成功， false ： 发送失败
     *  错误编码:
     *      @see MobileCodeService\getError
     */
    public function NewSendVerifyCode($mobile, $type = 1, $idno = null, $isEnterprise = false,$country_code="cn") {
        $MobileCodeServiceObj = new MobileCodeService();
        $is_send = $MobileCodeServiceObj->isSend($mobile, $type, 0, true, $isEnterprise);
        if ($is_send != 1) {
            $error_msg = $MobileCodeServiceObj->getError($is_send);
            $error_msg['reason'] = $error_msg['message'];
            unset($error_msg['message']);
            return $error_msg;
        }

        $isrsms = false;
        $ret = $MobileCodeServiceObj->sendVerifyCode($mobile,0,$isrsms,$type,$country_code, $idno);
        if (empty($ret)){
            return array('code' => -1, 'reason' => '系统繁忙，请稍后重试');
        }
        $ret = json_decode($ret,true);
        if (!empty($ret) && $ret['code'] == 1){
            return array('result' => true);
        }elseif(!empty($ret)){

            return array('code' =>$ret['code'],'reason' => $ret['message'] );
        }

        return array('code' => -1, 'reason' => '系统繁忙，请稍后重试');
    }
    /**
     * 校验用户名、邮箱、手机号的唯一性
     *
     * @param $username 用户名
     * @param $email 邮箱
     * @param $phone 手机号
     * @return \system\libs\json
     */
    public function checkUserInfo($username, $email, $phone) {

          return $this->NewCheckUserInfo($username, $email, $phone);

    }

    /**
     * firstp2p 检查手机用户名、邮箱、手机号的唯一性
     * @param $username 用户名
     * @param $email 邮箱
     * @param $phone 手机号
     * 返回结果:
     *      {"result":true}
     *      true(数据库中不存在)或者false（数据库中存在）
     *  错误编码:
     *      {"code":500,"reason":"服务端内部出错"}
     *      {"code":20005,"reason":"客户端不存在","option":"下次可以修改的时间"}
     *      {"code":303,"reason":"用户名被占用","option":"下次可以修改的时间"}
     *      {"code":304,"reason":"电话号码被占用","option":"下次可以修改的时间"}
     *      {"code":305,"reason":"邮箱被占用","option":"下次可以修改的时间"}
     */
    public function NewCheckUserInfo($username, $email, $phone){

        $userModelObj = UserModel::instance();
        // 检查用户名
        $usernameRet = $userModelObj->isUserExistsByUsername($username);
        if ($usernameRet === true){
            return array('code' => 303,'reason' => '用户名被占用');
        }
        $emailRet = $userModelObj->isUserExistsByEmail($email);
        if ($emailRet === true){
            return array('code' => 305,'reason' => '邮箱被占用');
        }
        $phoneRet = $userModelObj->isUserExistsByMobile($phone);
        if ($phoneRet === true){
            return array('code' => 304,'reason' => '电话号码被占用');
        }

        return array('result' => true);
    }

    public function checkUserMobile($phone){

        $userModelObj = UserModel::instance();
        $phoneRet = $userModelObj->isUserExistsByMobile($phone);
        if ($phoneRet === true){
            //判断是否需要引导修改密码
            $oUserBindService = new UserBindService();
            $bIs = $oUserBindService->isUserCanResetPwdByMobile($phone);
            if($bIs){
                return array('code' => 320,'reason' => '电话号码被占用');
            }else{
                return array('code' => 304,'reason' => '电话号码被占用');
            }
        }

        return array('result' => true);
    }


    /**
     * 通过code获取用户身份
     * (Controller请使用 getUserByToken 方法)
     */
    public function getUserByCode($code) {
        return (new UserTokenService())->getUserByToken($code);
    }

    /**
     * 获取某个用户是否是担保公司用户，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserAgencyInfoNew($user_info){

        //获取汇赢(HY)担保帐号
        \FP::import("libs.common.dict");
        $hydb_arr = \dict::get('HY_DB');

        $agency_info = array();
        if(in_array($user_info['user_name'], $hydb_arr)){
            $agency_info = array(
                'is_hy' => 1,
                'user_id' => $user_info['id'],
                'agency_id' => $GLOBALS['dict']['HY_DBGS'],
                'user_name' => $user_info['user_name'],
            );
        }else{
            //判断用户是否担保公司帐号
            $user_agency = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id']);
            if($user_agency){
                $agency_info = $user_agency;
                $agency_info['is_hy'] = 0;
            }
        }
        return array('agency_info' => $agency_info, 'is_agency' => empty($agency_info) ? 0 : 1);
    }

    /**
     * 获取某个用户是否是资产管理方用户，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserAdvisoryInfo($user_info){

        $advisory_info = array();

            //判断用户是否担保公司帐号
        $user_advisory = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id'],2);
        if($user_advisory){
            $advisory_info = $user_advisory;
        }
        return array('advisory_info' => $advisory_info, 'is_advisory' => empty($advisory_info) ? 0 : 1);
    }

    /**
     * 获取某个用户是否是渠道方，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserCanalInfo($user_info){

        $canal_info = array();

        //判断用户是否担保公司帐号
        $user_canal = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id'],10);
        if($user_canal){
            $canal_info = $user_canal;
        }
        return array('canal_info' => $canal_info, 'is_canal' => empty($canal_info) ? 0 : 1);
    }

    /**
     * 获取某个用户是否是委托机构用户，并返回信息
     * @param $user_id 用户id
     * @return array
     */
    public function getUserEntrustInfo($user_info){

        $entrust_info = array();

        //判断用户是否委托机构帐号
        $user_entrust = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id'],7);
        if($user_entrust){
            $entrust_info = $user_entrust;
        }
        return array('entrust_info' => $entrust_info, 'is_entrust' => empty($entrust_info) ? 0 : 1);
    }

    /**
     * 获取用户可用金额变动的资金记录
     *
     * @param $user_id
     * @param int $offset
     * @param int $page_size
     * @return \libs\db\Model
     */
    public function getUserAvailableMoneyLog($user_id, $offset = 0, $size = 20, $log_info = '', $start = 0, $end = 0) {
        if ($log_info == '支付收益') {
            $log_info = '付息';
        }
        $logRes = UserLogModel::instance()->getList($user_id, 'money_only', $log_info, $start, $end, array($offset, $size));
        if (!empty($logRes['list'])) {
            $list = $logRes['list'];
            foreach( $list as &$one ){
                if( $one['log_info'] == '付息'){
                    $one['log_info'] = '支付收益';
                }
                if($one['log_info'] =='邀请返利' || $one['log_info'] =='投资返利' ){
                    $one['note'] = UserLogService :: phone_format( $one['note'] );
                }
            }
            return $list;
        }
        return array();
    }

    /**
     * 更新用户信息
     * @param type $data
     * @return boolean
     */
    public function updateInfo($data)
    {
        $userDao = UserModel::instance();
        if(empty($data['id']))
        {
            return false;
        }
        $userDao->setRow(array('id'=>$data['id']));
        // 身份证号采用加密存储，统一使用大写的X后缀
        if (isset($data['idno'])) {
            $data['idno'] = strtoupper(trim($data['idno']));
        }

        return $userDao->update($data);

    }

    /**
     * 修改用户邮箱
     */
    public function changeEmail($passport_id, $user_name, $email) {
        $res_arr = array(
            "res" => false,
        );
        $user = UserModel::instance()->getUserByPassportId($passport_id, $user_name);
        if (!$user) {
            return $res_arr;
        }

        $res_arr['user'] = $user;
        $user->email = $email;
        $user->update_time = get_gmtime();
        if ($user->save() === false) {
            return $res_arr;
        } else {
            $res_arr['res'] = true;
            return $res_arr;
        }
    }


    /**
     * 检查邮箱是否存在
     * @param $email
     * @return boolean
     */
    public function checkEmailExist($email){
        return UserModel::instance()->isUserExistsByEmail($email);
    }

    /**
     * 检查副邮箱是否存在
     * @param $email
     * @return boolean
     */
    public function checkEmailSubExist($email){
        return UserModel::instance()->isUserExistsByEmailSub($email);
    }
    public function getUserId($offset=0,$page_size=10, $start_id=0, $end_id=0) {
        return UserModel::instance()->getUserId($offset,$page_size,$start_id,$end_id);
    }

    /**
     * photoPassedPass
     * 照片审核通过
     *
     * @param mixed $user_id
     * @access public
     * @return void
     */
    public function photoPassedPass($user_id) {
        return UserModel::instance()->photoPass($user_id, UserModel::PHOTO_STATUS_PASS);
    }

    /**
     * photoPassedReject
     * 照片审核拒绝
     *
     * @param mixed $user_id
     * @access public
     * @return void
     */
    public function photoPassedReject($user_id) {
        return UserModel::instance()->photoPass($user_id, UserModel::PHOTO_STATUS_REJECT);
    }

    /**
     * photoPassedInit
     * 照片上传成功，进入审核中状态
     *
     * @param mixed $user_id
     * @access public
     * @return void
     */
    public function photoPassedInit($user_id) {
        return UserModel::instance()->photoPass($user_id, UserModel::PHOTO_STATUS_INIT);
    }

    public function id5CheckUser($uid, $name, $idno) {
        $user = UserModel::instance()->find($uid);
        $len = strlen($idno);
        if ($len != 15 && $len != 18) {
            return false;
        } else {
            $id5 = new \libs\idno\CommonIdnoVerify();

            $flag = app_conf("ID5_VALID");
            //身份认证接口
            if (!empty($flag)) {
                $ret = $id5->checkIdno($name, $idno);
                if ($ret['code'] == '0') {
                    $reinfo = 1;
                } else {
                    $reinfo = $ret['code'];
                }
            } else {
                $reinfo = 1;
            }
            if ($reinfo == 1) {
                $userData = new \core\data\UserData();
                $ret = $userData->pushCreditReg(array('user_id' => $uid,'ip' => get_client_ip(), 'time' => time()));
                $user->real_name = $name;
                $user->idno = $idno;
                $user->idcardpassed = 1;
                $user->idcardpassed_time = time();
                $user->sex = $id5->getSex($idno);
                $user->id_type = 1; // 标识为身份证

                // 设置出生日期
                $birth = $id5->getBirthDay($idno);
                $user->byear = $birth['year'];
                $user->bmonth = $birth['month'];
                $user->bday = $birth['day'];

                $user->save();

                // 记录日志文件
                $log = array (
                        'type' => 'idno',
                        'user_name' => $name,
                        'user_login_name' => $GLOBALS ['user_info'] ['user_login_name'],
                        'indo' => $idno,
                        'path' => __FILE__,
                        'function' => 'id5CheckUser',
                        'msg' => '身份证认证成功.',
                        'time' => time ()
                );
                logger::wLog ( $log );

                return true;
            } else {
                // 记录日志文件
                $log = array (
                        'type' => 'idno',
                        'user_name' => $name,
                        'user_login_name' => $GLOBALS ['user_info'] ['user_login_name'],
                        'indo' => $idno,
                        'path' => __FILE__,
                        'function' => 'id5CheckUser',
                        'msg' => '身份证认证失败.',
                        'time' => time ()
                );
                logger::wLog ( $log );

                // 如果 姓名与身份证号不一致, 姓名与身份证号库中无此号, 姓名与身份证号 未查到数据
                /* if ($reinfo == 2 || $reinfo == 3 || $reinfo == 4)
                    showErr ( $GLOBALS ['lang'] ['IDNO_ERROR'], 1 );
                else */
                return false;
            }
        }
    }
/**
 * 如果$uid参数为空，则直接调用公安部接口，查询姓名和身份证号是否存在；否则进行身份认证，而不再调用公安部接口
 * 去掉公安部接口，改用上海爱金接口（2016-04-26）
 * @param $name
 * @param $idno
 * @param $uid
 * @return boolean
 */
    public function psCheckUserNoid($name='', $idno='',$uid='')
    {
        // 身份证号采用加密存储，统一使用大写的X后缀
        $idno = strtoupper(trim($idno));

//         \FP::import ( "libs.id5.SynPlat" );
//         $id5 = new \SynPlatAPI($GLOBALS['sys_config']['id5_url'], $GLOBALS['sys_config']['id5_user'], $GLOBALS['sys_config']['id5_passwd'], $GLOBALS['sys_config']['id5_key'], $GLOBALS['sys_config']['id5_iv']);
        if (empty($uid)) {
            $idnoObj = new \libs\idno\CommonIdnoVerify();
            return ($idnoObj->checkIdno($name, $idno));
        } else {
            $id5 = new \libs\idno\CommonIdnoVerify();
            $userinfo = UserModel::instance()->find($uid);
            // 设置出生日期
            $birth = $id5->getBirthDay($idno);
            $userinfo->byear = $birth['year'];
            $userinfo->bmonth = $birth['month'];
            $userinfo->bday = $birth['day'];
            $userinfo->real_name = $name;
            $userinfo->idno = $idno;
            $userinfo->idcardpassed = 1;
            $userinfo->idcardpassed_time = time();
            $userinfo->sex = $id5->getSex($idno);
            $userinfo->id_type = 1; // 标识为身份证
            $userinfo->save();//更新
            return true;
        }
    }

    /**
     * 根据用户身份证号获取用户信息
     * @param type $idon
     * @param type $userid 不包括的用户id
     * @return type
     */
    public function getUserByIdno($idno, $userid = '') {
        $user_dao = new UserModel();
        return $user_dao->getUserByIdno($idno, $userid);
    }

    /**
     * 根据用户身份证号获取所有用户信息
     */
    public function getAllUserByIdno($idno) {
        $userDao = new UserModel();
        return $userDao->getAllUserByIdno($idno);
    }

    /**
     * checkIsJrgcUser检查是否是金融工场注册身份证
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2015-12-02
     * @param mixed $idno
     * @access public
     * @return void
     */
    public function checkIsJrgcUser($idno) {
        $salt = $GLOBALS['config']['jrgcConfig']['salt'];
        $aesKey = $GLOBALS['config']['jrgcConfig']['aesKey'];
        $url = $GLOBALS['config']['jrgcConfig']['url'];
        $aesId = Aes::encode($idno, $aesKey);
        $token = md5($salt.$idno);
        $data = array('id' => base64_encode($aesId), 'token' => $token);
        $response = Curl::post($url, $data);
        $result = json_decode($response, true);
        if(is_array($result)) {
            if(isset($result['data']) && ($result['data'] == 1)) {
                PaymentApi::log('jrgc_user_find,id:'.$idno.' request:'.json_encode($data).' response:'.$response);
                return true;
            } else {
                PaymentApi::log('jrgc_not_find,id:'.$idno.' request:'.json_encode($data).' response:'.$response);
            }
        } else {
            PaymentApi::log('jrgc_query_failed,id:'.$idno.' request:'.json_encode($data).' response:'.$response);
        }
        return false;
    }
    /**
     * 根据相关条件获取用户信息
     * @param type $condition
     * @param type $fileds
     * @return type
     */
    public function getUserByCondition($condition,$fields="*")
    {
        $user_dao = new UserModel();
        return $user_dao->findBy($condition,$fields);
    }

    /**
     * @根据企业名称、企业证件号码获取企业信息
     * @param string $credentials_no
     * @param string $company_name
     */
    public function getEnterpriseByCondition($credentials_no, $company_name)
    {
        return EnterpriseModel::instance()->db->get_slave()->getAll("SELECT user_id AS id ,company_purpose ,identifier FROM firstp2p_enterprise WHERE credentials_no= '{$credentials_no}' AND company_name='{$company_name}'");
    }
    /**
     * @根据企业名称、企业证件号码获取所有符合条件的企业信息
     * @param string $credentials_no
     * @param string $company_name
     */
    public function getAllEnterpriseByCondition($credentials_no, $company_name)
    {
        $result = EnterpriseModel::instance()->findAllBySqlViaSlave("SELECT user_id AS id ,company_purpose FROM firstp2p_enterprise WHERE credentials_no= '{$credentials_no}' AND company_name='{$company_name}'",true);
        if (empty($result)) {
            return false;
        }
        $enterpriseInfo = array();
        foreach ($result as $value) {
            $enterpriseInfo[$value['id']] = explode(',', $value['company_purpose']);
        }
        return $enterpriseInfo;
    }

    /**
     * queryPayPwd
     * 调用支付端接口查询指定用户是否已经设置了支付密码
     *
     * @param mixed $uid
     * @param mixed $merchant
     * @access public
     * @return void
     */
    public function queryPayPwd($uid, $merchant) {
        $params = array(
                    'userId' => $uid,
                    'merchantId' => $merchant,
                );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_USER_QUERY_PAYPWD'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $ret = Curl::post($api, array('data'=>$aesData));
        // 记录日志文件
        $log = array(
            'type' => 'UserService',
            'uid' => $uid,
            'path' =>  __FILE__,
            'function' => 'queryPayPwd',
            'msg' => '调用支付端用户支付密码是否设置接口',
            'api' => $api,
            'request' => $aesData,
            'response' => $ret,
            'time' => time(),
        );
        logger::wLog($log);
        $ret = json_decode($ret, true);

        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        if (\libs\utils\Aes::validate($datas)) {
            // 验证成功
            return $datas;
        } else {
            return false;
        }
    }

    /**
     * 获取注册用户数
     * @param type $idon
     * @param type $userid 不包括的用户id
     * @return type
     */
    public function getRegisterUserCounter()
    {
        $user_dao = new UserModel();
        return $user_dao->count(" 1=1 ");
    }

    /**
     * 获取用户身份信息
     * @param intger $id
     * @return array
     */
    public function getIdnoAndType($id) {

        if (empty($id)) {
            return false;
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);
        if (empty($user)) {
            return false;
        }

        $idTypes = $GLOBALS['dict']['ID_TYPE'];
        $idField = isset($idTypes[$user['id_type']]['field']) ? $idTypes[$user['id_type']]['field'] : '';
        if ($user[$idField]) {
            $user['idno'] = $user[$idField];
        }

        return array('id_type' => $user['id_type'], 'idno' => $user['idno']);
    }

    /**
     * 手机号码是否已经存在
     * @param $moblie
     * @return int
     */
    public function isExistsMobile($moblie){
        $user_dao = new UserModel();
        return $user_dao->isUserExistsByMobile($moblie);
    }

   /**
    * 获取用户总数
    * @return array
    */
    public function getCount() {
        $user_dao = new UserModel();
        $condition = " is_delete = 0";
        return $user_dao->getCount($condition);
    }

    /**
     * 获取某天用户注册数
     * @param $day
     * @return array
     */
    public function getCountByDay($day){
        $user_dao = new UserModel();
        $startTime = to_timespan($day);
        $endTime = $startTime+86399;

        $condition = " is_delete = 0 AND create_time BETWEEN  $startTime AND $endTime";
        return $user_dao->getCount($condition);
    }

    public function isUserExistsByUsername($username) {
        $userDao = UserModel::instance();
        return $userDao->isUserExistsByUsername($username);
    }

    public function checkUserExistIsNormal($ip,$ua){
            if(!empty($ua))
            {
                    $key = md5($ip.$ua);
                    $session_token = \es_session::get('user_exist_token');
                    if($key == $session_token)
                    {
                        $ret =  Block::check(USERNAME_EXIST,$key);
                        return $ret;
                    }
                    else
                    {
                        return false;
                    }
            }
            else
            {
                return false;
            }
   }

    /**
     * 根据用户ID 读取用户年龄
     */
    public function getAgeByUserId($userId)
    {
        $user = $this->getUser($userId);
        $date = substr($user['idno'], 6, 8);
        $today = date("Ymd");
        $diff = substr($today, 0, 4) - substr($date, 0, 4);
        $age = substr($date, 4) > substr($today, 4) ? ($diff - 1) : $diff;
        return $age;
    }
    /**
     * 计算用户年龄
     */
    public function getAge($year, $month = 1) {
        $birth = "{$year}-{$month}-01";
        $today = date("Y-m-d", time());
        $diff = XDateTime::monthDiff(XDateTime::valueOf($birth), XDateTime::valueOf($today));
        $age = intval($diff / 12);
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP ,"birth:{$birth} today:{$today} diff:{$diff} age:{$age}",'line:'.__LINE__ )));
        return $age;

    }

    /**
     * 根据配置判定是否允许70岁以上的用户注册
     * @param int $refer_user_id
     * @return bool
     */
    public function checkReferee($refer_user_id) {
        if (!$refer_user_id) {
            return true;
        }

        $groups = explode(',', app_conf('INVEST_CONFIG_AGE_SEVENTY'));
        if (!$groups) {
            return true;
        }

        $refer_user_info = $this->getUser($refer_user_id);
        if (in_array($refer_user_info['group_id'], $groups)) {
            return false;
        }
        return true;
    }

    /**
     * 获取用户信息
     * @param int $id
     * @param string $fileds
     */
    public function getByFieldUser($id, $fields='*'){
        if (!is_numeric($id)){
            return false;
        }
        $user_model = new UserModel();
        $user_info = $user_model->find($id,$fields);

        return $user_info;
    }

    /**
     * getMoneyLogDetailById
     * 获取资金交易详情
     *
     * @param mixed $id
     * @access public
     * @return void
     */
    public function getMoneyLogDetailById($id) {
        return UserLogModel::instance()->find($id);
    }

    public function getByMobile($mobile, $fields = "*") {
        if (!$mobile) {
            return false;
        }
        $userModel = new UserModel();
        return $userModel->findBy("mobile = '" . $userModel->escape($mobile) . "'", $fields);
    }

    /**
     * 根据用户名或手机号获得用户信息
     * @param $username
     */
    public function getUserinfoByUsername($username){
        $userModel = new UserModel();
        return $userModel->getUserinfoByUsername($username);
    }

    /**
     * 身份证是否存在
     * @param type $passport   cardID
     * @return boolean
     */
    public function isIdCardExist($idno) {
        // 身份证号采用加密存储，统一使用大写的X后缀
        $idno = strtoupper(trim($idno));
        $userPassport = new UserModel();
        $condition = "1=1 and (`idno` = '{$idno}')";
        $ret = $userPassport->countViaSlave($condition);
        if($ret > 0){
            return true;
        }
        return false;
    }

    /**
     * 给用户投资次数相关打tag
     * @param unknown $user_id
     * @return boolean
     */
    public function makeUserBidTag($user_id, $money, $coupon_id, $deal_load_id,
        $isRedeem = false, $bidMore = NULL, $is_bid_compound = false, array $extra = array()
    ) {
        $remoteTagService = new RemoteTagService();
        $tag_service = new UserTagService();
        $siteId = \libs\utils\Site::getId();
        $GLOBALS['db']->startTrans();
        try {
            // 如果没有取过是否是复投，取一次
            if ($bidMore === NULL) {
                $bidMore = $tag_service->getTagByConstNameUserId('BID_MORE', $user_id);
            }
            $data = array(
                'user_id' => $user_id,
                'deal_load_id' => $deal_load_id,
            );

            if ($bidMore) {
                $action = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            } else {
                $firstDeal = DealLoadModel::instance()->getFirstDealByUser($user_id);
                if ($firstDeal['id'] != $deal_load_id) {
                    $action = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
                    $res1 = $tag_service->delUserTagsByConstName($user_id, 'BID_ONE');
                    $res2 = $tag_service->addUserTagsByConstName($user_id, 'BID_MORE');
                    if ($res1 === false || $res2 === false) {
                        throw new \Exception("用户[{$user_id}]添加BID_MORE标签失败");
                    }
                } else {
                    $action = CouponGroupEnum::TRIGGER_FIRST_DOBID;
                    $res = $tag_service->addUserTagsByConstName($user_id, 'BID_ONE');
                    if ($res === false) {
                        throw new \Exception("用户[{$user_id}]添加BID_ONE标签失败");
                    }
                    // 远程TAG
                    try {
                        $res =  $remoteTagService->addUserTag($user_id, 'FirstBidAmount', $money);
                    } catch (\Exception $e) {
                    }
                }
            }

            // 增加年化额字段透传
            if ($is_bid_compound == false) {
                $annualizedAmount = \core\service\oto\O2OUtils::getAnnualizedAmountByDealLoadId($deal_load_id, false);
            } else {
                $annualizedAmount = false;
            }

            // 获取交易信息
            $dealLoadInfo = DealLoadModel::instance()->find($deal_load_id);
            // 排除多投的交易
            if (!$isRedeem && $dealLoadInfo && $dealLoadInfo['source_type'] != DealLoadModel::$SOURCE_TYPE['dtb']) {
                // 获取标的tag
                $tags = array();
                $dealTags = DealTagModel::instance()->getTagByDealId($dealLoadInfo['deal_id']);
                if ($dealTags) {
                    foreach ($dealTags as $key => $tag) {
                        $tags[] = $tag['tag_name'];
                    }
                }

                // 尝试从标里获取投资天数
                $columns = 'advisory_id, project_id, type_id, loantype, deal_type, deal_crowd, deal_tag_name, repay_time';
                $dealInfo = DealModel::instance()->find($dealLoadInfo['deal_id'], $columns);
                $dealBidDays = intval($dealInfo['repay_time']);
                if ($dealInfo['loantype'] != O2OService::LOAN_TYPE_5) {
                    $dealBidDays = $dealBidDays * 30;
                }

                // 额外的信息
                $extra['inviter'] = $coupon_id;
                $extra['dealTag'] = $tags;
                $extra['dealBidDays'] = $dealBidDays;
                $extra['loantype'] = $dealInfo['loantype'];
                $extra['deal_type'] = $dealInfo['deal_type'];

                O2OService::triggerO2OOrder(
                    $user_id,
                    $action,
                    $deal_load_id,
                    $siteId,
                    $money,
                    $annualizedAmount,
                    CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG,
                    CouponGroupEnum::TRIGGER_TYPE_ZHUANXIANG,
                    $extra
                );
            }

            $GLOBALS['db']->commit();
            // 增加排行榜数据统计处理逻辑;统一用黑名单功能控制
            if (($dealLoadInfo['source_type'] != DealLoadModel::$SOURCE_TYPE['reservation']) && !(BwlistService::inList('O2O_RANK_BLACK', $user_id))) {
                $extra = array('dealTag' => (new \core\service\DealTagService())->getTagByDealId($dealLoadInfo['deal_id']));
                $res = RankService::updateRankScoreByTrigger($user_id, $money, $annualizedAmount, $deal_load_id, CouponGroupEnum::RANK_DEAL_TYPE_ZHUANXIANG, $extra);
                PaymentApi::log('updateRankScoreByTrigger userId|'.$user_id.'|dealLoadId|'.$deal_load_id.',res:'.json_encode($res));
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * forceResetInitPwd
     * 重置初始密码
     *
     * @param mixed $userId
     * @param mixed $newPwd
     * @access public
     * @return void
     */
    public function forceResetInitPwd($userId, $newPwd)
    {
        $ret = array('status' => 0, 'msg' => '');
        $user = UserModel::instance()->find($userId);
        if ($user->force_new_passwd != 1) {
            $ret['status'] = 1;
            $ret['msg'] = '不可以重置初始密码，请走修改密码步骤！';
            return $ret;
        }
        $phone = $user->mobile;
        $webboObj = new WebBO('api');
        $rr = $webboObj->resetPwd($phone, $newPwd);
        $user->force_new_passwd = 0;
        $user->save();
        return $ret;
    }

    public function getBankCodeByUid($userId) {
        if (empty($userId)) {
            return false;
        }
        $shortName = '';
        $bankId = $GLOBALS['db']->getOne("SELECT bank_id FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
        if (!empty($bankId)) {
            $shortName = $GLOBALS['db']->getOne("SELECT short_name FROM firstp2p_bank WHERE id = '{$bankId}'");
        }
        return $shortName;
    }

    // 判断是不是O2O用户
    public function isOtoUser($uid, &$userInfo) {

        $userInfo['showO2O'] = app_conf('O2O_SHOW_APP_ENTRANCE');
        $userInfo['isO2oUser'] = 0;
        if (O2OService::getSiteO2OStatus()) {
            $userInfo['isO2oUser'] = 1;
        }

        return true;
    }

    // 判断是不是商户
    public function isSeller($uid, &$userInfo) {
        $userInfo['isSeller'] = 0;
        $userInfo['couponUrl'] = '';
        $tagService = new \core\service\UserTagService;
        if ($tagService->getTagByConstNameUserId('O2O_SELLER', $uid)){
            $userInfo['isSeller'] = 1;
            $title = urlencode('礼券兑换');
            $url= urlencode(app_conf('O2O_SELLER_COUPON_LIST_URL'));
            $userInfo['couponUrl'] = sprintf('coupon://api?type=webview&identity=couponRedeem&needback=true&needrefresh=true&title=%s&url=%s', $title, $url);
        }
        else {

            $userInfo['couponUrl'] = 'coupon://api?type=native&name=coupontab';
        }
        $this->isOtoUser($uid, $userInfo);
    }

    public function moveUserToNewGroup($userId, $newGroupId = 0, $coupon_level_id = 0) {
        $userInfo = array('group_id' => $newGroupId);
        //临时逻辑，20150608 群强需要修复
        if($newGroupId == 205){
            $userInfo['coupon_level_id'] = 608;
        }
        // 增加用户优惠码等级支持
        if (!empty($coupon_level_id)) {
            $userInfo['coupon_level_id'] = $coupon_level_id;
        }


        $GLOBALS['db']->autoExecute('firstp2p_user', $userInfo, 'UPDATE', " id = '{$userId}'");
        $affected = $GLOBALS['db']->affected_rows();
        if ($affected >= 1) {
            \libs\utils\PaymentApi::log('尝试转移用户成功');
            return true;
        }
        \libs\utils\PaymentApi::log('尝试转移用户失败');
        return false;
    }

    public function getUserByInviteCode($couponCode) {
        $couponService = new \core\service\CouponService();
        $couponInfo = $couponService->queryCoupon($couponCode);
        if (!empty($couponInfo['refer_user_id'])) {
            return $this->getUser($couponInfo['refer_user_id']);
        }
        return array();
    }


    /**
     * 修复会员用户注册信息
     * @param user 要操作的用户相关信息
        $user = array(
            'id', 'refer_user_id', 'invite_code',
        )

     * @param $data 用户修复的数据
        $data = array(
            'idno', 'group_id', 'level_id', 'tags', 'invite_code'
        )
     */
    public function fixUserRegister($userId, $data = array()) {
        $GLOBALS['db']->startTrans();
        try {
            $toUpdate = array();
            // 邀请码 修改
            if (!empty($data['invite_code'])) {
                $couponService = new \core\service\CouponService();
                // 转化邀请码为大写形式
                $data['invite_code'] = strtoupper($data['invite_code']);
                $couponInfo = $couponService->queryCoupon($data['invite_code']);
                if (!empty($couponInfo['refer_user_id'])) {
                    $toUpdate['invite_code'] = $data['invite_code'];
                    $toUpdate['refer_user_id'] = $couponInfo['refer_user_id'];
                }
                $refer_user_name = $GLOBALS['db']->get_slave()->getOne("SELECT user_name FROM firstp2p_user WHERE id = '{$toUpdate['refer_user_id']}'");
                $couponUpdate = array_merge(array(),$toUpdate);
                if (!empty($couponInfo['agency_id'])) {
                    $couponUpdate['agency_id'] = $couponInfo['agency_id'];
                }
                if (!empty($refer_user_name)) {
                    $couponUpdate['refer_user_name'] = $refer_user_name;
                }
                // 修改coupon log表
                $couponLogService = new \core\service\CouponLogService();
                $couponLogService->changeRegShortAlias($userId, $data['invite_code']);
            }

            // 挪组和优惠码等级ID
            if (!empty($data['group_id']) && !empty($data['level_id'])) {
                $toUpdate['group_id'] = $data['group_id'];
                $toUpdate['coupon_level_id'] = $data['level_id'];
            }
            if (empty($toUpdate)) {
                throw new \Exception(__FUNCTION__.':empty user transfer data');
            }
            $GLOBALS['db']->autoExecute('firstp2p_user', $toUpdate, 'UPDATE', " id = '{$userId}' ");
            // 用户tags处理
            if (!empty($data['tags'])) {
                $tagService = new \core\service\UserTagService();
                $tags = explode('|', $data['tags']);
                $tagService->addUserTagsByConstName($userId, $tags);
            }
            $GLOBALS['db']->commit();
            return true;
        }
        catch(\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log('fixUserRegister: uid:{'.$userId.'} '.json_encode($data).' failed.'.$e->getMessage());
        }
        return false;
    }

    /**
     * 将用户更新为经讯时代用户
     * @param $userId
     * @return bool
     */
    public function updateUserToJXSD($userId) {
        $userId = intval($userId);
        $user_model = new UserModel();
        $user = $user_model->find($userId, 'id,is_dflh', true);
        //用户不存在
        if (empty($user)) {
            return false;
        }
        //用户已经是经讯时代用户
        if ($user['is_dflh'] != 1) {
            return true;
        }
        $updateUserData = array(
            'id' => $userId,
            'is_dflh' => 0,
        );
        $ret =  $user_model->updateInfo($updateUserData, 'update');
        if($ret) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"用户迁移确认成功userId:{$userId}")));
            return true;
        } else {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"用户迁移确认失败userId:{$userId}")));
            return false;
        }
    }

    /**
     * 批量导入用户身份证信息
     */
    public function addUserRegisterInfo($data) {
        $toUpdate = array();
        $toUpdate['partner'] = $data['partner'];
        // 身份证号采用加密存储，统一使用大写的X后缀
        $toUpdate['idno'] = strtoupper(addslashes($data['idno']));
        $toUpdate['transferToGroupId'] = intval($data['group_id']);
        $toUpdate['transferToLevelId'] = intval($data['level_id']);
        $toUpdate['tags'] = addslashes($data['tags']);
        $toUpdate['inviteCode'] = addslashes(strtoupper($data['invite_code']));
        $toUpdate['is_validate'] = true;
        $toUpdate['create_time'] = get_gmtime();
        $idnoExist = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM firstp2p_idno_register WHERE idno = '{$toUpdate['idno']}'");
        $affectRows = 0;
        if($idnoExist == 0) {
            $GLOBALS['db']->autoExecute('firstp2p_idno_register', $toUpdate, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
        }
        if ($affectRows) {
            return true;
        }
        return false;
    }


    public function checkUserIdno($userId, $idno = '') {
        if (empty($idno)) {
            $idno = $GLOBALS['db']->get_slave()->getOne("SELECT idno FROM firstp2p_user WHERE id = '{$userId}'");
        }
        if (empty($idno)) {
            PaymentApi::log(__FUNCTION__.' empty idno');
            return false;
        }
        // 身份证号采用加密存储，统一使用大写的X后缀
        $idno = strtoupper(addslashes($idno));
        // 检查身份证号是否在身份证注册表中
        $idnoRegisterInfo = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM firstp2p_idno_register WHERE idno = '{$idno}'");
        if (empty($idnoRegisterInfo)) {
            PaymentApi::log(__FUNCTION__.' empty idnoRegisterInfo');
            return false;
        }
        $data['group_id'] = $idnoRegisterInfo['transferToGroupId'];
        $data['level_id'] = $idnoRegisterInfo['transferToLevelId'];
        $data['tags'] = $idnoRegisterInfo['tags'];
        $data['invite_code'] = $idnoRegisterInfo['inviteCode'];
        $data['idno'] = $idnoRegisterInfo['idno'];
        return $this->fixUserRegister($userId, $data);
    }

    public function getUserArray($userId, $fields = '*', $isSlave = true) {
        $userInfo = UserModel::instance()->find($userId, $fields, $isSlave);
        if (!empty($userInfo)) {
            return $userInfo->getRow();
        }
        return false;
    }

    public function getFormatUsername($user_id = 0) {
        $user_name = '';
        if (!empty($user_id)) {
           $deal_model = new \core\dao\DealModel();
           $user_name = $deal_model->getDealUserName($user_id);
        }
        return $user_name;
    }

    /**
     * 用户是否实名认证、是否绑卡、是否验过卡
     */
    public function isBindBankCard($opt = [])
    {
        // 默认检查权限
        if (empty($opt))
        {
            $opt = ['check_validate' => true];
        }

        if (isset($this->_userObject['id']) && $this->_userObject['id'] > 0)
        {
            // 检查用户是否验证手机号
            if (isset($this->_userObject['mobilepassed']) && intval($this->_userObject['mobilepassed']) <= 0)
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_MOBILE, 'respMsg'=>'您尚未绑定手机号');
            }
            // 检查用户是否实名认证
            if (isset($this->_userObject['idcardpassed']) && intval($this->_userObject['idcardpassed']) <= 0)
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_IDCARD, 'respMsg'=>'请先进行实名认证');
            }
            // 用户银行卡检查
            $userBankcardService = new UserBankcardService();
            $bankcardInfo = $userBankcardService->getBankcard($this->_userObject['id']);
            if (empty($bankcardInfo) || (isset($bankcardInfo['status']) && $bankcardInfo['status'] != 1))
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNBIND, 'respMsg'=>'请先绑定银行卡');
            }
            else
            {
                if (isset($opt['check_validate']) && $opt['check_validate'] === true)
                {
                    if (isset($bankcardInfo['verify_status']) && $bankcardInfo['verify_status'] != 1)
                    {
                        return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNVALID, 'respMsg'=>'请先验证银行卡');
                    }
                }
            }
            // 检查用户是否在支付开户
            if (isset($this->_userObject['payment_user_id']) && intval($this->_userObject['payment_user_id']) <= 0)
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_PAYMENTUSERID, 'respMsg'=>'您尚未在支付开户');
            }
            return array('ret'=>true, 'respCode'=>'00', 'respMsg'=>'校验通过');
        }
        return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNLOGIN, 'respMsg'=>'您尚未登录');
    }

    /** 企业用户方法集合**/
    /**
     * 判断当前用户是否是企业用户
     *
     * @return boolean
     */
    public function isEnterprise()
    {
        if (isset($this->_userObject['id']) && $this->_userObject['id'] > 0)
        {
            return $this->_userObject['user_type'] == UserModel::USER_TYPE_ENTERPRISE ? true : false;
        }
        return false;
    }

    /**
     * 判断是否是企业用户(user_type=1或者mobile首位为6的认定为企业用户)
     * @return bool
     */
    function isEnterpriseUser()
    {
        if (isset($this->_userObject['id']) && $this->_userObject['id'] > 0)
        {
            if($this->_userObject['user_type'] == UserModel::USER_TYPE_ENTERPRISE
               || (!empty($this->_userObject['mobile']) && substr($this->_userObject['mobile'], 0, 1) == '6'
                && (empty($this->_userObject['mobile_code']) || $this->_userObject['mobile_code'] == '86'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取个人/企业用户信息
     * @return boolean
     */
    function getEnterpriseOrCompanyUser()
    {
        $isEnterprise = $this->isEnterpriseUser();
        if ($isEnterprise) {
            if ($this->_userObject['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                // 企业用户
                $data = $this->getEnterpriseInfo();
                return array('userBizType'=>UserModel::USER_TYPE_ENTERPRISE, 'data'=>$data, 'companyName'=>$data['company_name']);
            }else if (!empty($this->_userObject['mobile']) && substr($this->_userObject['mobile'], 0, 1) == '6'
              && (empty($this->_userObject['mobile_code']) || $this->_userObject['mobile_code'] == '86')) {
                  // 手机号特殊的企业用户
                  return array('userBizType'=>UserModel::USER_TYPE_ENTERPRISE, 'data'=>$this->_userObject, 'companyName'=>$this->_userObject['real_name']);
            }
        }else{
            return array('userBizType'=>UserModel::USER_TYPE_NORMAL, 'data'=>$this->_userObject, 'realName'=>$this->_userObject['real_name']);
        }
    }

    /**
     * 返回企业用户资料
     * @param boolean $withContractInfo 同时读取企业联系人信息
     *
     * @return array 企业用户信息数组
     */
    public function getEnterpriseInfo($withcontactInfo = false)
    {
        if (!$this->isEnterprise())
        {
            return array();
        }
        $enterprise = array();
        // 企业基本信息
        $enterpriseModel = $this->_getEnterpriseInfo();
        $enterprise = array_merge($enterprise, ($enterpriseModel ? $enterpriseModel->getRow() : array()));
        if ($withcontactInfo && !empty($enterprise))
        {
            $contactInfo = $this->_getEnterpriseContactInfo(true);
            $enterprise['contact'] = $contactInfo;
        }
        // 企业证件类型汉化
        $credentials_types = !empty($GLOBALS['dict']['CREDENTIALS_TYPE']) ? $GLOBALS['dict']['CREDENTIALS_TYPE'] : array(
            //'0' => '其他',
            '1' => '营业执照',
            //'2' => '组织机构代码证',
            '3' => '三证合一营业执照'
        );
        $enterprise['credentials_type_cn'] = $credentials_types[$enterprise['credentials_type']];
        // 企业证件号码遮罩
        $enterprise['credentials_no_mask'] = substr($enterprise['credentials_no'], 0, 2).'*****'.substr($enterprise['credentials_no'], strlen($enterprise['credentials_no']) - 2 , 2);

        return $enterprise;
    }

    /**
     * 读取企业用户接收短信手机列表，默认区号为86 ，仅支持大陆企业用户
     *
     * @return array
     */
    public function getEnterpriseSmsNumber()
    {
        if (!$this->isEnterprise())
        {
            return array();
        }
        $mobileInfo = array();
        $contactInfo = $this->getEnterpriseContactInfo(true);
        return $contactInfo;
    }

    public function getBankcardCount($userId) {
        $count = UserBankcardModel::instance()->findBySqlViaSlave(sprintf("SELECT count(*) FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'"));
        return intval($count);
    }
    /**
     * 返回企业联系人信息
     *
     * @return array
     */
    public function getEnterpriseContactInfo($smsOnly = false)
    {
        if (!$this->isEnterprise())
        {
            return array();
        }
        $mobileInfo = array();
        $enterpriseInfo = $this->getEnterpriseInfo(true);
        // receive_msg_mobile 单独处理
        if ($smsOnly)
        {
            $userId = $this->_userObject->id;
            if ($this->getBankcardCount($userId) > 0) {
                $mobileList = trim($enterpriseInfo['contact']['receive_msg_mobile'], ',');
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
                    'code' => isset($enterpriseInfo['contact']['consignee_phone_code']) ? $enterpriseInfo['contact']['consignee_phone_code'] : '86',
                    'mobile' => isset($enterpriseInfo['contact']['consignee_phone']) ? $enterpriseInfo['contact']['consignee_phone'] : '',
                );
            }

            return $mobileInfo;
        }
        // 法人联系信息
        $legalbodyInfo = array();
        if (!empty($enterpriseInfo['legalbody_name']))
        {
            $legalbodyInfo['name'] = $enterpriseInfo['legalbody_name'];
            $legalbodyInfo['code'] = $enterpriseInfo['legalbody_mobile_code'];
            $legalbodyInfo['mobile'] = $enterpriseInfo['legalbody_mobile'];
            $mobileInfo[] = $legalbodyInfo;
        }
        // 企业负责人联系信息
        $majorInfo = array();
        if (!empty($enterpriseInfo['contact']['major_name']))
        {
            $majorInfo['name'] = $enterpriseInfo['contact']['major_name'];
            $majorInfo['code'] = $enterpriseInfo['contact']['major_mobile_code'];
            $majorInfo['mobile'] = $enterpriseInfo['contact']['major_mobile'];
            $mobileInfo[] = $majorInfo;
        }
        // 企业联系人2信息
        $contactInfo = array();
        if (!empty($enterpriseInfo['contact']['contact_name']))
        {
            $contactInfo['name'] = $enterpriseInfo['contact']['contact_name'];
            $contactInfo['code'] = $enterpriseInfo['contact']['contact_mobile_code'];
            $contactInfo['mobile'] = $enterpriseInfo['contact']['contact_mobile'];
            $mobileInfo[] = $contactInfo;
        }
        // 经办人信息
        $employeeInfo = array();
        if (!empty($enterpriseInfo['contact']['employee_name']))
        {
            $employeeInfo['name'] = $enterpriseInfo['contact']['employee_name'];
            $employeeInfo['code'] = $enterpriseInfo['contact']['employee_mobile_code'];
            $employeeInfo['mobile'] = $enterpriseInfo['contact']['employee_mobile'];
            $mobileInfo[] = $employeeInfo;
        }
        return $mobileInfo;
    }

    /**
     * 读所有企业用户信息
     *
     * @return array
     */
    public function getAllEnterpriseInfo()
    {
        $allEnterpriseInfo = EnterpriseModel::instance()->db->get_slave()->getAll("SELECT id,real_name,user_name FROM firstp2p_user WHERE user_type = '".UserModel::USER_TYPE_ENTERPRISE."' AND is_effect = 1");
        return $allEnterpriseInfo;
    }

    /**
     * 读取企业用户基本信息
     * @param boolean $retArray 以数组方式返回, false 返回model对象
     *
     * @return mixed array|EnterpriseModel
     */
    private function _getEnterpriseInfo($retArray = false)
    {
        $enterpriseModel = EnterpriseModel::instance()->findBySqlViaSlave(sprintf("SELECT * FROM firstp2p_enterprise WHERE user_id = '{$this->_userObject->id}'"));
        if (!$retArray)
        {
            return $enterpriseModel;
        }
        return $enterpriseModel->getRow();
    }

    /**
     * 读取企业用户联系人信息数据
     * @param boolean $retArray 以数组方式返回， false 返回model对象
     *
     * @return mixed array|EnterprisecontactModel
     */
    private function _getEnterpriseContactInfo($retArray = false)
    {
        $contactInfo = $this->_getEnterpriseInfo()->getContactInfo();
        if (!$retArray)
        {
            return $contactInfo;
        }
        return $contactInfo ? $contactInfo->getRow() : array();
    }

    /**
     * [根据用户的id，获取对应用户类型的用户基本信息]
     * @author <fanjingwen@ucfgroup.com>
     * @param array[seq => userID] $userIDArr [用户id]
     * @param bool $needUserTypeName 是否返回userTypeName
     * @return array[userID => [userInfo]] [二维数组，user_type对应的用户基本信息]
     */
    public function getUserInfoListByID($userIDArr, $needUserTypeName = false) {
        $listOfUser = array();
        if (!is_array($userIDArr) || empty($userIDArr)) {
            return $listOfUser;
        }

        // 批量获取用户信息
        $userInfoArr = UserModel::instance()->getUserInfoByIDs($userIDArr);
        foreach ($userInfoArr as $userInfo) {
            $userID = $userInfo['id'];
            $listOfUser[$userID] = $userInfo;
            // 判断用户类型
            if (UserModel::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userInfo['id']);
                $listOfUser[$userID]['company_name'] = $enterpriseInfo['company_name'];
                $listOfUser[$userID]['mobile'] = '-';
            }

            // 判断是否需要userTypeName
            if ($needUserTypeName) {
                $userTypeName = UserModel::USER_TYPE_NORMAL_NAME;
                if (UserModel::USER_TYPE_NORMAL == $userInfo['user_type']) {
                    $userTypeName = UserModel::USER_TYPE_NORMAL_NAME;
                    $company = UserCompanyModel::instance()->findByViaSlave("user_id = '$userID'", 'name');
                    $userTypeName = $company ? UserModel::USER_TYPE_ENTERPRISE_NAME : UserModel::USER_TYPE_NORMAL_NAME;
                } else {
                    $userTypeName = UserModel::USER_TYPE_ENTERPRISE_NAME;
                }

                $listOfUser[$userID]['user_type_name'] = $userTypeName;
            }
        }

        return $listOfUser;
    }
     /* Get Users By Site Id
     * @param int site_id
     * @param int offset
     * @param int count
     * @return mixed array|UserModel
     */
    public function getUserBySiteId($site_id = 1, $offset = 0, $count = 10, $updateTime = 0, $sortType = 0)
    {
        return UserModel::instance()->getUserBySiteId($site_id, $offset, $count, $updateTime, $sortType);
    }

    public function getUserByMobileORIdno($mobile, $idno) {
        $feilds = 'id, real_name, idno, mobile';
        return UserModel::instance()->getUserByMobileOrIdno($mobile, $idno, $feilds);
    }

    //通过真实姓名查询
    public function getUserByRealName($realName) {
        $feilds = 'id, real_name, idno, mobile';
        return UserModel::instance()->getUserByRealName($realName, $feilds);
    }

    public function getUserByUserId($userId, $fields = '*') {
        $user = UserModel::instance()->find($userId, $fields, true);
        return empty($user) ? array() : $user->getRow();
    }

    public function webUnionUserDel($mobile)
    {
        return UserModel::webUnionUserDel($mobile);
    }


    /**
     * 网信理财用户银行卡解绑
     * @param string $userId 用户id
     * @param string $bankcardNo 银行卡号
     * @return boolean
     */
    public function unbindCard($userId, $bankcardNo) {
        PaymentApi::log('User Unbindcard Request, userId:'.$userId);
        $db = \libs\db\Db::getInstance('firstp2p');
        try {
            $db->startTrans();
            // 清空用户银行卡数据
            $unbindBankcard = UserBankcardModel::instance()->unbindCard($userId);
            if (!$unbindBankcard) {
                throw new \Exception('解绑银行卡失败');
            }
            // 清除用户换卡申请数据
            $clearBankcardAudit = UserBankcardAuditModel::instance()->clearBankcardAudit($userId, $bankcardNo);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
        return true;
    }

    /*
     * 账户注销-网信理财
     * @param int $userId 用户ID
     * @return array
     */
    public function wxMemberCancel($userId) {
        try{
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();
            // user_bankcard表，解绑用户绑卡记录
            UserBankcardModel::instance()->unbindCard($userId);

            // user表，把用户注销账户
            $updateRet = UserModel::instance()->setUserCancel($userId);
            if ($updateRet <= 0) {
                throw new \Exception('Update UserInvalid Failed');
            }
            $db->commit();
            return true;
        } catch(\Exception $e) {
            $db->rollback();
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, '理财账户注销异常,' . $e->getMessage())));
            return false;
        }
    }

    //根据用户id或者手机号获取用户信息
    public function getUserByUidOrMobile($userId = 0, $mobile = '') {
        if (empty($userId) && empty($mobile)) {
            return false;
        }

        if (!empty($userId)) {
            $res = $this->getUser($userId);
            if (!is_object($res)) {
                return false;
            }

            $row = $res->_row;
            return ((empty($row)) || (!empty($mobile) && $row['mobile'] != $mobile)) ? false : $row;
        }

        $res = $this->getByMobile($mobile);
        if (!is_object($res)) {
            return false;
        }

        $row = $res->_row;
        return empty($row) ? false : $row;
    }

    /**
     * 用户签署网信超级账户免密协议
     * @param $userId
     * @return bool
     */
    public function signWxFreepayment($userId) {
        $userId = (int)$userId;
        $user_model = new UserModel();
        $user = $user_model->find($userId, 'id, wx_freepayment', true);
        if (empty($user)) {
            return false;
        }
        if ((int)$user['wx_freepayment'] == 1) {
            return true;
        }
        $updateUserData = array(
            'id' => $userId,
            'wx_freepayment' => 1,
        );
        $ret = $user_model->updateInfo($updateUserData, 'update');
        if($ret) {
            return true;
        } else {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"用户签署失败userId:{$userId}")));
            return false;
        }
    }
    /*
     * 返回用户账户类型信息
     * @param $userPurpose 用户的账户类型
     * @return array
     */
    public function getUserPurposeInfo($userPurpose)
    {
        $purposeList = !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE']) ? $GLOBALS['dict']['ENTERPRISE_PURPOSE'] : [];
        return !empty($purposeList[(int)$userPurpose]) ? $purposeList[(int)$userPurpose] : [];
    }

    //根据用户IDs，获取用户某些信息,开发平台使用
    public function getUserInfoByIds($idArr, $columns = 'id, mobile, real_name') {
        $user_model = new UserModel();
        $userInfoArr = $user_model->getUserInfoByIDs($idArr, $columns);
        if (empty($userInfoArr)) {
            return array();
        }
        //根据用户ID，建立映射，返回用户身份信息
        foreach ($userInfoArr as $userInfo) {
            $userID = $userInfo['id'];
            $listOfUser[$userID] = $userInfo;
       }
        return $listOfUser;
    }

    /**
     * 根据用户 id 获取用户的角色列表
     * @param int $userId
     * @return array
     */
    public function getUserRoleListByUserId($userId)
    {
        $userService = new UserService();

        //判断用户是否为担保
        $userAgencyInfo = $userService->getUserAgencyInfoNew(array('id'=>$userId));
        $isAgency = intval($userAgencyInfo['is_agency']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $userAdvisoryInfo = $userService->getUserAdvisoryInfo(array('id'=>$userId));
        $isAdvisory = intval($userAdvisoryInfo['is_advisory']);

        //判断是否为新合同签署流程并判断是否为资产管理方
        $userEntrustInfo = $userService->getUserEntrustInfo(array('id'=>$userId));
        $isEntrust = intval($userEntrustInfo['is_entrust']);

        //判断是否为新合同签署流程并判断是否为渠道
        $userCanalInfo = $userService->getUserCanalInfo(array('id'=>$userId));
        $isCanal = intval($userCanalInfo['is_canal']);

        $result['is_agency'] = $isAgency == 1?true:false;
        $result['is_advisory'] = $isAdvisory == 1?true:false;
        $result['is_entrust'] = $isEntrust == 1?true:false;
        $result['is_canal'] = $isCanal == 1?true:false;
        $result['is_borrow'] = DealModel::instance()->isBorrowUser($userId) == true?true:false;

        return $result;
    }

    /**
     * 更新用户实名认证信息
     * @param int $userId
     * @param array $params
     * @param obj $id5Obj
     * @return boolean
     */
    public function updateUserIdnoInfo($userId, $params, $id5Obj = NULL) {
        if (is_null($id5Obj)) {
            $id5Obj = new \libs\idno\CommonIdnoVerify();
        }
        $datas = [];
        $datas['real_name'] = $params['realName'];
        $datas['idno'] = trim($params['cardNo']);
        $datas['idcardpassed'] = 1;
        $datas['idcardpassed_time'] = time();
        $idType = $GLOBALS['db']->getOne("SELECT id_type FROM firstp2p_user WHERE id = '{$userId}'");
        $birth = [];
        if ($idType == 1) {
            $datas['sex'] = $id5Obj->getSex($datas['idno']);
            // 取得生日（由身份证号）
            $birth = $id5Obj->getBirthDay(trim($params['cardNo']));
        } else {
            // 港澳台用户取资料
            $passportInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_user_passport WHERE uid = '{$userId}'");
            if (!empty($passportInfo)) {
                $datas['sex'] = $passportInfo['sex'];
                $birth = array_combine(['year', 'month', 'day'], explode('-', $passportInfo['birthday']));
            }
        }
        // 设置出生日期
        if (!empty($birth)) {
            $datas['byear'] = $birth['year'];
            $datas['bmonth'] = $birth['month'];
            $datas['bday'] = $birth['day'];
        }
        // 更新用户信息
        return $GLOBALS['db']->autoExecute('firstp2p_user', $datas, 'UPDATE', " id = '{$userId}' ");
    }

    /**
     * toggle用户状态
     * @return boolean
     */
    public function setUserEffect($userId) {
        Monitor::add('WX_CARRY_REFUSE');
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $userInfo = $db->getRow("SELECT id,user_name,is_effect FROM firstp2p_user WHERE id = '{$userId}'");
        if (empty($userInfo)) {
            PaymentApi::log('Reject Usercarry Failed, msg:empty userinfo, uid :'.$userId);
            return ['status' => false, 'setState' => null];
        }
        $userCurrentState = intval($userInfo['is_effect']);
        $setState = $userCurrentState == 0 ? 1 : 0; //需设置的状态
        try {
            if ($setState == 0) {
                // 用户tag 检查
                $tagName = 'SAFE_CUSTOMER_17711';
                $tagService = new UserTagService();
                if ($tagService->getTagByConstNameUserId($tagName, $userId)) {
                    // 判断是否有未处理的提现数据
                    $carries = $db->getAll("SELECT id FROM firstp2p_user_carry WHERE user_id = '{$userId}' AND status = 3 AND withdraw_status = 0");
                    if (empty($carries)) {
                        return ['status' => true, 'username' => $userInfo['user_name'], 'setState' => $setState];
                    }
                    $carryService = new UserCarryService();
                    $ctr  = count($carries);
                    foreach ($carries as $carryRecord) {
                        $carryService->doRefuse($carryRecord['id'], 2);
                    }

                }
            }
            $db->autoExecute('firstp2p_user', ['is_effect' => $setState], 'UPDATE', " id = '{$userId}'");
        } catch (\Exception $e) {
            PaymentApi::log('Reject Usercarry Failed, msg:refuse user carry failed, userid:'.$userId);
            return ['status' => false, 'setState' => $setState];
        }
        return ['status' => true, 'username' => $userInfo['user_name'], 'setState' => $setState];
    }

    public function getUserByPPID($ppID) {
        $user = (new PassportService())->userBind($ppID);
        if (empty($user)) {
            return false;
        }
        $this->isSeller($user['id'], $user);
        return $user;
    }

    public function getUserByMobile($mobile) {
        $user = $this->getUserByUidOrMobile(0, $mobile);
        if (empty($user)) {
            return false;
        }
        $this->isSeller($user['id'], $user);
        return $user;
    }

    /**
     * 更新用户实名信息
     * @param array $params
     *  user_id 用户id
     *  real_name 姓名
     *  id_type 卡类型
     *  idno 卡号
     */
    public function updateUserIdentityInfo($params) {
        if (empty($params)) {
            return false;
        }

        $db = \libs\db\Db::getInstance('firstp2p');
        $db->startTrans();
        try {
            // 更新用户信息
            $res = UserModel::instance()->updateUserIdentity(
                $params['user_id'],
                $params['real_name'],
                $params['id_type'],
                $params['idno']
            );

            if (!$res) {
                throw new \Exception('更新用户实名信息失败');
            }

            // 更新用户银行卡开户名
            $userBankcardService = new UserBankcardService();
            $res = $userBankcardService->wxUpdateUserBankCardName($params['user_id'], $params['real_name']);
            if (!$res) {
                throw new \Exception('更新用户银行卡开户名失败');
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "err: ".$e->getMessage())));
            return false;
        }
    }

    /**
     * 更新用户实名信息
     * @param array $params
     *  user_id 用户id
     *  order_id 请求订单id
     *  status 订单状态
     *  fail_reason 错误原因
     */
    public function updateUserIdentityByLog($params) {
        if (empty($params)) {
            return false;
        }
        $modifyLogModel = UserIdentityModifyLogModel::instance();
        $modifyLog = $modifyLogModel->getLogByOrderId($params['order_id']);
        if ($modifyLog['status'] == $params['status']) {
            return true;
        }
        $db = \libs\db\Db::getInstance('firstp2p');
        try {
            $db->startTrans();
            //更新日志
            $res = $modifyLog->updateLog($params);
            if (!$res) {
                throw new \Exception('更新用户实名信息日志失败');
            }

            //成功才更新用户信息
            if ($params['status'] == UserIdentityModifyLogModel::STATUS_SUCCESS) {
                $updateParams = [
                    'user_id' => $modifyLog['user_id'],
                    'real_name' => $modifyLog['real_name'],
                    'id_type' => $modifyLog['id_type'],
                    'idno' => $modifyLog['idno'],
                ];
                $res = $this->updateUserIdentityInfo($updateParams);
                if (!$res) {
                    throw new \Exception('更新用户实名信息和开户名失败');
                }
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"err: ".$e->getMessage())));
            return false;
        }
    }

    /**
     * 是否开通存管账户
     * @return boolean
     */
    public function isSupervisionUser() {
        $superAccountService = new \core\service\SupervisionAccountService();
        $isSuperUser = $superAccountService->isSupervisionUser($this->_userObject);
        if ($isSuperUser) {
            return true;
        }
        return false;
    }

    /**
     * 实名验证是否通过
     * @return array
     */
    public function isIdCardPassed() {
        if (isset($this->_userObject['idcardpassed']) && intval($this->_userObject['idcardpassed']) <= 0) {
            return false;
        }
        return true;
    }

    /**
     * 用户银行卡绑定检查
     * @return array
     */
    public function isBankCardBinded() {
        $userBankcardService = new UserBankcardService();
        $bankcardInfo = $userBankcardService->getBankcard($this->_userObject['id']);
        if (empty($bankcardInfo) || (isset($bankcardInfo['status']) && $bankcardInfo['status'] != 1)) {
            return false;
        }
        return true;
    }

    /**
     * 未验证银行卡
     * @return 未验证($verify_status=1) true 已验证 false
     */
    public function isBankCardUnverfied($verify_status = 1) {
        $userBankcardService = new UserBankcardService();
        $bankcardInfo = $userBankcardService->getBankcard($this->_userObject['id']);
        if (isset($bankcardInfo['verify_status']) && $bankcardInfo['verify_status'] == $verify_status) {
            return false;
        }
        return true;
    }

    /**
     * 判断是否是企业会员用户
     * return array 企业用户信息数组
     */
    public function getEnterpriseUserInfo($userId) {
        return EnterpriseModel::instance()->getEnterpriseInfoByUserID($userId);
    }

    /**
     * 支付白名单接口调用
     * @param type $userId
     * @return boolean
     */
    public function paymentWhiteListApi($userId) {

        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $userInfo = $db->getRow("SELECT user_purpose,id,user_type,mobile,mobile_code FROM firstp2p_user WHERE id = '{$userId}'");
        if (empty($userInfo)) {
            return false;
        }
        $bankInfo = $db->getRow("SELECT bank_id,bankcard FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
        if (empty($bankInfo)) {
            return false;
        }
        $userInfo['bankcard'] = $bankInfo['bankcard'];
        $shortName = $db->getOne("SELECT short_name FROM firstp2p_bank WHERE id = '{$bankInfo['bank_id']}'");
        $params = [];
        $params['userId'] = $userId;
        $params['orderId'] = md5(time());
        $params['cardNo'] = $userInfo['bankcard'];
        $params['bankCode'] = $shortName;
        $result = PaymentApi::instance()->request('staticWhitelist', $params);
        if (isset($result['status']) && $result['status'] != '00') {
            return $result;
        }
        return $params;
    }

    /**
     * 调用支付认证类型接口
     * 调用searchbankcard接口
     * @param type $userId
     */
    public function paymentVerifyTypeApi($userId) {
        // 获取支付系统所有银行卡列表-安全卡数据
        $obj = new UserBankcardService();
        $bankInfo = $obj->queryBankCardsList($userId, true);
        if (is_array($bankInfo) && !empty($bankInfo['list'])) {
            return $bankInfo['list'];
        }
        return $bankInfo;
    }

    /**
     * 完成理财侧用户认证类型更新
     * 根据支付的认证类型，更新理财用户绑卡表 firstp2p_user_bankcard 的 cert_status 字段
     * 同步支付和理财的认证类型
     */
    public function updateUserBankcardCertStatus($userId, $cardNo, $certStatus) {
        $userBankcardInfoObj = UserBankcardModel::instance()->getByUserId($userId);
        $userBankcardInfo = $userBankcardInfoObj->getRow();
        $current_cert_status = $userBankcardInfo['cert_status'];
        $cert_status_map = UserBankcardModel::$cert_status_map;
        if ($current_cert_status == $cert_status_map[$certStatus]) {
            //用户状态已更新到最新，无改动则无需执行更新语句
            return true;
        }
        return UserBankcardModel::instance()->updateCertStatusByUserIdAndCardNo($userId, $cardNo, $certStatus);
    }

    /**
     * 更新四要素认证状态
     */
    public function updateUserBankcardVerifyStatus($userId, $cardNo, $verifyStatus = 1) {
        $userBankcardInfoObj = UserBankcardModel::instance()->getByUserId($userId);
        $userBankcardInfo = $userBankcardInfoObj->getRow();
        $current_verify_status = $userBankcardInfo['verify_status'];
        if ($current_verify_status == $verifyStatus) {
            //用户更新四要素认证状态已更新到最新，无改动则无需执行更新语句
            return true;
        }
        return UserBankcardModel::instance()->updateVerifyStatusByUserIdAndCardNo($userId, $cardNo, $verifyStatus);
    }

    /**
     * 获取用户名
     * @return string
     */
    public function getUserName() {
        return $this->_userObject['user_name'];
    }

    /**
     * 获取借款用户在途未还清的标的数量
     * @param int $userId
     * @return int $result
     */
    public function getUserInTheLoanCount($userId){
        if(empty($userId)){
            return false;
        }
        $forbidStatus = DealModel::$DEAL_STATUS['repaid'] . "," . DealModel::$DEAL_STATUS['failed'];

        $countSql = "SELECT count(*) FROM ".DealModel::instance()->tableName()." WHERE user_id = ".intval($userId)." AND deal_type = ".DealModel::DEAL_TYPE_GENERAL." AND deal_status  not in ($forbidStatus)";
        $result = DealModel::instance()->countBySql($countSql,null,true);

        return intval($result);
    }

    /**
     * 获取借款用户在途借款金额
     * @param int $userId
     * @return int $result
     */
    public function getUserInTheLoanMoney($userId){
        if(empty($userId)){
            return false;
        }

        $result = array();

        $countSql = "SELECT sum(borrow_ammount) as money FROM ".DealModel::instance()->tableName()." WHERE user_id = ".intval($userId)." AND deal_status = ".DealModel::$DEAL_STATUS['repaying'].";";
        $result = DealModel::instance()->findBySql($countSql,null,true);

        if(empty($result)){
            $result['money'] = 0;
        }

        return $result;
    }

    /**
     * 智多鑫获取用户进行中的投资数量
     * @param int $userId
     * @return 查询失败返回false 查询成功返回具体数量
     */
    public function getUserDuotouInTheLoanCount($userId){
        if(empty($userId)){
            return false;
        }
        $service = new DtInvestNumService();
        $count = $service->getUserOngoingLoanCount($userId);
        return $count;
    }

    /**
     * 判断用户是否允许投资
     */
    public function allowAccountLoan($userPurpose){
        //借贷混合户,投资户允许投资
        return in_array(intval($userPurpose), array(EnterpriseModel::COMPANY_PURPOSE_INVESTMENT, EnterpriseModel::COMPANY_PURPOSE_MIX));
    }

    /**
      *切换用户优惠码状态
     */
    public function setCouponDisable($userId,$status){
        $user_model = new UserModel();
        $user = $user_model->find(intval($userId), 'id, coupon_disable,user_name', true);
        $coupon_disable = $user['coupon_disable'] == 0?1:0;
        $result = $GLOBALS['db']->autoExecute('firstp2p_user', array('coupon_disable'=>$coupon_disable), 'UPDATE', " id = ".intval($userId));
        if(!$result){
            return array('status' => false, 'coupon_disable' => $coupon_disable);
        }

        return array('status' => true, 'username' => $user['user_name'], 'coupon_disable' => $coupon_disable);
    }


    //修改用户组
    public function changeGroupAndLevel($correct, $adm_session)
    {
        try {
            $GLOBALS['db']->startTrans();
            foreach ($correct as $correct_key => $correct_row) {
                $userid = $correct_row['user_id'];
                $params = array(
                        'group_id' => $correct_row['group_id'],
                        'new_coupon_level_id' => $correct_row['level_id'],
                        );
                $userModel = new UserModel();
                $res = $userModel->updateBy($params, sprintf("id ='%d'", $userid));
                if (false === $res) {
                    throw new \Exception(sprintf('序号:%s，用户名：%s，更新分组处理失败', $correct_key, $correct_row['user_name']));
                } else {
                    $userYifang = new ChangeGroupLevelLogModel();
                    $userYifang->user_name = $correct_row['user_name'];
                    $userYifang->real_name = $correct_row['real_name'];
                    $userYifang->old_groupid = $correct_row['old_groupid'];
                    $userYifang->old_levelid = $correct_row['old_levelid'];
                    $userYifang->mobile = $correct_row['mobile'];
                    $userYifang->new_groupid = $correct_row['group_id'];
                    $userYifang->new_levelid = $correct_row['level_id'];
                    $userYifang->adm_id = $adm_session['adm_id'];
                    $userYifang->adm_name = $adm_session['adm_name'];
                    $userYifang->update_time = date('Y-m-d H:i:s');
                    $add_res = $userYifang->insert();
                    if (!$add_res) {
                        throw new \Exception(sprintf('序号:%s，用户名：%s，处理失败', $correct_key, $correct_row['user_name']));
                    }
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.' | '.__FUNCTION__.' | '.'error : '.$e->getMessage());

            return false;
        }

        return true;
    }
    public function getUserAccountInfo($userId)
    {
        $userInfoSql = "select id as userId, money, lock_money from " . UserModel::instance()->tableName() . " where id = {$userId}";
        $userInfo = UserModel::instance()->findBySql($userInfoSql, null, true);
        $userInfo = $userInfo->_row;

        $userThirdSql = "select user_id as userId, supervision_balance, supervision_lock_money from " . UserThirdBalanceModel::instance()->tableName() . " where user_id = {$userId}";
        $userThird = UserThirdBalanceModel::instance()->findBySql($userThirdSql, null, true);
        $userThird = $userThird->_row;

        $userLoanSql = "select user_id as userId, norepay_principal, dt_norepay_principal from " . UserLoanRepayStatisticsModel::instance()->tableName() . " where user_id = {$userId}";
        $userLoan = UserLoanRepayStatisticsModel::instance()->findBySql($userLoanSql, null, true);
        $userLoan = $userLoan->_row;

        $onlineProperty = $userLoan['norepay_principal'] + $userLoan['dt_norepay_principal'];
        $accountBalance = $userInfo['money'] + $userThird['supervision_balance'];
        $lockMoney = $userInfo['lock_money'] + $userThird['supervision_lock_money'];
        $totalProperty = $onlineProperty + $accountBalance + $lockMoney;

        return array(
            'onlineProperty' => $onlineProperty,
            'accountBalance' => $accountBalance,
            'lockMoney' => $lockMoney,
            'totalProperty' => $totalProperty,
        );

    }

    /**
     * 判断用户是否投过资
     * @param $userId
     * @return bool
     *
     */
    public function hasLoan($userId) {
        // 普通标的
        $userFirstDeal = DealLoadModel::instance()->getFirstDealByUser($userId);
        if (!empty($userFirstDeal)) {
            return true;
        }

        //普惠是否投资
        $ncfPhFirstDeal = \core\service\ncfph\DealLoadService::getFirstDealByUser($userId);
        if (!empty($ncfPhFirstDeal)) {
            return true;
        }

        // 智多鑫
        if ((new DtInvestNumService())->getInvestNum($userId)) {
            return true;
        }

        return false;
    }

    /**
     * 更新用户邮箱信息
     * @param $userId 用户ID
     * @param $email 邮箱
     * @return array
     */
    public function updateUserEmail($userId, $email) {
        $result = ['code'=>0, 'msg'=>''];
        $userId = intval($userId);
        if (empty($userId) || empty($email)) {
            $result['code'] = -1;
            $result['msg'] = '参数错误或不合法';
            return $result;
        }

        $user_model = new UserModel();
        $user = $user_model->find($userId, 'id,email', true);
        // 用户不存在
        if (empty($user)) {
            $result['code'] = -2;
            $result['msg'] = '用户不存在';
            return $result;
        }
        // 检查邮箱是否已存在
        $is_exist = $this->checkEmailExist($email);
        if ($is_exist) {
            $result['code'] = -3;
            $result['msg'] = '该邮箱已被使用';
            return $result;
        }

        $updateUserData = array(
            'id' => $userId,
            'email' => $email,
        );
        $ret = $this->updateInfo($updateUserData);
        if($ret) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "更新用户邮箱成功, userId:{$userId}")));
            return $result;
        } else {
            $result['code'] = -4;
            $result['msg'] = '邮箱更新失败';
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "更新用户邮箱失败, userId:{$userId}")));
            return $result;
        }
    }

    //添加用户锁定解锁原因
    public function UserLockComments($id) {
        $user_id = $id;
        $comments = '';
        $db = Db::getInstance('firstp2p');
        $sql1 = "select batch_id from firstp2p_batch_user_res where user_id=$user_id order by batch_id desc";
        $res1 = $db -> getOne($sql1);
        if (!empty($res1)) {
            $sql2 = "select comments from firstp2p_batch_user_change where id=$res1";
            $comments = $db -> getOne($sql2);
        }
        return $comments;
    }

    /**
     * 当前登录用户信息
     */
    private static $loginUserInfo = array();

    /**
     * 设置登录用户信息
     */
    public static function setLoginUser($userInfo)
    {
        self::$loginUserInfo = $userInfo;
    }

    /**
     * 获取登录用户信息
     */
    public static function getLoginUser()
    {
        return self::$loginUserInfo;
    }

    /**
     * 获取当前用户的服务人信息
     */
    public function getReferUserGroupName($userId)
    {
        $res = array();
        $couponBindService = new CouponBindService();
        $couponBind = $couponBindService->getByUserId($userId);
        if(empty($couponBind)){
            return array();
        }

        $referUserInfo = $this->getUser($couponBind['refer_user_id']);
        $userGroupService = new UserGroupService();
        $referUserGroup = $userGroupService->getGroupInfo($referUserInfo['group_id']);
        $res['referUserId'] = $couponBind['refer_user_id'];
        $res['referUserGroupName'] = $referUserGroup['name'];

        return $res;
    }

}
