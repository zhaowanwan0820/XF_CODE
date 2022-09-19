<?php
/**
 *   个人信息设置
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\utils\Site;
use core\service\account\AccountService;
use core\service\user\BankService;
use core\service\user\PassportService;
use core\service\payment\PaymentService;
use core\service\payment\PaymentUserAccountService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionService;
use core\service\risk\RiskAssessmentService;
use core\service\reserve\UserReservationService;
use core\service\creditloan\CreditLoanService;
use core\service\coupon\CouponService;
use core\service\user\UserLoginService;
use core\enum\UserEnum;
use core\Enum\UserAccountEnum;

class Setup extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS['user_info'];
        $userId = $user_info['id'];
        // 获取用户账户ID
        $accountId = AccountService::getUserAccountId($userId, $user_info['user_purpose']);
        //是否为18家银行
        $bank_list = BankService::getBankUserByPaymentMethod();
        $hideExtra = true;
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                if (!isset($bank['id'])) continue;
                $hideExtraBanks[] = $bank['id'];
            }
        }

        //获取用户银行卡信息
        $bankcard_info = BankService::getNewCardByUserId($userId);
        if (!empty($bankcard_info) && !in_array($bankcard_info['bank_id'], $hideExtraBanks)){
            $hideExtra = false;
        }

        $protect_pwd = get_user_security($userId);
        $protect_pwd = $protect_pwd == false ? 0 : 1;

        // 银行卡信息
        $bankcard = BankService::getUserBankInfo($userId);
        $hasPassport = PaymentUserAccountService::hasPassport($userId, $user_info);
        // 快捷支付
        $usedQuickPay = false;
        if ($user_info['user_type'] != UserEnum::USER_TYPE_ENTERPRISE)
        {
            $usedQuickPay = PaymentService::usedQuickPay($userId);
        }

        // 用户绑定邀请码
        $coupon = CouponService::getByUserId($userId);

       // 企业用户的逻辑
        if (isset($user_info['user_type']) && $user_info['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
            $enterpriseMobileArray = array();
            // 获取企业用户接收短信的手机号
            $enterpriseContactMobileInfo = UserLoginService::getEnterpriseMobileList($userId, true, $bankcard_info);
            if (!empty($enterpriseContactMobileInfo)) {
                foreach ($enterpriseContactMobileInfo as $mobileItem) {
                    $enterpriseMobileArray[] = moblieFormat($mobileItem['mobile'], $mobileItem['code']);
                }
                $this->tpl->assign('enterpriseReceiveMobile', join(',', $enterpriseMobileArray));
            }
        }
        $paymentObj = new PaymentService();
        $formString = $paymentObj->getBindCardForm(['token' => base64_encode(microtime(true))], true, false, 'bindCardForm');
        $bankcardValidateForm = $paymentObj->getBankcardValidateForm(['token' => base64_encode(microtime(true))], true, false, 'bankcardValidateForm');

        //风险评估
        if ($user_info['idcardpassed'] == 1) {
            $obj = new RiskAssessmentService();
            $ura = $obj->getUserRiskAssessmentData($userId);
            $ura['riskValid'] = date("Y年m月d日",$ura['riskValid']);
        }

        // 总资产是否为零
        $supersisionAccountObj = new SupervisionAccountService();
        $isZeroAssets = $supersisionAccountObj->isZeroUserAssets($accountId);
        // 存管相关参数
        $svData = ['isOpenAccount'=>0, 'quickBidAuth'=>0, 'isReserveValid'=>0, 'yxtRepayAuth'=>0, 'isYxtValid'=>0, 'isShowTransfer' => 0];
        // 存管开关是否开启
        $isSupervisionData = $supersisionAccountObj->isSupervision($accountId);
        $svData['isOpenSv'] = isset($isSupervisionData['isSvOpen']) ? (int)$isSupervisionData['isSvOpen'] : 0;
        if ($svData['isOpenSv'] === 1) {
            // 查询用户是否开启存管系统帐号
            $svData['isOpenAccount'] = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;

            // 获取PC端是否显示取消授权的开关
            $supervisionObj = new SupervisionService();
            $isAuthOpen = $supervisionObj->isCancelAuthOpen();
            if ($isAuthOpen == 1) {
                // 随心约-快捷投资服务
                $svData['quickBidAuth'] = (int)$supersisionAccountObj->isQuickBidAuthorization($accountId);
                if ($svData['quickBidAuth'] == 1) {
                    // 随心约-用户是否有未结束的预约记录
                    $userReservationObj = new UserReservationService();
                    $userValidReservelist = $userReservationObj->getUserValidReserveList($accountId);
                    $svData['isReserveValid'] = !empty($userValidReservelist['userReserveList']) ? 1 : 0;
                    unset($userValidReservelist);
                }

                // 银信通-免密还款授权
                $svData['yxtRepayAuth'] = (int)$supersisionAccountObj->isYxtAuthorization($accountId);
                if ($svData['yxtRepayAuth'] == 1) {
                    // 银信通-用户是否有未还款的记录
                    $creditLoanObj = new CreditLoanService();
                    $userCreditCnt = $creditLoanObj->getNotFinishCreditCount($userId);
                    $svData['isYxtValid'] = (int)$userCreditCnt > 0 ? 1 : 0;
                }
            }
        }

        // 通行证逻辑, 如果不是本地生成的用户，修改密码按钮置灰
        if (\es_session::get('ppId') && $bizInfo = PassportService::isThirdPassport($user_info['mobile'])) {
            $this->tpl->assign('passportBizInfo', $bizInfo);
            $this->tpl->assign('noPassportPwdEdit', true);
        }else{
            $this->tpl->assign('passportBizInfo', ['platformName'=>'', 'url'=>'']);
        }

        //存管降级
        $isSvDown = SupervisionService::isServiceDown();
        $svMaintainMessage = SupervisionService::maintainMessage();
        $this->tpl->assign('isSvDown', $isSvDown);
        $this->tpl->assign('svMaintainMessage', $svMaintainMessage);

        // TODO 用户用途判断的时候使用账户用途体系
        // 开通超级账户和存管账户并且 用户是非投资户
        $disableTransfer = app_conf('SV_UNTRANSFERABLE');
        if (!$isSvDown && $svData['isOpenAccount'] && !$disableTransfer && $user_info['payment_user_id'] != 0 && !in_array($user_info['user_purpose'], [UserAccountEnum::ACCOUNT_INVESTMENT, UserAccountEnum::ACCOUNT_MIX])) {
            $svData['isShowTransfer'] = 1;
        }
        //账户授权管理开关
        $this->tpl->assign('accountAuthManageSwitch', (int)app_conf('ACCOUNT_AUTH_MANAGE_SWITCH'));

        $siteId = Site::getId();
        $this->tpl->assign('siteId', $siteId);

        // 主站以及大陆二代身份证用户,做四要素验卡, 其他用户保持原样
        $this->tpl->assign('needVerifyNewCard', Site::getId() == 1 && !$hasPassport);
        $this->tpl->assign('formString', $formString);
        $this->tpl->assign('bankcardValidateForm', $bankcardValidateForm);
        $this->tpl->assign('canResetBank', $isZeroAssets && app_conf('WEB_APP_REMOVE_BANKCARD'));
        $this->tpl->assign('hideExtra', $hideExtra);
        $this->tpl->assign('protect_pwd', $protect_pwd);
        $this->tpl->assign('hasPassport', $hasPassport);
        $this->tpl->assign('usedQuickPay', $usedQuickPay);
        $this->tpl->assign('bankcard', $bankcard);
        $this->tpl->assign('is_audit', $bankcard['is_audit']);
        $this->tpl->assign('coupon', $coupon);
        $this->tpl->assign('ura',isset($ura) ? $ura : array());
        $this->tpl->assign('svData', $svData);
        $this->tpl->assign('user_info', $user_info);
        $this->tpl->assign("inc_file", "web/views/account/setup.html");
        $this->template = "web/views/account/frame.html";
    }
}