<?php

namespace core\service;

use core\dao\EnterpriseContactModel;
use core\dao\EnterpriseModel;
use core\dao\UserBankcardModel;
use core\dao\EnterpriseRegisterModel;
use libs\db\Db;
use libs\utils\Logger;
use core\service\BankService;
use core\service\DeliveryRegionService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use libs\utils\PaymentApi;

/**
 * 企业用户注册相关逻辑
 *
 */
class EnterpriseService extends BaseService {
    /**
     * 以极少的资料注册企业用户
     * @param array $enterpriseData 企业用户注册资料
     * @param array $enterpriseBaseData 企业基础资料信息
     * @param array $contactInfo 企业联系人相关信息
     * @return bool
     */
    public function registerSimpleData($enterpriseRegisterData, $enterpriseBaseData, $contactInfo) {
        try {
            $db = \libs\db\Db::getInstance('firstp2p', 'master');
            $db->startTrans();

            $db->autoExecute('firstp2p_enterprise_register', $enterpriseRegisterData, 'INSERT');
            $db->autoExecute('firstp2p_enterprise', $enterpriseBaseData, 'INSERT');
            $db->autoExecute('firstp2p_enterprise_contact', $contactInfo, 'INSERT');

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
        }
        return false;
    }

    /**
     * 企业全称是否可以注册
     * @param string $enterpriseName
     * @return boolean false 不可以注册 | true 可以注册
     */
    public function canRegisterName($enterpriseName) {
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $count = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_enterprise WHERE company_name = '%s'", addslashes($enterpriseName)));
        if ($count >= 1) {
            return false;
        }
        return true;
    }

    /**
     * 企业全称是否可以注册
     * @param string $enterpriseName
     * @param int $user_id 用户id，默认为0
     * @param int $company_purpose 账户类型，默认是投资户
     * @return boolean false 不可以注册 | true 可以注册
     */
    public function canName($company_name , $user_id = 0, $company_purpose = UserAccountEnum::ACCOUNT_INVESTMENT) {
        // 对于非投资户用户可以重名
        // @jira http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-6370
        // 融资户和投资户唯一限制
        if ($company_purpose != UserAccountEnum::ACCOUNT_INVESTMENT && $company_purpose != UserAccountEnum::ACCOUNT_FINANCE) {
            return true;
        }

        $result = EnterpriseModel::instance()->getByCompanyName($company_name, $user_id, $company_purpose);
        if (count($result) >= 1) {
            return false;
        }
        return true;
    }

    /**
     * 企业证件号是否可以注册
     * @param string $enterpriseName
     * @param int $user_id 用户id，默认为0
     * @param int $company_purpose 账户类型，默认是投资户
     * @return boolean false 不可以注册 | true 可以注册
     */
    public function canCredentialsNo($credentialsNo , $user_id = 0, $company_purpose = UserAccountEnum::ACCOUNT_INVESTMENT) {
        // 对于非投资户用户可以重名
        // @jira http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-6370
        // 融资户和投资户唯一限制
        if ($company_purpose != UserAccountEnum::ACCOUNT_INVESTMENT && $company_purpose != UserAccountEnum::ACCOUNT_FINANCE) {
            return true;
        }

        $result = EnterpriseModel::instance()->getByCredentialsNo($credentialsNo, $user_id, $company_purpose);
        if (count($result) >= 1) {
            return false;
        }
        return true;
    }

    /**
     * 企业证件号是否可以注册
     * @param string $credentialsNo
     * @return boolean false 不可以注册 | true 可以注册
     */
    public function canRegisterCredentialsNo($credentialsNo) {
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $count = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_enterprise WHERE credentials_no = '%s'", addslashes($credentialsNo)));
        if ($count >= 1) {
            return false;
        }
        return true;
    }

    /**
     * 企业全称是否可以注册
     * @param string $phone
     * @return boolean false 不可以注册 | true 可以注册
     */
    public function canRegisterPhone($phone) {
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $count = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_user WHERE mobile = '%s'", addslashes($phone)));
        if ($count >= 1) {
            return false;
        }
        return true;
    }

    /**
     * 企业登录名是否可以注册
     * @param string $loginName
     * @return boolean false 不可以注册 | true 可以注册
     */
    public function canRegisterLoginName($loginName) {
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $count = $db->getOne(sprintf("SELECT COUNT(*) FROM firstp2p_user WHERE user_name = '%s'", addslashes($loginName)));
        if ($count >= 1) {
            return false;
        }
        return true;
    }

    /**
     * 根据企业用户ID获取企业相关信息
     * @param $user_id
     * @return array
     */
    public function getInfo($user_id = 0){
        $result_base = EnterpriseModel::instance()->getEnterpriseInfoByUserID($user_id);
        $result_contact = EnterpriseContactModel::instance()->getEnterpriseInfoByUserID($user_id);
        $result_register = EnterpriseRegisterModel::instance()->getEnterpriseInfoByUserID($user_id);
        $result_bank = UserBankcardModel::instance()->getByUserId($user_id);
        $result = array(
            'base'      => empty($result_base) ? array() : $result_base->getRow(),
            'contact'   => empty($result_contact) ? array() : $result_contact->getRow(),
            'register'  => empty($result_register) ? array() : $result_register->getRow(),
            'bank'      => empty($result_bank) ? array() : $result_bank->getRow(),
        );

        return $result;
    }

    /**
     * 判断代理人与法定代表人证件号是否一致
     * @param int $user_id
     * @return bool
     */
    public function isSameOperator($user_id = 0) {
        $result_base = EnterpriseModel::instance()->getEnterpriseInfoByUserID($user_id);
        $result_contact = EnterpriseContactModel::instance()->getEnterpriseInfoByUserID($user_id);
        return $result_base['legalbody_credentials_no']==$result_contact['major_condentials_no'];
    }

    /**
     * 根据企业用户ID更新企业相关信息
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function updateByUid($user_id = 0,$data = array()){
        if(empty($user_id)) return false;
        if(empty($data)) return false;

        $result = EnterpriseModel::instance()->updateByUid($user_id,$data);
        return $result;
    }

    /**
     * 根据企业用户ID更新企业相关信息
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function updateContactByUid($user_id = 0,$data = array()){
        if(empty($user_id)) return false;
        if(empty($data)) return false;

        $result = EnterpriseContactModel::instance()->updateByUid($user_id,$data);
        return $result;
    }

    /**
     * 根据企业用户ID更新企业相关信息
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function updateRegisterByUid($user_id = 0,$data = array()){
        if(empty($user_id)) return false;
        if(empty($data)) return false;

        $result = EnterpriseRegisterModel::instance()->updateVerifyStatus($user_id,$data);
        return $result;
    }

    /**
     * 获取企业用户的审核状态
     */
    public function getVerifyStatus($userId) {
        $info = EnterpriseRegisterModel::instance()->getInfoByUserID($userId);
        return isset($info['verify_status']) ? $info['verify_status'] : EnterpriseRegisterModel::VERIFY_STATUS_PASS;
    }

    /**
     * 组织数据用于用户开户绑卡-通知存管系统的数据
     */
    public function getSupervisionBankData($userId, $data) {
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
     * 提交企业用户开户信息
     */
    public function apply($data, $userId) {
        Logger::info('EnterpriseService.Apply data: '.json_encode($data));

        // all of params in data
        // firstp2p_enterprise
        $basicInfo = [
            'user_id' => $userId,
            'company_name' => $data['company_name'],                                // 企业名称
            'company_shortname' => $data['company_name'],                           // 企业简称
            'credentials_no' => $data['credentials_no'],                            // 企业证件号
            'reg_amt' => $data['company_register_amount'],                          // 企业注册资金
            'app_no' => $data['company_apply_license_id'],                          // 企业开户许可证核准号
            'indu_cate' => $data['credentials_type'],                               // 企业行业分类
            'legalbody_name' => $data['legalbody_name'],                            // 法定代表人姓名
            'legalbody_credentials_type' => $data['legalbody_credentials_type'],    // 法定代表人证件类别
            'legalbody_credentials_no' => $data['legalbody_credentials_no'],        // 法定代表人证件号码
            'credentials_expire_date' => date('Y-m-d', 0),                          // 默认证件件有效期的起始时间是1970 01 01
        ];

        // 根据首位是不是9 确定证件类别；9 三证合一营业执照, 非9 营业执照
        if ($this->isNewCredentials($data['credentials_no'])) {
            $basicInfo['credentials_type'] = UserAccountEnum::CREDENTIALS_TYPE_LICENSE_NEW;     // 三证合一营业执照
        } else {
            $basicInfo['credentials_type'] = UserAccountEnum::CREDENTIALS_TYPE_LICENSE;             // 营业执照
        }

        // 选填项
        if (!empty($data['is_permanent'])) {
            $basicInfo['is_permanent'] = 1;       // 是否是长期 0非长期 1长期
        } else if (!empty($data['credentials_expire_at'])) {
            $basicInfo['credentials_expire_at'] = $data['credentials_expire_at'];              // 有效期结束时间
            $basicInfo['is_permanent'] = 0;       // 是否是长期 0非长期 1长期
        }

        // firstp2p_enterprise_contact
        $contactInfo = [
            'user_id' => $userId,
            'contract_mobile' => $data['company_phone_number'],             // 企业联系方式
            'major_type' => $data['major_type'],                            // 操作人类型 1法人本人 2委托代理人
        ];

        // 区分账户操作人是本人或者是代理人的情况
        if ($data['major_type'] == UserAccountEnum::MAJOR_TYPE_SELF) {
            $contactInfo['major_name'] = $data['legalbody_name'];
            $contactInfo['major_condentials_type'] = $data['legalbody_credentials_type'];
            $contactInfo['major_condentials_no'] = $data['legalbody_credentials_no'];
            $contactInfo['major_mobile_code'] = $GLOBALS['dict']['MOBILE_CODE'][$data['sms_country_code1']]['code'];  // 代理人手机号区号
            $contactInfo['major_mobile'] = $data['major_mobile_self'];                           // 代理人手机号
            $contactInfo['major_email'] = $data['major_email_self'];                             // 代理人电子邮件
            $basicInfo['legalbody_mobile_code'] = $GLOBALS['dict']['MOBILE_CODE'][$data['sms_country_code1']]['code'];  // 法人手机号
            $basicInfo['legalbody_mobile'] = $data['major_mobile_self'];    // 法人手机号
            $basicInfo['legalbody_email'] = $data['major_email_self'];      // 法人邮箱
        } else {
            $contactInfo['major_name'] = $data['major_name'];                               // 代理人姓名
            $contactInfo['major_condentials_type'] = $data['major_condentials_type'];       // 代理人证件类型
            $contactInfo['major_condentials_no'] = $data['major_condentials_no'];           // 代理人证件号码
            $contactInfo['major_mobile_code'] = $GLOBALS['dict']['MOBILE_CODE'][$data['sms_country_code']]['code'];  // 代理人手机号区号
            $contactInfo['major_mobile'] = $data['major_mobile'];                          // 代理人手机号
            $contactInfo['major_email'] = $data['major_email'];                            // 代理人电子邮件
        }
        // firstp2p_user_bank
        $bankInfo = [
            'user_id' => $userId,
            'card_name' => $data['company_name'],           // 开户人姓名
            'bankcard' => $data['bankcard'],                // 银行账户
            'bank_id' => $data['bank_id'],                  // 开户行名称id
            'region_lv1' => 1,                              // 开户行所在 国家 默认 1中国
            'region_lv2' => $data['bankzone_region0'],      // 开户行所在 省
            'region_lv3' => $data['bankzone_region1'],      // 开户行所在 市
            'bankzone' => $data['bankzone'],                // 开户网点
        ];
        // 选填项
        if (!empty($data['bankzone_region2'])) {
            $bankInfo['region_lv4'] = $data['bankzone_region2'];    // 开户行所在 区
        }

        $GLOBALS['db']->startTrans();
        try {
            // 保存企业基本信息
            $resultBasic = $this->applyEnterpriseBasicInfo($basicInfo);
            if (!$resultBasic) {
                throw new \Exception('Fail to commit applyEnterpriseBasicInfo');
            }
            // 保存企业联系人信息
            $resultContact = $this->applyEnterpriseContactInfo($contactInfo);
            if (!$resultContact) {
                throw new \Exception('Fail to commit applyEnterpriseContactInfo');
            }
            // 保存企业银行卡信息
            $resultBank = $this->applyEnterpriseBankInfo($bankInfo);
            if (!$resultBank) {
                throw new \Exception('Fail to commit applyEnterpriseBankInfo');
            }

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log('EnterpriseService.Apply Error: '
                    .$e->getMessage()
                    .', basicInfo: '.json_encode($basicInfo, JSON_UNESCAPED_UNICODE)
                    .', contactInfo: '.json_encode($contactInfo, JSON_UNESCAPED_UNICODE)
                    .', bankInfo: '.json_encode($bankInfo, JSON_UNESCAPED_UNICODE)
                , Logger::ERR);
            return false;
        }
    }

    /**
     * 提交企业用户基本信息
     */
    public function applyEnterpriseBasicInfo($basicInfo) {
        $userId = $basicInfo['user_id'];
        $enterpriseModel = EnterpriseModel::instance()->getEnterpriseInfoByUserID($userId);
        $result = false;
        // 前端输入的是以万为单位
        $basicInfo['reg_amt'] = bcmul($basicInfo['reg_amt'], 10000, 2);
        if (empty($enterpriseModel)) {
            $basicInfo['create_time'] = time();
            $basicInfo['update_time'] = $basicInfo['create_time'];
            $result = EnterpriseModel::instance()->addEnterpriseInfo($basicInfo);
        } else {
            $basicInfo['update_time'] = time();
            $result = $enterpriseModel->updateEnterpriseInfo($basicInfo);
        }
        return $result;
    }

    /**
     * 提交企业用户联系人相关信息
     */
    public function applyEnterpriseContactInfo($contactInfo) {
        $userId = $contactInfo['user_id'];
        $enterpriseContactModel = EnterpriseContactModel::instance()->getEnterpriseInfoByUserID($userId);
        $result = false;
        if (empty($enterpriseContactModel)) {
            $contactInfo['create_time'] = time();
            $contactInfo['update_time'] = $contactInfo['create_time'];
            $result = EnterpriseContactModel::instance()->addEnterpriseContactInfo($contactInfo);
        } else {
            $contactInfo['update_time'] = time();
            $result = $enterpriseContactModel->updateByUid($userId, $contactInfo);
        }
        return $result;
    }

    /**
     * 提交企业用户银行卡信息
     */
    public function applyEnterpriseBankInfo($bankInfo) {
        $userId = $bankInfo['user_id'];
        $userBankcardModel = UserBankcardModel::instance()->getByUserId($userId);
        $result = false;
        if (empty($userBankcardModel)) {
            $bankInfo['create_time'] = time();
            $bankInfo['update_time'] = $bankInfo['create_time'];
            $result = UserBankcardModel::instance()->insertCard($bankInfo);
        } else {
            $bankInfo['update_time'] = time();
            $result = $userBankcardModel->updateCardByUserId($userId, $bankInfo);
        }
        return $result;
    }

    /**
     * 开户数据校验 全部校验 4步
     */
    public function validateApply($userId, $data) {
        $checkResultData = [];

        $resultStep1 = $this->validateStep1($userId, $data);
        if (!empty($resultStep1)) {
            $checkResultData = array_merge($checkResultData, $resultStep1);
        }

        $resultStep2 = $this->validateStep2($userId, $data);
        if (!empty($resultStep2)) {
            $checkResultData = array_merge($checkResultData, $resultStep2);
        }

        $resultStep3 = $this->validateStep3($userId, $data);
        if (!empty($resultStep3)) {
            $checkResultData = array_merge($checkResultData, $resultStep3);
        }

        $resultStep4 = $this->validateStep4($userId, $data);
        if (!empty($resultStep4)) {
            $checkResultData = array_merge($checkResultData, $resultStep4);
        }

        return $checkResultData;
    }

    /**
     * 校验开户第1步数据
     *
     * @param int userId
     * @param array form
     */
    public function validateStep1($userId, $form) {
        $checkResultData = [];
        if (empty($userId)) {
            $checkResultData[] = ['field' => 'login_user', 'message' => '请您先登录'];
        }

        // 企业全称
        if (empty($form['company_name'])) {
            $checkResultData[] = ['field' => 'company_name', 'message' => '企业全称不能为空'];
        } else  {
            $enterpriseName = addslashes(trim($form['company_name']));
            if (!$this->canName($enterpriseName, $userId)) {
                $checkResultData[] = ['field' => 'company_name', 'message' => '企业已存在'];
            }
        }

        // 企业证件号码
        if (empty($form['credentials_no'])) {
            $checkResultData[] = ['field' => 'credentials_no', 'message' => '证件号码不能为空'];
        } else {
            $credentialsNo = addslashes(trim($form['credentials_no']));
            if (!$this->canCredentialsNo($credentialsNo, $userId)) {
                $checkResultData[] = ['field' => 'credentials_no', 'message' => '企业已存在'];
            }
        }

        // 企业注册资金
        if (empty($form['company_register_amount'])) {
            $checkResultData[] = ['field' => 'company_register_amount', 'message' => '请输入企业注册资金'];
        } else {
            if (!is_numeric($form['company_register_amount'])) {
                 $checkResultData[] = ['field' => 'company_register_amount', 'message' => '请输入小写数字'];
            }
        }

        // 企业开户许可证核准号
        if (empty($form['company_apply_license_id'])) {
            $checkResultData[] = ['field' => 'company_apply_license_id', 'message' => '请输入企业开户许可证核准号'];
        } else {
            $result = $this->canAppId($form['company_apply_license_id']);
            if (!$result) {
                $checkResultData[] = ['field' => 'company_apply_license_id', 'message' => '请输入以J开头的14位企业开户许可证核准号'];
            }
        }

        // 企业联系方式
        if (empty($form['company_phone_number'])) {
            $checkResultData[] = ['field' => 'company_phone_number', 'message' => '请输入企业联系方式'];
        } else {
            if (!is_mobile($form['company_phone_number']) && !is_telephone($form['company_phone_number'])) {
                $checkResultData[] = ['field' => 'company_phone_number', 'message' => '手机号格式不正确'];
            }
            if (!$this->canCompanyPhoneNumber($form['company_phone_number'], $userId)) {
                $checkResultData[] = ['field' => 'company_phone_number', 'message' => '企业联系方式与法人/代理人手机号码相同，请重新填写'];
            }
        }

        // 证件有效期
/*         if (empty($form['credentials_expire_date']) || $form['credentials_expire_date'] == '0000-00-00') { */
            // $checkResultData[] = ['field' => 'credentials_expire_date', 'message' => '企业证件号有效期不能为空'];
        /* }  else { */
            // $credentialsExpireDate = strtotime(trim($from['credentials_expire_date']));
            // $credentialsExpireAt = strtotime(trim($form['credentials_expire_at']));
            // if ($credentialsExpireAt - $credentialsExpireDate < 86400) {
            //     $checkResult['data'][] = ['field' => 'credentials_expire_date', 'message' => '企业证件号有效期无效'];
            //     $checkPassed = false;
            // }
        // }

        // 企业证件截止时间、是否长期有效的校验
        if (empty($form['credentials_expire_at']) && (int)$form['is_permanent'] === 0) {
            $checkResultData[] = ['field' => 'credentials_expire_at', 'message' => '请选择正确的有效期'];
        }

        // 企业行业分类
        if (!isset($form['credentials_type'])) {
            $checkResultData[] = ['field' => 'credentials_type', 'message' => '请选择企业行业类型'];
        }

        return $checkResultData;
    }

    /**
     * 校验开户第2步数据
     *
     * @param int userId
     * @param array form
     */
    public function validateStep2($userId, $form) {
        $checkResultData = [];
        if (empty($userId)) {
            $checkResultData[] = ['field' => 'login_user', 'message' => '请您先登录'];
        }

        // 法人姓名
        if (empty($form['legalbody_name'])) {
            $checkResultData[] = ['field' => 'legalbody_name', 'message' => '法人姓名不能为空'];
        } else if (!$this->isMatchChinese($form['legalbody_name'])) {
            $checkResultData[] = ['field' => 'legalbody_name', 'message' => '请输入中文'];
        }

        // 法人证件类别
        if (empty($form['legalbody_credentials_type']) || !is_numeric($form['legalbody_credentials_type'])) {
            $checkResultData[] = ['field' => 'legalbody_credentials_type', 'message' => '法人证件类别不能为空'];
        }

        // 法人证件号码
        if (empty($form['legalbody_credentials_no'])) {
            $checkResultData[] = ['field' => 'legalbody_credentials_no', 'message' => '法人证件号码不能为空'];
        } elseif ($form['legalbody_credentials_type'] == 1 && strlen($form['legalbody_credentials_no']) != 18){
            $checkResultData[] = ['field' => 'legalbody_credentials_no', 'message' => '法人证件号码格式有误'];
        }

        return $checkResultData;
    }

    /**
     * 校验开户第3步数据
     *
     * @param int userId
     * @param array form
     */
    public function validateStep3($userId, $form) {
        $checkResultData = [];
        if (empty($userId)) {
            $checkResultData[] = ['field' => 'login_user', 'message' => '请您先登录'];
        }
        // 开户人姓名
        /* if (empty($form['card_name'])) { */
            // $checkResultData[] = ['field' => 'card_name', 'message' => '开户名不能为空'];
        /* } */

        // 银行账号
        if (empty($form['bankcard'])) {
            $checkResultData[] = ['field' => 'bankcard', 'message' => '银行账号不能为空'];
        }elseif (!is_numeric($form['bankcard'])){
            $checkResultData[] = ['field' => 'bankcard', 'message' => '银行账号格式有误'];
        }

        // 开户行名称
        if (empty($form['bank_id'])) {
            $checkResultData[] = ['field' => 'bank_id', 'message' => '开户行名称不能为空'];
        }

        // 开户行所在地
        if (empty($form['bankzone_region1']) || empty($form['bankzone_region2'])) {
            $checkResultData[] = ['field' => 'bankzone_region0', 'message' => '开户行所在地不能为空'];
        }

        // 开户网点
        if (empty($form['bankzone'])) {
           $checkResultData[] = ['field' => 'bankzone', 'message' => '开户网点不能为空'];
        }
        return $checkResultData;

    }

    /**
     * 校验开户第4步数据
     *
     * @param int userId
     * @param array form
     */
    public function validateStep4($userId, $form) {
        $checkResultData = [];
        if (empty($userId)) {
            $checkResultData[] = ['field' => 'login_user', 'message' => '请您先登录'];
        }

        // 企业账户代理人类型 默认是本人操作
        $form['major_type'] = empty($form['major_type'])
            ? UserAccountEnum::MAJOR_TYPE_SELF
            : $form['major_type'];

        if ($form['major_type'] == UserAccountEnum::MAJOR_TYPE_PROXY) {
            // 企业账户负责人姓名
            if (empty($form['major_name'])) {
                $checkResultData[] = ['field' => 'major_name', 'message' => '代理人姓名不能为空'];
            } else if (!$this->isMatchChinese($form['major_name'])) {
                $checkResultData[] = ['field' => 'major_name', 'message' => '请输入中文'];
            }

            // 企业用户负责人证件类别
            if (empty($form['major_condentials_type']) || !is_numeric($form['major_condentials_type'])) {
                $checkResultData[] = ['field' => 'major_condentials_type', 'message' => '代理人证件类别不能为空'];
            }

            // 企业用户负责人证件号码
            if (empty($form['major_condentials_no'])) {
                $checkResultData[] = ['field' => 'major_condentials_no', 'message' => '代理人证件号码不能为空'];
            } elseif ($form['major_condentials_no'] == 1 && strlen($form['major_condentials_no']) != 18){
                $checkResultData[] = ['field' => 'major_condentials_no', 'message' => '代理人证件号码格式有误'];
            }
            // 企业用户负责人手机号码
            if (empty($form['major_mobile'])) {
                $checkResultData[] = ['field' => 'major_mobile', 'message' => '代理人手机号码不能为空'];
            } else if (!is_mobile($form['major_mobile'])) {
                $checkResultData[] = ['field' => 'major_mobile', 'message' => '代理人手机号码格式有误'];
            } else if ($form['major_mobile'] == $form['company_phone_number']) {         // 确认代理人手机号是否与企业联系方式一致
                $checkResultData[] = ['field' => 'major_mobile', 'message' => '代理人手机号码不能与企业联系方式相同'];
            }
            // 代理人邮箱地址
            if (empty($form['major_email'])) {
                $checkResultData[] = ['field' => 'major_email', 'message' => '代理人邮箱地址不能为空'];
            } elseif(!is_email($form['major_email'])){
                $checkResultData[] = ['field' => 'major_email', 'message' => '代理人邮箱地址格式错误'];
            }
        } else {
            // 企业用户负责人手机号码
            if (empty($form['major_mobile_self'])) {
                $checkResultData[] = ['field' => 'major_mobile_self', 'message' => '法定代表人手机号码不能为空'];
            } else if (!is_mobile($form['major_mobile_self'])) {
                $checkResultData[] = ['field' => 'major_mobile_self', 'message' => '法定代表人手机号码格式有误'];
            } else if ($form['major_mobile_self'] == $form['company_phone_number']) {         // 确认代理人手机号是否与企业联系方式一致
                $checkResultData[] = ['field' => 'major_mobile_self', 'message' => '法定代表人手机号码不能与企业联系方式相同'];
            }
            // 代理人邮箱地址
            if (empty($form['major_email_self'])) {
                $checkResultData[] = ['field' => 'major_email_self', 'message' => '法定代表人邮箱地址不能为空'];
            } elseif(!is_email($form['major_email_self'])){
                $checkResultData[] = ['field' => 'major_email_self', 'message' => '法定代表人邮箱地址格式错误'];
            }
        }

        return $checkResultData;
    }

    /**
     * 匹配企业开户许可证核准号 J开头 14位
     */
    public function canAppId($appId) {
        $appId = trim($appId);
        return preg_match('/^J\d{13}$/', $appId);
    }

    /**
     * 匹配中文
     */
    public function isMatchChinese($data) {
        return preg_match("/^[\x{4e00}-\x{9fa5}]+$/u",$data);
    }

    /**
     * 根据首位 判断营业执照类型
     */
    public function isNewCredentials($data) {
        return substr($data, 0, 1) == 9 ? true : false;
    }

    /**
     * 判断企业联系方式 是否与代理人/法人手机号相同
     */
    public function canCompanyPhoneNumber($companyPhone, $userId) {
        $user = $this->getInfo($userId);
        return (!empty($user['contact']['major_mobile']) && $user['contact']['major_mobile'] == $companyPhone)
            ? false
            : true;
    }

    /**
     * 获取企业用户接受短信手机号列表的第一个手机信息
     */
    public function getFirstReceiveMsgPhone($enterpriseUserId) {
        $userInfo = $this->getInfo($enterpriseUserId);
        if (empty($userInfo['contact']['receive_msg_mobile'])) {
            return '';
        }
        $list = explode(',', $userInfo['contact']['receive_msg_mobile']);
        return array_shift($list);
    }
}
