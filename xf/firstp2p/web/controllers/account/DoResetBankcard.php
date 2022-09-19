<?php

/**
 * 执行解绑卡操作
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 *
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;
use core\dao\MobileVcodeModel;
use core\dao\AdvModel;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

class DoResetBankcard extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'mobile'=>array("filter"=>'string'),
            'code'=>array("filter"=>'string'),
        );

       if (!$this->form->validate()){
           return $this->response("参数错误",1);
       }

    }

    public function invoke() {
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            return $this->show_error(\libs\utils\PaymentApi::maintainMessage(), '', 1);
        }

        // 解绑卡开关
        if (!app_conf('WEB_APP_REMOVE_BANKCARD')) {
            return $this->show_error("该功能维护中，请您稍后再试！",'',1);
        }

        $data = $this->form->data;
        $data['code'] = trim($data['code']);
        $user_id = intval ( $GLOBALS['user_info']['id'] );
        $user_info = $this->rpc->local('UserService\getUser', array($user_id));
        $data['mobile'] = $user_info['mobile'];

        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$user_id]) && Supervision::isServiceDown()) {
            return $this->show_error(Supervision::maintainMessage(), '', 1);
        }

        if($data['code'] && $data['mobile'] && $_SESSION['resetbank']){
            $mobile_regex = '^1[3456789]\d{9}$';
            $mobile_code = 86;
            $country_code = 'cn';
            if (!empty($_POST['country_code']) && !empty($GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['regex']) && $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['is_show']){
                $mobile_regex = $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['regex'];
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$_POST['country_code']]['code'];
                $country_code = $_POST['country_code'];
            }
            if(!preg_match("/$mobile_regex/", $data['mobile'])){
                return $this->show_error("手机号码格式不正确！",'',1);
            }
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($data['mobile']));
            if($vcode != $data['code'])
            {
                return $this->show_error("验证码不正确",'',1);
            }
            $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile']));

            // 查询用户总资产
            $isZeroUserAssets = $this->rpc->local('SupervisionAccountService\isZeroUserAssets', array($user_id));
            if (!$isZeroUserAssets)
            {
                return $this->show_error('您目前无法进行解绑银行卡操作，如需帮助，请与客服联系。', '', 1);
            }

            // 增加修改手机号同步
            try {
                // 如果开启对接先锋支付启用验证
                if (app_conf('PAYMENT_ENABLE')) {
                    $userbankcard = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT bankcard FROM firstp2p_user_bankcard WHERE user_id = '{$user_id}'");
                    if (!empty($userbankcard)) {
                        //同时解绑tradep2p、supervision、理财银行卡
                        $result = $this->rpc->local('SupervisionAccountService\memberCardUnbind', array($user_id, $userbankcard['bankcard']));
                        if (empty($result)) {
                            \libs\utils\Alarm::push('payment', '解绑银行卡失败', "{$user_id}解绑银行卡失败");
                            throw new \Exception('解绑银行卡失败');
                        }
                    }
                }

                //生产用户访问日志
                UserAccessLogService::produceLog($user_id, UserAccessLogEnum::TYPE_UNBIND_BANK_CARD, '解绑银行卡成功', $userbankcard, '', DeviceEnum::DEVICE_WEB);

                unset($_SESSION['resetbank']);
                return $this->show_success('解绑银行卡成功', '', 1, 0, '/account');
            }
            catch (\Exception $e) {
                return $this->show_error('解绑银行卡失败', '', 1, 0, '/account');
            }
        }
    }

}
