<?php

/**
 * CheckRegister.php
 * 注册时  校验手机号、邀请码和活动编码
 */

namespace api\controllers\user;

use libs\web\Form;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class CheckRegister extends Signup {

    protected $useSession = true;

    public function init() {
        // 获取基类
        $grandParent = self::getRoot();
        $grandParent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'phone' => array("filter" => 'required'),
            'password' => array("filter" => 'required'),
            'verify' => array("filter" => 'required'),
            'invite' => array("filter" => 'string'),
            'site_id' => array("filter" => 'string'),
            'euid' => array("filter" => 'string'),
            'country_code' => array("filter" => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
     }

        if (!$this->check_phone() && !$this->check_password()) {
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        //校验手机号是否注册过
        $result = $this->rpc->local('UserService\checkUserMobile', array($data['phone']));
        if (!empty($result) && !isset($result['code'])) {
            $this->json_data = $result;
        } else{
            if($result['code'] == 320){
                $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
            }else{
                $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
            }
            return ;
        }

        //校验图形验证码
        $sessionId = session_id();
        $verify = \SiteApp::init()->cache->get("verify_" . $sessionId);
        \SiteApp::init()->cache->delete("verify_" . $sessionId);
        $data['verify'] = strtolower($data['verify']);
        if ($verify != md5($data['verify'])) {
            $this->setErr('ERR_VERIFY_ILLEGAL');
            return ;
        }

        //校验邀请码是否有效
        if(!empty($data['invite']) && !$this->check_invite()) {
            return false;
        }

        // 分站优惠购活动
        $appInfo = \libs\web\Open::getAppBySiteId($data['site_id']);
        $ticketInfo  = \core\service\OpenService::toCheckTicket($appInfo, $data['euid']);
        if ($ticketInfo['status'] != 0) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $ticketInfo['msg']);
            return false;
        }
    }

}
