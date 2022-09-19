<?php
/**
* 忘记密码，进行身份验证
* @author zhaohui<zhaohui3@ucfgroup.com>
* @date 2016-04-21
*/
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use core\service\user\PassportService;
use core\service\user\UserService;

class FpwdCheckIdno extends AppBaseAction {
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'idno' => array('filter' => 'reg', "message" => "ERR_PARAMS_ERROR", 'option' => array("regexp" => "/(^\d{18}$)|(^\d{17}(\d|X|x)$)/")),
            'phone' => array('filter' => 'reg', "message" => "ERR_SIGNUP_PARAM_PHONE", 'option' => array("regexp" => "/^1[3456789]\d{9}$/",'optional' => true)),
            "token" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg(),'身份证格式不正确');
        }
    }

    public function invoke() {
        $data = $this->form->data;

        // 非本地通行证，禁止理财修改密码
        $bizInfo = PassportService::isThirdPassport($data['phone']);
        if (!empty($bizInfo)) {
            $app = $bizInfo['platformName'] ?: '注册端';
            $this->setErr('ERR_PARAMS_ERROR','该账号已开通网信通行证，请移步"'.$app.'"修改密码');
        }

        if(!empty($data['token'])){
            $userInfoByToken = $this->getUserByToken();
            $data['phone'] = $userInfoByToken['mobile'];
        }

        if(empty($data['phone'])){
            $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
        }

        $userinfo = isset($userInfoByToken) ? $userInfoByToken : UserService::getUserByMobile($data['phone'],'id,mobile,idno');
        setLog(array('uid'=>$userinfo['id']));
        $old_check_idno_hours = Block::check('MODIFYPWD_CHECK_IDNO_HOURS',$userinfo['id'],true);
        if ($old_check_hours === false) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY',$ret['msg']);//身份证号输入频率限制
        }
        if ($userinfo && $userinfo['idno'] == $data['idno']) {
            $ret['msg'] = '身份验证通过';
        } else {
            $old_check_idno_hours = Block::check('MODIFYPWD_CHECK_IDNO_HOURS',$userinfo['id'],false);//旧密码输入错误频率限制检查
            if ($old_check_hours === false) {
                $msg = '错误次数过多,请稍后重试';
            }
            $this->setErr('ERR_IDENTITY_NO_VERIFY','身份证不正确');
        }
        $this->json_data = $ret;
        return ture;
    }
}
