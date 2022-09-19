<?php
/**
 * 修改手机号码页面
 * @author pengchanglu@ucfgroup.com
 */
namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use core\dao\AdvModel;
use libs\utils\PaymentApi;
use core\service\curlHook\ThirdPartyHookService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\service\MobileCodeService;
use libs\payment\supervision\Supervision;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

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

    public function invoke ()
    {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            return $this->show_tips(\libs\utils\PaymentApi::maintainMessage(), '温馨提示');
        }

        $data = $this->form->data;
        $step = intval($data['sp']);//step
        $step = $step <= 0 ? 1:$step;
        $step = $step >= 3 ? 3:$step;

        $data['mobile'] = trim($data['mobile']);
        $data['code'] = trim($data['code']);
        $user_id = intval ( $GLOBALS['user_info']['id'] );
        $user_info = $this->rpc->local('UserService\getUser', array($user_id));

        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$user_id]) && Supervision::isServiceDown()) {
            return $this->show_tips(Supervision::maintainMessage(), '温馨提示');
        }
        //哈哈突然说不上了。临时注释掉
        // 哈哈又要上了，崩溃了
        // 增加记录值
        $user_info['oldMobile'] = $user_info['mobile'];
        $this->tpl->assign('user_info', $user_info);

        session_start();
        if($step == 2 && $data['code']){
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($user_info['mobile']));
            if($vcode != $data['code'])
            {
                return $this->setError("验证码不正确",1);
            }
            $this->rpc->local('MobileCodeService\delMobileCode', array($user_info['mobile']));
            $_SESSION['edit_mobile'] = 1;//防止跳过第二步
            $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
            $this->template = "web/views/user/editmb_{$step}.html";
            return;
        }

        if($step == 3 && $data['code'] && $data['mobile'] && $_SESSION['edit_mobile']){
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
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($data['mobile']));
            if($vcode != $data['code'])
            {
                return $this->setError("验证码不正确",2);
            }
            $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile']));
            $is_exist_moblie = $this->rpc->local('UserService\isExistsMobile',array($data['mobile']));
            if($is_exist_moblie){
                return $this->setError("手机号码已经绑定！",2);
            }
            $arr = [];
            $arr['id'] = $user_id;
            $arr['mobile'] = $data['mobile'];
            $arr['update_time'] = get_gmtime();
            $arr['country_code'] = $country_code;
            $arr['mobile_code'] =  $mobile_code;
            //  哈哈突然说不上了。临时注释掉
            // 增加新修改的手机号
            $user_info['newMobile'] = $data['mobile'];

            try {
                $gtm = new GlobalTransactionManager();
                $gtm->setName('memberUpdateMobile');
                $supervisionAccountObj = new \core\service\SupervisionAccountService();
                // 如果开启对接先锋支付启用验证
                if (app_conf('PAYMENT_ENABLE')) {
                    // 用户已在网信账户开户
                    $isUcfpayUser = $supervisionAccountObj->isUcfpayUser($user_info);
                    if ($isUcfpayUser) {
                        $gtm->addEvent(new \core\tmevent\supervision\UcfpayUpdateUserMobileEvent($user_id, $arr['mobile'], $arr['mobile_code']));
                    }
                }
                $gtm->addEvent(new \core\tmevent\supervision\WxUpdateUserMobileEvent($arr));
                // 检查存管开关是否开启、用户已在存管账户开户或者是存管预开户用户
                $isSupervision = $supervisionAccountObj->isSupervision($user_info);
                $svService = new \core\service\SupervisionService();
                if (($isSupervision['isSvOpen'] && $isSupervision['isSvUser']) || $svService->isUpgradeAccount($user_id)) {
                    $gtm->addEvent(new \core\tmevent\supervision\SupervisionUpdateUserMobileEvent($user_id, $arr['mobile']));
                }

                // 检查是否需要同步通行证
                $passportService = new \core\service\PassportService();
                if ($passportInfo = $passportService->isLocalPassport($user_id)) {
                    $gtm->addEvent(new \core\tmevent\passport\UpdateIdentityEvent($passportInfo['ppid'], $user_info['oldMobile'], $user_info['newMobile']));
                }

                $rs = $gtm->execute();
                if (!$rs) {
                    throw new \Exception($gtm->getError());
                }

                //生产用户访问日志
                UserAccessLogService::produceLog($user_id, UserAccessLogEnum::TYPE_UPDATE_MOBILE, '修改手机号成功', ['mobile' => $user_info['oldMobile']], $arr, DeviceEnum::DEVICE_WEB);

                //$content = $user_info['user_name'].','.date('Y年m月d日H时i分s秒').','.substr($data['mobile'],-4);
                // 发送短信走的短信模板
                //$res = \SiteApp::init()->sms->send($user_info['mobile'], $content,$GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_CHANGE_MOBILE_NEW'],0);
                // 下面这行代码会在页面上输出json，相关的review是http://review.corp.ncfgroup.com/#/c/15747/1
                //$res = $this->rpc->local('MobileCodeService\sendVerifyCode',array($user_info['mobile'],1,false,10,$user_info['country_code']));
                $mobileCodeObj = new MobileCodeService();
                $mobileCodeObj->isReturnJsonSendCode = true;
                $mobileCodeObj->sendVerifyCode($user_info['mobile'], 1, false, 10, $user_info['country_code']);

                if(!$rs){
                    $step = 2;
                }
                unset($_SESSION['edit_mobile']);
                //AdvModel....让老衲着实一愣。。
                //下专为haha定制,面增加修改手机号的回调,操作完毕了直接异步通知

                $hahaGroupId = $GLOBALS['sys_config']['SITE_USER_GROUP']['caiyitong'];
                // 增加业务上的判断，如果不是哈哈用户组就不用进入队列里面了。
                if($user_info['oldMobile'] != $user_info['newMobile'] && $user_info['group_id']==$hahaGroupId){
                    $tphs = new ThirdPartyHookService();
                    $channel = 'HaHa';
                    $param = array(
                        'client_id'=>10001,
                        'id' =>  $user_info['id'],
                        'oldMobile' => $user_info['oldMobile'],
                        'newMobile' => $user_info['newMobile'],
                        'real_name' => $user_info['real_name'],
                        'group_id' => $user_info['group_id'],
                    );
                    $url = $GLOBALS['sys_config']['CURL_HOOK_CONF'][$channel];
                    $ret = $tphs->asyncCall($url,$user_info,$channel);
                    if(!empty($ret)){
                        \libs\utils\Monitor::add('HAHA_CHANGE_MOBILE_PUSH');
                    }else{
                        \libs\utils\Alarm::push('thirdparty_push', '哈哈农庄修改手机号推送失败', sprintf("userId: %s",$user_id));
                    }
                }
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
