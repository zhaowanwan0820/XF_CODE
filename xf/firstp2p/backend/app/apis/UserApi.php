<?php

namespace NCFGroup\Ptp\Apis;

use core\dao\DealLoadModel;
use NCFGroup\Common\Library\ApiBackend;
use core\service\UserService;
use core\dao\UserModel;
use core\dao\UserGroupModel;
use core\dao\UserCompanyModel;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseRegisterModel;
use core\dao\EnterpriseContactModel;
use core\service\UserTagService;
use core\service\AccountService;
use core\service\PaymentService;
use core\service\PassportService;
use core\service\user\WebBO;
use NCFGroup\Common\Library\ApiService;
use core\service\MsgBoxService;
use core\service\BwlistService;
use core\service\WeixinInfoService;
use core\service\BonusBindService;
use core\service\DealCustomUserService;

/**
 * 用户信息接口
 */
class UserApi extends ApiBackend {
    /**
     * 性能测试接口
     */
    public function hello() {
        return $this->formatResult($this->getParam());
    }

    /**
     * 通过用户真实姓名获取用户id
     * @param $name string 真实姓名
     * @return array
     */
    public function getUserIdByRealName() {
        $realName = $this->getParam('name');
        if (empty($realName)) {
            return $this->formatResult(array());
        }

        $res = UserModel::instance()->getUserIdsByRealName($realName);
        return $this->formatResult($res);
    }

    /**
     * 根据用户id判断某用户是否是企业用户
     * @param $userId string 用户id
     * @return array
     */
    public function isEnterprise() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            $res = 0;
        }

        $userService = new UserService();
        $res = $userService->checkEnterpriseUser($userId) ? 1 : 0;
        return $this->formatResult($res);
    }

    /**
     * 通过user_id获取用户信息
     * @param $userId string 用户id
     * @return array
     */
    public function getUserById() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        // 默认返回的字段
        $defaultFields = 'id,user_name,real_name,mobile,idno,is_effect,user_purpose,group_id,user_type,create_time';
        $fields = $this->getParam('fields', $defaultFields);
        $condition = 'id = :userId';
        $user = UserModel::instance()->findBy($condition, $fields, array(':userId'=>$userId), true);
        $user = $user ? $user->getRow() : array();
        return $this->formatResult($user);
    }

    /**
     * 根据手机号获取用户信息
     * @param $mobile string 手机号
     * @param $feilds string 需要的字段名称
     * @return array
     */
    public function getUserByMobile() {
        $mobile = $this->getParam('mobile');

        // 默认返回的字段
        $defaultFields = 'id,user_name,real_name,mobile,idno,idcardpassed,is_effect,supervision_user_id,user_purpose,group_id,user_type';
        $fields = $this->getParam('fields', $defaultFields);
        if (empty($mobile)) {
            return $this->formatResult(array());
        }

        $user = UserModel::instance()->getUserByMobile($mobile);
        $user = $user ? $user->getRow() : array();
        return $this->formatResult($user);
    }

    /**
     * 获取用户的服务费率
     */
    public function getUserServicesFee() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(0);
        }

        $levelList = load_auto_cache("level");
        $condition = 'id = :userId';
        $user = UserModel::instance()->findBy($condition, 'level_id', array(':userId'=>$userId), true);
        $servicesFee = $user ? $levelList['services_fee'][$user['level_id']] : 0;
        return $this->formatResult($servicesFee);
    }

    /**
     * 根据用户身份证号获取用户信息
     * @param $idno string 身份证号
     * @param $exclude int 需要排除的用户id
     * @return array
     */
    public function getUserByIdno() {
        $idno = $this->getParam('idno');
        $excludeUserId = $this->getParam('exclude', '');
        if (empty($idno)) {
            return $this->formatResult(array());
        }

        $user = UserModel::instance()->getUserByIdno($idno, $excludeUserId);
        $user = $user ? $user->getRow() : array();
        return $this->formatResult($user);
    }

    /**
     * 通过用户名称获取用户信息
     * @param $mobile string 用户名
     * @param $feilds string 需要的字段名称
     * @return array
     */
    public function getUserByName() {
        $username = $this->getParam('username');

        // 默认返回的字段
        $defaultFields = 'id,user_name,real_name,mobile,idno,idcardpassed,is_effect,supervision_user_id,user_purpose,group_id,user_type';
        $fields = $this->getParam('fields', $defaultFields);
        if (empty($username)) {
            return $this->formatResult(array());
        }

        $user = UserModel::instance()->getInfoByName($username, $fields);
        $user = $user ? $user->getRow() : array();
        return $this->formatResult($user);
    }

    /**
     * 通过用户名称或手机号，获取用户信息
     * @param $mobile string 用户名/手机号
     * @return array
     */
    public function getUserByNameMobile() {
        $username = $this->getParam('username');
        if (empty($username)) {
            return $this->formatResult(array());
        }

        $user = UserModel::instance()->getUserinfoByUsername($username);
        $user = $user ? $user->getRow() : array();
        return $this->formatResult($user);
    }

    /**
     * 根据相关条件获取用户信息
     * @param string $condition
     * @param type $fileds
     * @return array
     */
    public function getUserByCondition() {
        $condition = $this->getParam('cond');

        // 默认返回的字段
        $defaultFields = 'id,user_name,real_name,mobile,idno,idcardpassed,is_effect,supervision_user_id,user_purpose,group_id,user_type';
        $fields = $this->getParam('fields', $defaultFields);
        if (empty($condition)) {
            throw new \Exception("查询条件不能为空");
        }

        $user = UserModel::instance()->findBy($condition, $fields);
        $user = $user ? $user->getRow() : array();
        return $this->formatResult($user);
    }

    /**
     * 批量获取用户的基本信息
     * @param $userIds array 用户id列表，也可是逗号分割的字符串
     * @return array
     */
    public function getUserInfoByIds() {
        $userIds = $this->getParam('userIds');
        $needUserTypeName = $this->getParam('needUserTypeName', false);
        if (empty($userIds)) {
            return $this->formatResult(array());
        }

        if (!is_array($userIds)) {
            $userIds = explode(',', $userIds);
        }

        $userService = new UserService();
        $res = $userService->getUserInfoListByID($userIds, $needUserTypeName);
        return $this->formatResult($res);
    }

    /**
     * 获取企业用户信息
     */
    public function getEnterpriseInfo() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        $res = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userId);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 获取用户组列表
     */
    public function getUserGroupList() {
        $condition = $this->getParam('cond');

        $defaultFields = 'id,name,score,discount,channel_pay_factor,basic_group_id,agency_user_id';
        $fields = $this->getParam('fields', $defaultFields);
        $res = UserGroupModel::instance()->getGroupsByCond($condition, $fields);
        return $this->formatResult($res);
    }

    /**
     * 获取用户的公司信息
     */
    public function getUserCompanyInfo() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        $fields = $this->getParam('fields', '*');
        $res = UserCompanyModel::instance()->findByUserId($userId);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 根据用户的id，获取用户类型对应的名称
     */
    public function getUserTypeName() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult('');
        }

        $flag = $this->getParam('flag', 1);
        $res = '';
        $userInfo = UserModel::instance()->findViaSlave($userId);
        // user_type为0：个人客户
        if (UserModel::USER_TYPE_NORMAL == $userInfo['user_type']) {
            // JIRA#FIRSTPTOP-4024 变更企业会员账户唯一标识
            if ($flag == 0) {
                $res = UserModel::USER_TYPE_NORMAL_NAME;
            } else {
                $company = UserCompanyModel::instance()->findByViaSlave("user_id = '$userId'", 'name');
                $res = $company ? UserModel::USER_TYPE_ENTERPRISE_NAME : UserModel::USER_TYPE_NORMAL_NAME;
            }
        } else if (UserModel::USER_TYPE_ENTERPRISE == $userInfo['user_type']) {
            $res = UserModel::USER_TYPE_ENTERPRISE_NAME;
        }

        return $this->formatResult($res);
    }

    /**
     * 判断用户是否有该标签
     */
    public function checkUserTag() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(0);
        }

        $tagName = $this->getParam('tagName');
        if (empty($tagName)) {
            return $this->formatResult(0);
        }

        $tagService = new UserTagService();
        $hasTag = $tagService->getTagByConstNameUserId($tagName, $userId);
        return $this->formatResult($hasTag ? 1 : 0);
    }

    /**
     * 获取用户所有标签
     */
    public function getUserTags() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(0);
        }

        $tagService = new UserTagService();
        $res = $tagService->getTags($userId);
        return $this->formatResult($res);
    }

    /**
     * 公司名称获取企业用户信息
     */
    public function getEnterpriseByCompanyName() {
        $companyName = $this->getParam('name');
        if (empty($companyName)) {
            return $this->formatResult(array());
        }

        $userId = $this->getParam('userId', 0);
        $purpose = $this->getParam('purpose', 0);
        $res = EnterpriseModel::instance()->getByCompanyName($companyName, $userId, $purpose);
        return $this->formatResult($res);
    }

    /**
     * 根据公司证件号获取企业用户信息
     */
    public function getEnterpriseByCredentialsNo() {
        $credentialsNo = $this->getParam('credentialsNo');
        if (empty($credentialsNo)) {
            return $this->formatResult(array());
        }

        $userId = $this->getParam('userId', 0);
        $purpose = $this->getParam('purpose', 0);
        $res = EnterpriseModel::instance()->getByCredentialsNo($credentialsNo, $userId, $purpose);
        return $this->formatResult($res);
    }
    /**
     * 根据公司证件号、公司名称和用户名获取用户id
     * @param credentialsNo 证件号
     * @param companyName 公司名称
     * @param userName 用户名称
     * @return array
     */
    public function getUserIdByCCU() {
        $credentialsNo = $this->getParam('credentialsNo');
        $companyName = $this->getParam('companyName');
        $userName = $this->getParam('userName');
        if (empty($credentialsNo) || empty($companyName) || empty($userName)) {
            return $this->formatResult(array());
       }

        $res = EnterpriseModel::instance()->getUserIdByCCU($credentialsNo, $companyName, $userName);
        return $this->formatResult($res);
    }
    /**
     * 根据联系人手机号获取企业联系人信息
     */
    public function getEnterpriseContactByMobile() {
        $mobile = $this->getParam('mobile');
        if (empty($mobile)) {
            return $this->formatResult(array());
        }

        $res = EnterpriseContactModel::instance()->getEnterpriseContactByMobile($mobile);
        return $this->formatResult($res);
    }
    /**
     * 根据联系人用户ID获取企业联系人信息
     */
    public function getEnterpriseContactByUserId() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        $res = EnterpriseContactModel::instance()->getEnterpriseInfoByUserID($userId);
        return $this->formatResult($res);
    }
    /**
     * 根据const_name删除用户标签
     * @param $uid 用户id
     * @param $constNames 标签名称
     * @return array
     */
    public function delUserTagsByConstName() {
        $uid = $this->getParam('userId');
        $constNames = $this->getParam('constNames');
        if (empty($uid)) {
            return $this->formatResult(0);
        }

        $userTagService = new UserTagService();
        $res = $userTagService->delUserTagsByConstName($uid, $constNames);
        return $this->formatResult($res);
    }

    /**
     * 获取格数化的用户名
     * @param $userId int 用户id
     * @return string 格式化的用户名
     */
    public function getFormatUserName() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult('');
        }

        $userService = new UserService();
        return $this->formatResult($userService->getFormatUsername($userId));
    }

    /**
     * 获取用户的真实姓名
     * 获取真实姓名。普通用户获取真实姓名，企业用户获得企业名称
     */
    public function getUserRealName() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult('');
        }

        // 默认返回的字段
        $fields = 'id,user_name,real_name,user_type';
        $condition = 'id = :userId';
        $ids = is_array($userId) ? $userId : explode(',', $userId);
        $res = array();
        foreach ($ids as $id) {
            if (empty($id)) continue;

            $user = UserModel::instance()->findBy($condition, $fields, array(':userId'=>$id));
            $user_name = '';
            if ($user) {
                $user_name = $user['real_name'];
                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                    $enterpriseInfo = EnterpriseModel::instance()->findByViaSlave('user_id=:userId', 'company_name', array(':userId' => $id));
                    if ($enterpriseInfo) {
                        $user_name = $enterpriseInfo['company_name'];
                    }
                } else {
                    $company = UserCompanyModel::instance()->findByViaSlave("user_id=:userId", 'name', array(':userId' => $id));
                    if ($company) {
                        $user_name = $company->name;
                    }
                }
            }

            $res[$id] = $user_name;
        }

        return $this->formatResult(count($ids) == 1 ? array_pop($res) : $res);
    }

    /**
     * 新用户实名信息
     *  user_id 用户id
     *  real_name 姓名
     *  id_type 卡类型
     *  idno 卡号
     */
    public function updateUserIdentityInfo() {
        $userId = $this->getParam('userId');
        $realname = $this->getParam('realname');
        $idType = $this->getParam('idType');
        $idno = $this->getParam('idno');

        $userService = new UserService();
        $params = array(
            'user_id'=>$userId,
            'real_name'=>$realName,
            'id_type'=>$idType,
            'idno'=>$idno
        );

        $res = $userService->updateUserIdentityInfo($params);
        return $this->formatResult($res);
    }

    /**
     * 修改会员基本信息同步接口
     */
    public function modifyUserInfo() {
        $userId = $this->getParam('userId');
        $newInfo = $this->getParam('newInfo');

        $service = new \core\service\PaymentUserAccountService();
        $res = $service->modifyUserInfo($userId, $newInfo);
        return $this->formatResult($res);
    }

    /**
     * 修改会员基本信息同步接口
     */
    public function updateWxUserInfo() {
        $userData = $this->getParam('userData');

        $service = new UserService();
        $res = $service->updateInfo($userData);
        if ($res === false) {
            return $this->formatResult([], '100000', '更新用户信息失败');
        }
        return $this->formatResult($res);
    }

    public function allowAccountLoan(){
        $userPurpose  = $this->getParam('userPurpose');
        $userService = new UserService();
        $res = $userService->allowAccountLoan($userPurpose);
        return $this->formatResult($res);
    }

    /**
     * 借款人信息
     */
    public function getDealUserInfo() {
        $userId = $this->getParam('userId');
        $needRegion = $this->getParam('needRegion');
        $needWorkInfo = $this->getParam('needWorkInfo');

        $userService = new UserService();
        $deal_user_info = $userService->getUserViaSlave($userId, $needRegion, $needWorkInfo);
        $deal_user_info = $userService->getExpire($deal_user_info); //工作认证是否过期
        return $this->formatResult($deal_user_info);
    }

    /**
      *先锋支付接口平台注册方法
      */
    public function paymentRegister() {
        $userId = $this->getParam('userId');
        $regData = $this->getParam('regData');

        $paymentService = new PaymentService();
        $res = $paymentService->register($userId, $regData);
        return $this->formatResult($res);
    }

    /**
     * 判断用户是否是港澳台、军官证、护照用户
     */
    public function hasPassport() {
        $userId = $this->getParam('userId');
        $accountService = new AccountService();
        $res = $accountService->hasPassport($userId);
        return $this->formatResult($res);
    }

    /**
     * 企业用户注册信息
     */
    public function getEnterpriseRegisterInfo() {
        $userId = $this->getParam('userId');
        $res = EnterpriseRegisterModel::instance()->getInfoByUserID($userId);
        return $this->formatResult($res);
    }

    /**
     * 检查用户手机号是否存在
     */
    public function checkUserMobile() {
        $phone = $this->getParam('phone');
        if (empty($phone)) {
            return $this->formatResult(['code'=>'-1', 'reason'=>'参数不能为空']);
        }

        // 这里先保持以前的功能逻辑
        $userService = new UserService();
        $res = $userService->checkUserMobile($phone);
        return $this->formatResult($res);
    }

    /**
     * 检查该身份证是否已存在
     */
    public function isIdCardExist() {
        $idNo = $this->getParam('idNo');
        if (empty($idNo)) {
            return $this->formatResult(false);
        }

        $obj = new UserService();
        $res = $obj->isIdCardExist($idNo);
        return $this->formatResult($res);
    }

    /**
     * 检查该邮箱是否已存在
     */
    public function isEmailExist() {
        $email = $this->getParam('email');
        if (empty($email)) {
            return $this->formatResult(false, '-1', '参数不能为空');
        }

        $obj = new UserService();
        $res = $obj->checkEmailExist($email);
        return $this->formatResult($res);
    }

    /**
     * 更新用户邮箱信息
     */
    public function updateUserEmail() {
        $userId = $this->getParam('userId');
        $email = $this->getParam('email');

        $obj = new UserService();
        $res = $obj->updateUserEmail($userId, $email);
        if (!isset($res['code']) || $res['code'] !== 0) {
            return $this->formatResult([], $res['code'], $res['msg']);
        }
        return $this->formatResult($res);
    }

    /**
     * forceResetInitPwd
     * 重置初始密码
     *
     * @param int $userId
     * @param string $newPwd
     */
    public function forceResetInitPwd() {
        $userId = $this->getParam('userId');
        $newPwd = $this->getParam('newPwd');
        if (empty($userId) || empty($newPwd)) {
            return $this->formatResult(['status'=>1, 'msg'=>'参数错误']);
        }

        $userService = new UserService();
        $res = $userService->forceResetInitPwd($userId, $newPwd);
        return $this->formatResult($res);
    }

    /**
     * signWxFreepayment
     * 用户签署网信超级账户免密协议
     */
    public function signWxFreepayment() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(false);
        }

        $userService = new UserService();
        $res = $userService->signWxFreepayment($userId);
        return $this->formatResult($res);
    }

    /**
     * 实名认证
     *
     * @param int $userId
     * @param array $data
     */
    public function doIdValidate() {
        try{
            $userId = $this->getParam('userId');
            $data = $this->getParam('data');
            $isUpdateIdcard = $this->getParam('isUpdateIdcard');

            $paymentService = new PaymentService();
            $paymentService->doIdValidate($data, $userId, $isUpdateIdcard);
            return $this->formatResult(PaymentService::REGISTER_SUCCESS);
        }catch(\Exception $e){
            return $this->formatResult(PaymentService::REGISTER_FAILURE, $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 实名认证并开通超级账户
     *
     * @param int $userId
     * @param array $data
     */
    public function doIdValidateRegister() {
        try{
            $userId = $this->getParam('userId');
            $data = $this->getParam('data');
            $isUpdateIdcard = $this->getParam('isUpdateIdcard');

            $paymentService = new PaymentService();
            $status = $paymentService->register($userId, $data, $isUpdateIdcard);
            if ($status != PaymentService::REGISTER_SUCCESS) {
                $lastError = $paymentService->getLastError();
                $exceptionMsg = !empty($lastError) ? $lastError : '用户已经实名认证，无需重复认证';
                throw new \Exception($exceptionMsg, -1);
            }
            return $this->formatResult(PaymentService::REGISTER_SUCCESS);
        }catch(\Exception $e){
            return $this->formatResult(PaymentService::REGISTER_FAILURE, $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 是否网信通行证用户
     * @param int userId 用户ID
     */
    public function isLocalPassport() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult([]);
        }
        $passportService = new PassportService();
        $res = $passportService->isLocalPassport($userId);
        return $this->formatResult($res);
    }

    /**
     * 更新通行证信息
     */
    public function updatePassportInfo() {
        $ppId = $this->getParam('ppId');
        $oldMobile = $this->getParam('oldMobile');
        $newMobile = $this->getParam('newMobile');
        $requestId = $this->getParam('requestId');

        $passportService = new PassportService();
        $res = $passportService->updatePassportInfo($ppId, $oldMobile, $newMobile, $requestId);
        return $this->formatResult($res);
    }

    /**
     * 判断是否是第三方用户
     */
    public function isThirdPassport()
    {
        $mobile = $this->getParam('mobile');
        $bizInfo = $this->getParam('bizInfo');

        $passportService = new PassportService();
        $res = $passportService->isThirdPassport($mobile, $bizInfo);
        return $this->formatResult($res);
    }

    /**
     * 绑定通行证
     */
    public function userBind() {
        $ppId = $this->getParam('ppId');

        $passportService = new PassportService();
        $res = $passportService->userBind($ppId);
        return $this->formatResult($res);
    }

    /**
     * 验证是否需要二次验证
     */
    public function needLocalVerify() {
        $mobile = $this->getParam('mobile');

        $passportService = new PassportService();
        $res = $passportService->needLocalVerify($mobile);
        return $this->formatResult($res);
    }

    /**
     * 本地修改密码同步通行证逻辑
     */
    public function sessionDestroyByUserId() {
        $userId = $this->getParam('userId');

        $passportService = new PassportService();
        $res = $passportService->sessionDestroyByUserId($userId);
        return $this->formatResult($res);
    }

    /**
     * 更新超级账户手机号
     */
    public function updateUcfpayMobile() {
        $userId = $this->getParam('userId');
        $mobile = $this->getParam('mobile');
        $mobileCode = $this->getParam('mobileCode');

        $paymentService = new PaymentService();
        $res = $paymentService->updateMobile($userId, $mobile, $mobileCode);
        return $this->formatResult($res);
    }

    /*
     * 修改用户的登录密码
     */
    public function resetPwd() {
        $mobile = $this->getParam('mobile');
        $pwd = $this->getParam('pwd');

        $res = (new WebBO())->resetPwd($mobile,$pwd);
        return $this->formatResult($res);
    }

    /*
     * 用户迁移到经讯时代确认
     */
    public function updateUserToJXSD()
    {
        $userId = $this->getParam('userId');
        $result = (new UserService)->updateUserToJXSD($userId);

        return $this->formatResult($result);
    }

    /*
     * 更新用户存管id
     */
    public function updateSupervisionUserId()
    {
        $userId = $this->getParam('userId');
        // 更新用户存管系统id
        UserModel::instance()->updateSupervisionUserId($userId);
        // 企业用户，更新存管用户标识
        $userObject = UserModel::instance()->find($userId);
        $userServiceObject = new UserService($userObject);
        if ($userServiceObject->isEnterprise()) {
            EnterpriseModel::instance()->updateEnterpriseSupervisionUserId($userId);
        }
        return $this->formatResult(true);
    }

    /**
     * 提供给基金业务的用户信息
     */
    public function getUserDealInfoForFund()
    {
        $res = array(
            'isVip' => 0,               // 用户是否vip
            'isRiskAssessment'=>0,      // 是否参与了风险评测
            'riskLevelName'=>'',        // 风险评测的等级
            'riskScore' => 0,           // 风险评测的分数
            'investTimes'=>0,           // 近7日的投资次数
            'investAnnuMoney'=>0        // 用户近一年的投资总额/投资次数
        );

        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult($res);
        }

        $isVip = (new \core\service\vip\VipService())->isVip($userId);
        $res['isVip'] = $isVip ? 1 : 0;

        $risk = \core\dao\UserRiskAssessmentModel::instance()->getURA($userId);
        if ($risk) {
            $res['isRiskAssessment'] = 1;
            $res['riskLevelName'] = $risk->last_level_name;
            $res['riskScore'] = $risk->last_score;
        }

        $dealSql = "`user_id`=:userId AND `create_time`>=:time";
        $dealLoadModel = \core\dao\DealLoadModel::instance();
        //时间为7天前的格林尼治标准时间
        $wxInvestTimes = $dealLoadModel->countViaSlave($dealSql, array(
            ':userId'=>$userId,
            ':time'=>time() - 7*86400 - 8*3600
        ));
        $dealLoadService = new \core\service\ncfph\DealLoadService();
        $phInvestTimes = $dealLoadService->getDealCount($userId);
        $res['investTimes'] = $wxInvestTimes + $phInvestTimes;

        //获取用户在网信近一年的投资信息
        $wxInvest = $this->getInvestInfoByUserId($userId, strtotime("-1 year"));
        $wxInvestMoney = $wxInvest['total'];

        //获取用户在普惠近一年的投资金信息
        $phInvest = $dealLoadService->getInvestInfoByUserId($userId, strtotime("-1 year"));
        $phInvestMoney = $phInvest['total'];

        $totalInvestTimes = $wxInvest['investTimes'] + $phInvest['investTimes'];

        $res['investAnnuMoney'] = $totalInvestTimes == 0 ? 0 : bcdiv(($wxInvestMoney + $phInvestMoney) , $totalInvestTimes, 2);
        return $this->formatResult($res);
    }

    /**
     * 获取用户某段时间在ncfwx的投资总额，投资次数
     */
    private function getInvestInfoByUserId($userId, $startTime = false, $endTime = false){
        $ds = new DealLoadModel();

        $sql = "SELECT SUM(money) AS total, COUNT(*) AS investTimes FROM %s WHERE user_id=':user_id' ";
        $param = array(':user_id' => $userId);
        if($startTime){
            $sql .= "AND create_time >= ':date_start'";
            $param [':date_start'] = $startTime;
        }
        if($endTime){
            $sql .= "AND create_time < ':date_end'";
            $param [':date_end'] = $endTime;
        }

        $sql = sprintf($sql, $ds->tableName());
        $result = $ds->findBySql($sql, $param, true);
        if(empty($result)){
            $result['total'] = 0;
            $result['investTimes'] = 0;
        }

        return $result;
    }

    /**
     * 判断是否是投资过的用户
     * @param $userId string 真实姓名
     * @return array
     */
    public function isInvestUser() {
        $userId = $this->getParam('userId');
        $wxCount = DealLoadModel::instance()->countByUserId($userId);
        if ($wxCount > 0) {
            $result = true;
        } else {
            $phCount = (new \core\service\ncfph\AccountService())->getDealLoadCount($userId);
            $result = ($phCount > 0) ? true : false;
        }
        return $this->formatResult($result);
    }

    public function push()
    {
        $userId = $this->getParam('userId');
        $type = $this->getParam('type');
        $title = $this->getParam('title');
        $content = $this->getParam('content');
        $extraContent = $this->getparam('extraContent');

        $result = 1;
        try {
            (new MsgBoxService())->create($userId, $type, $title, $content, $extraContent);
        } catch (\Exception $e) {
            $result = 0;
        }

        return $this->formatResult($result);
    }

    public function getUserSummary()
    {
        $userId = $this->getParam('userId');
        $result = (new AccountService())->getUserSummaryNew($userId);
        return $this->formatResult($result);
    }

    public function hasLoan()
    {
        $userId = $this->getParam('userId');

        $reuslt = (new UserService())->hasLoan($userId);
        return $this->formatResult($reuslt);
    }

    public function inList()
    {
        $userId = $this->getParam('userId');
        $typeKey = $this->getParam('typeKey');

        $result = BwlistService::inList($typeKey, $userId);
        return $this->formatResult($result);
    }

    public function getNickHeadimg()
    {
        $mobile = $this->getParam('mobile');
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $bindRedisKey = 'assistance_service_dolike_weixin_bind_info_'.$mobile;
        $bindInfo = $redis->get($bindRedisKey);
        if (empty($bindInfo)) {
            $bindInfo = array_pop((new BonusBindService())->getBindInfoByMobile($mobile));
        }

        $nickname = '网信用户';
        $headimg = '';
        if (!empty($bindInfo['openid'])) {
            $weixinInfo = (new WeixinInfoService())->getWeixinInfo($bindInfo['openid']);
            if (!empty($weixinInfo) && isset($weixinInfo['user_info'])) {
                $nickname = $weixinInfo['user_info']['nickname'];
                $headimg = $weixinInfo['user_info']['headimgurl'];
            }
        }

        return $this->formatResult(['nickname' => $nickname, 'headimg' => $headimg]);
    }

    public function inListBatch()
    {
        $userId = $this->getParam('userId');
        $typeKeys = $this->getParam('typeKeys');

        $typeKeys = explode(',', $typeKeys);
        $data = [];
        foreach ($typeKeys as $typeKey) {
            $data[$typeKey] = BwlistService::inList($typeKey, $userId);
        }

        return $this->formatResult($data);
    }

    public function isDealCustomUser()
    {
        $userId = $this->getParam('userId');
        $result = (new DealCustomUserService())->checkIsShowUser($userId, false, true, 0, [], false, true);
        return $this->formatResult($result);
    }
}
