<?php

/**
 *   个人信息设置
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;
use core\dao\WangxinPassportModel;
use core\service\PassportService;
use core\service\CouponService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class Setup extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {

        $user_info = $GLOBALS['user_info'];

        //是否为18家银行
        $bank_list = $this->rpc->local('BankService\getBankUserByPaymentMethod', array());
        $hideExtra = true;
        $hideExtraBanks = array();
        if (is_array($bank_list)) {
            foreach ($bank_list as $bank) {
                $hideExtraBanks[] = $bank['id'];
            }
        }


        //$bank_list = BankModel::instance()->getAllByStatusOrderByRecSortId('0');
        //地区列表
        //获取用户银行卡信息
        $bankcard_info = $this->rpc->local('BankService\userBank',array($GLOBALS['user_info']['id'],true));
        if (!in_array(@$bankcard_info['bank_id'], $hideExtraBanks)){
            $hideExtra = false;
        }

        $protect_pwd = get_user_security($GLOBALS['user_info']['id']);
        $protect_pwd = $protect_pwd == false ? 0 : 1;

        // 银行卡信息
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($user_info['id']));
        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_info['id']));
        // 快捷支付
        $usedQuickPay = false;
        if ($user_info['user_type'] != '1')
        {
            $usedQuickPay = $this->rpc->local('AccountService\usedQuickPay', array($user_info['id']));
        }

        //用户绑定邀请码
        $couponService = new CouponService();
        $coupon = $this->rpc->local('CouponBindService\getByUserId',array($user_info['id']));
        if(!empty($coupon) && !empty($coupon['refer_user_id']) && !$couponService->hasServiceAbility($coupon['refer_user_id'])){
            $coupon['short_alias'] = '';
        }

        // 收获地址信息
        $delivery_infor = $this->rpc->local('AddressService\getList', array($user_info['id']));
        $delivery_infor = str_replace(':','',$delivery_infor[0]['area']);

        // 企业用户的逻辑 Add By guofeng 20160118 15:50
        if (isset($GLOBALS['user_info']['user_type']) && $GLOBALS['user_info']['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) {
            $enterpriseMobileArray = array();
            // 获取企业用户接收短信的手机号
            $userService = new \core\service\UserService($GLOBALS['user_info']['id']);
            $enterpriseContactMobileInfo = $userService->getEnterpriseContactInfo(true);
            if ($enterpriseContactMobileInfo) {
                foreach ($enterpriseContactMobileInfo as $mobileItem) {
                    $enterpriseMobileArray[] = moblieFormat($mobileItem['mobile'], $mobileItem['code']);
                }
                $this->tpl->assign('enterpriseReceiveMobile', join(',', $enterpriseMobileArray));
            }
        }
        $formString = $this->rpc->local('PaymentService\getBindCardForm', [['token' => base64_encode(microtime(true))], true, false, 'bindCardForm']);
        $bankcardValidateForm = $this->rpc->local('PaymentService\getBankcardValidateForm', [['token' => base64_encode(microtime(true))], true, false, 'bankcardValidateForm']);

        //风险评估
        if ($user_info['idcardpassed'] == 1) {
            $ura = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array($user_info['id']));
            $ura['riskValid'] = date("Y年m月d日",$ura['riskValid']);
        }

        // 总资产是否为零
        $isZeroAssets = $this->rpc->local('SupervisionAccountService\isZeroUserAssets', array($user_info['id']));//存管

        // 存管相关参数
        $svData = ['isOpenAccount'=>0, 'quickBidAuth'=>0, 'isReserveValid'=>0, 'yxtRepayAuth'=>0, 'isYxtValid'=>0, 'isShowTransfer' => 0];
        // 存管开关是否开启
        $isSupervisionData = $this->rpc->local('SupervisionAccountService\isSupervision', [$user_info['id']]);
        $svData['isOpenSv'] = isset($isSupervisionData['isSvOpen']) ? (int)$isSupervisionData['isSvOpen'] : 0;
        if ($svData['isOpenSv'] === 1) {
            // 查询用户是否开启存管系统帐号
            $svData['isOpenAccount'] = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;


            // 获取PC端是否显示取消授权的开关
            $isAuthOpen = (int)$this->rpc->local('SupervisionBaseService\isCancelAuthOpen');
            if ($isAuthOpen == 1) {
                // 随鑫约-快捷投资服务
                $svData['quickBidAuth'] = (int)$this->rpc->local('SupervisionAccountService\isQuickBidAuthorization', [$user_info['id']]);
                if ($svData['quickBidAuth'] == 1) {
                    // 随鑫约-用户是否有未结束的预约记录
                    $userValidReservelist = $this->rpc->local('UserReservationService\getUserValidReserveList', [$user_info['id']]);
                    $svData['isReserveValid'] = !empty($userValidReservelist['userReserveList']) ? 1 : 0;
                    unset($userValidReservelist);
                }

                // 银信通-免密还款授权
                $svData['yxtRepayAuth'] = (int)$this->rpc->local('SupervisionAccountService\isYxtAuthorization', [$user_info['id']]);
                if ($svData['yxtRepayAuth'] == 1) {
                    // 银信通-用户是否有未还款的记录
                    $userCreditCnt = $this->rpc->local('CreditLoanService\getNotFinishCreditCount', [$user_info['id']]);
                    $svData['isYxtValid'] = (int)$userCreditCnt > 0 ? 1 : 0;
                }
            }
        }

        // 通行证逻辑, 如果不是本地生成的用户，修改密码按钮置灰
        $passportService = new PassportService();
        if (\es_session::get('ppId') && $bizInfo = $passportService->isThirdPassport($user_info['mobile'])) {
            $this->tpl->assign('passportBizInfo', $bizInfo);
            $this->tpl->assign('noPassportPwdEdit', true);
        }

        //存管降级
        $isSvDown = Supervision::isServiceDown();
        $svMaintainMessage = Supervision::maintainMessage();
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

        $siteId = \libs\utils\Site::getId();
        $this->tpl->assign('siteId', $siteId);

        // 主站以及大陆二代身份证用户,做四要素验卡, 其他用户保持原样
        $this->tpl->assign('needVerifyNewCard', \libs\utils\Site::getId() == 1 && !$hasPassport);
        $this->tpl->assign('formString', $formString);
        $this->tpl->assign('bankcardValidateForm', $bankcardValidateForm);
        $this->tpl->assign('canResetBank', $isZeroAssets && app_conf('WEB_APP_REMOVE_BANKCARD'));
        $this->tpl->assign('delivery_infor', $delivery_infor);
        $this->tpl->assign('hideExtra', $hideExtra);
        $this->tpl->assign('protect_pwd', $protect_pwd);
        $this->tpl->assign('hasPassport', $hasPassport);
        $this->tpl->assign('usedQuickPay', $usedQuickPay);
        $this->tpl->assign('bankcard',$bankcard);
        $this->tpl->assign('is_audit',$bankcard['is_audit']);
        $this->tpl->assign('coupon',$coupon);
        $this->tpl->assign('ura',isset($ura) ? $ura : array());
        $this->tpl->assign('svData', $svData);
        $this->tpl->assign('user_info',$user_info);
        $this->tpl->assign("inc_file","web/views/v2/account/setup.html");
        $this->template = "web/views/v2/account/frame.html";
    }
}
