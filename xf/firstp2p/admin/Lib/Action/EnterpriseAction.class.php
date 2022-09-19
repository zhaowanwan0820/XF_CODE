<?php

// +----------------------------------------------------------------------
// | 管理后台-企业用户相关
// +----------------------------------------------------------------------
// | Author: guofeng
// +----------------------------------------------------------------------
FP::import("libs.libs.msgcenter");

use core\service\user\BOBase;
use core\service\CouponService;
use core\service\UserTagService;
use core\service\BanklistService;
use core\service\UserBankcardService;
use core\service\PaymentService;
use core\service\EnterpriseService;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseContactModel;
use core\dao\EnterpriseRegisterModel;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\UserThirdBalanceService AS UserAccountService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use core\service\UserCouponLevelService;
use core\service\ncfph\AccountService as PhAccountService;
use libs\db\Db;
use core\dao\BankModel;

require_once __DIR__.'/EnterpriseBase.class.php';

class EnterpriseAction extends EnterpriseBase {
    /**
     * 用户组列表
     */
    protected $groups = array();

    public function __construct() {
        parent::__construct();
    }

    /**
     * 用户管理-企业会员列表
     * @see CommonAction::index()
     */
    public function index() {
        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);
        // 会员所属网站
        $this->groups = \core\dao\UserGroupModel::instance()->getGroups();

        // 获取会员列表
        $name = $this->getActionName();
        $list = $this->_list(DI($name), $map);
//        $enterpriseRegisterModel = new EnterpriseRegisterModel();
        foreach ($list as $key=>$item) {
            $userId = intval($item['user_id']);
            // 你看不见我
            if ($this->is_cn && in_array($userId, [8118934,7963653])) {
                unset($list[$key]);
            }
//
//            $register = $enterpriseRegisterModel->getInfoByUserID($item['user_id']);
//            if (!empty($register) && $register['verify_status'] != EnterpriseRegisterModel::VERIFY_STATUS_PASS
//                && $register['verify_status'] != EnterpriseRegisterModel::VERIFY_STATUS_FIRST_PASS) {
//                unset($list[$key]);
//                continue;
//            }
        }
        $list = $this->appendAccountInfo($list);

        // 模板变量重新赋值
        $this->assign('list', $list);
        $this->assign('main_title','企业会员列表');
        $this->assign("group_list", $this->groups);
        // 限制提现类型
        $this->assign('limit_types', \core\service\UserCarryService::$withdrawLimitTypeCn);
        // Tag列表
        $user_tag_service = new UserTagService();
        $this->assign('user_tags', $user_tag_service->lists());
        // 企业用户类型
        $this->assign('company_purpose_map', EnterpriseModel::getCompanyPurposeMap());

        // 是否开通存管户
        $this->assign('supervision_account_list', [
            ['id' => 1, 'name' => '已开通'],
            ['id' => 2, 'name' => '未开通'],
        ]);

        $new_coupon_level =  M("UserCouponLevel")->findAll();
        $this->assign('new_coupon_level',$new_coupon_level);
        //设置列表当前页号
        \es_session::set('enterpriseListCurrentPage', isset($_GET['p']) ? (int)$_GET['p'] : 1);
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }

    private function appendAccountInfo($list) {
        //收集id
        $userIds = $typeList = [];
        foreach ($list as $key => $item) {
            $userIds[] = $item['user_id'];
            $typeList[] = $item['user_purpose_enum'];
        }

        //获取账户信息
        if (!empty($userIds)) {
            $phAccountService = new PhAccountService();
            $phResult = $phAccountService->getInfoByUserIdsAndTypeList($userIds, $typeList, false); //这里不同步状态，减少存管请求次数
        }

        foreach ($list as $key => $item) {
            $index = $item['user_id'] . '_' . $item['user_purpose_enum'];
            $list[$key]['sv_money'] = isset($phResult[$index]) ? number_format($phResult[$index]['money'], 2) : 0;
            $list[$key]['sv_lock_money'] = isset($phResult[$index]) ? number_format($phResult[$index]['lockMoney'], 2) : 0;
            $list[$key]['sv_status_desc'] = isset($phResult[$index]) ? $phResult[$index]['statusDesc'] : '未开通';
            $list[$key]['sv_account_desc'] = isset($phResult[$index]) && $phResult[$index]['status'] != 0 ? $phResult[$index]['accountTypeDesc'] : '';
        }

        return $list;
    }


    /**
     * 用户管理-企业会员列表-新增页面
     */
    public function add() {
        // 会员所属网站
        $groupList = M('UserGroup')->findAll();
        $this->assign('groupList', $groupList);

        // 一级地区
        $nRegionLv1 = MI('DeliveryRegion')->where(array('region_level' => 1))->findAll();
        $this->assign('nRegionLv1', $nRegionLv1);
        // 国家默认中国
        $this->assign('bank_region_input_lv1', 1);

        // 银行卡列表
        $bankList = $GLOBALS['db']->getAll('SELECT * FROM ' . DB_PREFIX . 'bank WHERE status=0 ORDER BY is_rec DESC,sort DESC,id ASC');
        $this->assign('bankList', $bankList);

        // 用户类型
        $this->assign('company_purpose_list', $this->is_cn ? EnterpriseModel::getCompanyPurposeListCn() : EnterpriseModel::getCompanyPurposeList());
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
        $this->assign('jumpUrl', u(MODULE_NAME . '/add'));
        $this->assign('inducateTypes',UserAccountEnum::$inducateTypes);
        $template = $this->is_cn ? 'add_cn' : 'add';
        $this->display($template);
    }

    /**
     * 用户管理-企业会员列表-新增逻辑
     * @see CommonAction::insert()
     */
    public function insert() {
        $data = $_POST;

        $isTradecenter = false;
        if($data['company_purpose'] == UserAccountEnum::ACCOUNT_TRADECENTER) {
            $isTradecenter = true;
        }

        // 开始验证有效性
        if (empty($data['company_purpose'])) {
            self::jsonOutput(-1, '账户类型不能为空');
        }
        if (!check_empty($data['user_name'])) {
            self::jsonOutput(-1, '企业会员名称不能为空');
        }
        if(!$isTradecenter) {
            if (!check_empty($data['user_pwd']) || !check_empty($data['user_confirm_pwd'])) {
                self::jsonOutput(-1, L('USER_PWD_EMPTY_TIP'));
            }
            if ($data['user_pwd'] != $data['user_confirm_pwd']) {
                self::jsonOutput(-1, L('USER_PWD_CONFIRM_ERROR'));
            }
        }

        if (!check_empty($data['company_name'])) {
            self::jsonOutput(-1, '企业全称不能为空');
        }

        $enterpriseService = new EnterpriseService();
        // 企业全称唯一性校验
        if (!$enterpriseService->canName($data['company_name'], 0, $data['company_purpose'])) {
            self::jsonOutput(-1, '企业已存在');
        }

        if (!check_empty($data['company_shortname'])) {
            self::jsonOutput(-1, '企业简称不能为空');
        }

        if (!check_empty($data['credentials_no'])) {
            self::jsonOutput(-1, '企业证件号码不能为空');
        }

        if (!$enterpriseService->canCredentialsNo($data['credentials_no'], 0, $data['company_purpose'])) {
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
        if (!check_empty($data['legalbody_name'])) {
            self::jsonOutput(-1, '法定代表人姓名不能为空');
        }
        if (!$isTradecenter && !check_empty($data['legalbody_credentials_no'])) {
            self::jsonOutput(-1, '法定代表人证件号码不能为空');
        }
//        if (!check_empty($data['legalbody_mobile'])) {
//            self::jsonOutput(-1, '法定代表人手机号码不能为空');
//        }
//        if (!is_mobile($data['legalbody_mobile'])) {
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
        if(!$isTradecenter && !check_empty($data['app_no'])) {
            self::jsonOutput(-1,'企业开户许可证核准号不能为空');
        }
        $data['reg_amt'] = $data['reg_amt']*10000;
        // 校验银行账户信息
        if (!$isTradecenter && (check_empty($data['card_name']) || check_empty($data['bankcard']) || !empty($data['bank_id'])
             || check_empty($data['bank_shortno']) || $data['bank_region_lv2'] > 0 || $data['bank_region_lv3'] > 0
             || !empty($data['bank_bankzone']) || check_empty($data['branch_no']))) {
             if (!check_empty($data['card_name']) || !check_empty($data['bankcard']) || empty($data['bank_id'])
                 || !check_empty($data['bank_shortno']) || $data['bank_region_lv1'] <= 0 || $data['bank_region_lv2'] <= 0
                 || $data['bank_region_lv3'] <= 0 || empty($data['bank_bankzone'])
                 || !check_empty($data['branch_no'])) {
                 self::jsonOutput(-1, '请填写完整银行账户信息');
             }
        }
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
        // 检查企业账户负责人邮箱是否有效
        if (!$isTradecenter && !check_email($data['major_email'])) {
            self::jsonOutput(-1, '接收邮件地址无效');
        }
        // 检查法人邮箱是否有效
        if (!$isTradecenter && (!empty($data['legalbody_email']) && !check_email($data['legalbody_email']))) {
            self::jsonOutput(-1, '法人邮箱地址无效');
        }
        if(!$isTradecenter && !array_key_exists($data['indu_cate'],UserAccountEnum::$inducateTypes)) {
            self::jsonOutput(-1,'行业类型不存在');
        }
        if (!$isTradecenter && !check_empty($data['app_no'])) {
            self::jsonOutput(-1, '企业行业类别');
        }
        if (!intval($data['group_id'])) {
            self::jsonOutput(-1, '会员组不能为空');
        }
        if (!intval($data['new_coupon_level_id'])) {
            self::jsonOutput(-1, '服务等级不能为空');
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
        // 会员名称
        $data['user_name'] = trim($data['user_name']);

        try {
            if(!(new UserCouponLevelService())->checkLevelMatchGroupById(intval($data['group_id']),intval($data['new_coupon_level_id']))){
                self::jsonOutput(-1, '会员组和服务等级不匹配');
            }
        } catch (\Exception $e) {
            self::jsonOutput(-1, '会员组和服务等级不匹配');
        }

        // 理财新增用户成功，支付系统开户失败的处理逻辑
        $userIdInput = isset($data['userId']) ? (int)$data['userId'] : 0;
        if (!$isTradecenter && ($userIdInput > 0)) {
            $userBankLastId = isset($data['userBankLastId']) ? (int)$data['userBankLastId'] : 0;
            //记录日志
            $enterpriseLogInfo = array(__CLASS__, __FUNCTION__, json_encode($data));
            try {
                // 检查用户是否已存在或者需要去支付开户
                $userBaseInfo = M('User')->where(array('id'=>$userIdInput))->find();
                if (!$userBaseInfo) {
                    throw new \Exception(sprintf('-2|企业会员数据异常|%d|%d', $userIdInput, $userBankLastId));
                }
                if ($userBaseInfo && strcmp($userBaseInfo['user_name'], $data['user_name']) != 0) {
                    $userBaseInfoTmp = M('User')->where(array('user_name'=>$data['user_name']))->find();
                    if ($userBaseInfoTmp && $userBaseInfoTmp['id'] > 0) {
                        throw new \Exception(sprintf('-2|企业会员名称已存在|%d|%d', $userIdInput, $userBankLastId));
                    }
                }
                // 检查法人邮箱是否存在
                if (!empty($data['legalbody_email']) && strcmp($userBaseInfo['email'], $data['legalbody_email']) != 0) {
                    $userBaseInfoTmp = M('User')->where(array('email'=>$data['legalbody_email']))->find();
                    if ($userBaseInfoTmp && $userBaseInfoTmp['id'] > 0) {
                        throw new \Exception(sprintf('-2|企业会员邮箱已存在|%d|%d', $userIdInput, $userBankLastId));
                    }
                }
                // 检查企业证件号码是否存在
                if (!empty($data['credentials_no']) && strcmp($userBaseInfo['idno'], $data['credentials_no']) != 0) {
                    // 检查企业证件号码是否唯一(FIRSTPTOP-4024)
                    $isIdno = UserModel::instance()->isUserExistsByIdno($data['credentials_no']);
                    if ($isIdno) {
                        //throw new \Exception(sprintf('-2|企业证件号码已存在|%d|%d', $userIdInput, $userBankLastId));
                        \libs\utils\PaymentApi::log('企业证件号已经存在,old_credentials_no:'.$userBaseInfo['idno'].',credentials_no:'.$data['credentials_no']);
                    }
                }
                // 检查银行卡号是否存在
                //if (isset($data['bankcard']) && !empty($data['bankcard'])) {
                //    $userCardInfo = UserBankcardModel::instance()->getUserBankCardRow(sprintf('id > 0 AND bankcard=\'%s\'', $data['bankcard']));
                //    if ($userCardInfo && $userCardInfo['user_id'] != $userIdInput) {
                //        throw new \Exception(sprintf('-2|该银行帐号已存在！|%d|%d', $userIdInput, $userBankLastId));
                //    }
                //}

                // 组织数据用于用户开户绑卡-通知支付部门的数据
                $paymentData = self::_getBankOpenAccountData($userIdInput, $data);

                // 创建或更新银行账户信息
                self::_saveBankAccountInfo($userIdInput, $data, $paymentData, false);

                // 调用支付部门的[企业会员注册]接口，并更新用户绑卡状态
                if (empty($paymentData)) {
                    throw new \Exception(sprintf('-2|企业会员提交数据为空|%d|%d', $userIdInput, $userBankLastId));
                }

                // 调用支付部门的[企业会员注册]接口
                $paymentService = new PaymentService();
                $paymentCompanyRet = $paymentService->companyRegister($paymentData);

                // 整理操作数据日志
                $enterpriseLogInfo = array_merge($enterpriseLogInfo, array("userBankLastId:{$userBankLastId},paymentData:".json_encode($paymentData).",paymentCompanyRet:{$paymentCompanyRet}"));

                if (true !== $paymentCompanyRet) {
                    throw new \Exception(sprintf('-2|企业会员在支付系统开立失败' . (is_array($paymentCompanyRet) && isset($paymentCompanyRet['respMsg']) ? '-' . $paymentCompanyRet['respMsg'] : '') . '|%d|%d', $userIdInput, $userBankLastId));
                }

                // 更新用户的支付ID、状态等
                $timestamp = get_gmtime();
                $updateUserData = array(
                    'id' => $userIdInput,
                    'user_name' => self::stripString($data['user_name']), // 企业会员名称
                    'real_name' => self::stripString(trim($data['company_name'])), // 真实姓名-企业全称
                    'group_id' => (int)$data['group_id'], // 会员所属网站:
                    'new_coupon_level_id' => (int)$data['new_coupon_level_id'], // 新会员等级
                    'id_type' => (int)$data['legalbody_credentials_type'], // 法定代表人证件类型
                    'idno' => self::stripString($data['credentials_no']), // 企业证件号码(FIRSTPTOP-4024)
                    'is_effect' => (int)$data['is_effect'],
                    'payment_user_id' => $userIdInput,
                    'update_time' => $timestamp,
                    'idcardpassed' => 1, // 身份认证
                    'idcardpassed_time' => $timestamp, // 身份认证时间
                    'mobilepassed' => 1, //手机认证
                    'user_purpose' => intval($data['company_purpose']), // 用户的账户类型
                );
                if(isset($data['user_pwd']) && !empty($data['user_pwd'])) {
                    $boBase = new core\service\user\BOBase();
                    $updateUserData['user_pwd'] = $boBase->compilePassword(self::stripString($data['user_pwd']));
                }
                // 法定代表人邮箱地址
                !empty($data['legalbody_email']) && $updateUserData['email'] = self::stripString($data['legalbody_email']);
                // 法定代表人手机号码-国家前缀
                if (!empty($data['legalbody_mobile_code']) && strpos($data['legalbody_mobile_code'], '|') !== false) {
                    list($shortCountryCode, $shortCountryNo) = explode('|', $data['legalbody_mobile_code']);
                    $updateUserData['country_code'] = $shortCountryCode;
                    $updateUserData['mobile_code'] = $shortCountryNo;
                    $updateUserData['mobile'] = '';
                }
                // 用户简介
                isset($data['info']) && $updateUserData['info'] = self::stripString($data['info']);
                UserModel::instance()->updateInfo($updateUserData, 'update');

                // 更新企业用户-基本信息
                self::_updateUserEnterpriseInfo($userIdInput, $data);

                // 更新企业用户-联系人信息
                self::_updateUserEnterpriseContactInfo($userIdInput, $data);

                // 更新用户绑卡信息
                self::_updateUserBankInfo($userIdInput, $data, $userBankLastId);

                // 记录日志
                Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array('支付开户并绑卡成功'))));

                // 添加用户标签
                $userTagService = new \core\service\UserTagService();
                $userTag = array('REG_Y_'.date('Y'),'REG_M_'.date('m'),'USER_TYPE_QY');
                $userTagService->addUserTagsByConstName($userIdInput, $userTag);

                // 记录admin日志
                $messageTips = '【' . $data['user_name'] . '】' . L('INSERT_SUCCESS');
                save_log($messageTips, 1);
                self::jsonOutput(1, $messageTips);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array('exception:' . $errorMessage))));
                if (false !== strpos($errorMessage, '|')) {
                    list($errorCode, $errorMsg, $userIdEx, $userBankLastIdEx) = explode('|', $errorMessage);
                }
                $exCode = isset($errorCode) ? $errorCode : -1;
                $exMsg = isset($errorMsg) ? $errorMsg : $errorMessage;
                $exUserId = isset($userIdEx) ? $userIdEx : 0;
                $exuserBankLastId = isset($userBankLastIdEx) ? $userBankLastIdEx : 0;
                self::jsonOutput($exCode, $exMsg, array('userId'=>$exUserId, 'userBankLastId'=>$exuserBankLastId));
            }
        }

        // 检查用户是否已存在或者需要去支付开户
        $userBaseInfo = M('User')->where(array('user_name'=>$data['user_name']))->find();
        if ($userBaseInfo && $userBaseInfo['id'] > 0) {
            self::jsonOutput(-1, '企业会员名称已存在');
        }
        // 检查企业证件号码是否唯一(FIRSTPTOP-4024)
        $isIdno = UserModel::instance()->isUserExistsByIdno($data['credentials_no']);
        if ($isIdno) {
            //self::jsonOutput(-1, '企业证件号码已存在');
            \libs\utils\PaymentApi::log('企业证件号已经存在,credentials_no:'.$data['credentials_no']);
        }

        //记录日志
        $enterpriseLogInfo = array(__CLASS__, __FUNCTION__, json_encode($data));
        //开启事务
        $GLOBALS['db']->startTrans();
        try {
            $userData = array(
                 'oauth' => true, // oauth数据不做邮箱认证
                 'user_type' => UserModel::USER_TYPE_ENTERPRISE, // 用户类型:企业用户
                 'user_name' => self::stripString($data['user_name']), // 企业会员名称
                 'real_name' => self::stripString(trim($data['company_name'])), // 真实姓名-企业全称
                 'user_pwd' => self::stripString(trim($data['user_pwd'])),
                 'group_id' => (int)$data['group_id'], // 会员所属网站:
                 'new_coupon_level_id' => (int)$data['new_coupon_level_id'], // 新会员等级
                 'is_effect' => 0, // 用户状态(0:无效1:有效)
                 'id_type' => (int)$data['legalbody_credentials_type'], // 法定代表人证件类型
                 'idno' => self::stripString($data['credentials_no']), // 企业证件号码(FIRSTPTOP-4024)
                 'force_new_passwd' => 1, // 企业用户首次登录强制修改密码
                 'user_purpose' => intval($data['company_purpose']), // 用户的账户类型
            );
            // 法定代表人邮箱地址
            !empty($data['legalbody_email']) && $userData['email'] = self::stripString(trim($data['legalbody_email']));
            // 法定代表人手机号码-国家前缀
            if (!empty($data['legalbody_mobile_code']) && strpos($data['legalbody_mobile_code'], '|') !== false) {
               list($shortCountryCode, $shortCountryNo) = explode('|', $data['legalbody_mobile_code']);
               $userData['country_code'] = $shortCountryCode;
               $userData['mobile_code'] = $shortCountryNo;
               $userData['mobile'] = '';
            }
            // 法定代表人手机号码
            //!empty($data['legalbody_mobile']) && $userData['mobile'] = self::stripString($data['legalbody_mobile']);
            // 会员等级失效时间
            !empty($data['coupon_level_valid_end']) && $userData['coupon_level_valid_end'] = $data['coupon_level_valid_end'];
            // 返利系数
            !empty($data['channel_pay_factor']) && $userData['channel_pay_factor'] = self::stripString($data['channel_pay_factor']);
            // 用户简介
            !empty($data['info']) && $userData['info'] = self::stripString($data['info']);
            // 后台添加默认手机号已认证
            $userData['mobilepassed'] = true;
            // 录入企业用户信息
            $userInfoRet = save_user($userData, 'INSERT', 0, true);
            if ($userInfoRet['status'] == 0 || !is_numeric($userInfoRet['data']) || $userInfoRet['data'] <= 0) {
                $errorField = $userInfoRet['data'];
                $errorMsg = '创建企业用户基本信息失败！';
                if ($errorField['error'] == EMPTY_ERROR) {
                    if ($errorField['field_name'] == 'user_name') {
                        $errorMsg = L('USER_NAME_EMPTY_TIP');
                    } elseif ($errorField['field_name'] == 'email') {
                        $errorMsg = L('USER_EMAIL_EMPTY_TIP');
                    } else {
                        $errorMsg = sprintf(L('USER_EMPTY_ERROR'), $errorField['field_show_name']);
                    }
                }
                if ($errorField['error'] == FORMAT_ERROR) {
                    if ($errorField['field_name'] == 'email') {
                        $errorMsg = L('USER_EMAIL_FORMAT_TIP');
                    }
                    if ($errorField['field_name'] == 'mobile') {
                        $errorMsg = L('USER_MOBILE_FORMAT_TIP');
                    }
                }
                if ($errorField['error'] == EXIST_ERROR) {
                    if ($errorField['field_name'] == 'user_name') {
                        $errorMsg = L('USER_NAME_EXIST_TIP');
                    } elseif ($errorField['field_name'] == 'email') {
                        $errorMsg = L('USER_EMAIL_EXIST_TIP');
                    } elseif ($errorField['field_name'] == 'mobile') {
                        $errorMsg = '该手机号已经存在！';
                    }
                }
                throw new \Exception($errorMsg);
            }
            // 新生成的用户UID
            $userId = intval($userInfoRet['data']);


            // 录入企业用户-基本信息
            self::_insertUserEnterpriseInfo($userId, $data);

            // 录入企业用户-联系人信息
            self::_insertUserEnterpriseContactInfo($userId, $data);

            // 组织数据用于用户开户绑卡-通知支付部门的数据
            $paymentData = self::_getBankOpenAccountData($userId, $data);

            // 创建或更新银行账户信息
            $userBankLastId = self::_saveBankAccountInfo($userId, $data, $paymentData);

            // 初始化第三方账户余额
            //\core\dao\UserThirdBalanceModel::instance()->initBalance($userId);

            // 提交事务
            $commitRet = $GLOBALS['db']->commit();
            Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array("commitRet:{$commitRet}"))));

            // 调用支付部门的[企业会员注册]接口，并更新用户绑卡状态
            if (empty($paymentData)) {
                throw new \Exception(sprintf('-2|企业会员提交数据为空|%d|%d', $userId, $userBankLastId));
            }

            if(!$isTradecenter) {
                // 调用支付部门的[企业会员注册]接口
                $paymentService = new PaymentService();
                $paymentCompanyRet = $paymentService->companyRegister($paymentData);

                // 整理操作数据日志
                $enterpriseLogInfo = array_merge($enterpriseLogInfo, array("userBankLastId:{$userBankLastId},paymentData:".json_encode($paymentData).",paymentCompanyRet:{$paymentCompanyRet}"));

                if (true !== $paymentCompanyRet) {
                    throw new \Exception(sprintf('-2|企业会员在支付系统开立失败' . (is_array($paymentCompanyRet) && isset($paymentCompanyRet['respMsg']) ? '-' . $paymentCompanyRet['respMsg'] : '') . '|%d|%d', $userId, $userBankLastId));
                }
            }

            // 更新用户的支付ID、状态等字段
            $timestamp = get_gmtime();
            $updateUserData = array(
                'id' => $userId,
                'is_effect' => (int)$data['is_effect'],
                'payment_user_id' => $userId,
                'update_time' => $timestamp,
                'idcardpassed' => 1, // 身份认证
                'idcardpassed_time' => $timestamp, // 身份认证时间
                'mobilepassed' => 1, //手机认证
            );
            UserModel::instance()->updateInfo($updateUserData, 'update');

            if (is_numeric($userBankLastId) && $userBankLastId > 0) {
                // 更新用户绑卡状态
                $updateUserBankData = array(
                    'status' => 1,
                    'update_time' => $timestamp,
                );
                UserBankcardModel::instance()->updateCard($userBankLastId, $updateUserBankData);
            }

            // 记录日志
            Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array('理财创建企业用户成功,支付开户并绑卡成功'))));

            // 添加用户标签
            $userTagService = new \core\service\UserTagService();
            $userTag = array('REG_Y_'.date('Y'),'REG_M_'.date('m'),'USER_TYPE_QY');
            $userTagService->addUserTagsByConstName($userInfoRet['data'], $userTag);

            // 记录admin日志
            $messageTips = '【' . $data['user_name'] . '】' . L('INSERT_SUCCESS');
            save_log($messageTips, 1);
            self::jsonOutput(1, $messageTips);
        } catch (\Exception $e) {
            $GLOBALS->transTimes > 0 && $GLOBALS['db']->rollback();
            $errorMessage = $e->getMessage();
            Logger::info(implode(' | ', array_merge($enterpriseLogInfo, array('exception:' . $errorMessage))));
            if (false !== strpos($errorMessage, '|')) {
                list($errorCode, $errorMsg, $userIdEx, $userBankLastIdEx) = explode('|', $errorMessage);
            }
            $exCode = isset($errorCode) ? $errorCode : -1;
            $exMsg = isset($errorMsg) ? $errorMsg : $errorMessage;
            $exUserId = isset($userIdEx) ? $userIdEx : 0;
            $exuserBankLastId = isset($userBankLastIdEx) ? $userBankLastIdEx : 0;
            self::jsonOutput($exCode, $exMsg, array('userId'=>$exUserId, 'userBankLastId'=>$exuserBankLastId));
        }
    }

    /**
     * 拷贝用户信息到新建用户页面
     */
    public function copy_user() {
        $this->assign('isCopyUser', 1);
        $this->edit();
    }

    /**
     * 用户管理-企业会员列表-编辑页面
     * @see CommonAction::edit()
     */
    public function edit() {
        parent::edit();
    }

    /**
     * 用户管理-企业会员列表-更新逻辑
     * @see CommonAction::update()
     */
    public function update() {
        parent::update(true);
    }

    /**
     * 根据省份、城市，获取银行网点列表
     */
    public function getBankListHtml() {
        // 城市
        $city = trim(htmlspecialchars($_GET['c']));
        // 省份
        $province = trim(htmlspecialchars($_GET['p']));
        // 开户行名称
        $bankName = trim(htmlspecialchars($_GET['b']));
        // 开户行网点
        $bankPointName = trim(htmlspecialchars($_GET['n']));
        // jsonp回调方法名
        $jsonpCallback = trim(htmlspecialchars($_GET['jsonpCallback']));
        // 是否支持手动填写
        $isSupply = isset($_GET['s']) ? (int)$_GET['s'] : 0;
        // 是否禁用select
        $disableSelect = isset($_GET['d']) ? (int)$_GET['d'] : 0;
        $inputId = isset($_GET['i']) ? (int)$_GET['i'] : 0;
        $bankZoneSelect = isset($_GET['z']) ? (int)$_GET['z'] : 1;
        $bankListObj = new BanklistService();
        $result = $bankListObj->getBanklist($city, $province, $bankName);
        $data = self::_toBankListHtml($result, $bankPointName, $jsonpCallback, $isSupply, $disableSelect, $inputId, $bankZoneSelect);
        echo json_encode($data);
        exit;
    }

    /**
     * 创建/编辑银行开户信息
     */
    public function editBankAccount() {
        parent::editBankAccount(true);
    }

    /**
     * 编辑银行开户信息
     */
    public function editSupervisionBankAccount() {
        parent::editBankAccount(true, true);
    }

    public function view_bank() {

        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);

        // 获取会员列表
        $name = $this->getActionName();
        $list = $this->_list(DI($name), $map);
        foreach ($list as $key=>&$item) {
            $userId = intval($item['user_id']);
            // 你看不见我
            if ($this->is_cn && in_array($userId, [8118934,7963653])) {
                unset($list[$key]);
            }
            // 获取用户绑定的银行卡信息
            $bankcard = MI('UserBankcard')->where('user_id=' . $item['user_id'] . ' ORDER BY id DESC')->find();
            $item['user_bankcard_origin'] = $bankcard['bankcard'];
            $item['bankzone'] = $bankcard['bankzone'];
            $item['branch_no'] = $bankcard['branch_no'];
            $item['card_name'] = $bankcard['card_name'];
            $item['card_type'] = $bankcard['card_type'] == 1 ? "公司账户" : "个人账户";
            $bank = BankModel::instance()->find($bankcard['bank_id']);
            $item['bank_name'] = $bank['name'];
            $item['bank_shortno'] = $bank['short_name'];
            $item['branch_no'] = $bankcard['branch_no'];
        }

        // 模板变量重新赋值
        $this->assign('list', $list);
        $this->assign('main_title','企业会员列表');

        //设置列表当前页号
        \es_session::set('enterpriseListCurrentPage', isset($_GET['p']) ? (int)$_GET['p'] : 1);
        $this->display();
    }

    public function export_csv($page = 1) {
        // 禁用导出功能#FIRSTPTOP-4818
        $this->error('该功能已被禁用');

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // 组织查询条件
        $map = $this->_getSqlMap($_REQUEST);
        // 会员所属网站
        $this->groups = \core\dao\UserGroupModel::instance()->getGroups();
        // 获取会员列表
        $name = $this->getActionName();
        $list = $this->_list(DI($name), $map);
        if ($list) {
            $user_value = array('id' => '""', 'user_name' => '""', 'email' => '""', 'mobile' => '""', 'group_id' => '""');
            if ($page == 1) {
                $content = iconv("utf-8", "gbk", "编号,用户名,电子邮箱,手机号,会员组,会员余额,冻结资金,最后登录IP,注册时间,最后登录时间,姓名,身份证号码,开户名,银行卡号,银行,开户国家,开户省,开户市,开户区,开户网点,账户用途");
            }
            //开始获取扩展字段
            $extend_fields = M("UserField")->order("sort desc")->findAll();
            foreach ($extend_fields as $k => $v) {
                $user_value[$v['field_name']] = '""';
                if ($page == 1) {
                    $content = $content . "," . iconv('utf-8', 'gbk', $v['field_show_name']);
                }
            }
            if ($page == 1) {
                $content = $content . "\n";
            }

            foreach ($list as $k => $v) {
                // 获取银行卡信息
                $bankInfo = M("UserBankcard")->where("user_id=" . $v['id'])->find();
                $bankInfo['region_lv1_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv1'])->getField("name");
                $bankInfo['region_lv2_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv2'])->getField("name");
                $bankInfo['region_lv3_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv3'])->getField("name");
                $bankInfo['region_lv4_name'] = M("DeliveryRegion")->where("id=" . $bankInfo['region_lv4'])->getField("name");
                $bankInfo['bank_name'] = M("bank")->where("id=" . $bankInfo['bank_id'])->getField("name");
                $user_value = array();
                $user_value['id'] = iconv('utf-8', 'gbk', '"' . $v['id'] . '"');
                //$user_value['user_name'] = iconv('utf-8','gbk','"' . $v['user_name'] . '"');
                //为兼容线上用户id为253的用户名（包含类似空格的特殊字符）
                $user_value['user_name'] = iconv('utf-8', 'gbk', '"' . str_replace(' ', ' ', $v['user_name']) . '"');
                $user_value['email'] = iconv('utf-8', 'gbk', '"' . $v['email'] . '"');
                $user_value['mobile'] = "\"\t" . $v['mobile'] . "\"";
                $user_value['group_id'] = iconv('utf-8', 'gbk', '"' . $v['name'] . '"');
                $user_value['money'] = iconv('utf-8', 'gbk', '"' . $v['money'] . '"');
                $user_value['lock_money'] = iconv('utf-8', 'gbk', '"' . $v['lock_money'] . '"');
                $user_value['login_ip'] = iconv('utf-8', 'gbk', '"' . $v['login_ip'] . '"');
                $user_value['create_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['create_time']) . '"');
                $user_value['login_time'] = iconv('utf-8', 'gbk', '"' . to_date($v['login_time']) . '"');
                $user_value['real_name'] = iconv('utf-8', 'gbk', '"' . $v['real_name'] . '"');
                $user_value['idno'] = "\"\t" . $v['idno'] . "\"";
                $user_value['card_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['card_name'] . '"');
                $user_value['bankcard'] = "\"\t" . $bankInfo['bankcard'] . "\"";
                $user_value['bank_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['bank_name'] . '"');
                $user_value['region_lv1_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv1_name'] . '"');
                $user_value['region_lv2_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv2_name'] . '"');
                $user_value['region_lv3_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv3_name'] . '"');
                $user_value['region_lv4_name'] = iconv('utf-8', 'gbk', '"' . $bankInfo['region_lv4_name'] . '"');
                $user_value['bankzone'] = iconv('utf-8', 'gbk', '"' . $bankInfo['bankzone'] . '"');

                //过滤敏感信息
                if(!empty($user_value['idno'])){
                    $user_value['idno'] = idnoFormat($user_value['idno']);
                }
                if(!empty($item['user_name'])){
                    $item['user_name'] = adminMobileFormat($item['user_name']);
                }
                if(!empty($user_value['mobile'])){
                    $user_value['mobile'] = adminMobileFormat($user_value['mobile']);
                }
                if(!empty($user_value['email'])){
                    $user_value['email'] = adminEmailFormat($user_value['email']);
                }
                if(!empty($user_value['bankcard'])){
                    $user_value['bankcard'] = formatBankcard($user_value['bankcard']);
                }
                //取出扩展字段的值
                $extend_fieldsval = M("UserExtend")->where("user_id=" . $v['id'])->findAll();
                foreach ($extend_fields as $kk => $vv) {
                    foreach ($extend_fieldsval as $kkk => $vvv) {
                        if ($vv['id'] == $vvv['field_id']) {
                            $user_value[$vv['field_name']] = iconv('utf-8', 'gbk', '"' . $vvv['value'] . '"');
                            break;
                        }
                    }
                }
                $content .= implode(",", $user_value) . "\n";
            }
            // 获取最后一个用户id号
            $n = count($list) - 1;
            $uid = $list[$n]['id'];
            $uid = str_pad($uid, 6, "0", STR_PAD_LEFT);
            $filename = 'user-' . $uid . '_' . to_date(get_gmtime(), 'Y-m-d_H-i-s');
            //记录导出日志
            setLog(
                array(
                    'sensitive' => 'exportuser',
                    'analyze' => $map
                )
            );

            header("Content-Disposition: attachment; filename=" . $filename . ".csv");
            echo $content;
        } else {
            if ($page == 1) {
                $this->error(L("NO_RESULT"));
            }
        }
    }

    /**
     * 组织查询条件
     * @param array $request
     */
    private function _getSqlMap(&$request) {
        $map = $map_user = array();

        if (intval($request['group_id']) > 0) {
            $map_user['group_id'] = intval($request['group_id']);
        }

        if (trim($request['user_id']) != '') {
            $map_user['id'] = intval($request['user_id']);
        }

        if (trim($request['invite_code'])) {
            $map_user['invite_code'] = trim($request['invite_code']);
        }
        if(trim($_REQUEST['supervision_account'])!='')
        {
            $account_type = intval($_REQUEST['supervision_account']);
            if ($account_type == 1) {
                $map['supervision_user_id'] = array('gt', 0);
            } else if ($account_type == 2) {
                $map['supervision_user_id'] = 0;
            }
        }

        // 企业会员标识
        $identifier = isset($_REQUEST['identifier']) ? trim($_REQUEST['identifier']) : '';
        if (!empty($identifier)) {
            $map['identifier'] = array('eq', addslashes($identifier));
        }
        if(trim($request['user_name']) !='') {
            $name = addslashes(trim($request['user_name']));
            $map_user['user_name'] = array('like', $name . '%');
        }

        if(trim($request['real_name']) !='') {
            $real_name = addslashes(trim($request['real_name']));
            // 引发慢查询，没必要模糊匹配的都关闭
            //$map_user['user_name'] = array('like', $real_name . '%');
            $map_user['real_name'] = $real_name;
        }

        if(trim($request['email']) !='') {
            //$map_user['email'] = array('like', $request['email'] . '%');
            $map_user['email'] = trim($request['email']);
        }

        if(trim($request['mobile']) !='') {
            $map_user['mobile'] = trim($request['mobile']);
        }

        if(trim($request['idno']) !='') {
            $map_user['idno'] = trim($request['idno']);
        }

        if (intval($request['new_coupon_level_id']) > 0) {
            $map_user['new_coupon_level_id'] = intval($request['new_coupon_level_id']);
        }

        if (trim($request['pid_name']) != '') {
            $pid = MI('User')->where("user_name='" . trim($request['pid_name']) . "'")->getField('id');
            if ($pid) {
                $map_user['pid'] = intval($pid);
            } else {
                $map_user['pid'] = array('lt', 0);
            }
        }

        // 获取符合上述条件的用户数据
        if ($map_user) {
            // 未删除的帐户
            $map_user['is_delete'] = 0;
            // 用户类型-企业用户
            $map_user['user_type'] = UserModel::USER_TYPE_ENTERPRISE;
            $userList = MI('User')->where($map_user)->findAll();
            if ($userList) {
                $ids = array();
                foreach ($userList as $value) {
                    $ids[] = $value['id'];
                }
                $ids && $map['user_id'] = array('in', join(',', $ids));
            }
        }

        if (trim($request['bankcard']) != '') {
            $sql = sprintf('SELECT group_concat(user_id) FROM %s WHERE bankcard=\'%s\'', DB_PREFIX . 'user_bankcard', trim($request['bankcard']));
            $ids = $GLOBALS['db']->get_slave()->getOne($sql);
            if ($ids) {
                $map['user_id'] = array('in', $ids);
            }
        }

        // 根据标签筛选用户
        if(trim($request['tag_id']) != '') {
            $user_tag_service = new UserTagService();
            $user_ids = $user_tag_service->getUidsByTagId(intval($request['tag_id']));
            if ($user_ids) {
                $map['user_id'] = array('in', implode(',', $user_ids));
            }
        }

        // 企业会员编号
        $member_sn = addslashes(trim($request['member_sn']));
        if(!empty($member_sn)) {
            $map['user_id'] = array('eq', de32Tonum($member_sn));
        }

        $company_name = addslashes(trim($request['company_name']));
        if(!empty($company_name)) {
            $map['company_name'] = array('like', $company_name . '%');
        }

        // 企业用户类型
        if (isset($request['company_purpose']) && $request['company_purpose'] != -1) {
            $map['company_purpose'] = array('eq', intval($request['company_purpose']));
        }

        if ($this->is_cn) {
            $map['supervision_user_id'] = array('gt', 0);
        }

        return $map;
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
            $couponBindService = new \core\service\CouponBindService();
            $userCouponLevelService = new UserCouponLevelService();
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
                $item['new_coupon_level_id'] = isset($userInfo['new_coupon_level_id']) ? $userInfo['new_coupon_level_id'] : 0;

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
                $item['service_status'] = $this->groups[$userInfo['group_id']]['service_status']==1?'有效':'无效';

                $item['coupon'] = CouponService::userIdToHex($item['id'],$this->groups[$item['group_id']]['prefix']);
                $item['coupon'] =  '<a href="m.php?m=Enterprise&a=index&invite_code=' . $item['coupon'] . '">' . $item['coupon'] . '</a>';

                $invite_uid = $userInfo['invite_code'] ? CouponService::hexToUserId($userInfo['invite_code']) : 0;
                $item['invite_code'] = "<a href='m.php?m=Enterprise&a=index&user_id={$invite_uid}'>" . $userInfo['invite_code'] . "</a>";
                $item['email'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$item['email']}</div>";
                $item['login_ip'] = "<div style='width:50px;white-space:normal;word-wrap:break-word;'>{$userInfo['login_ip']}</div>";
                $item['user_tag'] = implode('|', array_map(function($val){return $val['tag_name'];}, $userTagService->getTags($userInfo['id'])));
                $item['user_purpose'] = $GLOBALS['dict']['ENTERPRISE_PURPOSE'][$userInfo['user_purpose']]['bizName'];
                $item['user_purpose_enum'] = $userInfo['user_purpose'];

                $couponBind = $couponBindService->getByUserId($item['id']);
                if(!empty($couponBind)){
                    $item['refer_user_id'] = $couponBind['refer_user_id']?$couponBind['refer_user_id']:'';
                    $item['refer_user_code'] =  $couponBind['short_alias'];
                    if($item['refer_user_id']){
                        $refer_user_group_id = MI('User')->where("id='".$item['refer_user_id']."'")->field('group_id')->find();
                        if(!empty($refer_user_group_id) && isset($refer_user_group_id['group_id'])){
                            $item['refer_user_group_name'] = $this->groups[$refer_user_group_id['group_id']]['name'];
                        }
                        $item['refer_user_id'] = "<a href='m.php?m=User&a=index&user_id={$item['refer_user_id']}'>" . $item['refer_user_id'] . "</a>";
                    }
                    $item['invite_user_id'] = $couponBind['invite_user_id']?$couponBind['invite_user_id']:'';
                    $item['invite_user_code'] =  $couponBind['invite_code'];
                    if($item['invite_user_id']){
                        $item['invite_user_id'] = "<a href='m.php?m=User&a=index&user_id={$item['invite_user_id']}'>" . $item['invite_user_id'] . "</a>";
                    }
                }

                $data= array();
                if($item['new_coupon_level_id'] != 0){
                    $data=$userCouponLevelService->getLevelById($item['new_coupon_level_id']);
                }
                $item['new_coupon_level_name'] = empty($data) ? '无' : $data['name'];
            }
        }
    }

    /**
     * 录入企业用户-基本信息
     * @param int $userId
     * @param array $data
     */
    private static function _insertUserEnterpriseInfo($userId, &$data) {
        // 企业基础信息入库
        $enterpriseModel = new EnterpriseModel();
        // 企业用户会员ID
        $enterpriseModel->user_id = $userId;
        // 企业会员账户用途
        !empty($data['company_purpose']) && $enterpriseModel->company_purpose = intval($data['company_purpose']);
        // 其他用途说明
        !empty($data['privilege_note']) && $enterpriseModel->privilege_note = self::stripString($data['privilege_note']);
        // 企业全称
        !empty($data['company_name']) && $enterpriseModel->company_name = self::stripString(trim($data['company_name']));
        // 企业简称
        !empty($data['company_shortname']) && $enterpriseModel->company_shortname = self::stripString(trim($data['company_shortname']));
        // 企业证件类别
        !empty($data['credentials_type']) && $enterpriseModel->credentials_type = (int)$data['credentials_type'];
        // 企业证件号码
        !empty($data['credentials_no']) && $enterpriseModel->credentials_no = self::stripString($data['credentials_no']);
        // 企业证件有效期-Start
        !empty($data['credentials_expire_date']) && $enterpriseModel->credentials_expire_date = self::stripString($data['credentials_expire_date']);
        // 企业证件有效期-End
        !empty($data['credentials_expire_at']) && $enterpriseModel->credentials_expire_at = self::stripString($data['credentials_expire_at']);
        // 企业证件长期有效
        isset($data['is_permanent']) && $enterpriseModel->is_permanent = intval($data['is_permanent']);
        // 法定代表人姓名
        !empty($data['legalbody_name']) && $enterpriseModel->legalbody_name = self::stripString(trim($data['legalbody_name']));
        // 法定代表人证件类别
        !empty($data['legalbody_credentials_type']) && $enterpriseModel->legalbody_credentials_type = (int)$data['legalbody_credentials_type'];
        // 法定代表人证件号码
        !empty($data['legalbody_credentials_no']) && $enterpriseModel->legalbody_credentials_no = self::stripString($data['legalbody_credentials_no']);
        // 法定代表人手机号码-国家前缀
        if (!empty($data['legalbody_mobile_code']) && strpos($data['legalbody_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['legalbody_mobile_code']);
            $enterpriseModel->legalbody_mobile_code = $shortCountryNo;
        }
        // 法定代表人手机号码
        !empty($data['legalbody_mobile']) && $enterpriseModel->legalbody_mobile = self::stripString(trim($data['legalbody_mobile']));
        // 法定代表人邮箱地址
        !empty($data['legalbody_email']) && $enterpriseModel->legalbody_email = self::stripString(trim($data['legalbody_email']));
        //企业注册资金
        !empty($data['reg_amt']) && $enterpriseModel->reg_amt = intval($data['reg_amt']);
        //企业行业类别
        !empty($data['indu_cate']) && $enterpriseModel->indu_cate = (int)$data['indu_cate'];
        //企业开户许可证核准号
        !empty($data['app_no']) && $enterpriseModel->app_no = self::stripString($data['app_no']);
        $registrationRegion = array();
        // 企业注册地址-国家
        !empty($data['registration_region_lv1']) && $registrationRegion[] = (int)$data['registration_region_lv1'];
        // 企业注册地址-省份
        !empty($data['registration_region_lv2']) && $registrationRegion[] = (int)$data['registration_region_lv2'];
        // 企业注册地址-城市
        !empty($data['registration_region_lv3']) && $registrationRegion[] = (int)$data['registration_region_lv3'];
        // 企业注册地址-地区
        !empty($data['registration_region_lv4']) && $registrationRegion[] = (int)$data['registration_region_lv4'];
        // 企业注册地址-整理
        !empty($registrationRegion) && $enterpriseModel->registration_region = join(',', $registrationRegion);
        // 企业联系地址
        !empty($data['registration_address']) && $enterpriseModel->registration_address = self::stripString($data['registration_address']);
        $contractRegion = array();
        // 企业联系地址-国家
        !empty($data['contract_region_lv1']) && $contractRegion[] = (int)$data['contract_region_lv1'];
        // 企业联系地址-省份
        !empty($data['contract_region_lv2']) && $contractRegion[] = (int)$data['contract_region_lv2'];
        // 企业联系地址-城市
        !empty($data['contract_region_lv3']) && $contractRegion[] = (int)$data['contract_region_lv3'];
        // 企业联系地址-地区
        !empty($data['contract_region_lv4']) && $contractRegion[] = (int)$data['contract_region_lv4'];
        // 企业联系地址-整理
        !empty($registrationRegion) && $enterpriseModel->contract_region = join(',', $contractRegion);
        // 企业联系地址
        !empty($data['contract_address']) && $enterpriseModel->contract_address = self::stripString($data['contract_address']);
        // 备注
        !empty($data['memo']) && $enterpriseModel->memo = self::stripString($data['memo']);
        // 企业会员标识
        !empty($data['identifier']) && $enterpriseModel->identifier = self::stripString($data['identifier']);
        // 创建时间
        $enterpriseModel->create_time = get_gmtime();
        $enterRet = $enterpriseModel->insert();
        if (!$enterRet) {
            throw new \Exception("创建企业用户基本信息失败!");
        }
    }

    /**
     * 录入企业用户-联系人信息
     * @param int $userId
     * @param array $data
     */
    private static function _insertUserEnterpriseContactInfo($userId, &$data) {
        // 企业联系人信息入库
        $enterpriseContactModel = new EnterpriseContactModel();
        // 企业用户会员ID
        $enterpriseContactModel->user_id = $userId;
        // 企业账户负责人姓名
        !empty($data['major_name']) && $enterpriseContactModel->major_name = self::stripString(trim($data['major_name']));
        // 企业账户负责人证件类别
        !empty($data['major_condentials_type']) && $enterpriseContactModel->major_condentials_type = (int)$data['major_condentials_type'];
        // 企业账户负责人证件号码
        !empty($data['major_condentials_no']) && $enterpriseContactModel->major_condentials_no = self::stripString($data['major_condentials_no']);
        // 企业账户负责人手机号码-国家前缀
        if (!empty($data['major_mobile_code']) && strpos($data['major_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['major_mobile_code']);
            $enterpriseContactModel->major_mobile_code = $shortCountryNo;
            $data['payment_major_mobile_code'] = $shortCountryNo;
        }
        // 企业账户负责人手机号码
        !empty($data['major_mobile']) && $enterpriseContactModel->major_mobile = self::stripString(trim($data['major_mobile']));
        // 企业账户负责人邮箱地址
        !empty($data['major_email']) && $enterpriseContactModel->major_email = self::stripString(trim($data['major_email']));
        $majorContractRegion = array();
        // 企业联系地址-国家
        !empty($data['major_contract_region_lv1']) && $majorContractRegion[] = (int)$data['major_contract_region_lv1'];
        // 企业联系地址-省份
        !empty($data['major_contract_region_lv2']) && $majorContractRegion[] = (int)$data['major_contract_region_lv2'];
        // 企业联系地址-城市
        !empty($data['major_contract_region_lv3']) && $majorContractRegion[] = (int)$data['major_contract_region_lv3'];
        // 企业联系地址-地区
        !empty($data['major_contract_region_lv4']) && $majorContractRegion[] = (int)$data['major_contract_region_lv4'];
        // 企业联系地址-整理
        !empty($majorContractRegion) && $enterpriseContactModel->major_contract_region = join(',', $majorContractRegion);
        // 企业账户负责人联系地址
        !empty($data['major_contract_address']) && $enterpriseContactModel->major_contract_address = self::stripString($data['major_contract_address']);
        // 企业联系人2姓名
        !empty($data['contract_name']) && $enterpriseContactModel->contract_name = self::stripString($data['contract_name']);
        // 企业联系人2手机号码-国家前缀
        if (!empty($data['contract_mobile_code']) && strpos($data['contract_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['contract_mobile_code']);
            $enterpriseContactModel->contract_mobile_code = $shortCountryNo;
        }
        // 企业联系人2手机号码
        !empty($data['contract_mobile']) && $enterpriseContactModel->contract_mobile = self::stripString($data['contract_mobile']);
        // 企业联络人手机号码-国家前缀
        if (!empty($data['consignee_phone_code']) && strpos($data['consignee_phone_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['consignee_phone_code']);
            $enterpriseContactModel->consignee_phone_code = $shortCountryNo;
        }
        // 企业联络人手机号码
        !empty($data['consignee_phone']) && $enterpriseContactModel->consignee_phone = self::stripString($data['consignee_phone']);

        // 接收短信通知号码
        if (!empty($data['receive_msg_mobile'])) {
            $receiveMsgMobile = self::_receiveUnique($data['receive_msg_mobile']);
            $enterpriseContactModel->receive_msg_mobile = self::stripString($receiveMsgMobile);
        }
        // 推荐人姓名
        isset($data['inviter_name']) && $enterpriseContactModel->inviter_name = self::stripString($data['inviter_name']);
        // 推荐人手机号码-国家前缀
        if (!empty($data['inviter_country_code']) && strpos($data['inviter_country_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['inviter_country_code']);
            $enterpriseContactModel->inviter_country_code = $shortCountryNo;
        }
        // 推荐人手机号码
        isset($data['inviter_phone']) && $enterpriseContactModel->inviter_phone = self::stripString($data['inviter_phone']);
        //邀请人机构
        isset($data['inviter_organization']) && $enterpriseContactModel->inviter_organization = self::stripString($data['inviter_organization']);
        // 经办人姓名
        !empty($data['employee_name']) && $enterpriseContactModel->employee_name = self::stripString($data['employee_name']);
        // 经办人手机号码-国家前缀
        if (!empty($data['employee_mobile_code']) && strpos($data['employee_mobile_code'], '|') !== false) {
            list($shortCountryCode, $shortCountryNo) = explode('|', $data['employee_mobile_code']);
            $enterpriseContactModel->employee_mobile_code = $shortCountryNo;
        }
        // 经办人手机号码
        !empty($data['employee_mobile']) && $enterpriseContactModel->employee_mobile = self::stripString($data['employee_mobile']);
        // 经办人所属机构
        !empty($data['employee_department']) && $enterpriseContactModel->employee_department = self::stripString($data['employee_department']);
        // 创建时间
        $enterpriseContactModel->create_time = get_gmtime();
        $enterContractRet = $enterpriseContactModel->insert();
        if (!$enterContractRet) {
            throw new \Exception('创建企业用户联系人信息失败!');
        }
    }

    /**
     * 更新用户绑卡信息
     * @param int $userId
     * @param array $data
     */
    private static function _updateUserBankInfo($userId, &$data, $userBankLastId = 0) {
        if (!is_numeric($userBankLastId) OR $userBankLastId <= 0) return false;
        // 开户名
        $cardName = isset($data['card_name']) && !empty($data['card_name']) ? $data['card_name'] : '';
        // 银行帐号
        $bankcard = isset($data['bankcard']) && !empty($data['bankcard']) ? $data['bankcard'] : '';
        // 开户行名称
        $bankIdValue = isset($data['bank_id_value']) && !empty($data['bank_id_value']) ? $data['bank_id_value'] : (isset($data['bank_id']) && !empty($data['bank_id']) ? $data['bank_id'] : '');
        // 开户行简码
        $bankShortno = isset($data['bank_shortno']) && !empty($data['bank_shortno']) ? $data['bank_shortno'] : '';
        // 开户行所在地-国家
        $bankRegionInputLv1 = isset($data['bank_region_input_lv1']) && !empty($data['bank_region_input_lv1']) ? $data['bank_region_input_lv1'] : (isset($data['bank_region_lv1']) && !empty($data['bank_region_lv1']) ? (int)$data['bank_region_lv1'] : 0);
        // 开户行所在地-省份
        $bankRegionInputLv2 = isset($data['bank_region_input_lv2']) && !empty($data['bank_region_input_lv2']) ? $data['bank_region_input_lv2'] : (isset($data['bank_region_lv2']) && !empty($data['bank_region_lv2']) ? (int)$data['bank_region_lv2'] : 0);
        // 开户行所在地-城市
        $bankRegionInputLv3 = isset($data['bank_region_input_lv3']) && !empty($data['bank_region_input_lv3']) ? $data['bank_region_input_lv3'] : (isset($data['bank_region_lv3']) && !empty($data['bank_region_lv3']) ? (int)$data['bank_region_lv3'] : 0);
        // 开户行所在地-地区
        $bankRegionInputLv4 = isset($data['bank_region_input_lv4']) && !empty($data['bank_region_input_lv4']) ? $data['bank_region_input_lv4'] : (isset($data['bank_region_lv4']) && !empty($data['bank_region_lv4']) ? (int)$data['bank_region_lv4'] : 0);
        // 开户网点
        $bankBankZone = isset($data['bank_bankzone']) && !empty($data['bank_bankzone']) ? $data['bank_bankzone'] : (isset($data['bank_bankzone2']) && !empty($data['bank_bankzone2']) ? $data['bank_bankzone2'] : '');
        // 联行网点
        $branchNo = isset($data['branch_no']) && !empty($data['branch_no']) ? $data['branch_no'] : 0;

        if (check_empty($cardName) && check_empty($bankcard) && check_empty($bankIdValue)
            && check_empty($bankShortno) && check_empty($bankRegionInputLv1)
            && check_empty($bankRegionInputLv2) && check_empty($bankRegionInputLv3)
            && check_empty($bankBankZone) && check_empty($branchNo)) {
            // 获取用户绑卡信息
            $userBankcardInfo = UserBankcardModel::instance()->findByViaSlave('id=:id', '*', array(':id' => $userBankLastId));
            // 开户名
            !empty($cardName) && $userBankcardInfo->card_name = $cardName;
            // 放款账号类型
            $userBankcardInfo->card_type = UserBankcardModel::CARD_TYPE_BUSINESS;
            // 银行帐号
            !empty($bankcard) && $userBankcardInfo->bankcard = $bankcard;
            // 开户行名称
            if (!empty($bankIdValue) && strpos($bankIdValue, '|') !== false) {
                list($shortBankId, $shortBankNo, $shortBankName) = explode('|', $bankIdValue);
                $shortBankId > 0 && $userBankcardInfo->bank_id = (int)$shortBankId;
                !empty($shortBankName) && $data['payment_bank_name'] = $shortBankName;
            }
            // 开户行所在地-国家
            if (!empty($bankRegionInputLv1)) {
                if (strpos($bankRegionInputLv1, '|') !== false) {
                    list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv1);
                    $bankRegionId > 0 && $userBankcardInfo->region_lv1 = $bankRegionId;
                }else{
                    $userBankcardInfo->region_lv1 = $bankRegionInputLv1;
                }
            }
            // 开户行所在地-省份
            if (!empty($bankRegionInputLv2)) {
                if (strpos($bankRegionInputLv2, '|') !== false) {
                    list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv2);
                    $bankRegionId > 0 && $userBankcardInfo->region_lv2 = $bankRegionId;
                }else{
                    $userBankcardInfo->region_lv2 = $bankRegionInputLv2;
                }
            }
            // 开户行所在地-城市
            if (!empty($bankRegionInputLv3)) {
                if (strpos($bankRegionInputLv3, '|') !== false) {
                    list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv3);
                    $bankRegionId > 0 && $userBankcardInfo->region_lv3 = $bankRegionId;
                }else{
                    $userBankcardInfo->region_lv3 = $bankRegionInputLv3;
                }
            }
            // 开户行所在地-地区
            if (!empty($bankRegionInputLv4)) {
                if (strpos($bankRegionInputLv4, '|') !== false) {
                    list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv4);
                    $bankRegionId > 0 && $userBankcardInfo->region_lv4 = $bankRegionId;
                }else{
                    $userBankcardInfo->region_lv4 = $bankRegionInputLv4;
                }
            }
            // 开户网点
            if (!empty($bankBankZone) && strpos($bankBankZone, '|') !== false) {
                list($bankZoneId, $bankZoneName) = explode('|', $bankBankZone);
                $bankZoneName && $userBankcardInfo->bankzone = $bankZoneName;
                $bankZoneName && $data['payment_bank_zone'] = $bankZoneName;
            }
            // 联行号码
            !empty($branchNo) && $userBankcardInfo->branch_no = $branchNo;
            // 用户绑卡状态(0:未验证1:已验证)
            $userBankcardInfo->status = 1;
            // 用户验卡状态(0:未验证1:已验证)
            $userBankcardInfo->verify_status = 1;
            // 更新时间
            $userBankcardInfo->update_time = get_gmtime();
            $userBankRet = $userBankcardInfo->save();
            if (!$userBankRet) {
                throw new \Exception('更新用户银行账户信息失败！');
            }
        }
    }

    /**
     * 更新银行账户信息
     */
    private static function _saveBankAccountInfo($userId, &$data, &$paymentData, $isInsert = true) {
        if ($userId <= 0) return false;
        // 开户名
        $cardName = isset($data['card_name']) && !empty($data['card_name']) ? $data['card_name'] : '';
        // 银行帐号
        $bankcard = isset($data['bankcard']) && !empty($data['bankcard']) ? $data['bankcard'] : '';
        // 开户行名称
        $bankIdValue = isset($data['bank_id_value']) && !empty($data['bank_id_value']) ? $data['bank_id_value'] : (isset($data['bank_id']) && !empty($data['bank_id']) ? $data['bank_id'] : '');
        // 开户行简码
        $bankShortno = isset($data['bank_shortno']) && !empty($data['bank_shortno']) ? $data['bank_shortno'] : '';
        // 开户行所在地-国家
        $bankRegionInputLv1 = isset($data['bank_region_input_lv1']) && !empty($data['bank_region_input_lv1']) ? $data['bank_region_input_lv1'] : (isset($data['bank_region_lv1']) && !empty($data['bank_region_lv1']) ? (int)$data['bank_region_lv1'] : 0);
        // 开户行所在地-省份
        $bankRegionInputLv2 = isset($data['bank_region_input_lv2']) && !empty($data['bank_region_input_lv2']) ? $data['bank_region_input_lv2'] : (isset($data['bank_region_lv2']) && !empty($data['bank_region_lv2']) ? (int)$data['bank_region_lv2'] : 0);
        // 开户行所在地-城市
        $bankRegionInputLv3 = isset($data['bank_region_input_lv3']) && !empty($data['bank_region_input_lv3']) ? $data['bank_region_input_lv3'] : (isset($data['bank_region_lv3']) && !empty($data['bank_region_lv3']) ? (int)$data['bank_region_lv3'] : 0);
        // 开户行所在地-地区
        $bankRegionInputLv4 = isset($data['bank_region_input_lv4']) && !empty($data['bank_region_input_lv4']) ? $data['bank_region_input_lv4'] : (isset($data['bank_region_lv4']) && !empty($data['bank_region_lv4']) ? (int)$data['bank_region_lv4'] : 0);
        // 开户网点
        $bankBankZone = isset($data['bank_bankzone']) && !empty($data['bank_bankzone']) ? $data['bank_bankzone'] : (isset($data['bank_bankzone2']) && !empty($data['bank_bankzone2']) ? $data['bank_bankzone2'] : '');
        // 联行网点
        $branchNo = isset($data['branch_no']) && !empty($data['branch_no']) ? $data['branch_no'] : 0;

        if (check_empty($cardName) && check_empty($bankcard) && check_empty($bankIdValue)
            && check_empty($bankShortno) && check_empty($bankRegionInputLv1)
            && check_empty($bankRegionInputLv2) && check_empty($bankRegionInputLv3)
            && check_empty($bankBankZone) && check_empty($branchNo)) {
            // 银行账户信息入库
            $userBankCardModel = new UserBankcardModel();
            // 开户行名称
            if (!empty($bankIdValue) && strpos($bankIdValue, '|') !== false) {
                list($shortBankId, $shortBankNo, $shortBankName) = explode('|', $bankIdValue);
                $shortBankId > 0 && $userBankCardModel->bank_id = (int)$shortBankId;
                !empty($shortBankName) && $data['payment_bank_name'] = $shortBankName;
            }
            // 开户网点
            if (!empty($bankBankZone) && strpos($bankBankZone, '|') !== false) {
                list($bankZoneId, $bankZoneName) = explode('|', $bankBankZone);
                $bankZoneName && $userBankCardModel->bankzone = $bankZoneName;
                $bankZoneName && $data['payment_bank_zone'] = $bankZoneName;
            }
            if ($isInsert) {
                // 检查银行卡号是否存在
                //$userCardInfo = $userBankCardModel->getUserBankCardRow(sprintf('id > 0 AND bankcard=\'%s\'', $bankcard));
                //if ($userCardInfo) {
                //    throw new \Exception('该银行帐号已存在！');
                //}
                // 开户名
                !empty($cardName) && $userBankCardModel->card_name = $cardName;
                // 银行帐号
                !empty($bankcard) && $userBankCardModel->bankcard = $bankcard;
                // 放款账号类型
                $userBankCardModel->card_type = UserBankcardModel::CARD_TYPE_BUSINESS;
                // 开户行所在地-国家
                if (!empty($bankRegionInputLv1)) {
                    if (strpos($bankRegionInputLv1, '|') !== false) {
                        list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv1);
                        $bankRegionId > 0 && $userBankCardModel->region_lv1 = $bankRegionId;
                    }else{
                        $userBankCardModel->region_lv1 = $bankRegionInputLv1;
                    }
                }
                // 开户行所在地-省份
                if (!empty($bankRegionInputLv2)) {
                    if (strpos($bankRegionInputLv2, '|') !== false) {
                        list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv2);
                        $bankRegionId > 0 && $userBankCardModel->region_lv2 = $bankRegionId;
                    }else{
                        $userBankCardModel->region_lv2 = $bankRegionInputLv2;
                    }
                }
                // 开户行所在地-城市
                if (!empty($bankRegionInputLv3)) {
                    if (strpos($bankRegionInputLv3, '|') !== false) {
                        list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv3);
                        $bankRegionId > 0 && $userBankCardModel->region_lv3 = $bankRegionId;
                    }else{
                        $userBankCardModel->region_lv3 = $bankRegionInputLv3;
                    }
                }
                // 开户行所在地-地区
                if (!empty($bankRegionInputLv4)) {
                    if (strpos($bankRegionInputLv4, '|') !== false) {
                        list($bankRegionId, $bankRegionName) = explode('|', $bankRegionInputLv4);
                        $bankRegionId > 0 && $userBankCardModel->region_lv4 = $bankRegionId;
                    }else{
                        $userBankCardModel->region_lv4 = $bankRegionInputLv4;
                    }
                }
                // 联行号码
                !empty($branchNo) && $userBankCardModel->branch_no = $branchNo;
                // 用户UID
                $userBankCardModel->user_id = $userId;
                // 用户绑卡状态(0:未验证1:已验证)
                $userBankCardModel->status = 0;
                // 三要素验证(支付小额转账认证结果(0:未通过1:通过))
                $userBankCardModel->verify_status = 1;
                // 创建时间
                $userBankCardModel->create_time = get_gmtime();
                $userBankRet = $userBankCardModel->insert();
                $userBankId = $userBankCardModel->db->insert_id();
                if (!$userBankRet || $userBankId <= 0) {
                    throw new \Exception('创建用户银行账户信息失败！');
                }
            }

            // 组织数据用于用户开户绑卡-通知支付部门的数据
            $paymentData['bankName'] = isset($data['payment_bank_name']) ? $data['payment_bank_name'] : ''; //银行名称-开户行名称
            $paymentData['bankCode'] = $bankShortno; //银行编码-开户行简码
            $paymentData['bankCardNo'] = $bankcard; //银行账户-银行帐号
            $paymentData['bankCardName'] = $cardName; //银行开户名-开户名
            $paymentData['bankProvince'] = isset($data['input_bank_region_name2']) && !empty($data['input_bank_region_name2']) ? $data['input_bank_region_name2'] : ''; //省(账户)
            $paymentData['bankCity'] = isset($data['input_bank_region_name3']) && !empty($data['input_bank_region_name3']) ? $data['input_bank_region_name3'] : ''; //市(账户)
            $paymentData['issuerName'] = isset($data['payment_bank_zone']) ? $data['payment_bank_zone'] : ''; //支行名称
            $paymentData['issuer'] = $branchNo; //支行-联行号码

            return $isInsert ? $userBankId : 0;
        }
        return false;
    }

    /**
     * 判断企业用户姓名是否已经存在
     */
    public function canEnterpriseName() {
        $name = !empty($_POST['name']) ? $_POST['name'] : '';
        $user = M('User')->where(array('user_name'=> $name))->find();
        if ($user && $user['id'] > 0) {
            self::jsonOutput(-2, '企业会员名称已存在');
        } else {
            self::jsonOutput(1, '');
        }

    }
}
