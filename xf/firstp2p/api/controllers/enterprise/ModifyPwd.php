<?php

namespace api\controllers\enterprise;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\user\BOBase;
use core\service\user\BOFactory;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Block;

/**
 * 修改密码
 */
class ModifyPwd extends BaseAction {

    protected $useSession = true;

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array("filter" => "required", "message" => "token is required"),
            'new_password' => array('filter' => 'required', 'message' => 'new_password is required'),
            'confirmPassword' => array('filter' => 'required', 'message' => 'confirmPassword is required'),
            'old_password' => array('filter' => 'required', 'message' => '密码不能为空'),
            "verify" => array("filter" => "reg", "message" => 'ERR_VERIFY_ILLEGAL', "option" => array("regexp" => "/^[0-9a-zA-Z]{0,4}$/", 'optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        // 修改密码修改，获取用户信息
        $userinfo = $this->getUserByToken();
        if (!$userinfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD, Risk::PF_API, Risk::getDevice($_SERVER['HTTP_OS']))
            ->check($userinfo, Risk::ASYNC, $data);

        if ($data['new_password'] != $data['confirmPassword']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '密码输入不一致');
            return false;
        }

        // 校验验证码
        if (!empty($data['verify'])) {
            $sessionId = session_id();
            $verify = \SiteApp::init()->cache->get("verify_" . $sessionId);
            \SiteApp::init()->cache->delete("verify_" . $sessionId);
            $data['verify'] = strtolower($data['verify']);
            if ($verify != md5($data['verify'])) {
                $this->setErr('ERR_VERIFY_ILLEGAL');
                return false;
            }
        }

        // 旧密码验证前先验证输入错误频率限制
        $old_check_hours = Block::check('OLDPWD_CHECK_HOURS', $userinfo['id'], true);
        if ($old_check_hours === false) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '错误次数过多,请稍后重试');
            return false;
        }

        $bo = BOFactory::instance('web');
        $ret = $bo->verifyPwd($userinfo['id'], $data['old_password']);
        if ($ret['code'] != 0) {
            if ($ret['code'] == '3') {
                // 旧密码输入错误频率限制
                $old_check_hours = Block::check('OLDPWD_CHECK_HOURS', $userinfo['id'], false);
                if ($old_check_hours === false) {
                    $this->setErr('ERR_PARAMS_VERIFY_FAIL', '错误次数过多,请稍后重试');
                    return false;
                }
            }

            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $ret['msg']);
            return false;
        }

        // 验证新旧密码
        $new_password = (new BOBase)->compilePassword($data['new_password']);
        if ($new_password == $userinfo['user_pwd']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '新密码和旧密码不能相同');
            return false;
        }

        // 弱密码校验
        \FP::import("libs.common.dict");
        // 获取密码黑名单
        $blacklist = \dict::get("PASSWORD_BLACKLIST");
        $mobile = $userinfo['mobile'];
        $password = $data['new_password'];
        $len = strlen($password);
        // 基本规则判断
        $base_rule_result = login_pwd_base_rule($len, $mobile, $password);
        if ($base_rule_result){
            $this->setErr('ERR_PASSWORD_ILLEGAL', $base_rule_result['errorMsg']);
            return false;
        }

        // 黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
        if ($forbid_black_result) {
            $this->setErr('ERR_PASSWORD_ILLEGAL', $forbid_black_result['errorMsg']);
            return false;
        }

        $password = (new BOBase)->compilePassword($password);
        $result = $this->rpc->local('UserService\updateInfo', array(array('id'=>$userinfo['id'], 'user_pwd'=>$password)));
        if ($result) {
            \SiteApp::init()->cache->delete('modify_pwd_checkverfycode'.$data['phone']);
            $ret['msg'] = '密码修改成功';
            $ret['needReLogin'] = '1';
            RiskServiceFactory::instance(Risk::BC_CHANGE_PWD, Risk::PF_API)->notify();
        } else {
            $this->setErr('ERR_MANUAL_REASON', '密码修改失败');
            return false;
        }

        $this->json_data = $ret;
        return true;
    }
}
