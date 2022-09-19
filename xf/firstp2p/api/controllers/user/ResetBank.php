<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\payment\supervision\Supervision;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 *
 * 用户解绑卡接口
 * @author weiwei12@ucfgroup.com
 * @date 2016-10-12
 */
class ResetBank extends AppBaseAction {

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'string'),
                'code'  => array("filter"=>'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->errorCode = "-4";
            $this->errorMsg = \libs\utils\PaymentApi::maintainMessage();
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        // 解绑卡开关
        if (!app_conf('WEB_APP_REMOVE_BANKCARD')) {
            $this->errorCode = '-4';
            $this->errorMsg = '该功能维护中，请您稍后再试';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        // 用户手机号
        $userId = $loginUser['id'];
        $mobile = $loginUser['mobile'];

        //存管服务降级
        if ($this->rpc->local('SupervisionAccountService\isSupervisionUser', [$userId]) && Supervision::isServiceDown()) {
            $this->errorCode = '-4';
            $this->errorMsg = Supervision::maintainMessage();
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($mobile, 180, 0));
        if($vcode != $data['code'])
        {
            $this->errorCode = '-4';
            $this->errorMsg = '验证码不正确';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }
        $this->rpc->local('MobileCodeService\delMobileCode', array($mobile));

        $isZeroUserAssets = $this->rpc->local('SupervisionAccountService\isZeroUserAssets', array($userId));
        if (!$isZeroUserAssets)
        {
            $this->errorCode = '-4';
            $this->errorMsg = '您目前无法进行解绑银行卡操作，如需帮助，请与客服联系。';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        try {
            $GLOBALS['db']->startTrans();

            // 如果开启对接先锋支付启用验证
            if (app_conf('PAYMENT_ENABLE')) {
                $userbankcard = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT bankcard FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
                // 资金托管 增加用户是否开户的判断逻辑
                if (!empty($loginUser['payment_user_id']) && !empty($userbankcard)) {
                    //同时解绑tradep2p、supervision、理财银行卡
                    $result = $this->rpc->local('SupervisionAccountService\memberCardUnbind', array($userId, $userbankcard['bankcard']));
                    if (empty($result)) {
                        \libs\utils\Alarm::push('payment', '解绑银行卡失败', "{$userId}解绑银行卡失败");
                        throw new \Exception('解绑银行卡失败');
                    }
                }
            }

            $GLOBALS['db']->commit();
            $ret = array('success' => '00', 'msg' => '银行卡已解绑');
            //生产用户访问日志
            UserAccessLogService::produceLog($userId, UserAccessLogEnum::TYPE_UNBIND_BANK_CARD, '解绑银行卡成功', $userbankcard, '', UserAccessLogService::getDevice($_SERVER['HTTP_OS']));

        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $ret = array('success' => '01', 'msg' => $e->getMessage());
            $this->errorCode = '-4';
            $this->errorMsg = $e->getMessage();
            $this->json_data = $ret;
            return false;
        }
        $this->json_data = $ret;
        return true;
    }
}
