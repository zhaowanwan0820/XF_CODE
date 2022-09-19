<?php
/**
* 验证手机验证码
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-04-19
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class CheckVerifyCode extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'phone' => array("filter" => 'reg', "message" => "ERR_SIGNUP_PARAM_PHONE", 'option' => array("regexp" => "/^1[3456789]\d{9}$/",'optional' => true)),
                "code" => array("filter" => "reg", "message" => 'ERR_VERIFY_ILLEGAL','option' => array("regexp" => "/^\w{4,20}$/")),
                "token" => array("filter" => "string"),
                "type" => array("filter" => "string",'option' => array('optional' => true))
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if(empty($data['phone']) && !empty($data['token'])){//忘记密码传手机号 ，修改密码传token
            $userInfoByToken = $this->getUserByToken();
            if (empty($userInfoByToken)) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            $data['phone'] = $userInfoByToken['mobile'];
        }
        if(empty($data['phone'])){
            $this->setErr('ERR_PARAMS_ERROR','请输入手机号');
            return false;
        }
        $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($data['phone'],180,0));
        if($vcode != $data['code']) {
            $this->setErr('ERR_VERIFY_ILLEGAL');
            return false;
        } 

        //type = 1 验证黄金提金验证码
        if($data['type'] == 1){
            \SiteApp::init()->cache->set('gold_deliver_checkverfycode'.$data['phone'],$data['code'],'180');
        }elseif($data['type'] == 2){
            \SiteApp::init()->cache->set('checkverifycode_candy_withdraw'.$data['phone'],$data['code'],'180');
        }else{
            \SiteApp::init()->cache->set('modify_pwd_checkverfycode'.$data['phone'],$data['code'],'180');//将短信验证码缓存，修改密码是加以确认,
            $userInfo = isset($userInfoByToken) ? $userInfoByToken : $this->rpc->local('UserService\getByMobile', array($data['phone'],'id,mobile,id_type,idcardpassed,idno'));
            $ret['is_verify_idno'] = $this->checkIdno($userInfo);
            //产生一个加密参数，防止跳过短信验证，直接修改密码
            $ticket = \libs\utils\Aes::encode($data['code'], base64_encode('modify_pwd_api'));
            $ret['ticket'] = $ticket;

        }
        $ret['msg'] = '验证码正确';
        $this->rpc->local('MobileCodeService\delMobileCode', array($data['phone'],0));
        $this->json_data = $ret;
        return true;
    }
    /**
     * 检查用户是否实名认证（只判断是否为大陆身份证认证，不是大陆身份证则按未实名处理）
     * @param unknown $userInfo
     * @return boolean
     */
    private function checkIdno($userInfo)
    {
        $flag = preg_match("/(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $userInfo['idno']);
        if ($userInfo['id_type'] ==1 && $userInfo['idcardpassed'] == 1 && $flag) {
            return true;
        } else {
            return false;
        }
    }
}
