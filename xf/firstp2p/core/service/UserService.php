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

    /** @var _userObject UserService ????????????????????? */
    private $_userObject;

    /**
     * ??????????????????-????????????
     * @var int
     */
    const STATUS_BINDCARD_UNLOGIN = 1000;

    /**
     * ??????????????????-????????????
     * @var int
     */
    const STATUS_BINDCARD_PAYMENTUSERID = 1001;

    /**
     * ?????????????????????-??????????????????
     * @var int
     */
    const STATUS_BINDCARD_IDCARD = 1002;

    /**
     * ?????????????????????-?????????????????????
     * @var int
     */
    const STATUS_BINDCARD_MOBILE = 1003;

    /**
     * ?????????????????????-?????????????????????
     */
    const STATUS_BINDCARD_UNBIND = 1004;

    /**
     * ??????????????????-?????????????????????
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

    const FIRSTP2P_LOGIN_VALUE = 3; // ?????????3????????????????????????????????????oauth

    /**
     * ??????????????????(?????????)
     */
    public function getUserViaSlave($id, $need_region = false, $need_workinfo = false)
    {
        return $this->getUser($id, $need_workinfo, $need_workinfo, true);
    }

    public function getUserIdByMobile($phone, $is_slave = false) {
        if(empty($phone)) {
            return false;
        }
        //????????????userId by liguizhi 20171018
        $user = UserModel::instance()->getUserIdByMobile($phone, $is_slave);
        if(empty($user)) {
            return false;
        }
        return $user;
    }

    /**
     * ?????????????????????????????????????????????????????????????????????
     * @param object $user
     * @param object $deal
     * @param float $load_money
     * @param bool $hasBank ???????????????????????????????????????????????????
     * @return array('ret'=>bool, 'money'=>array('lc'=>float, 'cg'=>float, 'bonus'=>float))
     */
    public function getMoneyInfo($user,$bidMoney,$orderId=false, $hasBank = true) {
        if(empty($user)){
            return false;
        }

        $bonusInfo = (new \core\service\BonusService())->getUsableBonus($user['id'], true, $bidMoney,$orderId);

        // ?????????????????? ?????????????????????
        $limitMoney = (new \core\service\UserCarryService())->getLimitAmountByUserId($user['id']);

        // ????????????
        $balance = bcsub($user['money'],$limitMoney,2);
        // ????????????????????????????????????????????????0?????????, ????????????????????????0
        if ($balance < 0 )
        {
            $balance = 0;
        }

        $bonusMoney = $bonusInfo['money']; // ????????????
        $bankMoney = 0; // ????????????

        //???????????????????????????????????????????????????
        if ($hasBank) {
            $superAccountService = new \core\service\SupervisionAccountService();
            $isSuperUser = $superAccountService->isSupervisionUser($user);

            // ?????????????????????????????????????????????
            if($isSuperUser && Supervision::isServiceDown() === false){
                $res = $superAccountService->balanceSearch($user['id']);
                if($res['status'] == SupervisionBaseService::RESPONSE_FAILURE){
                    $bankMoney = 0;
                    Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"?????????????????????????????? errMsg:".$res['respMsg'])));
                }else{
                    $bankMoney = bcdiv($res['data']['availableBalance'],100,2);
                }
            }
        }

        $moneyInfo = array('lc' => $balance,'bonus'=>$bonusMoney, 'bank' => $bankMoney, 'limit' => $limitMoney, 'bonusInfo'=>$bonusInfo);
        Logger::info(implode(" | ", array(__CLASS__,__FUNCTION__,"?????????????????? moneyInfo:".json_encode($moneyInfo))));
        return $moneyInfo;
    }

    /**
     * ??????????????????
     *
     * @param $id
     * @param $need_workinfo ??????false
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
            // ??????????????????
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

        // ??????????????????
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

        // ?????????????????????????????????
        $user['is_enterprise_user'] = 0;
        if ((!empty($user['mobile']) && substr($user['mobile'], 0, 1) == 6) || (isset($user['user_type']) && $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)) {
            $user['is_enterprise_user'] = 1;
        }
        if((int)app_conf('USER_JXSD_TRANSFER_SWITCH') !== 1) {
            $user['is_dflh'] = 0;
        }

        //?????????????????????
        $user['isCompliantUser'] = intval(BwlistService::inList('COMPLIANCE_BLACK', $id));

        return $user;
    }

    /**
     * ?????????????????????????????????
     * @param  int $id ??????id
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
        // ?????????????????????????????????:1.???????????????????????????;2.??????????????????86?????????????????????6
        if ((!empty($user['mobile']) && substr($user['mobile'], 0, 1) == 6 && $user['mobile_code'] == '86') || (isset($user['user_type']) && $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * ??????????????????
     * @param string $username ?????????
     * @return bool
     */
    public function freezeUserAccount($username) {
        return UserModel::instance()->freezeUserAccount($username);
    }

    /**
     * ????????????????????????
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
     * // TODO ???????????????????????????????????????
     * firstp2p ?????????
     * @param string @username
     * @param string @password
     * @return ????????????:
     *      {"code":alfjasdfjkaslf,"success":true}
     *  ????????????:
     *      {"code":0001,"reason":"????????????"}
     *      {"code":20010,"reason":"?????????????????????"}
     *      {"code":20001,"reason":"?????????????????????"}
     *      {"code":20002,"reason":"??????????????????"}
     *      {"code":20003,"reason":"??????????????????"}
     *      {"code":20004,"reason":"??????????????????"}
     *
     */
    public function apiNewLogin(
        $username,                                          // ??????
        $password,                                          // ??????
        $isPassport = false,                                // ?????????????????????
        $loginFrom = '',                                    // ????????????
        $country_code = "cn"                                // ?????????
    ) {
        if (empty($username)){
            return array('code' => 20001,'reason' => "?????????????????????");
        }

        if (empty($password)) {
            return array('code' => 20002, 'reason' => "??????????????????");
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

        // ?????????????????????????????????????????????
        if (!empty($result['code']) && $result['code'] == -20) {
            return array('code' => 10003, 'reason' => $result['msg']);
        }

        if (!empty($result['code']) && $result['code'] == -4) {
            return array('code' => 20007, 'reason' => $result['msg']);
        }

        if (!empty($result['code']) && $result['code'] == -1) {
            return array('code' => 20003, 'reason' => "???????????????????????????");
        }

        if (!empty($result['code']) && $result['code'] == -2) {
            return array('code' => 20004, 'reason' => "???????????????????????????");
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
            return array('code' => 20005, 'reason' => '???????????????????????????');
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
     * ?????????????????????
     */
    public function signupForJF($mobile)
    {
        $inviteCode = app_conf('AGENCY_COUPON_JF');
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['jifubao'];

        $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id, site_id');

        if (!empty($userInfo)) {
            //????????????
            if ($userInfo['site_id'] == $siteId) {
                return $userInfo['id'];
            } else {
                throw new \Exception('??????????????????', 1);
            }
        }

        $password = substr(md5($mobile.mt_rand(1000000, 9999999)), 0, 10);

        $userInfoExtra = array(
            'site_id' => $siteId,
            'group_id' => $GLOBALS['sys_config']['SITE_USER_GROUP']['jifubao'],
        );
        $result = $this->Newsignup('', $password, '', $mobile, '', $inviteCode, $userInfoExtra, false);

        if (!$result || $result['status'] != 0) {
            throw new \Exception('????????????:'.$result['reason']);
        }

        //???tag
        $tagService = new UserTagService();
        $tagService->addUserTagsByConstName($result['user_id'], array('JIFU_USER'));

        return $result['user_id'];
    }

    /**
     * ????????????????????????
     *
     * @param $username ?????????
     * @param $password ??????
     * @param $email ??????
     * @param $phone ?????????
     * @param $code ???????????????
     * @param $invite_code ?????????
     * @param $country_code ?????????
     * @return Null
     */
    public function signup($username, $password, $email, $phone, $code,$invite_code='') {

          return $this->Newsignup($username, $password, $email, $phone, $code,$invite_code);

    }
    /**
     * firstp2p ????????????????????????
     * @param $username ?????????
     * @param $password ??????
     * @param $email ??????
     * @param $phone ?????????
     * @param $code ???????????????
     * @return ????????????:
     *      {"createTime":"2013-07-11T13:30:51+08:00","deleted":1,"email":"zp.q@163.com","id":221,"idcard":11111,"loginTime":"2013-07-17T10:44:03+08:00","nickName":"qzp","pwd":"96E79218965EB72C92A549DD5A330112","sex":0,"state":0,"truename":111,"updateTime":"2013-07-17T10:44:03+08:00","username":"qzp","usertype":1??????passportid??????12 }
     *  ????????????passportid
     *  ????????????:
     *      {"code":500,"reason":"?????????????????????"}
     *      {"code":303,"reason":"??????????????????"}
     *      {"code":304,"reason":"?????????????????????"}
     *      {"code":319,"reason":"???????????????"}
     */
    public function Newsignup($username, $password, $email, $phone, $code,$invite_code='', $userInfoExtra = array(), $useMobileCode = true, $country_code="cn", $isPc = 0) {
        if ($useMobileCode) {
            $mobileCodeServiceObj = new MobileCodeService();
            $vcode = $mobileCodeServiceObj->getMobilePhoneTimeVcode($phone, 60, $isPc);
            if($vcode != $code) {
                Monitor::add('REGISTER_FAIL');
                return array('code' => 319, 'reason' => '???????????????');
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

        // ???????????????????????????
        // ??????????????????
        $ret = $webboObj->insertInfo($userInfo, false);
        if (!empty($ret) && $ret['status'] === -1 && !empty($ret['data']['username'])) {
            // ??????????????? ?????????????????????????????????????????????????????????
            return array('code' => 303,'reason' => $ret['data']['username']);
        }

        if (!empty($ret) && $ret['status'] === -1 && !empty($ret['data']['email'])) {
            return array('code' => 305,'reason' => '???????????????');
        }

        if (!empty($ret) && $ret['status'] === -1 && !empty($ret['data']['mobile'])) {
            return array('code' => 304,'reason' => '??????????????????????????????????????????????????????');
        }

        if (!empty($ret) && $ret['status'] === -33 && !empty($ret['data']['mobile'])) {
            return array('code' => 320,'reason' => '??????????????????????????????????????????????????????');
        }

        if (!empty($ret) && $ret['status'] === 0) {
            return $ret;
        }

        return false;
    }
    /**
     * ?????????????????????????????????
     *
     * @param $mobile ?????????
     * @return \system\libs\json
     */
    public function sendVerifyCode($mobile, $type = 1, $idno = null, $isEnterprise = false) {
        return $this->NewSendVerifyCode($mobile,$type, $idno, $isEnterprise);
    }

    /**
     * firstp2p ?????????????????????????????????
     * @param int $mobile
     * ????????????:
     *      {"result":true}   true : ??????????????? false ??? ????????????
     *  ????????????:
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
            return array('code' => -1, 'reason' => '??????????????????????????????');
        }
        $ret = json_decode($ret,true);
        if (!empty($ret) && $ret['code'] == 1){
            return array('result' => true);
        }elseif(!empty($ret)){

            return array('code' =>$ret['code'],'reason' => $ret['message'] );
        }

        return array('code' => -1, 'reason' => '??????????????????????????????');
    }
    /**
     * ????????????????????????????????????????????????
     *
     * @param $username ?????????
     * @param $email ??????
     * @param $phone ?????????
     * @return \system\libs\json
     */
    public function checkUserInfo($username, $email, $phone) {

          return $this->NewCheckUserInfo($username, $email, $phone);

    }

    /**
     * firstp2p ??????????????????????????????????????????????????????
     * @param $username ?????????
     * @param $email ??????
     * @param $phone ?????????
     * ????????????:
     *      {"result":true}
     *      true(?????????????????????)??????false????????????????????????
     *  ????????????:
     *      {"code":500,"reason":"?????????????????????"}
     *      {"code":20005,"reason":"??????????????????","option":"???????????????????????????"}
     *      {"code":303,"reason":"??????????????????","option":"???????????????????????????"}
     *      {"code":304,"reason":"?????????????????????","option":"???????????????????????????"}
     *      {"code":305,"reason":"???????????????","option":"???????????????????????????"}
     */
    public function NewCheckUserInfo($username, $email, $phone){

        $userModelObj = UserModel::instance();
        // ???????????????
        $usernameRet = $userModelObj->isUserExistsByUsername($username);
        if ($usernameRet === true){
            return array('code' => 303,'reason' => '??????????????????');
        }
        $emailRet = $userModelObj->isUserExistsByEmail($email);
        if ($emailRet === true){
            return array('code' => 305,'reason' => '???????????????');
        }
        $phoneRet = $userModelObj->isUserExistsByMobile($phone);
        if ($phoneRet === true){
            return array('code' => 304,'reason' => '?????????????????????');
        }

        return array('result' => true);
    }

    public function checkUserMobile($phone){

        $userModelObj = UserModel::instance();
        $phoneRet = $userModelObj->isUserExistsByMobile($phone);
        if ($phoneRet === true){
            //????????????????????????????????????
            $oUserBindService = new UserBindService();
            $bIs = $oUserBindService->isUserCanResetPwdByMobile($phone);
            if($bIs){
                return array('code' => 320,'reason' => '?????????????????????');
            }else{
                return array('code' => 304,'reason' => '?????????????????????');
            }
        }

        return array('result' => true);
    }


    /**
     * ??????code??????????????????
     * (Controller????????? getUserByToken ??????)
     */
    public function getUserByCode($code) {
        return (new UserTokenService())->getUserByToken($code);
    }

    /**
     * ???????????????????????????????????????????????????????????????
     * @param $user_id ??????id
     * @return array
     */
    public function getUserAgencyInfoNew($user_info){

        //????????????(HY)????????????
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
            //????????????????????????????????????
            $user_agency = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id']);
            if($user_agency){
                $agency_info = $user_agency;
                $agency_info['is_hy'] = 0;
            }
        }
        return array('agency_info' => $agency_info, 'is_agency' => empty($agency_info) ? 0 : 1);
    }

    /**
     * ??????????????????????????????????????????????????????????????????
     * @param $user_id ??????id
     * @return array
     */
    public function getUserAdvisoryInfo($user_info){

        $advisory_info = array();

            //????????????????????????????????????
        $user_advisory = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id'],2);
        if($user_advisory){
            $advisory_info = $user_advisory;
        }
        return array('advisory_info' => $advisory_info, 'is_advisory' => empty($advisory_info) ? 0 : 1);
    }

    /**
     * ??????????????????????????????????????????????????????
     * @param $user_id ??????id
     * @return array
     */
    public function getUserCanalInfo($user_info){

        $canal_info = array();

        //????????????????????????????????????
        $user_canal = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id'],10);
        if($user_canal){
            $canal_info = $user_canal;
        }
        return array('canal_info' => $canal_info, 'is_canal' => empty($canal_info) ? 0 : 1);
    }

    /**
     * ???????????????????????????????????????????????????????????????
     * @param $user_id ??????id
     * @return array
     */
    public function getUserEntrustInfo($user_info){

        $entrust_info = array();

        //????????????????????????????????????
        $user_entrust = AgencyUserModel::instance()->getAgencyInfoByUserId($user_info['id'],7);
        if($user_entrust){
            $entrust_info = $user_entrust;
        }
        return array('entrust_info' => $entrust_info, 'is_entrust' => empty($entrust_info) ? 0 : 1);
    }

    /**
     * ?????????????????????????????????????????????
     *
     * @param $user_id
     * @param int $offset
     * @param int $page_size
     * @return \libs\db\Model
     */
    public function getUserAvailableMoneyLog($user_id, $offset = 0, $size = 20, $log_info = '', $start = 0, $end = 0) {
        if ($log_info == '????????????') {
            $log_info = '??????';
        }
        $logRes = UserLogModel::instance()->getList($user_id, 'money_only', $log_info, $start, $end, array($offset, $size));
        if (!empty($logRes['list'])) {
            $list = $logRes['list'];
            foreach( $list as &$one ){
                if( $one['log_info'] == '??????'){
                    $one['log_info'] = '????????????';
                }
                if($one['log_info'] =='????????????' || $one['log_info'] =='????????????' ){
                    $one['note'] = UserLogService :: phone_format( $one['note'] );
                }
            }
            return $list;
        }
        return array();
    }

    /**
     * ??????????????????
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
        // ??????????????????????????????????????????????????????X??????
        if (isset($data['idno'])) {
            $data['idno'] = strtoupper(trim($data['idno']));
        }

        return $userDao->update($data);

    }

    /**
     * ??????????????????
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
     * ????????????????????????
     * @param $email
     * @return boolean
     */
    public function checkEmailExist($email){
        return UserModel::instance()->isUserExistsByEmail($email);
    }

    /**
     * ???????????????????????????
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
     * ??????????????????
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
     * ??????????????????
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
     * ??????????????????????????????????????????
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
            //??????????????????
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
                $user->id_type = 1; // ??????????????????

                // ??????????????????
                $birth = $id5->getBirthDay($idno);
                $user->byear = $birth['year'];
                $user->bmonth = $birth['month'];
                $user->bday = $birth['day'];

                $user->save();

                // ??????????????????
                $log = array (
                        'type' => 'idno',
                        'user_name' => $name,
                        'user_login_name' => $GLOBALS ['user_info'] ['user_login_name'],
                        'indo' => $idno,
                        'path' => __FILE__,
                        'function' => 'id5CheckUser',
                        'msg' => '?????????????????????.',
                        'time' => time ()
                );
                logger::wLog ( $log );

                return true;
            } else {
                // ??????????????????
                $log = array (
                        'type' => 'idno',
                        'user_name' => $name,
                        'user_login_name' => $GLOBALS ['user_info'] ['user_login_name'],
                        'indo' => $idno,
                        'path' => __FILE__,
                        'function' => 'id5CheckUser',
                        'msg' => '?????????????????????.',
                        'time' => time ()
                );
                logger::wLog ( $log );

                // ?????? ??????????????????????????????, ????????????????????????????????????, ????????????????????? ???????????????
                /* if ($reinfo == 2 || $reinfo == 3 || $reinfo == 4)
                    showErr ( $GLOBALS ['lang'] ['IDNO_ERROR'], 1 );
                else */
                return false;
            }
        }
    }
/**
 * ??????$uid???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
 * ???????????????????????????????????????????????????2016-04-26???
 * @param $name
 * @param $idno
 * @param $uid
 * @return boolean
 */
    public function psCheckUserNoid($name='', $idno='',$uid='')
    {
        // ??????????????????????????????????????????????????????X??????
        $idno = strtoupper(trim($idno));

//         \FP::import ( "libs.id5.SynPlat" );
//         $id5 = new \SynPlatAPI($GLOBALS['sys_config']['id5_url'], $GLOBALS['sys_config']['id5_user'], $GLOBALS['sys_config']['id5_passwd'], $GLOBALS['sys_config']['id5_key'], $GLOBALS['sys_config']['id5_iv']);
        if (empty($uid)) {
            $idnoObj = new \libs\idno\CommonIdnoVerify();
            return ($idnoObj->checkIdno($name, $idno));
        } else {
            $id5 = new \libs\idno\CommonIdnoVerify();
            $userinfo = UserModel::instance()->find($uid);
            // ??????????????????
            $birth = $id5->getBirthDay($idno);
            $userinfo->byear = $birth['year'];
            $userinfo->bmonth = $birth['month'];
            $userinfo->bday = $birth['day'];
            $userinfo->real_name = $name;
            $userinfo->idno = $idno;
            $userinfo->idcardpassed = 1;
            $userinfo->idcardpassed_time = time();
            $userinfo->sex = $id5->getSex($idno);
            $userinfo->id_type = 1; // ??????????????????
            $userinfo->save();//??????
            return true;
        }
    }

    /**
     * ??????????????????????????????????????????
     * @param type $idon
     * @param type $userid ??????????????????id
     * @return type
     */
    public function getUserByIdno($idno, $userid = '') {
        $user_dao = new UserModel();
        return $user_dao->getUserByIdno($idno, $userid);
    }

    /**
     * ????????????????????????????????????????????????
     */
    public function getAllUserByIdno($idno) {
        $userDao = new UserModel();
        return $userDao->getAllUserByIdno($idno);
    }

    /**
     * checkIsJrgcUser??????????????????????????????????????????
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
     * ????????????????????????????????????
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
     * @?????????????????????????????????????????????????????????
     * @param string $credentials_no
     * @param string $company_name
     */
    public function getEnterpriseByCondition($credentials_no, $company_name)
    {
        return EnterpriseModel::instance()->db->get_slave()->getAll("SELECT user_id AS id ,company_purpose ,identifier FROM firstp2p_enterprise WHERE credentials_no= '{$credentials_no}' AND company_name='{$company_name}'");
    }
    /**
     * @??????????????????????????????????????????????????????????????????????????????
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
     * ????????????????????????????????????????????????????????????????????????
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
        // ??????????????????
        $log = array(
            'type' => 'UserService',
            'uid' => $uid,
            'path' =>  __FILE__,
            'function' => 'queryPayPwd',
            'msg' => '???????????????????????????????????????????????????',
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
            // ????????????
            return $datas;
        } else {
            return false;
        }
    }

    /**
     * ?????????????????????
     * @param type $idon
     * @param type $userid ??????????????????id
     * @return type
     */
    public function getRegisterUserCounter()
    {
        $user_dao = new UserModel();
        return $user_dao->count(" 1=1 ");
    }

    /**
     * ????????????????????????
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
     * ??????????????????????????????
     * @param $moblie
     * @return int
     */
    public function isExistsMobile($moblie){
        $user_dao = new UserModel();
        return $user_dao->isUserExistsByMobile($moblie);
    }

   /**
    * ??????????????????
    * @return array
    */
    public function getCount() {
        $user_dao = new UserModel();
        $condition = " is_delete = 0";
        return $user_dao->getCount($condition);
    }

    /**
     * ???????????????????????????
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
     * ????????????ID ??????????????????
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
     * ??????????????????
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
     * ??????????????????????????????70????????????????????????
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
     * ??????????????????
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
     * ????????????????????????
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
     * ?????????????????????????????????????????????
     * @param $username
     */
    public function getUserinfoByUsername($username){
        $userModel = new UserModel();
        return $userModel->getUserinfoByUsername($username);
    }

    /**
     * ?????????????????????
     * @param type $passport   cardID
     * @return boolean
     */
    public function isIdCardExist($idno) {
        // ??????????????????????????????????????????????????????X??????
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
     * ??????????????????????????????tag
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
            // ?????????????????????????????????????????????
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
                        throw new \Exception("??????[{$user_id}]??????BID_MORE????????????");
                    }
                } else {
                    $action = CouponGroupEnum::TRIGGER_FIRST_DOBID;
                    $res = $tag_service->addUserTagsByConstName($user_id, 'BID_ONE');
                    if ($res === false) {
                        throw new \Exception("??????[{$user_id}]??????BID_ONE????????????");
                    }
                    // ??????TAG
                    try {
                        $res =  $remoteTagService->addUserTag($user_id, 'FirstBidAmount', $money);
                    } catch (\Exception $e) {
                    }
                }
            }

            // ???????????????????????????
            if ($is_bid_compound == false) {
                $annualizedAmount = \core\service\oto\O2OUtils::getAnnualizedAmountByDealLoadId($deal_load_id, false);
            } else {
                $annualizedAmount = false;
            }

            // ??????????????????
            $dealLoadInfo = DealLoadModel::instance()->find($deal_load_id);
            // ?????????????????????
            if (!$isRedeem && $dealLoadInfo && $dealLoadInfo['source_type'] != DealLoadModel::$SOURCE_TYPE['dtb']) {
                // ????????????tag
                $tags = array();
                $dealTags = DealTagModel::instance()->getTagByDealId($dealLoadInfo['deal_id']);
                if ($dealTags) {
                    foreach ($dealTags as $key => $tag) {
                        $tags[] = $tag['tag_name'];
                    }
                }

                // ?????????????????????????????????
                $columns = 'advisory_id, project_id, type_id, loantype, deal_type, deal_crowd, deal_tag_name, repay_time';
                $dealInfo = DealModel::instance()->find($dealLoadInfo['deal_id'], $columns);
                $dealBidDays = intval($dealInfo['repay_time']);
                if ($dealInfo['loantype'] != O2OService::LOAN_TYPE_5) {
                    $dealBidDays = $dealBidDays * 30;
                }

                // ???????????????
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
            // ???????????????????????????????????????;??????????????????????????????
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
     * ??????????????????
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
            $ret['msg'] = '?????????????????????????????????????????????????????????';
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

    // ???????????????O2O??????
    public function isOtoUser($uid, &$userInfo) {

        $userInfo['showO2O'] = app_conf('O2O_SHOW_APP_ENTRANCE');
        $userInfo['isO2oUser'] = 0;
        if (O2OService::getSiteO2OStatus()) {
            $userInfo['isO2oUser'] = 1;
        }

        return true;
    }

    // ?????????????????????
    public function isSeller($uid, &$userInfo) {
        $userInfo['isSeller'] = 0;
        $userInfo['couponUrl'] = '';
        $tagService = new \core\service\UserTagService;
        if ($tagService->getTagByConstNameUserId('O2O_SELLER', $uid)){
            $userInfo['isSeller'] = 1;
            $title = urlencode('????????????');
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
        //???????????????20150608 ??????????????????
        if($newGroupId == 205){
            $userInfo['coupon_level_id'] = 608;
        }
        // ?????????????????????????????????
        if (!empty($coupon_level_id)) {
            $userInfo['coupon_level_id'] = $coupon_level_id;
        }


        $GLOBALS['db']->autoExecute('firstp2p_user', $userInfo, 'UPDATE', " id = '{$userId}'");
        $affected = $GLOBALS['db']->affected_rows();
        if ($affected >= 1) {
            \libs\utils\PaymentApi::log('????????????????????????');
            return true;
        }
        \libs\utils\PaymentApi::log('????????????????????????');
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
     * ??????????????????????????????
     * @param user ??????????????????????????????
        $user = array(
            'id', 'refer_user_id', 'invite_code',
        )

     * @param $data ?????????????????????
        $data = array(
            'idno', 'group_id', 'level_id', 'tags', 'invite_code'
        )
     */
    public function fixUserRegister($userId, $data = array()) {
        $GLOBALS['db']->startTrans();
        try {
            $toUpdate = array();
            // ????????? ??????
            if (!empty($data['invite_code'])) {
                $couponService = new \core\service\CouponService();
                // ??????????????????????????????
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
                // ??????coupon log???
                $couponLogService = new \core\service\CouponLogService();
                $couponLogService->changeRegShortAlias($userId, $data['invite_code']);
            }

            // ????????????????????????ID
            if (!empty($data['group_id']) && !empty($data['level_id'])) {
                $toUpdate['group_id'] = $data['group_id'];
                $toUpdate['coupon_level_id'] = $data['level_id'];
            }
            if (empty($toUpdate)) {
                throw new \Exception(__FUNCTION__.':empty user transfer data');
            }
            $GLOBALS['db']->autoExecute('firstp2p_user', $toUpdate, 'UPDATE', " id = '{$userId}' ");
            // ??????tags??????
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
     * ????????????????????????????????????
     * @param $userId
     * @return bool
     */
    public function updateUserToJXSD($userId) {
        $userId = intval($userId);
        $user_model = new UserModel();
        $user = $user_model->find($userId, 'id,is_dflh', true);
        //???????????????
        if (empty($user)) {
            return false;
        }
        //?????????????????????????????????
        if ($user['is_dflh'] != 1) {
            return true;
        }
        $updateUserData = array(
            'id' => $userId,
            'is_dflh' => 0,
        );
        $ret =  $user_model->updateInfo($updateUserData, 'update');
        if($ret) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"????????????????????????userId:{$userId}")));
            return true;
        } else {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"????????????????????????userId:{$userId}")));
            return false;
        }
    }

    /**
     * ?????????????????????????????????
     */
    public function addUserRegisterInfo($data) {
        $toUpdate = array();
        $toUpdate['partner'] = $data['partner'];
        // ??????????????????????????????????????????????????????X??????
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
        // ??????????????????????????????????????????????????????X??????
        $idno = strtoupper(addslashes($idno));
        // ????????????????????????????????????????????????
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
     * ?????????????????????????????????????????????????????????
     */
    public function isBindBankCard($opt = [])
    {
        // ??????????????????
        if (empty($opt))
        {
            $opt = ['check_validate' => true];
        }

        if (isset($this->_userObject['id']) && $this->_userObject['id'] > 0)
        {
            // ?????????????????????????????????
            if (isset($this->_userObject['mobilepassed']) && intval($this->_userObject['mobilepassed']) <= 0)
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_MOBILE, 'respMsg'=>'????????????????????????');
            }
            // ??????????????????????????????
            if (isset($this->_userObject['idcardpassed']) && intval($this->_userObject['idcardpassed']) <= 0)
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_IDCARD, 'respMsg'=>'????????????????????????');
            }
            // ?????????????????????
            $userBankcardService = new UserBankcardService();
            $bankcardInfo = $userBankcardService->getBankcard($this->_userObject['id']);
            if (empty($bankcardInfo) || (isset($bankcardInfo['status']) && $bankcardInfo['status'] != 1))
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNBIND, 'respMsg'=>'?????????????????????');
            }
            else
            {
                if (isset($opt['check_validate']) && $opt['check_validate'] === true)
                {
                    if (isset($bankcardInfo['verify_status']) && $bankcardInfo['verify_status'] != 1)
                    {
                        return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNVALID, 'respMsg'=>'?????????????????????');
                    }
                }
            }
            // ?????????????????????????????????
            if (isset($this->_userObject['payment_user_id']) && intval($this->_userObject['payment_user_id']) <= 0)
            {
                return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_PAYMENTUSERID, 'respMsg'=>'????????????????????????');
            }
            return array('ret'=>true, 'respCode'=>'00', 'respMsg'=>'????????????');
        }
        return array('ret'=>false, 'respCode'=>self::STATUS_BINDCARD_UNLOGIN, 'respMsg'=>'???????????????');
    }

    /** ????????????????????????**/
    /**
     * ???????????????????????????????????????
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
     * ???????????????????????????(user_type=1??????mobile?????????6????????????????????????)
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
     * ????????????/??????????????????
     * @return boolean
     */
    function getEnterpriseOrCompanyUser()
    {
        $isEnterprise = $this->isEnterpriseUser();
        if ($isEnterprise) {
            if ($this->_userObject['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                // ????????????
                $data = $this->getEnterpriseInfo();
                return array('userBizType'=>UserModel::USER_TYPE_ENTERPRISE, 'data'=>$data, 'companyName'=>$data['company_name']);
            }else if (!empty($this->_userObject['mobile']) && substr($this->_userObject['mobile'], 0, 1) == '6'
              && (empty($this->_userObject['mobile_code']) || $this->_userObject['mobile_code'] == '86')) {
                  // ??????????????????????????????
                  return array('userBizType'=>UserModel::USER_TYPE_ENTERPRISE, 'data'=>$this->_userObject, 'companyName'=>$this->_userObject['real_name']);
            }
        }else{
            return array('userBizType'=>UserModel::USER_TYPE_NORMAL, 'data'=>$this->_userObject, 'realName'=>$this->_userObject['real_name']);
        }
    }

    /**
     * ????????????????????????
     * @param boolean $withContractInfo ?????????????????????????????????
     *
     * @return array ????????????????????????
     */
    public function getEnterpriseInfo($withcontactInfo = false)
    {
        if (!$this->isEnterprise())
        {
            return array();
        }
        $enterprise = array();
        // ??????????????????
        $enterpriseModel = $this->_getEnterpriseInfo();
        $enterprise = array_merge($enterprise, ($enterpriseModel ? $enterpriseModel->getRow() : array()));
        if ($withcontactInfo && !empty($enterprise))
        {
            $contactInfo = $this->_getEnterpriseContactInfo(true);
            $enterprise['contact'] = $contactInfo;
        }
        // ????????????????????????
        $credentials_types = !empty($GLOBALS['dict']['CREDENTIALS_TYPE']) ? $GLOBALS['dict']['CREDENTIALS_TYPE'] : array(
            //'0' => '??????',
            '1' => '????????????',
            //'2' => '?????????????????????',
            '3' => '????????????????????????'
        );
        $enterprise['credentials_type_cn'] = $credentials_types[$enterprise['credentials_type']];
        // ????????????????????????
        $enterprise['credentials_no_mask'] = substr($enterprise['credentials_no'], 0, 2).'*****'.substr($enterprise['credentials_no'], strlen($enterprise['credentials_no']) - 2 , 2);

        return $enterprise;
    }

    /**
     * ????????????????????????????????????????????????????????????86 ??????????????????????????????
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
     * ???????????????????????????
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
        // receive_msg_mobile ????????????
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
        // ??????????????????
        $legalbodyInfo = array();
        if (!empty($enterpriseInfo['legalbody_name']))
        {
            $legalbodyInfo['name'] = $enterpriseInfo['legalbody_name'];
            $legalbodyInfo['code'] = $enterpriseInfo['legalbody_mobile_code'];
            $legalbodyInfo['mobile'] = $enterpriseInfo['legalbody_mobile'];
            $mobileInfo[] = $legalbodyInfo;
        }
        // ???????????????????????????
        $majorInfo = array();
        if (!empty($enterpriseInfo['contact']['major_name']))
        {
            $majorInfo['name'] = $enterpriseInfo['contact']['major_name'];
            $majorInfo['code'] = $enterpriseInfo['contact']['major_mobile_code'];
            $majorInfo['mobile'] = $enterpriseInfo['contact']['major_mobile'];
            $mobileInfo[] = $majorInfo;
        }
        // ???????????????2??????
        $contactInfo = array();
        if (!empty($enterpriseInfo['contact']['contact_name']))
        {
            $contactInfo['name'] = $enterpriseInfo['contact']['contact_name'];
            $contactInfo['code'] = $enterpriseInfo['contact']['contact_mobile_code'];
            $contactInfo['mobile'] = $enterpriseInfo['contact']['contact_mobile'];
            $mobileInfo[] = $contactInfo;
        }
        // ???????????????
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
     * ???????????????????????????
     *
     * @return array
     */
    public function getAllEnterpriseInfo()
    {
        $allEnterpriseInfo = EnterpriseModel::instance()->db->get_slave()->getAll("SELECT id,real_name,user_name FROM firstp2p_user WHERE user_type = '".UserModel::USER_TYPE_ENTERPRISE."' AND is_effect = 1");
        return $allEnterpriseInfo;
    }

    /**
     * ??????????????????????????????
     * @param boolean $retArray ?????????????????????, false ??????model??????
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
     * ???????????????????????????????????????
     * @param boolean $retArray ???????????????????????? false ??????model??????
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
     * [???????????????id????????????????????????????????????????????????]
     * @author <fanjingwen@ucfgroup.com>
     * @param array[seq => userID] $userIDArr [??????id]
     * @param bool $needUserTypeName ????????????userTypeName
     * @return array[userID => [userInfo]] [???????????????user_type???????????????????????????]
     */
    public function getUserInfoListByID($userIDArr, $needUserTypeName = false) {
        $listOfUser = array();
        if (!is_array($userIDArr) || empty($userIDArr)) {
            return $listOfUser;
        }

        // ????????????????????????
        $userInfoArr = UserModel::instance()->getUserInfoByIDs($userIDArr);
        foreach ($userInfoArr as $userInfo) {
            $userID = $userInfo['id'];
            $listOfUser[$userID] = $userInfo;
            // ??????????????????
            if (UserModel::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userInfo['id']);
                $listOfUser[$userID]['company_name'] = $enterpriseInfo['company_name'];
                $listOfUser[$userID]['mobile'] = '-';
            }

            // ??????????????????userTypeName
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

    //????????????????????????
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
     * ?????????????????????????????????
     * @param string $userId ??????id
     * @param string $bankcardNo ????????????
     * @return boolean
     */
    public function unbindCard($userId, $bankcardNo) {
        PaymentApi::log('User Unbindcard Request, userId:'.$userId);
        $db = \libs\db\Db::getInstance('firstp2p');
        try {
            $db->startTrans();
            // ???????????????????????????
            $unbindBankcard = UserBankcardModel::instance()->unbindCard($userId);
            if (!$unbindBankcard) {
                throw new \Exception('?????????????????????');
            }
            // ??????????????????????????????
            $clearBankcardAudit = UserBankcardAuditModel::instance()->clearBankcardAudit($userId, $bankcardNo);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
        return true;
    }

    /*
     * ????????????-????????????
     * @param int $userId ??????ID
     * @return array
     */
    public function wxMemberCancel($userId) {
        try{
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();
            // user_bankcard??????????????????????????????
            UserBankcardModel::instance()->unbindCard($userId);

            // user???????????????????????????
            $updateRet = UserModel::instance()->setUserCancel($userId);
            if ($updateRet <= 0) {
                throw new \Exception('Update UserInvalid Failed');
            }
            $db->commit();
            return true;
        } catch(\Exception $e) {
            $db->rollback();
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, '????????????????????????,' . $e->getMessage())));
            return false;
        }
    }

    //????????????id?????????????????????????????????
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
     * ??????????????????????????????????????????
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
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,"??????????????????userId:{$userId}")));
            return false;
        }
    }
    /*
     * ??????????????????????????????
     * @param $userPurpose ?????????????????????
     * @return array
     */
    public function getUserPurposeInfo($userPurpose)
    {
        $purposeList = !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE']) ? $GLOBALS['dict']['ENTERPRISE_PURPOSE'] : [];
        return !empty($purposeList[(int)$userPurpose]) ? $purposeList[(int)$userPurpose] : [];
    }

    //????????????IDs???????????????????????????,??????????????????
    public function getUserInfoByIds($idArr, $columns = 'id, mobile, real_name') {
        $user_model = new UserModel();
        $userInfoArr = $user_model->getUserInfoByIDs($idArr, $columns);
        if (empty($userInfoArr)) {
            return array();
        }
        //????????????ID??????????????????????????????????????????
        foreach ($userInfoArr as $userInfo) {
            $userID = $userInfo['id'];
            $listOfUser[$userID] = $userInfo;
       }
        return $listOfUser;
    }

    /**
     * ???????????? id ???????????????????????????
     * @param int $userId
     * @return array
     */
    public function getUserRoleListByUserId($userId)
    {
        $userService = new UserService();

        //???????????????????????????
        $userAgencyInfo = $userService->getUserAgencyInfoNew(array('id'=>$userId));
        $isAgency = intval($userAgencyInfo['is_agency']);

        //?????????????????????????????????????????????????????????????????????
        $userAdvisoryInfo = $userService->getUserAdvisoryInfo(array('id'=>$userId));
        $isAdvisory = intval($userAdvisoryInfo['is_advisory']);

        //?????????????????????????????????????????????????????????????????????
        $userEntrustInfo = $userService->getUserEntrustInfo(array('id'=>$userId));
        $isEntrust = intval($userEntrustInfo['is_entrust']);

        //????????????????????????????????????????????????????????????
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
     * ??????????????????????????????
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
            // ?????????????????????????????????
            $birth = $id5Obj->getBirthDay(trim($params['cardNo']));
        } else {
            // ????????????????????????
            $passportInfo = $GLOBALS['db']->getRow("SELECT * FROM firstp2p_user_passport WHERE uid = '{$userId}'");
            if (!empty($passportInfo)) {
                $datas['sex'] = $passportInfo['sex'];
                $birth = array_combine(['year', 'month', 'day'], explode('-', $passportInfo['birthday']));
            }
        }
        // ??????????????????
        if (!empty($birth)) {
            $datas['byear'] = $birth['year'];
            $datas['bmonth'] = $birth['month'];
            $datas['bday'] = $birth['day'];
        }
        // ??????????????????
        return $GLOBALS['db']->autoExecute('firstp2p_user', $datas, 'UPDATE', " id = '{$userId}' ");
    }

    /**
     * toggle????????????
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
        $setState = $userCurrentState == 0 ? 1 : 0; //??????????????????
        try {
            if ($setState == 0) {
                // ??????tag ??????
                $tagName = 'SAFE_CUSTOMER_17711';
                $tagService = new UserTagService();
                if ($tagService->getTagByConstNameUserId($tagName, $userId)) {
                    // ???????????????????????????????????????
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
     * ????????????????????????
     * @param array $params
     *  user_id ??????id
     *  real_name ??????
     *  id_type ?????????
     *  idno ??????
     */
    public function updateUserIdentityInfo($params) {
        if (empty($params)) {
            return false;
        }

        $db = \libs\db\Db::getInstance('firstp2p');
        $db->startTrans();
        try {
            // ??????????????????
            $res = UserModel::instance()->updateUserIdentity(
                $params['user_id'],
                $params['real_name'],
                $params['id_type'],
                $params['idno']
            );

            if (!$res) {
                throw new \Exception('??????????????????????????????');
            }

            // ??????????????????????????????
            $userBankcardService = new UserBankcardService();
            $res = $userBankcardService->wxUpdateUserBankCardName($params['user_id'], $params['real_name']);
            if (!$res) {
                throw new \Exception('????????????????????????????????????');
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
     * ????????????????????????
     * @param array $params
     *  user_id ??????id
     *  order_id ????????????id
     *  status ????????????
     *  fail_reason ????????????
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
            //????????????
            $res = $modifyLog->updateLog($params);
            if (!$res) {
                throw new \Exception('????????????????????????????????????');
            }

            //???????????????????????????
            if ($params['status'] == UserIdentityModifyLogModel::STATUS_SUCCESS) {
                $updateParams = [
                    'user_id' => $modifyLog['user_id'],
                    'real_name' => $modifyLog['real_name'],
                    'id_type' => $modifyLog['id_type'],
                    'idno' => $modifyLog['idno'],
                ];
                $res = $this->updateUserIdentityInfo($updateParams);
                if (!$res) {
                    throw new \Exception('??????????????????????????????????????????');
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
     * ????????????????????????
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
     * ????????????????????????
     * @return array
     */
    public function isIdCardPassed() {
        if (isset($this->_userObject['idcardpassed']) && intval($this->_userObject['idcardpassed']) <= 0) {
            return false;
        }
        return true;
    }

    /**
     * ???????????????????????????
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
     * ??????????????????
     * @return ?????????($verify_status=1) true ????????? false
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
     * ?????????????????????????????????
     * return array ????????????????????????
     */
    public function getEnterpriseUserInfo($userId) {
        return EnterpriseModel::instance()->getEnterpriseInfoByUserID($userId);
    }

    /**
     * ???????????????????????????
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
     * ??????????????????????????????
     * ??????searchbankcard??????
     * @param type $userId
     */
    public function paymentVerifyTypeApi($userId) {
        // ???????????????????????????????????????-???????????????
        $obj = new UserBankcardService();
        $bankInfo = $obj->queryBankCardsList($userId, true);
        if (is_array($bankInfo) && !empty($bankInfo['list'])) {
            return $bankInfo['list'];
        }
        return $bankInfo;
    }

    /**
     * ???????????????????????????????????????
     * ????????????????????????????????????????????????????????? firstp2p_user_bankcard ??? cert_status ??????
     * ????????????????????????????????????
     */
    public function updateUserBankcardCertStatus($userId, $cardNo, $certStatus) {
        $userBankcardInfoObj = UserBankcardModel::instance()->getByUserId($userId);
        $userBankcardInfo = $userBankcardInfoObj->getRow();
        $current_cert_status = $userBankcardInfo['cert_status'];
        $cert_status_map = UserBankcardModel::$cert_status_map;
        if ($current_cert_status == $cert_status_map[$certStatus]) {
            //?????????????????????????????????????????????????????????????????????
            return true;
        }
        return UserBankcardModel::instance()->updateCertStatusByUserIdAndCardNo($userId, $cardNo, $certStatus);
    }

    /**
     * ???????????????????????????
     */
    public function updateUserBankcardVerifyStatus($userId, $cardNo, $verifyStatus = 1) {
        $userBankcardInfoObj = UserBankcardModel::instance()->getByUserId($userId);
        $userBankcardInfo = $userBankcardInfoObj->getRow();
        $current_verify_status = $userBankcardInfo['verify_status'];
        if ($current_verify_status == $verifyStatus) {
            //??????????????????????????????????????????????????????????????????????????????????????????
            return true;
        }
        return UserBankcardModel::instance()->updateVerifyStatusByUserIdAndCardNo($userId, $cardNo, $verifyStatus);
    }

    /**
     * ???????????????
     * @return string
     */
    public function getUserName() {
        return $this->_userObject['user_name'];
    }

    /**
     * ????????????????????????????????????????????????
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
     * ????????????????????????????????????
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
     * ?????????????????????????????????????????????
     * @param int $userId
     * @return ??????????????????false ??????????????????????????????
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
     * ??????????????????????????????
     */
    public function allowAccountLoan($userPurpose){
        //???????????????,?????????????????????
        return in_array(intval($userPurpose), array(EnterpriseModel::COMPANY_PURPOSE_INVESTMENT, EnterpriseModel::COMPANY_PURPOSE_MIX));
    }

    /**
      *???????????????????????????
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


    //???????????????
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
                    throw new \Exception(sprintf('??????:%s???????????????%s???????????????????????????', $correct_key, $correct_row['user_name']));
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
                        throw new \Exception(sprintf('??????:%s???????????????%s???????????????', $correct_key, $correct_row['user_name']));
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
     * ???????????????????????????
     * @param $userId
     * @return bool
     *
     */
    public function hasLoan($userId) {
        // ????????????
        $userFirstDeal = DealLoadModel::instance()->getFirstDealByUser($userId);
        if (!empty($userFirstDeal)) {
            return true;
        }

        //??????????????????
        $ncfPhFirstDeal = \core\service\ncfph\DealLoadService::getFirstDealByUser($userId);
        if (!empty($ncfPhFirstDeal)) {
            return true;
        }

        // ?????????
        if ((new DtInvestNumService())->getInvestNum($userId)) {
            return true;
        }

        return false;
    }

    /**
     * ????????????????????????
     * @param $userId ??????ID
     * @param $email ??????
     * @return array
     */
    public function updateUserEmail($userId, $email) {
        $result = ['code'=>0, 'msg'=>''];
        $userId = intval($userId);
        if (empty($userId) || empty($email)) {
            $result['code'] = -1;
            $result['msg'] = '????????????????????????';
            return $result;
        }

        $user_model = new UserModel();
        $user = $user_model->find($userId, 'id,email', true);
        // ???????????????
        if (empty($user)) {
            $result['code'] = -2;
            $result['msg'] = '???????????????';
            return $result;
        }
        // ???????????????????????????
        $is_exist = $this->checkEmailExist($email);
        if ($is_exist) {
            $result['code'] = -3;
            $result['msg'] = '?????????????????????';
            return $result;
        }

        $updateUserData = array(
            'id' => $userId,
            'email' => $email,
        );
        $ret = $this->updateInfo($updateUserData);
        if($ret) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "????????????????????????, userId:{$userId}")));
            return $result;
        } else {
            $result['code'] = -4;
            $result['msg'] = '??????????????????';
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, "????????????????????????, userId:{$userId}")));
            return $result;
        }
    }

    //??????????????????????????????
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
     * ????????????????????????
     */
    private static $loginUserInfo = array();

    /**
     * ????????????????????????
     */
    public static function setLoginUser($userInfo)
    {
        self::$loginUserInfo = $userInfo;
    }

    /**
     * ????????????????????????
     */
    public static function getLoginUser()
    {
        return self::$loginUserInfo;
    }

    /**
     * ????????????????????????????????????
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
