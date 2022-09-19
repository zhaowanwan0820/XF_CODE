<?php
/**
* 验证手机验证码
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-04-19
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\sms\MobileCodeService;
use core\service\user\UserService;

class CheckVerifyCode extends AppBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'phone' => array(
                "filter" => 'reg',
                "message" => "ERR_SIGNUP_PARAM_PHONE",
                'option' => array("regexp" => "/^1[3456789]\d{9}$/",'optional' => true)
            ),
            "code" => array(
                "filter" => "reg",
                "message" => 'ERR_VERIFY_ILLEGAL',
                'option' => array("regexp" => "/^\w{4,20}$/")
            ),
            "token" => array("filter" => "string"),
            "type" => array(
                "filter" => "string",
                'option' => array('optional' => true)
            )
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // 忘记密码传手机号 ，修改密码传token
        if (empty($data['phone']) && !empty($data['token'])) {
            $userInfoByToken = $this->getUserByToken();
            $data['phone'] = $userInfoByToken['mobile'];
        }

        if(empty($data['phone'])){
            $this->setErr('ERR_PARAMS_ERROR','请输入手机号');
        }

        $oMobileCodeService = new MobileCodeService();
        $vcode = $oMobileCodeService->getMobilePhoneTimeVcode($data['phone'], 180, 0);

        // 没有表示验证码已经过期
        if (empty($vcode)) {
            $this->setErr('ERR_VERIFY_EXPIRED');
        }

        if($vcode != $data['code']) {
            $this->setErr('ERR_VERIFY_ILLEGAL');
        }

        \SiteApp::init()->cache->set('modify_pwd_checkverfycode'.$data['phone'],$data['code'],'180');//将短信验证码缓存，修改密码是加以确认,
        $userInfo = isset($userInfoByToken) ? $userInfoByToken : UserService::getUserByMobile($data['phone'],'id,mobile,id_type,idcardpassed,idno');
        $ret['is_verify_idno'] = $this->checkIdno($userInfo);
        // 产生一个加密参数，防止跳过短信验证，直接修改密码
        $ticket = \libs\utils\Aes::encode($data['code'], base64_encode('modify_pwd_api'));
        $ret['ticket'] = $ticket;
        $ret['msg'] = '验证码正确';
        $oMobileCodeService->delMobileCode($data['phone'],0);
        $this->json_data = $ret;
        return true;
    }

    /**
     * 检查用户是否实名认证（只判断是否为大陆身份证认证，不是大陆身份证则按未实名处理）
     * @param unknown $userInfo
     * @return boolean
     */
    private function checkIdno($userInfo) {
        $flag = preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $userInfo['idno']);
        if ($userInfo['id_type'] ==1 && $userInfo['idcardpassed'] == 1 && $flag) {
            return true;
        } else {
            return false;
        }
    }
}
