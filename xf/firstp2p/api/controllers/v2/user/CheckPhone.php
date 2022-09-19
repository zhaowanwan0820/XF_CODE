<?php
/**
* 检查手机号是否为用户绑定的手机号
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-04-19
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\BOBase;
use libs\utils\Block;

class CheckPhone extends AppBaseAction
{
    protected $useSession = true;
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'string'),
                'phone' => array('filter' => 'reg', "message" => "ERR_SIGNUP_PARAM_PHONE", 'option' => array("regexp" => "/^1[3456789]\d{9}$/", 'optional' => true)),
                'verify' => array('filter' => "reg", "message" => 'ERR_VERIFY_ILLEGAL', "option" => array("regexp" => "/^[0-9a-zA-Z]{0,4}$/", 'optional' => true)),
                'old_password' => array('filter' => 'string'),
                'from' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),//modify:修改密码  forget：忘记密码
                'idno' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if($this->app_version < 460){
                $this->setErr('ERR_MANUAL_REASON','请您移步到网信PC端，进行找回密码');
                return false;
        }
        $data = $this->form->data;
        if ($data['from'] == 'modify') {
            $userinfo = $this->getUserByToken();
            if (!$userinfo) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            if(!empty($userinfo['mobile_code']) && $userinfo['mobile_code'] != 86){
                $this->setErr('ERR_MANUAL_REASON','国际手机用户请移步至电脑版修改密码');
                return false;
            }
            //旧密码输入错误频率限制
            $old_check_hours = Block::check('OLDPWD_CHECK_HOURS',$userinfo['id'],true);
            if ($old_check_hours ===false) {
                $this->setErr('ERR_AUTH_FAIL','错误次数过多,请稍后重试');
                return false;
            }
            //旧密码验证
            $oldpwd = (new BOBase())->compilePassword($data['old_password']);
            if ($oldpwd === $userinfo['user_pwd']) {
                $ret['oldpwd'] = '旧密码正确';
            } else {
                $msg = '旧密码输入错误，请重新输入';
                $old_check_hours = Block::check('OLDPWD_CHECK_HOURS',$userinfo['id'],false);//旧密码输入错误频率限制检查
                if ($old_check_hours === false) {
                    $msg = '错误次数过多,请稍后重试';
                }
                $this->setErr('ERR_AUTH_FAIL',$msg);
                return false;
            }
            if((!$userinfo || !$userinfo['mobile'] || $userinfo['is_effect'] != 1 || $userinfo['is_delete'] != 0) ){
                $this->setErr('ERR_MANUAL_REASON','用户不存在');
                return false;
            }
            if(!empty($data['phone']) && $data['phone'] != $userinfo['mobile']){//4.6版本以后 修改密码手机号不传
                $this->setErr('ERR_MANUAL_REASON','手机号输入错误');
                return false;
            }
            $ret['phone'] = '手机号正确';
        }
        if ($data['from'] == 'forget') {
            if(empty($data['phone'])){
                $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
                return false;
            }
            $userinfo = $this->rpc->local('UserService\getByMobile', array($data['phone'],'id,mobile,idno,idcardpassed,is_effect,is_delete'));
            if($userinfo['idcardpassed'] == 1){
                if(empty($data['idno'])){
                    $this->setErr('ERR_PARAMS_ERROR','请输入绑定的证件号');
                    return false;
                }
            }
            $ret['phone'] = '手机号正确';
            setLog(array('uid'=>$userinfo['id']));
        }

        if (!$data['verify']) {
            $this->setErr('ERR_VERIFY_EMPTY');
            return false;
        }

        // 校验验证码
        $sessionId = session_id();
        $verify = \SiteApp::init()->cache->get("verify_" . $sessionId);
        \SiteApp::init()->cache->delete("verify_" . $sessionId);
        $data['verify'] = strtolower($data['verify']);
        if ($verify != md5($data['verify'])) {
            $this->setErr('ERR_VERIFY_ILLEGAL');
            return false;
        } else {
            $ret['verify'] = '验证码正确';
        }
        $this->json_data = $ret;
        return ture;
    }
}
