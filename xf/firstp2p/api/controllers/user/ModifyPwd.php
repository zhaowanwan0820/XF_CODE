<?php
/**
* 修改密码
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-04-20
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\BOBase;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use core\service\PassportService;
use core\service\user\BOFactory;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskService;

class ModifyPwd extends AppBaseAction
{
    public function init()
    {
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
            $this->setErr('ERR_PARAMS_ERROR',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        //忘记密码，获取用户信息
        if ($data['from'] == 'forget') {
            if(empty($data['phone'])){
                $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
                return false;
            }
            $userinfo = $this->rpc->local('UserService\getByMobile', array($data['phone'],'id,mobile,idno,idcardpassed'));
            setLog(array('uid'=>$userinfo['id']));
            if ($userinfo['idcardpassed'] == 1 && $data['idno'] && $data['idno'] != $userinfo['idno']) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL','请输入绑定的证件号');
                return false;
            }
        }
        RiskServiceFactory::instance(Risk::BC_CHANGE_PWD,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check($userinfo,Risk::ASYNC,$data);
        //修改密码修改，获取用户信息
        $needValidate = 1;
        if ($data['from'] == 'modify') {
            $userinfo = $this->getUserByToken();
            if (!$userinfo) {
                $this->setErr('ERR_GET_USER_FAIL');
                return false;
            }
            if (empty($userinfo['ppID'])) {
                $needValidate = 0;
            }
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $userinfo['mobile'];
            if ($data['phone'] != $userinfo['mobile']) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL','参数输入错误');
                return false;
            }
            $new_password = (new BOBase)->compilePassword($data['new_password']);
            if ($new_password == $userinfo['user_pwd']) {
                $this->setErr('ERR_PARAMS_VERIFY_FAIL','新密码和旧密码不能相同');
                return false;
            }
        }

        if ($needValidate) {
            // 非本地通行证，禁止理财修改密码
            $bizInfo = (new PassportService())->isThirdPassport($data['phone']);
            if (!empty($bizInfo)) {
                $app = $bizInfo['platformName'] ?: '注册端';
                $this->setErr('ERR_PARAMS_ERROR','当前账户使用网信通行证登录，请您在"'.$app.'"修改密码');
                return false;
            }
        }

        //防止绕过短信验证，直接通过用户其他信息修改用户密码
        $code = \libs\utils\Aes::decode($data['ticket'], base64_encode('modify_pwd_api'));
        $vcode = \SiteApp::init()->cache->get('modify_pwd_checkverfycode'.$data['phone']);
        if (!$userinfo || !$code || $code != $vcode) {
            $this->setErr('ERR_SYSTEM');
            return false;
        }
        if ($data['new_password'] != $data['confirmPassword']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL','密码输入不一致');
            return false;
        }
        //弱密码校验
        \FP::import("libs.common.dict");
        $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
        $mobile = $userinfo['mobile'];
        $password = $data['new_password'];
        $len=strlen($password);
        //基本规则判断
        $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
        if ($base_rule_result){
            $this->setErr('ERR_PASSWORD_ILLEGAL',$base_rule_result['errorMsg']);
            return false;
        }
        //黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
        if ($forbid_black_result) {
            $this->setErr('ERR_PASSWORD_ILLEGAL',$forbid_black_result['errorMsg']);
            return false;
        }
        //风控检查
        $extraData = [
            'user_id' => $userinfo['id'],
            'user_name' => $userinfo['user_name'],
            'mobile' => $userinfo['mobile'],
            'change_password_verify' => 'phone',
        ];
        $checkRet = RiskService::check('CPWD', $extraData);
        if (false === $checkRet) {
            $this->setErr('ERR_MANUAL_REASON', '操作失败，请稍后再试');
            return false;
        }

        //$password = (new BOBase)->compilePassword($password);
        //$result = $this->rpc->local('UserService\updateInfo', array(array('id'=>$userinfo['id'], 'user_pwd'=>$password)));
        $bo = BOFactory::instance('app');
        $result = $bo->resetPwd($mobile, $password);
        if ($result) {
            \SiteApp::init()->cache->delete('modify_pwd_checkverfycode'.$data['phone']);
            $ret['msg'] = '密码修改成功';
            $ret['needReLogin'] = '1';
            RiskServiceFactory::instance(Risk::BC_CHANGE_PWD,Risk::PF_API)->notify();
            //生产用户访问日志
            UserAccessLogService::produceLog($userinfo['id'], UserAccessLogEnum::TYPE_UPDATE_PASSWORD, '修改密码成功', '', '', UserAccessLogService::getDevice($_SERVER['HTTP_OS']));
        } else {
            $this->setErr('ERR_MANUAL_REASON','密码修改失败');
            return false;
        }
        $this->json_data = $ret;
        return true;
    }
}
