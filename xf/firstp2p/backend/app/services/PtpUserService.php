<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoToken;
use NCFGroup\Protos\Ptp\ProtoAccessToken;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\ResponseLoginNew;
use NCFGroup\Protos\Ptp\ResponseGetLoginUser;
use NCFGroup\Protos\Ptp\RequestUserLogin;
use NCFGroup\Protos\Ptp\RequestGetUserFinancialRecord;
use NCFGroup\Protos\Ptp\ResponseGetUserFinancialRecord;
use NCFGroup\Protos\Ptp\ResponseCreditRegCount;
use NCFGroup\Protos\Ptp\ResponseCreditRegLog;
use NCFGroup\Protos\Ptp\RequestOauth;
use NCFGroup\Protos\Ptp\RequestUserMobile;
use NCFGroup\Protos\Ptp\RequestUserMoneyLog;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\ResponseUserMoneyLog;
use NCFGroup\Protos\Ptp\RequestCheckInviteCode;
use NCFGroup\Protos\Ptp\ResponseCheckInviteCode;
use NCFGroup\Protos\Ptp\RequestUserTags;
use NCFGroup\Protos\Ptp\ResponseUserTags;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use core\service\UserService;
use core\service\UserLogService;
use core\service\UserBankcardService;
use core\service\BankService;
use core\service\AttachmentService;
use core\service\user\WebBO;
use core\service\user\BOFactory;
use core\service\AccountService;
use core\service\BonusService;
use core\service\CouponService;
use core\dao\UserModel;
use core\service\UserGroupService;
use core\service\UserTagService;
use NCFGroup\Ptp\daos\UserDAO;
use NCFGroup\Protos\Ptp\Enum\UserEnum;
use NCFGroup\Protos\Ptp\RequestUserMoney;
use NCFGroup\Protos\Ptp\ResponseUserMoney;
use NCFGroup\Protos\Ptp\RequestUserList;
use NCFGroup\Protos\Ptp\ResponseUserList;
use libs\utils\Block;
use core\service\user\BOBase;
use NCFGroup\Protos\Ptp\RequestUser;
use NCFGroup\Protos\Ptp\RequestUserUpdate;
use NCFGroup\Protos\Ptp\RequestUserListInfo;
use NCFGroup\Protos\Ptp\ResponseUserListInfo;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Protos\Ptp\RequestResetPassword;
use NCFGroup\Protos\Ptp\RequestUidByToken;
use core\dao\DealLoadModel;
use core\service\CouponLevelService;
use core\service\PaymentService;
use libs\utils\Logger;
use NCFGroup\Ptp\daos\AdunionDealDAO;
use core\dao\DealModel;
use core\service\BonusBindService;
use libs\sms\SmsServer;
use NCFGroup\Protos\Ptp\RequestSendSms;
use NCFGroup\Protos\Ptp\ResponseSendSms;
use core\service\MobileCodeService;
use core\service\UserTokenService;

require_once APP_ROOT_PATH . "/libs/vendors/oauth2/Server.php";

/**
 * UserService
 * ????????????service
 * @uses ServiceBase
 * @package default
 */
class PtpUserService extends ServiceBase {

     private $_minAge = 18;
     private $_maxAge = 70;
     private $appName = APP_NAME;
    /**
     * ????????????
     * @param \NCFGroup\Protos\Ptp\RequestUserLogin $request
     * @return type
     */
    public function login(RequestUserLogin $request) {
        $userName = $request->getAccount();
        $password = $request->getPassword();
        $webBo = BOFactory::instance('openapi');
        $result = $webBo->authenticate($userName, $password);
        $response = new ProtoUser();
        if (!empty($result['code']) && $result['code'] == -4)
        {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 20007;
            $response->errorMsg = '??????????????????????????????????????????????????????PC?????????????????????';
        }
        if (!empty($result['code']) && $result['code'] == -1) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 20003;
            $response->errorMsg = "????????????????????????";
        }
        if (!empty($result['code']) && $result['code'] == -2) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 20004;
            $response->errorMsg = "????????????????????????";
        }
        if ($result['code'] == 0) {
            $userModel = UserModel::instance();
            $userInfo = $userModel->doLogin($userName, $password, 1);
            if (isset($userInfo['user']) && ($userInfo['user']['is_effect'] == 0 || $userInfo['user']['is_delete'] == 1)) {
                $response->resCode = RPCErrorCode::FAILD;
                $response->errorCode = 20005;
                $response->errorMsg = "???????????????????????????";
            } else {
                $response->resCode = RPCErrorCode::SUCCESS;
                $response->userId = intval($result['user_id']);
            }
        }
        return $response;
    }

    /**
     * ????????????????????????????????????token???????????????
     */
    public function loginNew(RequestUserLogin $request) {
        $r = $this->login($request);

        $response = new ResponseLoginNew();
        if ($r->userId) {
            $GLOBALS['user_info']['user_num'] = numTo32($GLOBALS['user_info']['id']);
            $response->resCode = $r->resCode;
            $response->userId = $r->userId;
            $response->userInfo = $GLOBALS['user_info'];
            $response->token = (new UserService())->genAppToken($GLOBALS['user_info']['id']);
        } else {
            $response->resCode = $r->resCode;
            $response->errorCode = $r->errorCode;
            $response->errorMsg = $r->errorMsg;
        }
        return $response;
    }

    /**
     * ?????????????????????????????????????????????????????????
     */
    public function sendSms(RequestSendSms $request) {
        $mobile = $request->getMobile();
        $countryCode = $request->getCountryCode();
        $invite = $request->getInvite();

        //??????????????????????????????(winse) ?????????????????????,?????????????????????,?????????????????????
        $source = 'hk_wx';
        $this->appName = $source;

        $response = new ResponseSendSms();
        if (!$GLOBALS['dict']['MOBILE_CODE'][$countryCode]) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = 1;
            $response->errorMsg = "???????????????";
            return $response;
        }

        $regexp = "/{$GLOBALS['dict']['MOBILE_CODE'][$countryCode]['regex']}/";
	if (!preg_match($regexp, $mobile)) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = -1;
            $response->errorMsg = "?????????????????????";
            return $response;
        }

        $ip = get_client_ip();
        if (Block::check('SEND_SMS_IP_MINUTE', $ip, false) === false) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = -13;
            $response->errorMsg = "????????????????????????????????????????????????";
            return $response;
        }
        if (Block::check('SEND_SMS_IP_TODAY', $ip, false) === false) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = -13;
            $response->errorMsg = "????????????????????????????????????????????????";
            return $response;
        }
        if (Block::check('SEND_SMS_PHONE_HOUR', $mobile, false) === false) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = -13;
            $response->errorMsg = "??????????????????????????????????????????????????????";
            return $response;
        }

        if ($invite && !(new CouponService())->checkCoupon($invite)) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = -14;
            $response->errorMsg = "??????????????????????????????";
            return $response;
        }

        $mcService = new MobileCodeService();        
        $errno = $mcService->isSend($mobile, 1, 0);
        if ($errno != 1) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $errno;
            $response->errorMsg = "???????????????????????????";
            return $response;
        }

        $result = $mcService->sendVerifyCode($mobile, 0, false, 200, $countryCode);
        $arr = json_decode($result, true);
        $response->resCode = $arr['code'] == 1 ? RPCErrorCode::SUCCESS : RPCErrorCode::FAILD;
        $response->errorCode = $arr['code'] ==1 ? 0:$arr['errno'];
        $response->errorMsg = json_encode($arr);
        return $response;
    }

    public function getLoginUser(ProtoToken $request) {
        $token = $request->getToken();
        $userInfo = (new UserService())->getUserByCode($token);
        $response = new ResponseGetLoginUser();
        if (isset($userInfo['code'])) {

            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->setUser($userInfo['user']->getRow());
            $response->resCode = RPCErrorCode::SUCCESS;
        }
        return $response;
    }

    /**
     * ??????accessToken????????????ID
     * @param \NCFGroup\Protos\Ptp\ProtoAccessToken $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserIdByAccessToken(ProtoAccessToken $request) {
        $accessToken = $request->getAccessToken();
        $oauth = new \PDOOAuth2();
        $result = $oauth->verifyOpenAccessToken($accessToken);
        $response = new ProtoUser();
        if ($result !== TRUE) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->errorCode = $result['errorCode'];
            $response->errorMsg = $result['errorMsg'];
            return $response;
        }

        $userId = $oauth->getAccessToken($accessToken);
        if ($userId > 0) {
            $response->setUserId(intval($userId));
            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }

        return $response;
    }

    /**
     * ??????accessToken??????clientId
     * @param \NCFGroup\Protos\Ptp\ProtoAccessToken $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getClientIdByAccessToken(ProtoAccessToken $request) {
        $accessToken = $request->getAccessToken();
        $oauth = new \PDOOAuth2();
        $result = $oauth->getClientInfoByAccessToken($accessToken);
        return $result;
    }

    /**
     * Get User By Site ID
     * @param \NCFGroup\Protos\Ptp\RequestUserList $request
     * @return \NCFGroup\Protos\Ptp\ResponseUserList $response
     */
    public function getUserBySiteId(RequestUserList $request){
        $siteId = $request->getSiteId();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $params = $request->getParams();
        $pageable = $request->getPageable();
        if (empty($pageable)) {
            $pageNo = ceil($offset/$count) + 1;
            $pageSize = $count;
            $pageable = new Pageable($pageNo, $pageSize);
        }
        $isDesensitize = $request->getIsDesensitize();
        $userList = UserDAO::getAllUserBySiteId($siteId, $params, $pageable, false);

        $list = array();
        if ($userList['list']) {
            //????????????ID??????????????????????????????
            $userIdArr = array();
            $euids = array();
            foreach ($userList['list'] as $val){
                $userIdArr[] = $val['id'];
            }
            $userIdArr = array_unique($userIdArr);
            //???firstp2p_adunion_deal???????????????euid??????
            $orders = AdunionDealDAO::getOrderInfoByUids($userIdArr);
            if(!empty($orders)){
               foreach($orders as $valOrder){
                   $euids[$valOrder['uid']] = $valOrder['euid'];
               }
            }

            $couponService = new \core\service\CouponService();
            foreach ($userList['list'] as $userInfo) {
                $tmp = array(
                    'userId' => $userInfo['id'],
                    'realName' => $userInfo['real_name'],
                    'mobile' => $userInfo['mobile'],
                    'idno' => $userInfo['idno'],
                    'registerTime' => $userInfo['create_time'],
                    'tags' => $userInfo['tag_info'],
                    'euid' => empty($euids[$userInfo['id']]) ? '' : $euids[$userInfo['id']]
                );
                $bankcard = (new UserBankcardService())->getBankcard($userInfo['id']);
                if (!empty($bankcard)) {
                    $bank = (new BankService())->getBank($bankcard['bank_id']);
                    $tmp['bankNo'] = $bankcard['bankcard'];
                }
                $list[] = $tmp;
            }
        }

        $response = new ResponseUserList();
        $response->resCode = RPCErrorCode::SUCCESS;
        $response->setList($list);
        if ($pageable) {
            $response->setTotal($userList['total']);
            $response->setPageNo($userList['pageNo']);
            $response->setPageSize($userList['pageSize']);
        }
        return $response;
    }

    /**
     * update User By Site ID
     * @param \NCFGroup\Protos\Ptp\RequestUserList $request
     * @return \NCFGroup\Protos\Ptp\ResponseUserList $response
     */
    public function updateUserBySiteId(RequestUserUpdate $request){
        $siteId = $request->getSiteId();
        $userId = $request->getUserId();

        $data = $request->getUpdateData();
        $data['id'] = $userId;
        $data['mobilepassed'] = 'true';
        $mobile_code_list = $GLOBALS['dict']['MOBILE_CODE'];
        $data['mobile_code'] = $mobile_code_list[$data['country_code']]['code'];

        $res = UserDAO::updateUserInfo($userId, $siteId, $data);
        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::FAILD;
        $response->msg = '';
        if ($res) {
            if ($res['status'] == 0) {
                $error = $res['data']['error'];
                $field = $res['data']['field_name'];
                if ($field == 'mobile') {
                    $res['msg'] = '?????????????????????';
                }
                if ($error == 'syncfailed') {
                    $res['msg'] = '???????????????????????????';
                }
                $response->msg = $res['msg'];
            } else {
                $response->resCode = RPCErrorCode::SUCCESS;
            }
        }
        return $response;
    }
    /**
     * ??????userId???????????? Open????????????
     * @param \NCFGroup\Protos\Ptp\RequestUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserByIdFromOpen(RequestUser $request) {
        $userId = $request->getUserId();
        $siteId = $request->getSiteId();
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);
        if (empty($userInfo)) {
            $userInfo = $userService->getUser($userId);
        }
        $response = new ProtoUser();
        if (!empty($userInfo)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->setUserId(intval($userInfo['id']));
            $response->setUserName($userInfo['user_name']);
            $response->setRealName($userInfo['real_name']);
            $response->setMobile($userInfo['mobile']);
            $email = $isDesensitize ? mailFormat($userInfo['email']) : $userInfo['email'];
            $response->setEmail($email);
            $response->setInviteCode($userInfo['invite_code']);
            $response->setRegisterTime($userInfo['create_time']);
            // ????????????
            $response->userType = (int)$userInfo['user_type'];
            // ????????????????????????????????????ID
            $response->paymentUserId = (int)$userInfo['payment_user_id'];
            $response->groupId = (int)$userInfo['group_id'];
            //??????group_id??????????????????
            //?????????Open???????????? bankUserName ??????
            $response->bankUserName = (new UserGroupService())->getGroupInfo((int)$userInfo['group_id'])['name'];

            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * ??????userId?????????????????? Open????????????
     * @param \NCFGroup\Protos\Ptp\RequestUserListInfo $request
     * @return \NCFGroup\Protos\Ptp\ResponseUserListInfo
     */
    public function getUserByIdListFromOpen(RequestUserListInfo $request) {
        $userIdList = $request->getUserIdList();
        $idArr = explode(',', $userIdList);
        $userService = new UserService();
        $resArr = array();
        foreach($idArr as $val){
            $userInfo = $userService->getUserViaSlave($val);
            if (empty($userInfo)) {
                $userInfo = $userService->getUser($userId);
            }
            $arr = array(
                    'userId' => intval($userInfo['id']),
                    'userName' => $userInfo['user_name'],
                    'realName' => $userInfo['real_name'],
                    'mobile' => $userInfo['mobile'],
                    'email' => $userInfo['email'],
                    'sex'    => $userInfo['sex'],
                    'groupName' => (new UserGroupService())->getGroupInfo((int)$userInfo['group_id'])['name'],
                    );
            array_push($resArr, $arr);
        }
        $response = new ResponseUserListInfo();
        $response->list = $resArr;
        return $response;
}



    /**
     * ??????userId????????????
     * @param \NCFGroup\Protos\Ptp\RequestUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserById(RequestUser $request) {
        $userId = $request->getUserId();
        $siteId = $request->getSiteId();
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);
        if (empty($userInfo)) {
            $userInfo = $userService->getUser($userId);
        }
        if (!empty($userInfo)) {
            $response = $this->_getProtoUser($userInfo->getRow(), $request->getIsDesensitize());
            $response->resCode = RPCErrorCode::SUCCESS;
            if ($request->getIsEditableInfo()) {
                $response->setIdTypeList($GLOBALS['dict']['ID_TYPE']);
            }
        } else {
            $response = new ProtoUser();
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }
    /**
     * Get ProtoUser Data
     * @param array $userInfo
     * @param int $isDesensitize ????????????
     * @return ProtoUser
     */
    private function _getProtoUser($userInfo, $isDesensitize = 1) {
        $response = new ProtoUser();
        $userService = new UserService();
        if (!empty($userInfo)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $isO2oUser = $userService->isOtoUser($userInfo['id'], $userInfo);
            $response->setIsO2oUser($userInfo['isO2oUser']);
            $response->setUserId(intval($userInfo['id']));
            $response->setIsDFLH(intval($userInfo['is_dflh']));
            $response->setUserName($userInfo['user_name']);
            $response->setRealName($userInfo['real_name']);
            $response->setMoney(number_format($userInfo['money'], 2));
            $response->setUnFormatted($userInfo['money']);
            $response->setIdno($userInfo['idno']);
            $response->setIdtype($userInfo['id_type']);
            $response->setIdcardPassed($userInfo['idcardpassed']);
            $response->setPhotoPassed($userInfo['photo_passed']);
            $response->setMobile($userInfo['mobile']);
            $response->setSex($userInfo['sex']);
            $email = $isDesensitize ? mailFormat($userInfo['email']) : $userInfo['email'];
            $response->setEmail($email);
            $response->setRemain(format_price($userInfo['money'], false));
            $response->setFrozen(format_price($userInfo['lock_money'], false));
            $response->setReferUserId($userInfo['refer_user_id']);
            $response->setInviteCode($userInfo['invite_code']);
            $response->setUserPwd($userInfo['user_pwd']);
            $bankcard = (new UserBankcardService())->getBankcard($userInfo['id']);
            if (!empty($bankcard)) {
                $bank = (new BankService())->getBank($bankcard['bank_id']);
                $bank_no = $isDesensitize ? formatBankcard($bankcard['bankcard']) : $bankcard['bankcard'];
                $bank_name = $bank['name'];
                $attachment = (new AttachmentService())->getAttachment($bank['img']);
                $bank_icon = empty($attachment['attachment']) ? "" : 'http:' . $GLOBALS['sys_config']['STATIC_HOST'] . '/' . $attachment['attachment'];
                $bank_zone = (string) $bankcard['bankzone'];
                $response->setBankId($bankcard['bank_id']);
                $response->setBankUserName($bankcard['card_name']);
                $response->setBankNo($bank_no);
                $response->setBank($bank_name);
                $response->setBankCode($bank['short_name']);
                $response->setBankIcon($bank_icon);
                $response->setBankZone($bank_zone);
                $cardStatus = 0;
                if ($bankcard['verify_status'] == 1) {
                    $cardStatus = ($bankcard['cert_status'] == 2) ? 1 : 2;
                }
                $response->setCardVerify($cardStatus);
            }
            $userStatics = (new AccountService())->getUserStaicsInfo($userInfo['id']);
            $bonus = (new BonusService())->get_useable_money($userInfo['id']);
            //?????????????????????????????????
            $response->setEarningAll(format_price(bcsub($userStatics['earning_all'], $userStatics['cg_total_earnings'], 2), false));
            $response->setIncome(format_price(bcsub($userStatics['interest'], $userStatics['cg_norepay_earnings'], 2), false));
            $response->setCorpus(format_price(bcsub($userStatics['principal'], $userStatics['cg_norepay_principal'], 2), false));
            //????????????
            $response->setEarningAllTotal(format_price($userStatics['earning_all'], false));
            $response->setIncomeTotal(format_price($userStatics['interest'], false));
            $response->setCorpusTotal(format_price($userStatics['principal'], false));

            $response->setDjsNorepayPrincipal(format_price($userStatics['js_norepay_principal'], false));
            $response->setDjsNorepayEarnings(format_price($userStatics['js_norepay_earnings'], false));
            $response->setDjsTotalEarnings(format_price($userStatics['js_total_earnings'], false));

            $response->setTotal(format_price($userInfo['money'] + $userInfo['lock_money'] + $userStatics['stay'], false));
            $response->setTotalExt(format_price($userInfo['money'] + $userInfo['lock_money'] + $userStatics['principal'], false));
            $response->setBonus(format_price($bonus['money'], false));
            $response->setGroupId($userInfo['group_id']);
            $response->setRegisterTime($userInfo['create_time']);
            $response->setUpdateTime($userInfo['update_time']);
            $response->setIsEffect($userInfo['is_effect']);
            // ????????????
            $response->userType = (int)$userInfo['user_type'];
            $response->userPurpose = (int)$userInfo['user_purpose'];
            // ????????????????????????????????????ID
            $response->paymentUserId = (int)$userInfo['payment_user_id'];

            $response->setCgNorepayPrincipal($userStatics['cg_norepay_principal']);
            $response->setIsWxFreePayment(intval($userInfo['wx_freepayment']));

            // ???????????????????????????????????????
            $userAssetRecord = array(
                'userAssetRecord',
                __CLASS__,
                __FUNCTION__,
                'userId:'.$userInfo['id'],
                'assetInfo:'.json_encode(
                    array(
                        'earning_all' => format_price($userStatics['earning_all'],false),
                        'interest' => format_price($userStatics['interest'], false),
                        'principal' => format_price($userStatics['principal'], false),
                        'total' => format_price($userInfo['money'] + $userInfo['lock_money'] + $userStatics['principal'], false),
                    )
                ),
            );
            Logger::debug(implode(',',$userAssetRecord));
        }
        return $response;
    }

    /**
     * ??????userId??????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserInfoById(ProtoUser $request) {
        $userId = $request->getUserId();
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);
        //??????????????????,???????????????,???????????????
        if (empty($userInfo)) {
            $userInfo = $userService->getUser($userId);
        }
        if (!empty($userInfo)) {
            $response = $this->_getProtoUser($userInfo->getRow(), $request->getIsTm());
        } else {
            $response = new ProtoUser();
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }
   /**
     * ??????userId?????????????????????,??????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserMobilById(ProtoUser $request) {
        $userId = $request->getUserId();
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);
        //??????????????????,???????????????,???????????????
        if (empty($userInfo)) {
            $userInfo = $userService->getUser($userId);
        }
        if (!empty($userInfo)){
            return $userInfo['mobile'];
        }
        return null;
    }


    /**
     * ??????mobile??????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserInfoByMobile(RequestUserMobile $request) {
        $mobile = $request->getMobile();
        $userInfo = (new UserService())->getByMobile($mobile);
        $response = new ProtoUser();
        if (!empty($userInfo)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->setUserId(intval($userInfo['id']));
            $response->setUserName($userInfo['user_name']);
            $response->setRealName($userInfo['real_name']);
            $response->setSex($userInfo['sex']);
            $response->setReferUserId(intval($userInfo['refer_user_id']));
            $response->setIdno($userInfo['idno']);
            $response->setRegisterTime($userInfo['create_time']);
            $response->setGroupId($userInfo['group_id']);
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * ??????mobile??????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserInfoByIdno(ProtoUser $request) {
        $idno = $request->getIdno();
        $userInfo = (new UserService())->getUserByIdno($idno);
        $response = $request;
        if (!empty($userInfo)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->setUserId(intval($userInfo['id']));
            $response->setUserName($userInfo['user_name']);
            $response->setRealName($userInfo['real_name']);
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * ??????groupId??????user_group??????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserGroupInfoById(ProtoUser $request) {
        $id = $request->getGroupId();
        $userInfo = (new UserGroupService())->getGroupInfo($id);
        $response = $request;
        if (!empty($userInfo)) {
            $response->setSiteName($userInfo['name']);
        }
        return $response;
    }

    /**
     * ??????idno,name,mobile,user_name??????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getUserInfoByINM(ProtoUser $request)
    {
        if($type = $request->getUserTypes() == 1){
            $userInfo = (new UserService())->getAllEnterpriseByCondition($request->getIdno(), $request->getRealName());
            $userName = $request->getUserName();
            if (empty($userInfo)) {
                $user_id = 0;
            } else {
                $uids = array_keys($userInfo);
                $userNames = UserModel::instance()->getUserNamesByIds($uids);
                //??????user_name????????????,???????????????????????????
                $userIsExist = array_search($userName, $userNames);
                $user_id = ($userIsExist && in_array(2, $userInfo[$userIsExist])) ? $userIsExist : 0;
                $request->setAllUserId($uids);
            }
        }else{
            $userInfo = (new UserService())->getByMobile($request->getMobile(), 'idno,real_name,id,is_effect');
            if(!empty($userInfo) && $userInfo['is_effect'] == 1){
                $res_id_no = strtoupper($userInfo['idno']);
                $pro_id_no = strtoupper($request->getIdno());
                $user_id   = (strcmp($res_id_no,$pro_id_no) == 0 && strcmp($userInfo['real_name'],$request->getRealName()) == 0) ? $userInfo['id'] : 0;
            }else{
                $user_id = 0;
            }
        }

        $response = $request;
        $response->resCode   = !empty($user_id) ? RPCErrorCode::SUCCESS : RPCErrorCode::FAILD;
        $response->setUserId((int) $user_id);

        return $response;
    }

    /**
     * ??????uid??????????????????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getBankInfoByUserid(ProtoUser $request)
    {
        $userInfo = (new UserBankcardService())->getBankcard($request->getUserId());

        $response = $request;
        $response->resCode   = !empty($userInfo) ? RPCErrorCode::SUCCESS : RPCErrorCode::FAILD;
        $response->setBankNo(isset($userInfo['bankcard']) ? (string)$userInfo['bankcard'] : '');

        return $response;
    }

    /**
     * ??????uid??????????????????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getCreditLoanInfoByUserId(ProtoUser $request)
    {
        $userInfo = (new UserService())->getUser($request->getUserId());
        $bankCardInfo = (new UserBankcardService())->getBankcard($request->getUserId());
        $response = $request;
        $response->resCode   = !empty($bankCardInfo) ? RPCErrorCode::SUCCESS : RPCErrorCode::FAILD;
        $response->setBankNo(isset($bankCardInfo['bankcard']) ? (string)$bankCardInfo['bankcard'] : '');
        $bankInfo = (new BankService())->getBank($bankCardInfo['bank_id']);
        $response->setBankCode(isset($bankInfo['short_name']) ? (string)$bankInfo['short_name'] : '');
        $response->setBank(isset($bankInfo['name']) ? (string)$bankInfo['name'] : '');
        // ??????????????????
        $response->setIdno(isset($userInfo['idno']) ? (string)$userInfo['idno'] : '');
        $response->setRealName(isset($userInfo['real_name']) ? (string)$userInfo['real_name'] : '');
        $response->setMobile(isset($userInfo['mobile']) ? (string)$userInfo['mobile'] : '');
        return $response;

    }


    /**
     * ??????????????????developer_credentials????????????token
     * @param type $param
     * @return type
     */
    public function getAccessTokenForClient(RequestOauth $request) {
        $oauth = new \PDOOAuth2();
        $response = $oauth->grantAccessTokenForClient($request->getClientId(), $request->getScope());
        return $response;
    }

    /*
     * ??????accessToken
     */

    public function getAccessToken() {
        $oauth = new \PDOOAuth2();
        $response = $oauth->grantAccessTokenForOpen();
        return $response;
    }

    /*
     * ??????code
     */

    public function getCode() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function getUserFinancialRecord(RequestGetUserFinancialRecord $request) {
        $response = (new UserService())->getUserAvailableMoneyLog($request->getUserId(), $request->getOffset(), $request->getCount());
        if (!empty($response)) {
            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    public function getCreditRegCount(){
        $response = new ResponseCreditRegCount();
        $user_data = new \core\data\UserData();
        $data = $user_data->getCreditRegCount();

        $response->setTotalRegCount(intval($data['TotalRegCount']));
        $response->setYearRegCount(intval($data['YearRegCount']));
        $response->setMonthRegCount(intval($data['MonthRegCount']));
        $response->setDayRegCount(intval($data['DayRegCount']));

        return $response;
    }

    public function getCreditRegLog(){
        $response = new ResponseCreditRegLog();
        $user_data = new \core\data\UserData();
        $data = $user_data->popCreditReg(100);
        $list = array();
        $ipList = array();
        if(!empty($data)){
            $userService = new UserService();
            foreach($data as $row){
                $row = json_decode($row,true);
                $user = $userService->getUser($row['user_id']);
                if($user){
                    $value = array();
                    $value['userID'] = $row['user_id'];
                    $value['realName'] = user_name_format($user['real_name']);
                    $value['sex'] = ($user['sex']=='1')?'???':'???';
                    $value['mobile'] = moblieFormat($user['mobile']);
                    $value['time'] = date('H:i:s',$row['time']);
                    $value['login_ip'] = $user['login_ip'];
                    $value['ip'] = $row['ip'];
                    $ipList[]= $row['ip'];
                    $list[] = $value;
                }
            }
        }
        if($ipList){
            $citys = getCityByIp($ipList);
            if($list && $citys){
                $citys = json_decode($citys, true);
                if($citys && $citys['errno'] == '0'){
                    $citys = $citys['locate'];
                    foreach($list as $key => $value){
                        $list[$key]['city'] = $citys[$value['ip']];
                        unset($list[$key]['ip']);
                    }
                }
            }
        }
        $response->setList($list);
        return $response;
    }

    /*
    * ????????????????????????
    */
    public function getMoneyLogByUid(RequestUserMoneyLog $request){
        $response = new ResponseUserMoneyLog();
        $userService = new UserLogService();
        $userId = $request->getUserId();
        $logInfo = $request->getLogInfo();
        $beginTime = $request->getBeginTime();
        $endTime = $request->getEndTime();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $moneyType = $request->getMoneyType();
        $response->setOffset($offset);
        $response->setCount($count);
        $logs = $userService->get_user_log(
            array($offset, $count),
            $userId,
            $moneyType,
            false,
            $logInfo,
            $beginTime,
            $endTime
        );
        $ret = array();
        foreach($logs['list'] as $one){
            $tmp = array();
            $tmp['log_time'] = $one['log_time']+28800;
            $tmp['log_info'] = $one['log_info'];
            if(strcmp($moneyType , 'money') == 0){
                $tmp['money'] =  $one['label'] == UserLogService::LOG_INFO_SHOU ? '+' . format_price($one['showmoney'],false) : format_price($one['showmoney'],false);
            }else{
                $tmp['money'] = $one['money'];
            }
            $tmp['note'] = $one['note'];
            $tmp['remain'] = $one['remaining_total_money'];
            $tmp['label'] = $one['label'];

            $ret[] = $tmp;
        }
        if(strcmp($moneyType , 'money') == 0){
            $logType = UserLogService::$money_log_types;
            $response->setLogType($logType);
        }
        $response->setList($ret);
        return $response;
    }

    /**
     * ???????????????????????????
     **/
    public function checkInviteCode(RequestCheckInviteCode $request) {
        $inviteCode = $request->getInviteCode();

        $couponService = new CouponService();
        $queryResult   = $couponService->queryCoupon($inviteCode);

        $response = new ResponseCheckInviteCode();
        if (!empty($queryResult)) {
            $response->checkRes = true;
            $response->setRebateRatio(floatval($queryResult['rebate_ratio_show']));
            $response->setReferUserId(intval($queryResult['refer_user_id']));
        } else {
            $response->checkRes = false;
        }

        return $response;
    }

    /**
     * ??????uid???tag_ids??????Tag,??????tag_ids?????????????????????array(???22???=>???22???)
     * @param \NCFGroup\Protos\Ptp\RequestUserTags $request
     * @return \NCFGroup\Protos\Ptp\ResponseUserTags
     */
    public function addUserTags(RequestUserTags $request) {
        $uid = $request->getUserId();
        $tag_ids = $request-> getTagIds();
        if (intval($uid) <$uid || intval($uid)<=0 || !is_array($tag_ids) || empty($tag_ids) || array_values($tag_ids) != array_keys($tag_ids)) {
            throw new \Exception('???????????????????????????');
        }
        $usertag = new UserTagService();
        $tagid_ret = $usertag->getBytagsIds($tag_ids);
        if (empty($tagid_ret)) {
            throw new \Exception('tagids????????????');
        }
        $response = new ResponseUserTags();
        $result = $usertag->addUserTags($uid, $tag_ids);
        $response->setResult($result);
        return $response;
    }

    /**
     * ??????uid???const_names??????Tag?????????const_names?????????????????????array('BID_MORE_FEMALE','REG_M_10')
     * @param \NCFGroup\Protos\Ptp\RequestUserTags $request
     * @return \NCFGroup\Protos\Ptp\ResponseUserTags
     */
    public function addUserTagsByConstName(RequestUserTags $request) {
        $uid = $request->getUserId();
        $const_names = $request->getConstName();
        if (intval($uid) <$uid || intval($uid)<=0 || !is_array($const_names) || empty($const_names)) {
            throw new \Exception('???????????????????????????');
        }
        $usertag = new UserTagService();
        $name_ret=$usertag->getTagIdsByConstName($const_names);
        if (empty($name_ret)) {
            throw new \Exception('tag???????????????');
        }
        $response = new ResponseUserTags();
        $result = $usertag->addUserTagsByConstName($uid, $const_names);
        $response->setResult($result);
        return $response;
    }

    /**
     * ????????????UID???????????????????????????????????????
     * @param \NCFGroup\Protos\Ptp\RequestUserMoney $request
     * @return \NCFGroup\Protos\Ptp\ResponseUserMoney $response
     */
    public function changeMoney(RequestUserMoney $request) {
        //??????request??????
        $userId = $request->getUserId();
        $money = $request->getMoney();
        $message = $request->getMessage();
        $note = $request->getNote();
        $moneyType = $request->getMoneyType();
        $negative = $request->getNegative();
        $adminId = $request->getAdminId();
        $isManage = $request->getIsManage();
        $isMoneyAsync = $request->getIsMoneyAsync();

        //????????????????????????????????????????????????
        $ret = UserDAO::changeMoney($userId, $money, $message, $note, $moneyType, $negative, $adminId, $isManage, $isMoneyAsync);
        $respCode = $ret === true ? UserEnum::ERROR_COMMON_SUCCESS : UserEnum::ERROR_COMMON_FAILED;
        $respMsg = UserEnum::$ERROR_MSG[$respCode];

        //??????response??????
        $response = new ResponseUserMoney();
        $response->setRespCode((string)$respCode);
        $response->setRespMsg($respMsg);
        return $response;
    }

    /**
     * ??????uid??????????????????
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function getUserInfoByUserIdFund(SimpleRequestBase $request) {
        $uid = $request->getParam(0);
        if (intval($uid) <$uid || intval($uid)<=0) {
            throw new \Exception('???????????????????????????');
        }
        $user = (new UserService())->getUserViaSlave($uid);
        if ($user && $user['id'] && $user['is_delete'] == 0 && $user['is_effect'] == 1) {
            unset($user['user_pwd']);
        } else {
            throw new \Exception('???????????????ID???');
        }
        $bankInfo = array('bank_no' => '', 'bank_name' => '', 'bank_code' => '', 'is_bank_bind' => 0);
        $bankcard = (new UserBankcardService())->getBankcard($uid);
        $bankInfo['bank_no'] = $bankcard['bankcard'];
        if (!empty($bankcard)) {
            if (empty($bankcard['bank_id']) && $user['payment_user_id']) {
                // ???????????????????????????????????????-???????????????
                $userBankCardObj = new UserBankcardService();
                $payBankInfo = $userBankCardObj->queryBankCardsList($uid, true);
                if (!empty($payBankInfo['list'])) {
                    $bankInfo['bank_name'] = $payBankInfo['list']['bankName'];
                    $bankInfo['bank_code'] = $payBankInfo['list']['bankCode'];
                    if ($bankInfo['bank_name']) {
                        (new UserBankcardService())->updateBankNameByCode($bankcard['id'], $payBankInfo['list']['bankCode']);
                    }
                }
            } else {
                $bank = (new BankService())->getBank($bankcard['bank_id']);
                $bankInfo['bank_code'] = $bank['short_name'];
                $bankInfo['bank_name'] = $bank['name'];
            }
            $bankInfo['is_bank_bind'] = 1;
        }

        $fields = array(
                'id', 'user_name', 'real_name', 'email', 'idno', 'id_type', 'mobile',
                'idcardpassed', 'money', 'lock_money', 'address', 'phone', 'invite_code'
        );

        foreach ($fields as $field) {
            $ret[$field] = $user[$field];
        }
        $ret = array_merge($ret, $bankInfo);
        $response = new ResponseBase();
        $response->userinfo = $ret;
        $response->resCode = RPCErrorCode::SUCCESS;
        return $response;
    }
    /**
     * ???????????????????????????????????????????????????????????????????????????????????????clientip???????????????
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function checkUserNameFund(SimpleRequestBase $request) {
        $par = $request->getParamArray();
        $usernum = $par['usernum'];
        $userpwd = $par['userpwd'];//$usernum,$userpwd???????????????
        $has_vcode = $par['hasvcode'];
        $client_ip = $par['clientip'];//???????????????clientip???????????????????????????ip???????????????????????????????????????????????????ip
        if ($client_ip) {
            $clientIp = $client_ip;
        } else {
            $clientIp=get_client_ip();
        }
        $check_client_ip_minute = Block::check('USERNAME_IP_CHECK_MINUTE', $clientIp, false);//??????client_Ip???????????????30???/min?????????????????????????????????
        $check_client_ip_hour = Block::check('USERNAME_IP_CHECK_HOUR', $clientIp, false);//??????client_Ip???????????????200???/h??????????????????????????????

        $check_username_minute = Block::check('USERNAME_CHECK_MINUTE', $usernum, false);//??????usernsme???????????????10???/min?????????????????????????????????
        $check_username_hour = Block::check('USERNAME_CHECK_HOUR', $usernum, false);//??????usernsme???????????????20???/h??????????????????????????????

        //??????1h???????????????????????????????????????200???/h
        if ($check_client_ip_hour == false || $check_username_hour == false) {
            throw new \RequestForbidException();
        }
        //???????????????
        //??????1min???,?????????????????????????????????????????????????????????
        if ($check_client_ip_minute == false || $check_username_minute == false) {
            if (!$has_vcode || $has_vcode !== 1) {
                throw new \RequestFrequentException();
            }
        }

        //????????????????????????????????????
        if ($has_vcode == 1) {
            $check_username_vcode_minute = Block::check('USERNAME_CHECK_VCODE_MINUTE', $clientIp, false);//??????username????????????????????????10???/min
            $check_client_ip_vcode_minute = Block::check('USERNAME_IP_CHECK_VCODE_MINUTE', $clientIp, false);//??????client_Ip????????????????????????30???/min
            if ($check_client_ip_vcode_minute == false || $check_username_vcode_minute == false) {
                throw new \RequestForbidException();
            }
        }
        if (empty($usernum) || empty($userpwd)) {
            throw new \ParamException();
        }
        $user = (new UserService())->getUserinfoByUsername($usernum);

        if ($user['id']) {
            $pwdCom = new BOBase();
            $userpwd = $pwdCom->compilePassword($userpwd);
            if ($user['user_pwd'] === $userpwd) {
                $response = new ResponseBase();
                $response->userId = $user[id];
                $response->isUserPwdExist = RPCErrorCode::SUCCESS;
                return $response;
            } else {
                throw new \UsernamePasswordNotMatchException();
            }
        } else {
            throw new \UserNameNotExistException();
        }
    }
    /**
     * ?????????????????????????????????????????????????????????????????????
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function checkIdnoFund(SimpleRequestBase $request) {
        $idno = $request->getParam(0);
        if (empty($idno)) {
            throw new \Exception('???????????????????????????');
        }
        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($idno))) {
            throw new \Exception('????????????????????????????????????');
        }
        $service = new UserService();
        $result = $service->isIdCardExist($idno);
        if ($result) {
            $userinf=$service->getUserByIdno($idno);
            $response = new ResponseBase();
            $response->userId = $userinf[id];
            $response->isIdnoExist = RPCErrorCode::SUCCESS;
        } else {
            $response = new ResponseBase();
            $response->isIdnoExist = RPCErrorCode::FAILD;
        }
        return $response;
    }
    /**
     * ????????????????????????,??????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function relnameAuthFund(SimpleRequestBase $request) {
        $par = $request->getParamArray();
        $name = $par['name'];
        $idno = $par['idno'];
        $userid = $par['userid'];

        if (empty($idno) || empty($name) || empty($userid)) {
            throw new \Exception('???????????????????????????');
        }
        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($idno))) {
            throw new \Exception('???????????????????????????????????????????????????!');
        }
        if (strpos($name, ' ') !== false ) {
            throw new \Exception('????????????????????????????????????????????????????????????');
        }
        if (!is_numeric($userid)) {
            throw new \Exception('??????id??????????????????');
        }
        $age = getAgeByID(trim($idno));
        if (($age < $this->_minAge) || (($age > $this->_maxAge) && checkReferee($userinfo['refer_user_id']))) {
            throw new \UserAgeException();
        }
        $service = new UserService();
        $user = $service->getUserByIdno($idno,$userid);
        if (!empty($user['idno'])) {
            throw new \IdnoAreadyExistException();
        }
        $userinfo=$service->getUser($userid);//print_r($userinfo);
        if (empty($userinfo['real_name']) || $userinfo['real_name'] != $name) {
            throw new \UserNameNotExistException();
        } elseif ($userinfo['idcardpassed'] == 1 && $userinfo['idno'] == $idno) {
            throw new \UserAreadyAuthException();
        } elseif ($userinfo['idcardpassed'] == 1 && $userinfo['idno'] != $idno) {
            throw new \Exception('???????????????????????????????????????');
        }

        if ($service->psCheckUserNoid($name,$idno,$userid)) {
            $response = new ResponseBase();
            $response->msg = '??????????????????';
            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response = new ResponseBase();
            $response->msg = '??????????????????????????????????????????';
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }
    /**
     * ?????????????????????
     * @param \NCFGroup\Common\Extensions\Base\SimpleRequestBase $request
     * @return \NCFGroup\Common\Extensions\Base\ResponseBase $response
     */
    public function securNetFund(SimpleRequestBase $request) {
        $par = $request->getParamArray();
        $name = $par['name'];
        $idno = $par['idno'];
        if (empty($name) || empty($idno)) {
            throw new \Exception('???????????????????????????');
        }
        if (!preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", trim($idno))) {
           throw new \Exception('???????????????????????????????????????????????????!');
        }
        $result=(new UserService())->psCheckUserNoid($name, $idno);
        if ($result['code']=='0') {
            $response = new ResponseBase();
            $response->msg = '???????????????????????????????????????!';
            $response->rescode = RPCErrorCode::SUCCESS;
        } else {
            $response = new ResponseBase();
            $response->msg = $result['msg'];
            $response->rescode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * Edit User Password
     * @param RequestResetPassword $request
     * @return Response
     */
    public function resetPassword(RequestResetPassword $request)
    {
        $password = $request->getPassword();
        $confirmPassword = $request->getConfirmPassword();
        $userId = $request->getUserId();
        $siteId = $request->getSiteId();
        Assert::same($password, $confirmPassword, '?????????????????????');
        Assert::betweenLength($password, 5, 25, '????????????????????? 5~25');
        $userObj = UserDAO::getUserInfo($userId);
        $userInfo = $userObj->toArray();
        if (empty($userInfo)) {
            throw new \Exception('?????????????????????');
        }
        $boBase = new BOBase();
        $userPwd = $boBase->compilePassword($password);
        if ($userInfo['user_pwd'] == $userPwd) {
            throw new \Exception('?????????????????????????????????');
        }
        $info = array(
            'user_pwd' => $userPwd,
            'force_new_passwd' => 1,
        );
        $flag = UserDAO::updateUser($userId, $siteId, $info);
        $response = new ResponseBase();
        if ($flag) {
            // ??????????????????
            if (app_conf("SMS_ON") == 1) {
                // SMSSend ??????????????????
                if ($userInfo['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
                    $_mobile = 'enterprise';
                    $accountTitle = get_company_shortname($userInfo['id']); // by fanjingwen
                } else {
                    $_mobile = $userInfo['mobile'];
                    $accountTitle = \core\dao\UserModel::MSG_FOR_USER_ACCOUNT_TITLE;
                }
                $msg_content = array(
                    'account_title' => $accountTitle,
                    'modify_time' => date("m-d H:i"),
                );

                SmsServer::instance($this->appName)->send($_mobile, 'TPL_SMS_MODIFY_PASSWORD_NEW', $msg_content, $user_id);
            }
            $response->resCode = RPCErrorCode::SUCCESS;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
    }

    /**
     * @??????????????????????????????--?????????????????????
     * @param  object $ProtoUser
     * @return object
     */
    public function getUserDealTotal(ProtoUser $request){
        $userId    = $request->getUserId();
        $dealModel = new DealLoadModel();

        $response = new ProtoUser();

        $total = $dealModel->getCountByUserIdInSuccess($userId);
        $total = (int) $total;

        if($total >0){
            $response->setTotal($total);
        }else{
            $response->resCode = RPCErrorCode::FAILD;
        }

        return $response;
    }

    /**
     * @??????????????????????????????--????????????????????????
     * @param  object $ProtoUser
     * @return object
     */
    public function getUserGroup(ProtoUser $request){
        $userId    = $request->getUserId();
        $group_obj = UserModel::instance()->find($userId, 'group_id');
        $group_res  = $group_obj->getRow();
        $userInfo = (new UserGroupService())->getGroupInfo($group_res['group_id']);

        $response = $request;
        if (!empty($userInfo)){
            $response->setInviteSiteName($userInfo['name']);
        }
        return $response;
    }

    /**
     * ??????????????????
     */
    public function getEnterpriseByCN(SimpleRequestBase $request) {
        $e_no = $request->companyNo;
        $e_name = $request->companyName;
        $companyInfo = (new UserService())->getEnterpriseByCondition($e_no, $e_name);
        $response = new ResponseBase();
        if (!empty($companyInfo)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->companyInfo = $companyInfo;
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     *?????????????????????token??????uid
     *@date 2016.11.07
     *@author zhaohui
     */
    public function getUidByToken(RequestUidByToken $request) {
        $token = $request->getLcsToken();
        if (empty($token)) {
            throw new \Exception('???????????????????????????!');
        }
        $response = new ResponseGetLoginUser();
        $ret = (new UserTokenService())->getUidByToken($token);
        if (empty($ret['uid'])) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->setUser(array('uid'=>$ret['uid']));
        }
        return $response;
    }

    /**
     * ??????userId????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getAgeByUserId(ProtoUser $request) {
        $userId = $request->getUserId();
        $userService = new UserService();
        $userAge = $userService->getAgeByUserId($userId);
        $response = new ProtoUser();
        if ($userAge !== false) {
            $response->setUserAge($userAge);
        } else {
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * ????????????uid??????????????????p2p???????????????,????????????????????????p2p???????????????p2p????????????
     * @param array $user_ids
     * @return float
     */
    public function getUnrepayP2pMoneyByUids(ProtoUser $request) {
        $user_ids = $request->getAllUserId();
        $money = (new DealModel())->getUnrepayP2pMoneyByUids($user_ids);
        return $money;
    }

    public function getBindMobileByOpenId(SimpleRequestBase $req)
    {
        $bindInfo = (new BonusBindService)->getBindInfoByOpenid($req->openId);
        return $bindInfo['mobile'];
    }

    public function saveWeixinBind(SimpleRequestBase $req)
    {
        return (new BonusBindService)->bindUser($req->openId, $req->mobile);
    }

    /**
     * ??????userIds????????????
     */
    public function getUserByIds($request) {
        $par = $request->getParamArray();
        $userService = new UserService();
        $rObj = new ResponseBase();
        $rObj->userInfos = $userService->getUserInfoByIds($par['user_ids']);
        return $rObj;
    }

    /**
     * ??????????????????????????????
     */
    public function getUserByRealName($request) {
        $par = $request->getParamArray();
        $userService = new UserService();
        $rObj = new ResponseBase();
        $rObj->userInfo = $userService->getUserByRealName($par['user_real_name']);
        return $rObj;
    }

    /**
     * ??????userId??????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getSimpleUserInfoById(ProtoUser $request) {
        $userId = $request->getUserId();
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);
        //??????????????????,???????????????,???????????????
        if (empty($userInfo)) {
            $userInfo = $userService->getUser($userId);
        }
        if (!empty($userInfo)) {
            $response = $this->_getSimpleProtoUser($userInfo->getRow(), $request->getIsTm());
        } else {
            $response = new ProtoUser();
            $response->resCode = RPCErrorCode::FAILD;
        }
        return $response;
    }

    /**
     * ????????????????????????
     * @param array $userInfo
     * @param int $isDesensitize ????????????
     * @return ProtoUser
     */
    private function _getSimpleProtoUser($userInfo, $isDesensitize = 1) {
        $response = new ProtoUser();
        if (empty($userInfo)) {
            $response->resCode = RPCErrorCode::FAILD;
            return $response;
        }

        $userService = new UserService();
        $response->resCode = RPCErrorCode::SUCCESS;
        $isO2oUser = $userService->isOtoUser($userInfo['id'], $userInfo);
        $response->setIsO2oUser($userInfo['isO2oUser']);
        $response->setUserId(intval($userInfo['id']));
        $response->setIsDFLH(intval($userInfo['is_dflh']));
        $response->setUserName($userInfo['user_name']);
        $response->setRealName($userInfo['real_name']);
        $response->setMoney(number_format($userInfo['money'], 2));
        $response->setLock_money(number_format($userInfo['lock_money'], 2));
        $response->setUnFormatted($userInfo['money']);
        $response->setIdno($userInfo['idno']);
        $response->setIdtype($userInfo['id_type']);
        $response->setIdcardPassed($userInfo['idcardpassed']);
        $response->setPhotoPassed($userInfo['photo_passed']);
        $response->setMobile($userInfo['mobile']);
        $response->setSex($userInfo['sex']);
        $email = $isDesensitize ? mailFormat($userInfo['email']) : $userInfo['email'];
        $response->setEmail($email);
        $response->setRemain(format_price($userInfo['money'], false));
        $response->setFrozen(format_price($userInfo['lock_money'], false));
        $response->setReferUserId($userInfo['refer_user_id']);
        $response->setInviteCode($userInfo['invite_code']);
        $response->setUserPwd($userInfo['user_pwd']);
        $response->setGroupId($userInfo['group_id']);
        $response->setRegisterTime($userInfo['create_time']);
        $response->setUpdateTime($userInfo['update_time']);
        $response->setIsEffect($userInfo['is_effect']);
        // ????????????
        $response->userType = (int)$userInfo['user_type'];
        // ????????????????????????????????????ID
        $response->paymentUserId = (int)$userInfo['payment_user_id'];
        $response->setIsWxFreePayment(intval($userInfo['wx_freepayment']));

        // ???????????????????????????????????????
        $userAssetRecord = array(
            'userAssetRecord',
            __CLASS__,
            __FUNCTION__,
            'userId:'.$userInfo['id'],
        );
        Logger::debug(implode(',', $userAssetRecord));
        return $response;
    }



    /**
     * ??????idno,name,mobile,user_name????????????????????????
     * @param \NCFGroup\Protos\Ptp\ProtoUser $request
     * @return \NCFGroup\Protos\Ptp\ProtoUser
     */
    public function getCompanyUserInfoByINM(ProtoUser $request)
    {
        $userInfo = (new UserService())->getAllEnterpriseByCondition($request->getIdno(), $request->getRealName());
        $userName = $request->getUserName();
        if (empty($userInfo)) {
            $user_id = 0;
        } else {
            $uids = array_keys($userInfo);
            $request->setAllUserId($uids);
            if(!empty($userName)){
                $userNames = UserModel::instance()->getUserNamesByIds($uids);
                //??????user_name????????????,???????????????????????????
                $userIsExist = array_search($userName, $userNames);
                $user_id = ($userIsExist && in_array(2, $userInfo[$userIsExist])) ? $userIsExist : 0;
            }else{
                $user_id = $uids;
            }
        }
        $response = $request;
        $response->resCode   = !empty($user_id) ? RPCErrorCode::SUCCESS : RPCErrorCode::FAILD;
        $response->setUserId((int) $user_id);
        return $response;
    }
}
