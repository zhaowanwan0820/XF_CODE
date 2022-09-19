<?php
/**
* 修改密码
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-04-20
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\service\user\UserService;
use core\service\user\UserLoginService;

class ModifyPwd extends AppBaseAction {
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'new_password' => array('filter' => 'required', 'message' => 'new_password is required'),
            'phone' => array('filter' => 'required', 'message' => 'phone is required','option' => array('optional' => true)),
            'idno' => array('filter' => 'string'),
            'confirmPassword' => array('filter' => 'required', 'message' => 'confirmPassword is required'),
            'from' => array('filter' => 'required', 'message' => 'from is required'),//modify:修改密码  forget：忘记密码
            'ticket' => array('filter' => 'required', 'message' => 'ticket不能为空'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;

        // 手机号验证
        if (empty($data['phone'])) {
            $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
        }

        if ($data['from'] == 'forget') {
            // 忘记密码，获取用户信息
            $userinfo = UserService::getUserByMobile($data['phone'], 'id,mobile,idno,idcardpassed');
            setLog(array('uid'=>$userinfo['id']));
            if ($userinfo['idcardpassed'] == 1
                && !empty($data['idno'])
                && $data['idno'] != $userinfo['idno']
            ) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL','请输入绑定的证件号');
            }
        } else if ($data['from'] == 'modify') {
            // 修改密码
            $userinfo = $this->getUserByToken();
            // 传入的手机号和注册的手机号需要一致
            if ($data['phone'] != $userinfo['mobile']) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL','参数输入错误');
            }

            $new_password = UserLoginService::compilePassword($data['new_password']);
            if ($new_password == $userinfo['user_pwd']) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL', '新密码和旧密码不能相同');
            }
        }

        RiskServiceFactory::instance(
            Risk::BC_CHANGE_PWD,
            Risk::PF_API,
            Risk::getDevice($_SERVER['HTTP_OS'])
        )->check($userinfo, Risk::ASYNC, $data);

        // 防止绕过短信验证，直接通过用户其他信息修改用户密码
        $code = \libs\utils\Aes::decode($data['ticket'], base64_encode('modify_pwd_api'));
        $vcode = \SiteApp::init()->cache->get('modify_pwd_checkverfycode'.$data['phone']);
        if (!$userinfo || !$code) {
            $this->setErr('ERR_SYSTEM');
        }

        // 当用户在修改密码页停留超过3分钟的时候会触发这个case 现单独提示
        if ($code != $vcode) {
            $this->setErr('ERR_VERIFY_EXPIRED');
        }

        if ($data['new_password'] != $data['confirmPassword']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '密码输入不一致');
        }

        // 弱密码校验
        \FP::import("libs.common.dict");

        // 获取密码黑名单
        $blacklist = \dict::get("PASSWORD_BLACKLIST");
        $mobile = $data['phone'];
        $password = $data['new_password'];

        // 基本规则判断
        $base_rule_result = login_pwd_base_rule(strlen($password), $mobile, $password);
        if ($base_rule_result){
            $this->setErr('ERR_PASSWORD_ILLEGAL', $base_rule_result['errorMsg']);
        }

        // 黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
        if ($forbid_black_result) {
            $this->setErr('ERR_PASSWORD_ILLEGAL', $forbid_black_result['errorMsg']);
        }

        // 请求修改密码
        $result = UserService::resetPwd($mobile, $password);
        if (!$result) {
            $this->setErr('ERR_MANUAL_REASON', '密码修改失败');
        }

        \SiteApp::init()->cache->delete('modify_pwd_checkverfycode'.$data['phone']);
        $ret['msg'] = '密码修改成功';
        $ret['needReLogin'] = '1';
        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD, Risk::PF_API)->notify();

        $this->json_data = $ret;
        return true;
    }
}
