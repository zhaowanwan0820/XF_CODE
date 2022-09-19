<?php

// +----------------------------------------------------------------------
// | 管理后台-企业用户相关
// +----------------------------------------------------------------------
// | Author: guofeng
// +----------------------------------------------------------------------
FP::import("libs.libs.msgcenter");
FP::import("libs.libs.user");

use core\service\user\BOBase;
use core\service\BankService;
use core\service\CouponService;
use core\service\UserCouponLevelService;
use core\service\UserTagService;
use core\service\BanklistService;
use core\service\UserBankcardService;
use core\service\PaymentService;
use core\service\DeliveryRegionService;
use core\service\SupervisionAccountService;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseContactModel;
use core\dao\EnterpriseRegisterModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use core\dao\UserTagRelationModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class EnterpriseBase extends CommonAction {
    /**
     * 用户组列表
     */
    protected $groups = array();

    public function __construct() {
        parent::__construct();
    }

    /**
     * 用户管理-企业会员列表-编辑页面
     * @see CommonAction::edit()
     */
    public function edit() {
        $userId = intval($_REQUEST['id']);
        if (!is_numeric($userId) || $userId <= 0) {
            self::jsAlert('企业会员ID无效', '', 'history.go(-1);');
        }
        // 获取企业用户-user表信息
        $condition['id'] = $userId;
        // 未删除的帐户
        $condition['is_delete'] = 0;
        // 用户类型-企业用户
        // @todo 需要check
        $condition['user_type'] = UserModel::USER_TYPE_ENTERPRISE;
        $vo = M('User')->where($condition)->find();
        if (empty($vo)) {
            self::jsAlert('企业会员信息不存在', '', 'history.go(-1);');
        }
        // 返利系数
        $vo['channel_pay_factor'] = ($vo['channel_pay_factor'] > 0.0000) ? $vo['channel_pay_factor'] : '';
        // 所属组别返利系数
        $vo['group_factor'] = M('UserGroup')->where('id=' . $vo['group_id'])->getField('channel_pay_factor');
        $this->assign('vo', $vo);
        $this->assign('userId', $userId);
        $this->assign('companyMemberSn', numTo32($userId, 1));

        // 企业用户-基本信息
        $enterpriseBaseInfo = M('Enterprise')->where(array('user_id'=>$userId))->find();
        // 企业会员标识
        if (empty($enterpriseBaseInfo['identifier'])) {
            $enterpriseBaseInfo['identifier'] = $vo['user_name'];
        }
        // 企业注册地址
        if (!empty($enterpriseBaseInfo['registration_region'])) {
            $RegionArray = explode(',', $enterpriseBaseInfo['registration_region']);
            if (!empty($RegionArray)) {
                foreach ($RegionArray as $key => $registrId) {
                    $enterpriseBaseInfo['registration_region_lv1_' . ($key + 1)] = $registrId;
                }
            }
        }
        // 企业联系地址
        if (!empty($enterpriseBaseInfo['contract_region'])) {
            $RegionArray = explode(',', $enterpriseBaseInfo['contract_region']);
            if (!empty($RegionArray)) {
                foreach ($RegionArray as $key => $registrId) {
                    $enterpriseBaseInfo['contract_region_lv1_' . ($key + 1)] = $registrId;
                }
            }
        }
        $enterpriseBaseInfo['reg_amt'] = floatval(bcdiv($enterpriseBaseInfo['reg_amt'], 10000, 6));
        $this->assign('enterpriseBaseInfo', $enterpriseBaseInfo);
        // 企业用户-联系人信息
        $enterpriseContactInfo = M('EnterpriseContact')->where(array('user_id'=>$userId))->find();
        // 企业账户负责人联系地址
        if (!empty($enterpriseContactInfo['major_contract_region'])) {
            $RegionArray = explode(',', $enterpriseContactInfo['major_contract_region']);
            if (!empty($RegionArray)) {
                foreach ($RegionArray as $key => $registrId) {
                    $enterpriseContactInfo['major_contract_region_lv1_' . ($key + 1)] = $registrId;
                }
            }
        }
        $this->assign('enterpriseContactInfo', $enterpriseContactInfo);

        // 银行卡列表
        $bankList = $GLOBALS['db']->getAll('SELECT * FROM ' . DB_PREFIX . 'bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC');
        $this->assign('bankList', $bankList);
        // 用户绑定银行卡信息
        $bankcardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
        if ($bankcardInfo) {
            foreach ($bankList as $k => $v) {
                if ($v['id'] == $bankcardInfo['bank_id']) {
                    $bankcardInfo['is_rec'] = $v['is_rec'];
                    break;
                }
            }
            if ($this->is_cn) {
                $bankcardInfo['bankcard'] = formatBankcard($bankcardInfo['bankcard']);
            }
            $this->assign('bankcardInfo', $bankcardInfo);
        }
        if (!empty($bankcardInfo)) {
            if ($vo['payment_user_id'] > 0) {
                $paymentStatusMsg = $vo['payment_user_id'];
            }else{
                $paymentStatusMsg = '开户失败';
            }
        }else{
            $paymentStatusMsg = '未开通';
        }
        $this->assign('paymentStatusMsg', $paymentStatusMsg);
        // 一级地区
        $nRegionLv1 = MI('DeliveryRegion')->where(array('region_level' => 1))->findAll();
        $this->assign('nRegionLv1', $nRegionLv1);
        // 会员所属网站
        $groupList = M('UserGroup')->findAll();
        $this->assign('groupList', $groupList);
        // 银行卡列表
        $bankList = $GLOBALS['db']->getAll('SELECT * FROM ' . DB_PREFIX . 'bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC');
        $this->assign('bankList', $bankList);
        // 证件类别
        $this->assign('idTypes', $GLOBALS['dict']['ID_TYPE']);
        // 企业证件类别
        $this->assign('credentialsTypes', $GLOBALS['dict']['CREDENTIALS_TYPE']);
        // 国家区号
        $this->assign('mobileCodeList', $GLOBALS['dict']['MOBILE_CODE']);
        // 当前主站域名
        $this->assign('getWwwUrl', str_replace('admin.', '', get_domain()));
        // 当前页号
        $this->assign('currentPage', max(1, (int)\es_session::get('enterpriseListCurrentPage')));
        // 用户类型
        $this->assign('company_purpose_list', $this->is_cn ?  EnterpriseModel::getCompanyPurposeListCn() : EnterpriseModel::getCompanyPurposeList());
        // 是否需要拷贝用户数据
        $isCopyUser = (int)$this->get('isCopyUser');
        // 跳转地址
        $this->assign('jumpUrl', (!empty($isCopyUser) && $isCopyUser == 1) ? $_SERVER['HTTP_REFERER'] : u(MODULE_NAME . '/edit?id=' . $userId));
        $this->assign('inducateTypes',UserAccountEnum::$inducateTypes);
        $this->display();
    }

    /**
     * 用户管理-企业会员列表-更新逻辑
     * @see CommonAction::update()
     */
    public function update($isVerify) {
        $data = $_POST;

        $isTradecenter = false;
        if($data['company_purpose'] == UserAccountEnum::ACCOUNT_TRADECENTER) {
            $isTradecenter = true;
        }

        // 开始验证有效性
        if (!isset($data['id']) || !is_numeric($data['id']) || $data['id'] <= 0) {
            self::jsonOutput(-1, '企业会员ID无效');
        }
        if (empty($data['company_purpose'])) {
            self::jsonOutput(-1, '企业会员账户类型不能为空');
        }
        if (!check_empty($data['user_name'])) {
            self::jsonOutput(-1, '企业会员名称不能为空');
        }

        if (!check_empty($data['company_name'])) {
            self::jsonOutput(-1, '企业全称不能为空');
        }

        $enterpriseService = new \core\service\EnterpriseService();
        if (!$enterpriseService->canName($data['company_name'], $data['id'], $data['company_purpose'])) {
            self::jsonOutput(-1, '企业已存在');
        }

        if (!check_empty($data['company_shortname'])) {
            self::jsonOutput(-1, '企业简称不能为空');
        }

        if (!check_empty($data['credentials_no'])) {
            self::jsonOutput(-1, '企业证件号码不能为空');
        }

        if (!$enterpriseService->canCredentialsNo($data['credentials_no'], $data['id'], $data['company_purpose'])) {
            self::jsonOutput(-1, '企业已存在');
        }

        if (!check_empty($data['credentials_expire_date'])) {
            self::jsonOutput(-1, '企业证件有效期不能为空');
        }
        // 证件有效截止时间跟长期有效，不能同时为空
        if (!check_empty($data['credentials_expire_at']) && (int)$data['is_permanent'] == 0) {
            self::jsonOutput(-1, '请选择正确的企业证件有效期');
        }
        // 选中长期有效的话
        if ((int)$data['is_permanent'] == 1) {
            $data['credentials_expire_at'] = EnterpriseModel::$credentialsExpireAtDefault;
        }
        $credentialsStartDate = explode('-', $data['credentials_expire_date'], 3);
        $_year = isset($credentialsStartDate[0]) ? (int)$credentialsStartDate[0] : 0;
        $_month = isset($credentialsStartDate[1]) ? (int)$credentialsStartDate[1] : 0;
        $_day = isset($credentialsStartDate[2]) ? (int)$credentialsStartDate[2] : 0;
        if (false === checkdate($_month, $_day, $_year)) {
            self::jsonOutput(-1, '企业证件开始日期格式不合法');
        }
        $credentialsEndDate = explode('-', $data['credentials_expire_at'], 3);
        $_year = isset($credentialsEndDate[0]) ? (int)$credentialsEndDate[0] : 0;
        $_month = isset($credentialsEndDate[1]) ? (int)$credentialsEndDate[1] : 0;
        $_day = isset($credentialsEndDate[2]) ? (int)$credentialsEndDate[2] : 0;
        if ((int)$data['is_permanent'] == 0 && false === checkdate($_month, $_day, $_year)) {
            self::jsonOutput(-1, '企业证件结束日期格式不合法');
        }
        if (!$isTradecenter && !check_empty($data['legalbody_name'])) {
            self::jsonOutput(-1, '法定代表人姓名不能为空');
        }
        if (!$isTradecenter && !check_empty($data['legalbody_credentials_no'])) {
            self::jsonOutput(-1, '法定代表人证件号码不能为空');
        }
//        if (!check_empty($data['legalbody_mobile'])) {
//            self::jsonOutput(-1, '法定代表人手机号码不能为空');
//        }
//        if (!empty($data['legalbody_mobile']) && !is_mobile($data['legalbody_mobile'])) {
//            self::jsonOutput(-1, '请输入有效的法定代表人手机号码');
//        }
//        if (!check_empty($data['legalbody_email'])) {
//            self::jsonOutput(-1, '法定代表人邮箱地址不能为空');
//        }
        if (!$isTradecenter && !check_empty($data['contract_mobile'])) {
            self::jsonOutput(-1, '企业联系方式不能为空');
        }
        if (!$isTradecenter && !$this->isEnterpriseTel($data['contract_mobile'])) {
            self::jsonOutput(-1, '请输入有效的企业联系方式');
        }
        if(!$isTradecenter && !check_empty($data['reg_amt'])) {
            self::jsonOutput(-1,'企业注册资金不能为空');
        }
        $data['reg_amt'] = bcmul($data['reg_amt'], 10000, 2);
        if (!$isTradecenter && !check_empty($data['major_name'])) {
            self::jsonOutput(-1, '代理人姓名不能为空');
        }
        // 检查企业账户负责人姓名是否符合规则
        if (!$isTradecenter && !self::_checkChinese($data['major_name'], '')) {
            self::jsonOutput(-1, '代理人姓名只允许输入中文');
        }
        if (!$isTradecenter && !check_empty($data['major_condentials_no'])) {
            self::jsonOutput(-1, '代理人证件号码不能为空');
        }
        if (!$isTradecenter && !check_empty($data['major_mobile'])) {
            self::jsonOutput(-1, '代理人手机号码不能为空');
        }
        if (!$isTradecenter && !is_mobile($data['major_mobile'])) {
            self::jsonOutput(-1, '请输入有效的代理人手机号码');
        }
        if (!$isTradecenter && !check_empty($data['receive_msg_mobile'])) {
            self::jsonOutput(-1, '接收短信通知号码不能为空');
        }
        if (!$isTradecenter && !check_empty($data['major_email'])) {
            self::jsonOutput(-1, '接收邮件地址不能为空');
        }

        if (!$isTradecenter && !intval($data['group_id'])) {
            self::jsonOutput(-1, '会员组不能为空');
        }
        if (!$isTradecenter && !intval($data['new_coupon_level_id'])) {
            self::jsonOutput(-1, '服务等级不能为空');
        }
        // 检查企业账户负责人邮箱是否有效
        if (!$isTradecenter && !check_email($data['major_email'])) {
            self::jsonOutput(-1, '接收邮件地址无效');
        }
        // 检查法人邮箱是否有效
        if (!$isTradecenter && !empty($data['legalbody_email']) && !check_email($data['legalbody_email'])) {
            self::jsonOutput(-1, '法人邮箱地址无效');
        }
        // 检查接收短信通知号码是否合法
        $receiveMsgMobileString = self::_receiveUnique($data['receive_msg_mobile']);
        $receiveMsgMobileArray = explode(',', $receiveMsgMobileString);
        if (!empty($receiveMsgMobileArray)) {
            foreach ($receiveMsgMobileArray as $key => $receiveMobileItem) {
                $receiveMobileInfo = explode('-', $receiveMobileItem);
                if (empty($receiveMobileInfo[1]) || !is_mobile($receiveMobileInfo[1])) {
                    unset($receiveMsgMobileArray[$key]);
                }
            }
        }
        $data['receive_msg_mobile'] = join(',', $receiveMsgMobileArray);

        // 检查用户是否已存在或者需要去支付开户
        $isPaymentUser = true;
        $userBaseInfo = M('User')->where(array('id'=>$data['id']))->find();
        if ($userBaseInfo && $userBaseInfo['id'] > 0) {
            if (isset($userBaseInfo['payment_user_id']) && empty($userBaseInfo['payment_user_id'])) {
                $isPaymentUser = false;
            }
        }else{
            self::jsonOutput(-1, '企业会员信息不存在');
        }

        try {
                if(!(new UserCouponLevelService())->checkLevelMatchGroupById(intval($data['group_id']),intval($data['new_coupon_level_id']))){
                self::jsonOutput(-1, '会员组和服务等级不匹配');
            }
        } catch (\Exception $e) {
                self::jsonOutput(-1, '会员组和服务等级不匹配');
        }

        // 检查法人邮箱是否存在
        //if (!empty($data['legalbody_email']) && strcmp($userBaseInfo['email'], $data['legalbody_email']) != 0) {
        //    $userBaseInfoTmp = M('User')->where(array('email'=>$data['legalbody_email']))->find();
        //    if ($userBaseInfoTmp && $userBaseInfoTmp['id'] > 0) {
        //        self::jsonOutput(-1, '企业会员邮箱已存在');
        //    }
        //}
        // 检查企业证件号码是否唯一(FIRSTPTOP-4024)
        if (!empty($data['credentials_no']) && strcmp($userBaseInfo['idno'], $data['credentials_no']) != 0) {
            $isIdno = UserModel::instance()->isUserExistsByIdno($data['credentials_no']);
            if ($isIdno) {
                //self::jsonOutput(-1, '企业证件号码已存在');
                \libs\utils\PaymentApi::log('企业证件号已经存在,credentials_no:'.$data['credentials_no']);
            }
        }

        //记录日志
        $enterpriseLogInfo = array(__CLASS__, __FUNCTION__, json_encode($data));
        //开启事务
        $GLOBALS['db']->startTrans();
        try {
            $userData = array(
                'oauth' => true, // oauth数据不做邮箱认证
                'id' => (int)$data['id'], // 用户UID
                'user_type' => UserModel::USER_TYPE_ENTERPRISE, // 用户类型:企业用户
                'real_name' => self::stripString($data['company_name']), // 真实姓名-企业全称
                'group_id' => (int)$data['group_id'], // 会员所属网站:
                'new_coupon_level_id' => (int)$data['new_coupon_level_id'], // 新会员等级
                'is_effect' => (int)$data['is_effect'], // 用户状态
                'id_type' => (int)$data['legalbody_credentials_type'], // 法定代表人证件类型
                'idno' => self::stripString($data['credentials_no']), // 企业证件号码(FIRSTPTOP-4024)
                'user_purpose' => intval($data['company_purpose']),     // 企业会员账户类型
            );

            // 法定代表人邮箱地址
            isset($data['legalbody_email']) && $userData['email'] = self::stripString($data['legalbody_email']);
            // 法定代表人手机号码-国家前缀
            if (!empty($data['legalbody_mobile_code']) && strpos($data['legalbody_mobile_code'], '|') !== false) {
                list($shortCountryCode, $shortCountryNo) = explode('|', $data['legalbody_mobile_code']);
                $userData['country_code'] = $shortCountryCode;
                $userData['mobile_code'] = $shortCountryNo;
                $userData['mobile'] = '';
            }
            // 法定代表人手机号码
//            !empty($data['legalbody_mobile']) && $userData['mobile'] = self::stripString($data['legalbody_mobile']);
            // 用户简介
            isset($data['info']) && $userData['info'] = self::stripString($data['info']);
            // 后台添加默认手机号已认证
            $userData['mobilepassed'] = true;

            // 编辑企业会员敏感信息时，记录管理员操作记录
            $operateLog = $this->_recordEnterptiseOperateLog($data, $userBaseInfo);

            // 更新企业用户信息
            $userInfoRet = save_user($userData, 'UPDATE', 0, true);
            if ($userInfoRet['status'] == 0 || !is_numeric($userInfoRet['data']) || $userInfoRet['data'] <= 0) {
                $errorField = $userInfoRet['data'];
                $errorMsg = '更新企业用户基本信息失败！';
                if ($errorField['error'] == EMPTY_ERROR) {
                    if ($errorField['field_name'] == 'user_name') {
                        $errorMsg = L('USER_NAME_EMPTY_TIP');
                    } else {
                        $errorMsg = sprintf(L('USER_EMPTY_ERROR'), $errorField['field_show_name']);
                    }
                }
                if ($errorField['error'] == FORMAT_ERROR) {
                    if ($errorField['field_name'] == 'mobile') {
                        $errorMsg = L('USER_MOBILE_FORMAT_TIP');
                    }
                }
                if ($errorField['error'] == EXIST_ERROR) {
                    if ($errorField['field_name'] == 'user_name') {
                        $errorMsg = L('USER_NAME_EXIST_TIP');
                    } elseif ($errorField['field_name'] == 'mobile') {
                        $errorMsg = '该手机号已经存在！';
                    }
                }
                if ($errorField['error'] == 'syncfailed') {
                    $this->error('手机号同步修改失败！');
                }
                throw new \Exception($errorMsg);
            }
            // 新生成的用户UID
            $userId = intval($userInfoRet['data']);

            // 更新企业用户-基本信息
            self::_updateUserEnterpriseInfo($userId, $data);

            // 更新企业用户-联系人信息
            self::_updateUserEnterpriseContactInfo($userId, $data);

            // 更新企业用户临时表-审核状态
            if ($isVerify) {
                // 审核通过
                $status = EnterpriseRegisterModel::VERIFY_STATUS_PASS;
            } else {
                // 资料完善
                $status = EnterpriseRegisterModel::VERIFY_STATUS_HAS_INFO;
            }

            self::_updateEnterpriseVerifyStatus($userId, $status, $data['reason']);

            //提交事务
            $commitRet = $GLOBALS['db']->commit();
            // 记录操作数据日志
            Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array("commitRet:{$commitRet}"))));

            if (!$isTradecenter && $isVerify) {
                // 组织数据用于用户开户绑卡-通知支付部门的数据
                $paymentData = self::_getBankOpenAccountData($userId, $data);

                // 调用支付部门的[企业会员注册]接口，并更新用户绑卡状态
                if (empty($paymentData)) {
                    throw new \Exception('-2|企业会员更新数据为空');
                }
                $paymentService = new PaymentService();
                if (!$isPaymentUser) {
                    // 企业用户尚未在支付开户的处理逻辑
                    // 创建或更新银行账户信息
                    self::_getBankAccountInfoForPayment($userId, $data, $paymentData);
                    // 调用支付部门的[企业会员注册]接口
                    $paymentCompanyRet = $paymentService->companyRegister($paymentData);
                }else{
                    // GTM事务管理
                    $supervisionAccountObj = new SupervisionAccountService();
                    $isUcfpayUser = $supervisionAccountObj->isUcfpayUser($userId);
                    $gtm = new GlobalTransactionManager();
                    $gtm->setName('adminDoEditEnterprise');
                    if ($isUcfpayUser) {
                        // 调用支付部门的[企业会员更新]接口
                        $gtm->addEvent(new \core\tmevent\supervision\UcfpayEnterpriseUpdateEvent($paymentData));
                    }
                    // 用户已在存管账户开户
                    $svService = new \core\service\SupervisionService();
                    if ($supervisionAccountObj->isSupervisionUser($userId) || $svService->isUpgradeAccount($userId)) {
                        // 组织数据用于用户开户绑卡-通知存管系统的数据
                        $supervisionPaymentData = $this->_getSupervisionOpenAccountData($userBaseInfo, $data);
                        $gtm->addEvent(new \core\tmevent\supervision\SupervisionEnterpriseUpdateEvent($supervisionPaymentData));
                    }
                    $paymentCompanyRet = $gtm->execute();
                    if (!$paymentCompanyRet) {
                        throw new \Exception('-3|'.$gtm->getError());
                    }
                }

                // 整理操作数据日志
                $enterpriseLogInfo = array_merge($enterpriseLogInfo, array("paymentData:".json_encode($paymentData).",paymentCompanyRet:{$paymentCompanyRet}"));

                if (true !== $paymentCompanyRet) {
                    throw new \Exception('-2|企业会员在支付系统更新失败' . (is_array($paymentCompanyRet) && isset($paymentCompanyRet['respMsg']) ? '-' . $paymentCompanyRet['respMsg'] : ''));
                }

                // 更新用户的支付ID
                $timestamp = get_gmtime();
                $updateUserData = array(
                    'id' => $userId,
                    'payment_user_id' => $userId,
                    'update_time' => $timestamp,
                    'mobilepassed' => 1, //手机认证
                );

                if (isset($data['idcardpassed']) && $data['idcardpassed'] != 1) {
                    $updateUserData['idcardpassed'] = 1; // 身份认证
                    $updateUserData['idcardpassed_time'] = $timestamp; // 身份认证时间
                }
                UserModel::instance()->updateInfo($updateUserData, 'update');

                // 添加用户标签
                $userTagService = new \core\service\UserTagService();
                $userTag = array('REG_Y_'.date('Y'), 'REG_M_'.date('m'), 'USER_TYPE_QY');
                $userTagService->addUserTagsByConstName($userInfoRet['data'], $userTag);
                // 企业型会员绑卡完成后自动增加“存管静态白名单标签”
                $userTagService->addSupervisionStaticWhitelistTag($userId);
	 }

            // 记录admin日志
            save_log('企业会员信息修改，会员id['.$userId.']，会员名称[' . $data['user_name'] . ']' . L('UPDATE_SUCCESS'), 1, $operateLog['oldInfo'], $operateLog['newInfo']);
            self::jsonOutput(1, '【' . $data['user_name'] . '】' . L('UPDATE_SUCCESS'));
        } catch (\Exception $e) {
            $GLOBALS->transTimes > 0 && $GLOBALS['db']->rollback();
            $errorMessage = $e->getMessage();
            Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array('exception:' . $errorMessage))));
            if (false !== strpos($errorMessage, '|')) {
                list($errorCode, $errorMsg) = explode('|', $errorMessage);
            }
            $exCode = isset($errorCode) ? $errorCode : -1;
            $exMsg = isset($errorMsg) ? $errorMsg : $errorMessage;
            self::jsonOutput($exCode, $exMsg);
        }
    }

    /**
     * 创建/编辑银行开户信息
     * @param bool $isVerify 是否需要检查
     * @param bool $onlyUpdateSupervision 是否只更新存管和本地数据
     */
    public function editBankAccount($isVerify, $onlyUpdateSupervision = false) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $errorMsg = array('code' => -1);
            // 用户绑定银行卡的自增ID
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            // 用户UID
            $userId = isset($_POST['userId']) ? (int)$_POST['userId'] : 0;
            // 用户银行卡审核状态已经通过的时候，进行同步
            // 同步修改银行卡信息
            try {
                if (!app_conf('PAYMENT_ENABLE') || !app_conf('PAYMENT_BIND_ENABLE')) {
                    throw new \Exception('支付开关已关闭');
                }
                if ($userId <= 0) {
                    throw new \Exception('用户不存在');
                }

                // 获取用户基本信息
                $userBaseInfo = M('User')->where(array('id'=>$userId, 'is_delete'=>0))->find();
                if (!$userBaseInfo || $userBaseInfo['id'] <= 0) {
                    throw new \Exception('用户无效或不存在');
                }

                // 检查银行卡号是否存在
                //$userCardInfo = UserBankcardModel::instance()->getUserBankCardRow(sprintf('id > 0 AND bankcard=\'%s\'', $_POST['bankcard']));
                //if ($userCardInfo && $userCardInfo['user_id'] != $userId) {
                //    throw new \Exception('该银行帐号已存在！');
                //}

                // 用户绑卡状态
                $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
                // 会员所属网站
                $groupId = isset($userBaseInfo['group_id']) ? (int)$userBaseInfo['group_id'] : (isset($_POST['groupId']) ? (int)$_POST['groupId'] : 0);
                // 检查是新添加银行卡/修改银行卡
                $isNew = $id > 0 ? false : true;

                $userbankArray = array();
                if (!empty($_POST['bank_id']) && strpos($_POST['bank_id'], '|') !== false) {
                    list($shortBankId, $shortBankNo, $shortBankName) = explode('|', $_POST['bank_id']);
                    $shortBankId > 0 && $userbankArray['bank_id'] = (int)$shortBankId;
                }

                if (!empty($_POST['bankcard'])) {
                    $userbankArray['bankcard'] = $_POST['bankcard'];
                } else {
                    $userBankcardInfo = (new UserBankcardService())->getBankcard($userId);
                    $userbankArray['bankcard'] = $userBankcardInfo['bankcard'];
                }

                if (!empty($_POST['bank_bankzone']) && strpos($_POST['bank_bankzone'], '|') !== false) {
                    list($bankZoneId, $bankZoneName) = explode('|', $_POST['bank_bankzone']);
                    $bankZoneName && $userbankArray['bankzone'] = htmlspecialchars($bankZoneName);
                }
                $userbankArray['user_id'] = $userId;
                !empty($_POST['card_name']) && $userbankArray['card_name'] = htmlspecialchars($_POST['card_name']);
                !empty($_POST['bank_region2_lv1']) && $userbankArray['region_lv1'] = (int)$_POST['bank_region2_lv1'];
                !empty($_POST['bank_region2_lv2']) && $userbankArray['region_lv2'] = (int)$_POST['bank_region2_lv2'];
                !empty($_POST['bank_region2_lv3']) && $userbankArray['region_lv3'] = (int)$_POST['bank_region2_lv3'];
                !empty($_POST['bank_region2_lv4']) && $userbankArray['region_lv4'] = (int)$_POST['bank_region2_lv4'];
                !empty($_POST['branch_no']) && $userbankArray['branch_no'] = $_POST['branch_no'];
                $userbankArray['card_type'] = $_POST['card_type'] == 0 ? UserBankcardModel::CARD_TYPE_PERSONAL : UserBankcardModel::CARD_TYPE_BUSINESS;
                // 编辑企业会员绑卡敏感信息时，记录管理员操作记录
                $operateLog = $this->_recordEnterptiseCardOperateLog($userbankArray);

                $errorMsg['msg'] = '银行账户信息更新成功';
                if ($isVerify) {
                    // 旧的绑卡数据
                    $bankcardInfoOld = [];
                    $paymentService = new PaymentService();
                    if (false === $isNew) {
                        $userbankArrayOld = UserBankcardModel::instance()->getNewCardByUserId($userId);
                        $bankcardInfoOld = $paymentService->getBankcardInfo($userbankArrayOld, $isNew, $groupId, $userId);
                    }
                    // 读取银行卡信息，构造银行信息完整信息
                    $bankcardInfo = $paymentService->getBankcardInfo($userbankArray, $isNew, $groupId, $userId);

                    // GTM事务管理
                    $supervisionAccountObj = new SupervisionAccountService();
                    $isUcfpayUser = $supervisionAccountObj->isUcfpayUser($userId);
                    $gtm = new GlobalTransactionManager();
                    $gtm->setName('adminDoEditEnterpriseBank');
                    if ($isUcfpayUser && !$onlyUpdateSupervision) {
                        // 超级账户-企业用户银行卡修改
                        $gtm->addEvent(new \core\tmevent\supervision\UcfpayEnterpriseUpdateBankEvent($userId, $bankcardInfo, $userBaseInfo, $bankcardInfoOld));
                    }
                    // 用户已在存管账户开户或者是预开户用户
                    $svService = new \core\service\SupervisionService();
                    if ($supervisionAccountObj->isSupervisionUser($userId) || $svService->isUpgradeAccount($userId)) {
                        // 组织数据用于用户开户绑卡-通知存管系统的数据
                        $supervisionPaymentData = $this->_getSupervisionBankData($userId, $userbankArray);
                        $cardInfo = [
                            'bank_bankcard' => $supervisionPaymentData['bankCardNo'],
                            'bank_name'     => $supervisionPaymentData['bankName'],
                            'short_name'    => $supervisionPaymentData['bankCode'],
                            'branch_no'     => $supervisionPaymentData['issuer'],
                            'bank_cardname' => $supervisionPaymentData['bankCardName'],
                        ];
                        $gtm->addEvent(new \core\tmevent\supervision\SupervisionUpdateUserBankCardEvent($userId, $cardInfo));
                    }
                    $result = $gtm->execute();
                    if (!$result) {
                        throw new \Exception($gtm->getError());
                    }
                    // 企业型会员绑卡完成后自动增加"存管静态白名单标签"
                    $userTagService = new UserTagService();
                    $userTagService->addSupervisionStaticWhitelistTag($userId);

                    $errorMsg['msg'] = '支付平台绑卡成功';
                }

                $userbankArray['status'] = 1;
                $userbankArray['verify_status'] = 1;
                if ($isNew) {
                    $userbankArray['create_time'] = get_gmtime();
                    UserBankcardModel::instance()->insertCard($userbankArray);
                    $errorMsg['bankLastId'] = UserBankcardModel::instance()->db->insert_id();

                    // 审核银行卡，支出返利
                    $coupon_service = new CouponService();
                    $coupon_service->regRebatePay($userId);
                } else {
                    $errorMsg['code'] = 1;
                    $errorMsg['bankLastId'] = $id;
                    // 更新用户绑卡状态
                    $userbankArray['update_time'] = get_gmtime();
                    UserBankcardModel::instance()->updateCard($id, $userbankArray);
                }

                $errorMsg['code'] = 1;

                // 记录admin日志
                save_log('企业会员绑卡信息修改，会员id['.$userId.']' . L('UPDATE_SUCCESS'), 1, $operateLog['oldUserCardInfo'], $operateLog['newUserCardInfo']);
                // 记录支付日志
                PaymentApi::log(sprintf('[%s] 支付平台绑卡成功,id:%d,userId:%d,POST:%s,isNew:%s,bankcardInfo:%s', __CLASS__.'::'.__FUNCTION__, $id, $userId, json_encode($_POST), $isNew, json_encode($bankcardInfo)));
            }catch(\Exception $e) {
                $errorMsg['code'] = -3;
                $errorMsg['msg'] = $e->getMessage();
                PaymentApi::log(sprintf('[%s] 支付平台绑卡异常,id:%d,userId:%s,POST:%s,isNew:%s,Exception:%s', __CLASS__.'::'.__FUNCTION__, $id, $userId, json_encode($_POST), $isNew, $e->getMessage()));
            }
            self::jsonOutput($errorMsg);
        }

        $userId = (int)$_GET['uid'];
        $source = self::stripString($_GET['s']);
        // 一级地区
        $nRegionLv1 = MI('DeliveryRegion')->where(array('region_level' => 1))->findAll();
        $this->assign('nRegionLv1', $nRegionLv1);
        // 银行卡列表
        $bankList = $GLOBALS['db']->getAll('SELECT * FROM ' . DB_PREFIX . 'bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC');
        $this->assign('bankList', $bankList);

        if ($userId > 0) {
            // 用户绑定银行卡信息
            $bankcardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
            if ($bankcardInfo) {
                foreach ($bankList as $k => $v) {
                    if ($v['id'] == $bankcardInfo['bank_id']) {
                        $bankcardInfo['is_rec'] = $v['is_rec'];
                        break;
                    }
                }
                if ($this->is_cn) {
                    $bankcardInfo['bankcard'] = '';
                }
            }
        }
        !$bankcardInfo && $bankcardInfo = array('status' => 1);
        $this->assign('bankcardInfo', $bankcardInfo);
        $this->assign('userId', $userId);
        $this->assign('s', $source);
        $this->display();
    }


    /**
     * 列表数据的后续处理
     * @see CommonAction::form_index_list()
     */
    protected function form_index_list(&$list) {
        if ($list) {
            $couponLevelService = new \core\service\CouponLevelService();
            $couponService = new CouponService();
            $userTagService = new UserTagService();
            foreach ($list as &$item) {
                $userInfo = MI('User')->where(array('id' => $item['user_id']))->find();
                if (empty($userInfo)) continue;
                $item['id'] = isset($userInfo['id']) ? $userInfo['id'] : 0;
                $item['user_name'] = isset($userInfo['user_name']) ? userNameFormat($userInfo['user_name']) : '';
                $item['real_name'] = isset($userInfo['real_name']) ? $userInfo['real_name'] : '';
                $item['money'] = isset($userInfo['money']) ? $userInfo['money'] : '';
                $item['lock_money'] = isset($userInfo['lock_money']) ? $userInfo['lock_money'] : '';
                $item['idno'] = isset($userInfo['idno']) ? idnoFormat($userInfo['idno']) : '';
                $item['email'] = isset($userInfo['email']) ? adminEmailFormat($userInfo['email']) : '';
                $item['is_effect'] = isset($userInfo['is_effect']) ? $userInfo['is_effect'] : 0;

                // 获取用户绑定的银行卡信息
                $bankcard = MI('UserBankcard')->where('user_id=' . $item['user_id'] . ' ORDER BY id DESC')->find();
                $item['user_bankcard'] = '未验证';
                $item['isbind_bankcard'] = '未开通';
                if ($bankcard && $bankcard['status'] == 1) {
                    $item['user_bankcard'] = formatBankcard($bankcard['bankcard']);
                    $item['isbind_bankcard'] = '已开通';
                }

                // 用户手机
                if(!empty($userInfo['mobile'])){
                    $item['mobile'] = adminMobileFormat($userInfo['mobile']);
                    if (!empty($userInfo['mobile_code']) && $userInfo['mobile_code'] != '86') {
                        $item['mobile'] = $userInfo['mobile_code'] . '-' . $userInfo['mobile'];
                    }
                }

                // 用户所属网站
                $item['group'] = $this->groups[$userInfo['group_id']]['name'];

                // 查询优惠码
                $item['coupon'] = CouponService::userIdToHex($item['id'],$this->groups[$item['group_id']]['prefix']);
                $item['coupon'] =  '<a href="m.php?m=Enterprise&a=index&invite_code=' . $item['coupon'] . '">' . $item['coupon'] . '</a>';

                // 根据会员ID获取会员等级
                $userLevel = $couponLevelService->getUserLevel($userInfo['id']);
                $item['level'] = isset($userLevel['level']) ? $userLevel['level'] : 0;
                $invite_uid = $userInfo['invite_code'] ? CouponService::hexToUserId($userInfo['invite_code']) : 0;
                $item['invite_code'] = "<a href='m.php?m=Enterprise&a=index&user_id={$invite_uid}'>" . $userInfo['invite_code'] . "</a>";
                $item['email'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['email']}</div>";
                $item['login_ip'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$userInfo['login_ip']}</div>";
                $item['user_tag'] = implode('|', array_map(function($val){return $val['tag_name'];}, $userTagService->getTags($userInfo['id'])));
            }
        }
    }

    /**
     * 更新企业用户-基本信息
     */
    protected static function _updateUserEnterpriseInfo($userId, &$data) {
        // 企业基础信息更新
        $enterpriseInfo = EnterpriseModel::instance()->findByViaSlave('user_id=:user_id', '*', array(':user_id' => $userId));
        // 企业会员账户用途
        !empty($data['company_purpose']) && $enterpriseInfo->company_purpose = intval($data['company_purpose']);
        // 其他用途说明
        isset($data['privilege_note']) && $enterpriseInfo->privilege_note = self::stripString($data['privilege_note']);
        // 企业全称
        !empty($data['company_name']) && $enterpriseInfo->company_name = self::stripString(trim($data['company_name']));
        // 企业简称
        !empty($data['company_shortname']) && $enterpriseInfo->company_shortname = self::stripString(trim($data['company_shortname']));
        // 企业证件类别
        !empty($data['credentials_type']) && $enterpriseInfo->credentials_type = (int)$data['credentials_type'];
        // 企业证件号码
        !empty($data['credentials_no']) && $enterpriseInfo->credentials_no = self::stripString($data['credentials_no']);
        // 企业证件有效期-Start
        !empty($data['credentials_expire_date']) && $enterpriseInfo->credentials_expire_date = self::stripString($data['credentials_expire_date']);
        // 企业证件有效期-End
        !empty($data['credentials_expire_at']) && $enterpriseInfo->credentials_expire_at = self::stripString($data['credentials_expire_at']);
        // 企业证件长期有效
        isset($data['is_permanent']) && $enterpriseInfo->is_permanent = intval($data['is_permanent']);
        // 法定代表人姓名
        !empty($data['legalbody_name']) && $enterpriseInfo->legalbody_name = self::stripString(trim($data['legalbody_name']));
        // 法定代表人证件类别
        !empty($data['legalbody_credentials_type']) && $enterpriseInfo->legalbody_credentials_type = (int)$data['legalbody_credentials_type'];
        // 法定代表人证件号码
        !empty($data['legalbody_credentials_no']) && $enterpriseInfo->legalbody_credentials_no = self::stripString($data['legalbody_credentials_no']);
        // 法定代表人手机号码-国家前缀
        if (!empty($data['legalbody_mobile_code']) && strpos($data['legalbody_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['legalbody_mobile_code']);
            $enterpriseInfo->legalbody_mobile_code = $shortCountryNo;
        }
        // 法定代表人手机号码
        !empty($data['legalbody_mobile']) && $enterpriseInfo->legalbody_mobile = self::stripString($data['legalbody_mobile']);
        // 法定代表人邮箱地址
        isset($data['legalbody_email']) && $enterpriseInfo->legalbody_email = self::stripString($data['legalbody_email']);
        //企业注册资金
        !empty($data['reg_amt']) && $enterpriseInfo->reg_amt = floatval($data['reg_amt']);
        //企业行业类别
        !empty($data['indu_cate']) && $enterpriseInfo->indu_cate = (int)$data['indu_cate'];
        //企业开户许可证核准号
        !empty($data['app_no']) && $enterpriseInfo->app_no = self::stripString($data['app_no']);
        $registrationRegion = array();
        // 企业注册地址-国家
        isset($data['registration_region_lv1']) && $registrationRegion[] = (int)$data['registration_region_lv1'];
        // 企业注册地址-省份
        isset($data['registration_region_lv2']) && $registrationRegion[] = (int)$data['registration_region_lv2'];
        // 企业注册地址-城市
        isset($data['registration_region_lv3']) && $registrationRegion[] = (int)$data['registration_region_lv3'];
        // 企业注册地址-地区
        isset($data['registration_region_lv4']) && $registrationRegion[] = (int)$data['registration_region_lv4'];
        // 企业注册地址-整理
        !empty($registrationRegion) && $enterpriseInfo->registration_region = join(',', $registrationRegion);
        // 企业联系地址
        isset($data['registration_address']) && $enterpriseInfo->registration_address = self::stripString($data['registration_address']);
        $contractRegion = array();
        // 企业联系地址-国家
        isset($data['contract_region_lv1']) && $contractRegion[] = (int)$data['contract_region_lv1'];
        // 企业联系地址-省份
        isset($data['contract_region_lv2']) && $contractRegion[] = (int)$data['contract_region_lv2'];
        // 企业联系地址-城市
        isset($data['contract_region_lv3']) && $contractRegion[] = (int)$data['contract_region_lv3'];
        // 企业联系地址-地区
        isset($data['contract_region_lv4']) && $contractRegion[] = (int)$data['contract_region_lv4'];
        // 企业联系地址-整理
        !empty($registrationRegion) && $enterpriseInfo->contract_region = join(',', $contractRegion);
        // 企业联系地址
        isset($data['contract_address']) && $enterpriseInfo->contract_address = self::stripString($data['contract_address']);
        // 备注
        isset($data['memo']) && $enterpriseInfo->memo = self::stripString($data['memo']);
        // 企业会员标识
        isset($data['identifier']) && $enterpriseInfo->identifier = self::stripString($data['identifier']);
        // 创建时间
        $enterpriseInfo->update_time = get_gmtime();
        $enterRet = $enterpriseInfo->save();
        if (!$enterRet) {
            throw new \Exception('更新企业用户基本信息失败!');
        }
    }

    /**
     * 更新企业用户-联系人信息
     */
    protected static function _updateUserEnterpriseContactInfo($userId, &$data) {
        // 企业联系人信息更新
        $enterpriseContactInfo = EnterpriseContactModel::instance()->findByViaSlave('user_id=:user_id', '*', array(':user_id' => $userId));
        // 企业账户负责人姓名
        !empty($data['major_name']) && $enterpriseContactInfo->major_name = self::stripString($data['major_name']);
        // 企业账户负责人证件类别
        !empty($data['major_condentials_type']) && $enterpriseContactInfo->major_condentials_type = (int)$data['major_condentials_type'];
        // 企业账户负责人证件号码
        !empty($data['major_condentials_no']) && $enterpriseContactInfo->major_condentials_no = self::stripString($data['major_condentials_no']);
        // 企业账户负责人手机号码-国家前缀
        if (!empty($data['major_mobile_code']) && strpos($data['major_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['major_mobile_code']);
            $enterpriseContactInfo->major_mobile_code = $shortCountryNo;
            $data['payment_major_mobile_code'] = $shortCountryNo;
        }
        // 企业账户负责人手机号码
        !empty($data['major_mobile']) && $enterpriseContactInfo->major_mobile = self::stripString($data['major_mobile']);
        // 企业账户负责人邮箱地址
        isset($data['major_email']) && $enterpriseContactInfo->major_email = self::stripString($data['major_email']);
        $majorContractRegion = array();
        // 企业联系地址-国家
        isset($data['major_contract_region_lv1']) && $majorContractRegion[] = (int)$data['major_contract_region_lv1'];
        // 企业联系地址-省份
        isset($data['major_contract_region_lv2']) && $majorContractRegion[] = (int)$data['major_contract_region_lv2'];
        // 企业联系地址-城市
        isset($data['major_contract_region_lv3']) && $majorContractRegion[] = (int)$data['major_contract_region_lv3'];
        // 企业联系地址-地区
        isset($data['major_contract_region_lv4']) && $majorContractRegion[] = (int)$data['major_contract_region_lv4'];
        // 企业联系地址-整理
        !empty($majorContractRegion) && $enterpriseContactInfo->major_contract_region = join(',', $majorContractRegion);
        // 企业账户负责人联系地址
        isset($data['major_contract_address']) && $enterpriseContactInfo->major_contract_address = self::stripString($data['major_contract_address']);
        // 企业联系人2姓名
        isset($data['contract_name']) && $enterpriseContactInfo->contract_name = self::stripString($data['contract_name']);
        // 企业联系人2手机号码-国家前缀
        if (!empty($data['contract_mobile_code']) && strpos($data['contract_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['contract_mobile_code']);
            $enterpriseContactInfo->contract_mobile_code = $shortCountryNo;
        }
        // 企业联系人2手机号码
        isset($data['contract_mobile']) && $enterpriseContactInfo->contract_mobile = self::stripString($data['contract_mobile']);

        // 企业联络人手机号码-国家前缀
        if (!empty($data['consignee_phone_code']) && strpos($data['consignee_phone_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['consignee_phone_code']);
            $enterpriseContactInfo->consignee_phone_code = $shortCountryNo;
        }
        // 企业联络人手机号码
        isset($data['consignee_phone']) && $enterpriseContactInfo->consignee_phone = self::stripString($data['consignee_phone']);

        // 接收短信通知号码
        if (!empty($data['receive_msg_mobile'])) {
            $receiveMsgMobile = self::_receiveUnique($data['receive_msg_mobile']);
            $enterpriseContactInfo->receive_msg_mobile = self::stripString($receiveMsgMobile);
        }
        // 推荐人姓名
        isset($data['inviter_name']) && $enterpriseContactInfo->inviter_name = self::stripString($data['inviter_name']);
        //邀请人所在机构
        isset($data['inviter_organization']) && $enterpriseContactInfo->inviter_organization = self::stripString($data['inviter_organization']);
        // 推荐人手机号码-国家前缀
        if (!empty($data['inviter_country_code']) && strpos($data['inviter_country_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['inviter_country_code']);
            $enterpriseContactInfo->inviter_country_code = $shortCountryNo;
        }
        // 推荐人手机号码
        isset($data['inviter_phone']) && $enterpriseContactInfo->inviter_phone = self::stripString($data['inviter_phone']);
        // 经办人姓名
        isset($data['employee_name']) && $enterpriseContactInfo->employee_name = self::stripString($data['employee_name']);
        // 经办人手机号码-国家前缀
        if (!empty($data['employee_mobile_code']) && strpos($data['employee_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['employee_mobile_code']);
            $enterpriseContactInfo->employee_mobile_code = $shortCountryNo;
        }
        // 经办人手机号码
        isset($data['employee_mobile']) && $enterpriseContactInfo->employee_mobile = self::stripString($data['employee_mobile']);
        // 经办人所属机构
        isset($data['employee_department']) && $enterpriseContactInfo->employee_department = self::stripString($data['employee_department']);

        // 创建时间
        $enterpriseContactInfo->update_time = get_gmtime();
        $enterContractRet = $enterpriseContactInfo->save();
        if (!$enterContractRet) {
            throw new \Exception('更新企业用户联系人信息失败!');
        }
    }


    /**
     * 获取用户银行账户信息，组织支付数据
     * @param int $userId
     * @param array $data
     * @param array $paymentData
     */
    protected static function _getBankAccountInfoForPayment($userId, &$data, &$paymentData) {
        $userBankInfo = M('UserBankcard')->where(sprintf('user_id=%d AND bankcard=\'%s\'', $userId, $data['bankcard']))->find();
        if ($userBankInfo['bank_id'] > 0 && check_empty($userBankInfo['card_name']) && check_empty($userBankInfo['bankcard'])
            && $userBankInfo['region_lv2'] > 0 && $userBankInfo['region_lv3'] > 0
            && check_empty($userBankInfo['bankzone']) && check_empty($userBankInfo['branch_no'])) {
            // 获取银行名称等信息
            $bankInfo = M('Bank')->where('id=' . $userBankInfo['bank_id'])->find();
            if (check_empty($bankInfo['name']) && check_empty($bankInfo['short_name'])) {
                // 根据地区ID获取地区名称
                $deliverRegionService = new DeliveryRegionService();
                $provinceRegion = $deliverRegionService->getRegion($userBankInfo['region_lv2']);
                !empty($provinceRegion['name']) && $provinceName = $provinceRegion['name'];
                $cityRegion = $deliverRegionService->getRegion($userBankInfo['region_lv3']);
                !empty($cityRegion['name']) && $cityName = $cityRegion['name'];

                // 组织数据用于用户开户绑卡-通知支付部门的数据
                $paymentData['bankName'] = $bankInfo['name']; //银行名称-开户行名称
                $paymentData['bankCode'] = $bankInfo['short_name']; //银行编码-开户行简码
                $paymentData['bankCardNo'] = $userBankInfo['bankcard']; //银行账户-银行帐号
                $paymentData['bankCardName'] = $userBankInfo['card_name']; //银行开户名-开户名
                $paymentData['bankProvince'] = isset($provinceName) && !empty($provinceName) ? $provinceName : ''; //省(账户)
                $paymentData['bankCity'] = isset($cityName) && !empty($cityName) ? $cityName : ''; //市(账户)
                $paymentData['issuerName'] = $userBankInfo['bankzone']; //支行名称
                $paymentData['issuer'] = $userBankInfo['branch_no']; //支行-联行号码
            }
        }
    }

    /**
     * 组织数据用于用户开户绑卡-通知支付部门的数据
     */
    protected static function _getBankOpenAccountData($userId, &$data) {
        if ($userId <= 0) return array();
        $paymentData = array(
            'userId' => $userId, // P2P用户ID-Y
            'enterpriseFullName' => $data['company_name'], // 企业全称-Y
            'enterpriseShortName' => $data['company_shortname'], // 企业简称-Y
            'certType' => isset(UserModel::$credentialsType[$data['credentials_type']]) ? UserModel::$credentialsType[$data['credentials_type']] : UserModel::$credentialsType['default'], // 营业执照-Y
            'businessLicense' => $data['credentials_no'], // 营业执照号-Y
            'certValidBeginDate' => $data['credentials_expire_date'], // 证件有效期开始
            'certValidEndDate' => $data['credentials_expire_at'], // 证件到期时间
            'province' => !empty($data['input_contract_region_name2']) ? $data['input_contract_region_name2'] : '', // 省-企业联系地址
            'city' => !empty($data['input_contract_region_name3']) ? $data['input_contract_region_name3'] : '', // 市-企业联系地址
            'area' => !empty($data['input_contract_region_name4']) ? $data['input_contract_region_name4'] : '', // 区-企业联系地址
            'address' => !empty($data['contract_address']) ? $data['contract_address'] : '', // 地址-企业联系地址
            'agentPersonPhone' => !empty($data['major_mobile']) ? $data['major_mobile'] : '', // 联系手机（原代理人手机号码）-Y-企业账户负责人手机号码
            'agentPersonName' => !empty($data['major_name']) ? $data['major_name'] : '', // 联系人姓名（原代理人姓名）-企业账户负责人姓名
            'agentPersonNo' => !empty($data['major_condentials_no']) ? $data['major_condentials_no'] : '', // 联系人证件号-企业账户负责人证件号码
            'agentPersonEmail' => !empty($data['major_email']) ? $data['major_email'] : '', // 联系人邮箱-企业账户负责人邮箱地址
            'coperation' => !empty($data['legalbody_name']) ? $data['legalbody_name'] : '', // 法人姓名
            'coperationCardType' => isset(UserModel::$idCardType[$data['legalbody_credentials_type']]) ? UserModel::$idCardType[$data['legalbody_credentials_type']] : UserModel::$idCardType['default'], // 法人证件类型
            'coperationCard' => !empty($data['legalbody_credentials_no']) ? $data['legalbody_credentials_no'] : '', // 法人证件
            'coperationCell' => !empty($data['legalbody_mobile']) ? $data['legalbody_mobile'] : '', // 法人手机号
            'coperationEmail' => !empty($data['legalbody_email']) ? $data['legalbody_email'] : '', // 法人邮箱
        );
        // 联系手机区域码-Y-企业账户负责人手机号码前缀
        if (isset($data['payment_major_mobile_code'])) {
            $paymentData['cellRgncode'] = $data['payment_major_mobile_code'];
        }else if (!empty($data['major_mobile_code']) && strpos($data['major_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['major_mobile_code']);
            $paymentData['cellRgncode'] = $shortCountryNo;
        }
        return $paymentData;
    }

    /**
     * 过滤字符串
     */
    protected static function stripString($val) {
        return (MAGIC_QUOTES_GPC && is_string($val)) ? stripslashes($val) : $val;
    }

    /**
     * 弹出提示框
     *
     * @param int $message 消息内容
     * @param string $url 要重定向的 url
     */
    protected static function jsAlert($message, $url = '', $initHtml = '') {
        $out = '<script language="JavaScript" type="text/javascript">';
        $out .= "alert('{$message}');";
        $url && $out .= "document.location='{$url}';";
        $initHtml && $out .= "{$initHtml}";
        $out .= '</script>';
        echo $out;
        exit;
    }

    /**
     * Json输出
     * @param int $code
     * @param string $msg
     */
    public static function jsonOutput($code, $msg = '', $data = array()) {
        echo (is_array($code) && !empty($code)) ? json_encode($code) : json_encode(self::_genErrorMsg($code, $msg, $data));
        exit;
    }

    /**
     * 组建错误消息
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected static function _genErrorMsg($code, $msg, $data = array()) {
        return array(
            'request' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REDIRECT_URL'] :
                (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
            'code' => $code,
            'msg' => $msg
        ) + (is_array($data) && !empty($data) ? $data : array());
    }

    /**
     * 根据银行支行列表,生成Html代码
     * @param $list
     * @param $name
     */
    protected static function _toBankListHtml($list, $name = '', $isJson = false, $isSupply = false, $disableSelect = false, $inputId = 0, $bankZoneSelect = 1) {
        $result = array('bankListHtml'=>'', 'bankNoHtml'=>'');
        $disableInputCss = $disableSelect ? ' readonly="readonly" ' : '';
        $disableSelectCss = $disableSelect ? ' disabled="disabled" ' : '';
        if(!$list) {
            if ($isSupply) {
                $result['bankListHtml'] =  $isJson ? "<input name='bank_bankzone' value='".$name."' class='textbox _js_bankinfo' {$disableInputCss}/>" : "<input class='idbox w315' name='bankzone' value='".$name." 'class='textbox _js_bankinfo' />";
            }
            return $result;
        }

        $_inputHiddenPrefix = $inputId > 0 ? 'bank_bankzone_value' . $inputId : 'bank_bankzone_value';
        $_selectIdPrefix = $inputId > 0 ? '_js_bankone' . $inputId : '_js_bankone';
        $_selectNamePrefix = $inputId > 0 ? 'bank_bankzone' . $inputId : 'bank_bankzone';
        $result['bankListHtml'] = '<input type="hidden" id="'.$_inputHiddenPrefix.'" name="'.$_inputHiddenPrefix.'" value="">';
        if ($bankZoneSelect) {
            if ($isJson) {
                $result['bankListHtml'] .= "<select id='{$_selectIdPrefix}' class='textbox' name='{$_selectNamePrefix}' {$disableSelectCss}>";
            }else{
                $result['bankListHtml'] .= "<select id='{$_selectIdPrefix}' class='select_box w323 textbox' name='{$_selectNamePrefix}' {$disableSelectCss}>";
            }
        }
        foreach($list as $k => $v) {
            $selected = !empty($name) && $name == $v['name'] ? 'selected' : '';
            $result['bankListHtml'] .= "<option value='" . $v['bank_id'].'|'.$v['name']."' {$selected}>{$v['name']}</option>";
        }
        $result['bankListHtml'] . '</select>' . ($isSupply ? "<a href='javascript:;' id='_js_shoudong' class='f14 pl5 pr10'>手动填写</a><input id='_js_shoudong_input' style='display:none;' class='idbox w255' name='bankzone_1'>" : '');
        return $result;
    }

    /**
     * 检查字符串是否符合中文等规则
     * @param string $string
     */
    protected static function _checkChinese($string, $rex = '\（\）') {
        if (preg_match("/^[\x{4e00}-\x{9fa5}{$rex}]+$/u", $string)) {
            return true;
        }
        return false;
    }

    /**
     * 编辑企业会员敏感信息时，记录管理员操作记录
     * @param int $userId
     * @param array $request
     */
    protected function _recordEnterptiseOperateLog($request, $userBaseInfo = array()) {
        $oldInfo = $newInfo = array();
        if (empty($userBaseInfo)) {
            return array('oldInfo'=>$oldInfo, 'newInfo'=>$newInfo);
        }
        // 记录更新前后，企业会员信息的变化
        // 法定代表人邮箱地址-邮箱
        if (isset($request['legalbody_email']) && strcmp($request['legalbody_email'], $userBaseInfo['email']) !== 0) {
            $oldInfo['email'] = $userBaseInfo['email'];
            $newInfo['email'] = $request['legalbody_email'];
        }
        // 法定代表人手机号码-手机号
        if (isset($request['legalbody_mobile']) && strcmp($request['legalbody_mobile'], $userBaseInfo['mobile']) !== 0) {
            $oldInfo['mobile'] = $userBaseInfo['mobile'];
            $newInfo['mobile'] = $request['legalbody_mobile'];
        }
        // 企业全称-真实姓名
        if (isset($request['company_name']) && strcmp($request['company_name'], $userBaseInfo['real_name']) !== 0) {
            $oldInfo['real_name'] = $userBaseInfo['real_name'];
            $newInfo['real_name'] = $request['company_name'];
        }
        // 企业证件号码-身份证号
        if (isset($request['credentials_no']) && strcmp($request['credentials_no'], $userBaseInfo['idno']) !== 0) {
            $oldInfo['idno'] = $userBaseInfo['idno'];
            $newInfo['idno'] = $request['credentials_no'];
        }
        // 会员所属网站
        if (isset($request['group_id']) && strcmp($request['group_id'], $userBaseInfo['group_id']) !== 0) {
            $oldInfo['group_id'] = $userBaseInfo['group_id'];
            $newInfo['group_id'] = $request['group_id'];
        }
        // -----------------------企业信息变化---------------------------
        // 获取企业会员信息
        $userEnterpriseBaseInfo = M('Enterprise')->where(array('user_id'=>intval($request['id'])))->find();
        if (empty($userEnterpriseBaseInfo)) {
            return array('oldInfo'=>$oldInfo, 'newInfo'=>$newInfo);
        }
        // 企业全称
        if (isset($request['company_name']) && strcmp($request['company_name'], $userEnterpriseBaseInfo['company_name']) !== 0) {
            $oldInfo['company_name'] = $userEnterpriseBaseInfo['company_name'];
            $newInfo['company_name'] = $request['company_name'];
        }
        // 企业简称
        if (isset($request['company_shortname']) && strcmp($request['company_shortname'], $userEnterpriseBaseInfo['company_shortname']) !== 0) {
            $oldInfo['company_shortname'] = $userEnterpriseBaseInfo['company_shortname'];
            $newInfo['company_shortname'] = $request['company_shortname'];
        }
        // 企业证件类别
        if (isset($request['credentials_type']) && strcmp($request['credentials_type'], $userEnterpriseBaseInfo['credentials_type']) !== 0) {
            $oldInfo['credentials_type'] = $userEnterpriseBaseInfo['credentials_type'];
            $newInfo['credentials_type'] = $request['credentials_type'];
        }
        // 企业证件号码
        if (isset($request['credentials_no']) && strcmp($request['credentials_no'], $userEnterpriseBaseInfo['credentials_no']) !== 0) {
            $oldInfo['credentials_no'] = $userEnterpriseBaseInfo['credentials_no'];
            $newInfo['credentials_no'] = $request['credentials_no'];
        }
        // 法定代表人姓名
        if (isset($request['legalbody_name']) && strcmp($request['legalbody_name'], $userEnterpriseBaseInfo['legalbody_name']) !== 0) {
            $oldInfo['legalbody_name'] = $userEnterpriseBaseInfo['legalbody_name'];
            $newInfo['legalbody_name'] = $request['legalbody_name'];
        }
        // 法定代表人证件号码
        if (isset($request['legalbody_credentials_no']) && strcmp($request['legalbody_credentials_no'], $userEnterpriseBaseInfo['legalbody_credentials_no']) !== 0) {
            $oldInfo['legalbody_credentials_no'] = $userEnterpriseBaseInfo['legalbody_credentials_no'];
            $newInfo['legalbody_credentials_no'] = $request['legalbody_credentials_no'];
        }
        // 法定代表人手机号码
        if (isset($request['legalbody_mobile']) && strcmp($request['legalbody_mobile'], $userEnterpriseBaseInfo['legalbody_mobile']) !== 0) {
            $oldInfo['legalbody_mobile'] = $userEnterpriseBaseInfo['legalbody_mobile'];
            $newInfo['legalbody_mobile'] = $request['legalbody_mobile'];
        }
        // 法定代表人邮箱地址
        if (isset($request['legalbody_email']) && strcmp($request['legalbody_email'], $userEnterpriseBaseInfo['legalbody_email']) !== 0) {
            $oldInfo['legalbody_email'] = $userEnterpriseBaseInfo['legalbody_email'];
            $newInfo['legalbody_email'] = $request['legalbody_email'];
        }
        // 接收短信通知号码
        if (isset($request['receive_msg_mobile']) && strcmp($request['receive_msg_mobile'], $userEnterpriseBaseInfo['receive_msg_mobile']) !== 0) {
            $oldInfo['receive_msg_mobile'] = $userEnterpriseBaseInfo['receive_msg_mobile'];
            $newInfo['receive_msg_mobile'] = $request['receive_msg_mobile'];
        }
        return array('oldInfo'=>$oldInfo, 'newInfo'=>$newInfo);
    }

    /**
     * 编辑企业会员绑卡敏感信息时，记录管理员操作记录
     * @param int $userId
     * @param array $request
     */
    protected function _recordEnterptiseCardOperateLog($request) {
        $oldUserCardInfo = $newUserCardInfo = array();
        // 获取个人会员绑卡信息
        $userBankcardService = new UserBankcardService();
        $userBankCardInfo = $userBankcardService->getBankcard($request['user_id']);
        if (empty($userBankCardInfo)) {
            return array('oldUserCardInfo'=>$oldUserCardInfo, 'newUserCardInfo'=>$newUserCardInfo);
        }
        // 记录更新前后，用户绑卡信息的变化
        // 银行编号
        if (isset($request['bank_id']) && strcmp($request['bank_id'], $userBankCardInfo['bank_id']) !== 0) {
            $oldUserCardInfo['bank_id'] = $userBankCardInfo['bank_id'];
            $newUserCardInfo['bank_id'] = $request['bank_id'];
        }
        // 银行卡号
        if (isset($request['bankcard']) && strcmp($request['bankcard'], $userBankCardInfo['bankcard']) !== 0) {
            $oldUserCardInfo['bankcard'] = $userBankCardInfo['bankcard'];
            $newUserCardInfo['bankcard'] = $request['bankcard'];
        }
        // 开户网点
        if (isset($request['bankzone']) && strcmp($request['bankzone'], $userBankCardInfo['bankzone']) !== 0) {
            $oldUserCardInfo['bankzone'] = $userBankCardInfo['bankzone'];
            $newUserCardInfo['bankzone'] = $request['bankzone'];
        }
        // 开户名
        if (isset($request['card_name']) && strcmp($request['card_name'], $userBankCardInfo['card_name']) !== 0) {
            $oldUserCardInfo['card_name'] = $userBankCardInfo['card_name'];
            $newUserCardInfo['card_name'] = $request['card_name'];
        }
        // 联行号码
        if (isset($request['branch_no']) && strcmp($request['branch_no'], $userBankCardInfo['branch_no']) !== 0) {
            $oldUserCardInfo['branch_no'] = $userBankCardInfo['branch_no'];
            $newUserCardInfo['branch_no'] = $request['branch_no'];
        }
        // 开户行所在地-国家
        if (isset($request['region_lv1']) && strcmp($request['region_lv1'], $userBankCardInfo['region_lv1']) !== 0) {
            $oldUserCardInfo['region_lv1'] = $userBankCardInfo['region_lv1'];
            $newUserCardInfo['region_lv1'] = $request['region_lv1'];
        }
        // 开户行所在地-省
        if (isset($request['region_lv2']) && strcmp($request['region_lv2'], $userBankCardInfo['region_lv2']) !== 0) {
            $oldUserCardInfo['region_lv2'] = $userBankCardInfo['region_lv2'];
            $newUserCardInfo['region_lv2'] = $request['region_lv2'];
        }
        // 开户行所在地-市
        if (isset($request['region_lv3']) && strcmp($request['region_lv3'], $userBankCardInfo['region_lv3']) !== 0) {
            $oldUserCardInfo['region_lv3'] = $userBankCardInfo['region_lv3'];
            $newUserCardInfo['region_lv3'] = $request['region_lv3'];
        }
        // 开户行所在地-区县
        if (isset($request['region_lv4']) && strcmp($request['region_lv4'], $userBankCardInfo['region_lv4']) !== 0) {
            $oldUserCardInfo['region_lv4'] = $userBankCardInfo['region_lv4'];
            $newUserCardInfo['region_lv4'] = $request['region_lv4'];
        }
        // 绑卡状态
        if (isset($request['status']) && strcmp($request['status'], $userBankCardInfo['status']) !== 0) {
            $oldUserCardInfo['status'] = $userBankCardInfo['status'];
            $newUserCardInfo['status'] = $request['status'];
        }
        // 验卡状态
        if (isset($request['verify_status']) && strcmp($request['verify_status'], $userBankCardInfo['verify_status']) !== 0) {
            $oldUserCardInfo['verify_status'] = $userBankCardInfo['verify_status'];
            $newUserCardInfo['verify_status'] = $request['verify_status'];
        }
        return array('oldUserCardInfo'=>$oldUserCardInfo, 'newUserCardInfo'=>$newUserCardInfo);
    }

    /**
     * 对企业用户接收短信通知号码进行去重过滤等
     * @param string $receive_mobile
     */
    protected static function _receiveUnique($receive_mobile) {
        $tmp = explode(',', trim($receive_mobile, ','));
        $tmpUnique = array_unique($tmp);
        foreach ($tmpUnique as $key => $value) {
            if (empty($value)) {
                unset($tmpUnique[$key]);
                continue;
            }
        }
        return join(',', $tmpUnique);
    }

    /**
     * 更新企业用户临时表verfify_status
     * @param $userId
     * @return bool
     */
    protected static function _updateEnterpriseVerifyStatus($userId, $status, $reason = '') {
        $params = ['verify_status' => $status, 'verify_remark' => $reason];
        return $update = (new EnterpriseRegisterModel())->updateVerifyStatus($userId, $params);
    }

    /**
     * 组织数据用于用户开户绑卡-通知存管系统的数据
     */
    protected function _getSupervisionOpenAccountData($userBaseInfo, &$data) {
        $userId = isset($userBaseInfo['id']) ? (int)$userBaseInfo['id'] : 0;
        if ($userId <= 0) return array();
        // 用户的账户类型
        $userObj = new \core\service\UserService($userBaseInfo);
        $userPurposeInfo = $userObj->getUserPurposeInfo($userBaseInfo['user_purpose']);
        $bizType = !empty($userPurposeInfo['supervisionBizType']) ? $userPurposeInfo['supervisionBizType'] : '06';
        $paymentData = array(
            'userId' => $userId, // P2P用户ID-Y
            'bizType' => $bizType, // 业务类型
            'enterpriseFullName' => $data['company_name'], // 企业全称
            'enterpriseShortName' => $data['company_shortname'], // 企业简称
            'businessLicenseType' => isset(UserModel::$credentialsType[$data['credentials_type']]) ? UserModel::$credentialsType[$data['credentials_type']] : UserModel::$credentialsType['default'], // 营业执照
            'businessLicense' => $data['credentials_no'], // 营业执照号
            'certValidBeginDate' => $data['credentials_expire_date'], // 证件有效期开始
            'certValidEndDate' => $data['credentials_expire_at'], // 证件到期时间
        );
        !empty($data['input_contract_region_name2']) && $paymentData['province'] = $data['input_contract_region_name2']; // 省-企业联系地址
        !empty($data['input_contract_region_name3']) && $paymentData['city'] = $data['input_contract_region_name3']; // 市-企业联系地址
        !empty($data['input_contract_region_name4']) && $paymentData['area'] = $data['input_contract_region_name4']; // 区-企业联系地址
        !empty($data['contract_address']) && $paymentData['address'] = $data['contract_address']; // 地址-企业联系地址
        !empty($data['major_mobile']) && $paymentData['agentPersonPhone'] = $data['major_mobile']; // 联系手机（原代理人手机号码）-企业账户负责人手机号码
        !empty($data['major_name']) && $paymentData['agentPersonName'] = $data['major_name']; // 联系人姓名（原代理人姓名）-企业账户负责人姓名
        !empty($data['major_condentials_type']) && $paymentData['agentPersonCertType'] = isset(UserModel::$idCardType[$data['major_condentials_type']]) ? UserModel::$idCardType[$data['major_condentials_type']] : UserModel::$idCardType['default']; // 联系人证件类型-企业账户负责人证件类型
        !empty($data['major_condentials_no']) && $paymentData['agentPersonCertNo'] = $data['major_condentials_no']; // 联系人证件号-企业账户负责人证件号码
        !empty($data['major_email']) && $paymentData['agentPersonEmail'] = $data['major_email']; // 联系人邮箱-企业账户负责人邮箱地址
        !empty($data['legalbody_name']) && $paymentData['corporationName'] = $data['legalbody_name']; // 法人姓名
        !empty($data['legalbody_credentials_type']) && $paymentData['corporationCertType'] = isset(UserModel::$idCardType[$data['legalbody_credentials_type']]) ? UserModel::$idCardType[$data['legalbody_credentials_type']] : UserModel::$idCardType['default']; // 法人证件类型
        !empty($data['legalbody_credentials_no']) && $paymentData['corporationCertNo'] = $data['legalbody_credentials_no']; // 法人证件
        !empty($data['legalbody_mobile']) && $paymentData['corporationCell'] = $data['legalbody_mobile']; // 法人手机号
        !empty($data['legalbody_email']) && $paymentData['corporationEmail'] = $data['legalbody_email']; // 法人邮箱
        return $paymentData;
    }

    /**
     * 组织数据用于用户开户绑卡-通知存管系统的数据
     */
    protected function _getSupervisionBankData($userId, &$data) {
        if ($userId <= 0) return array();
        // 查询银行信息
        $bankService = new BankService();
        $bankInfo = $bankService->getBank($data['bank_id']);
        if (empty($bankInfo['id'])) {
            throw new \Exception('查询银行信息失败');
        }
        $paymentData = [];
        $paymentData['userId'] = $userId;
        !empty($bankInfo['name']) && $paymentData['bankName'] = $bankInfo['name']; // 银行名称
        !empty($bankInfo['short_name']) && $paymentData['bankCode'] = $bankInfo['short_name']; // 银行编码
        !empty($data['bankcard']) && $paymentData['bankCardNo'] = $data['bankcard']; // 银行卡号
        !empty($data['card_name']) && $paymentData['bankCardName'] = $data['card_name']; // 银行开户名
        !empty($data['bankzone']) && $paymentData['issuerName'] = $data['bankzone']; // 支行名称
        !empty($data['branch_no']) && $paymentData['issuer'] = $data['branch_no']; // 支行-联行号
        // 省（账户）
        $deliverRegionService = new DeliveryRegionService();
        if (!empty($data['region_lv2'])) {
            $provinceRegion = $deliverRegionService->getRegion($data['region_lv2']);
            if (!empty($provinceRegion['name'])) {
                $paymentData['bankProvince'] = $provinceRegion['name'];
            }
        }
        // 市（账户）
        if (!empty($data['region_lv3'])) {
            $cityRegion = $deliverRegionService->getRegion($data['region_lv3']);
            if (!empty($cityRegion['name'])) {
                $paymentData['bankCity'] = $cityRegion['name'];
            }
        }
        return $paymentData;
    }

    /**
     * 匹配数字和横线
     * 只做展示 不参与逻辑 验证宽泛
     * @jira FIRSTPTOP-6291
     */
    protected function isEnterpriseTel($phone) {
        return preg_match("/[0-9\-]{8,13}$/", $phone);
    }
}
