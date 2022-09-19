<?php
/**
 * 修改手机号码页面
 * @author pengchanglu@ucfgroup.com
 */
namespace web\controllers\user;

use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Monitor;
use libs\utils\Alarm;
use libs\utils\PaymentApi;
use core\service\user\UserService;
use core\service\user\PassportService;
use core\service\account\AccountService;
use core\service\curlHook\ThirdPartyHookService;
use core\service\sms\MobileCodeService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionAccountService;

class Editmb extends BaseAction
{
    public function init ()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'sp'=>array("filter"=>'int'),
            'mobile'=>array("filter"=>'string'),
            'code'=>array("filter"=>'string'),
        );

       if (!$this->form->validate()){
           return $this->setError("参数错误",1);
       }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $step = intval($data['sp']);//step
        $step = $step <= 0 ? 1 : $step;
        $step = $step >= 3 ? 3 : $step;

        $data['mobile'] = trim($data['mobile']);
        $data['code'] = trim($data['code']);
        // 用户信息
        $user_info = $GLOBALS['user_info'];
        $user_id = intval($user_info['id']);
        // 获取用户账户ID
        $accountId = AccountService::getUserAccountId($user_id, $user_info['user_purpose']);

        // 存管服务降级
        $supervisionAccountObj = new SupervisionAccountService();
        if ($supervisionAccountObj->isSupervisionUser($accountId) && SupervisionService::isServiceDown()) {
            return $this->show_tips(SupervisionService::maintainMessage(), '温馨提示');
        }
        // 增加记录值
        $user_info['oldMobile'] = $user_info['mobile'];
        $this->tpl->assign('user_info', $user_info);

        $mobileCodeObj = new MobileCodeService();
        if ($step == 2 && $data['code']) {
            $vcode = $mobileCodeObj->getMobilePhoneTimeVcode($user_info['mobile']);
            if($vcode != $data['code'])
            {
                return $this->setError("验证码不正确",1);
            }
            $mobileCodeObj->delMobileCode($user_info['mobile']);
            $_SESSION['edit_mobile'] = 1; // 防止跳过第二步
            $this->tpl->assign("mobile_codes", $GLOBALS['dict']['MOBILE_CODE']);
            $this->template = "web/views/user/editmb_{$step}.html";
            return;
        }

        if ($step == 3 && $data['code'] && $data['mobile'] && $_SESSION['edit_mobile']) {
            $mobile_regex = '^1[3456789]\d{9}$';
            $mobile_code = 86;
            $country_code = 'cn';
            if (!empty($_POST['country_code']) && !empty($GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['regex']) && $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['is_show']){
                $mobile_regex = $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['regex'];
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['code'];
                $country_code = $_POST['country_code'];
            }
            if(!preg_match("/$mobile_regex/", $data['mobile'])){
                return $this->setError("手机号码格式不正确！",2);
            }
            $this->tpl->assign('mobile', $data['mobile']);
            $vcode = $mobileCodeObj->getMobilePhoneTimeVcode($data['mobile']);
            if($vcode != $data['code'])
            {
                return $this->setError("验证码不正确",2);
            }
            $mobileCodeObj->delMobileCode($data['mobile']);

            $result = UserService::checkUserMobile($data['mobile']);
            // 为空说明访问wxuser失败或者mobile为空
            if (!isset($result['isExist'])) {
                return $this->setError('系统繁忙, 请稍后再试', 2);
            }

            if ($result['isExist']) {
                return $this->setError("手机号码已经绑定！",2);
            }
            $arr = [];
            $arr['id'] = $user_id;
            $arr['mobile'] = $data['mobile'];
            $arr['update_time'] = get_gmtime();
            $arr['country_code'] = $country_code;
            $arr['mobile_code'] =  $mobile_code;
            // 增加新修改的手机号
            $user_info['newMobile'] = $data['mobile'];

            try {
                $gtm = new GlobalTransactionManager();
                $gtm->setName('memberUpdateMobile');
                // 用户已在网信账户开户
                if (!empty($user_info['payment_user_id'])) {
                    $gtm->addEvent(new \core\tmevent\supervision\UcfpayUpdateUserMobileEvent($user_id, $arr['mobile'], $arr['mobile_code']));
                }
                $gtm->addEvent(new \core\tmevent\supervision\WxUpdateUserMobileEvent($arr));
                // 检查存管开关是否开启、用户已在存管账户开户或者是存管预开户用户
                $isSupervision = $supervisionAccountObj->isSupervision($accountId);
                $svService = new SupervisionService();
                if (($isSupervision['isSvOpen'] && $isSupervision['isSvUser']) || $svService->isUpgradeAccount($user_id)) {
                    $gtm->addEvent(new \core\tmevent\supervision\SupervisionUpdateUserMobileEvent($accountId, $arr['mobile']));
                }

                // 检查是否需要同步通行证
                $passportService = new PassportService();
                $passportInfo = PassportService::isLocalPassport($user_id);
                if (!empty($passportInfo)) {
                    $gtm->addEvent(new \core\tmevent\passport\UpdateIdentityEvent($passportInfo['ppid'], $user_info['oldMobile'], $user_info['newMobile']));
                }

                $rs = $gtm->execute();
                if (!$rs) {
                    throw new \Exception($gtm->getError());
                }

                $mobileCodeObj = new MobileCodeService();
                $mobileCodeObj->isReturnJsonSendCode = true;
                $mobileCodeObj->sendVerifyCode($user_info['mobile'], 1, false, 10, $user_info['country_code']);

                if(!$rs){
                    $step = 2;
                }
                unset($_SESSION['edit_mobile']);
            }
            catch (\Exception $e) {
                return $this->show_error($e->getMessage(), '', 0, 0, '/account');
            }
        }else{
            $step = 1;
        }
        $this->template = "web/views/user/editmb_{$step}.html";
    }

    /**
     * 错误页面
     * @param $err
     * @param $step
     */
    protected function setError($err,$step){
        $this->tpl->assign('error', $err);
        $this->template = "web/views/user/editmb_{$step}.html";
        return;
    }
}
