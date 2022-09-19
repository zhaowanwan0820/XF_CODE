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
use core\service\user\UserService;
use libs\utils\Block;
use libs\idno\IdnoFormatVerify;

class CheckPhone extends AppBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'phone' => array(
                'filter' => 'reg',
                "message" => "ERR_SIGNUP_PARAM_PHONE",
                'option' => array("regexp" => "/^1[3456789]\d{9}$/", 'optional' => true)
            ),
            'verify' => array(
                'filter' => "reg",
                "message" => 'ERR_VERIFY_ILLEGAL',
                "option" => array("regexp" => "/^[0-9a-zA-Z]{0,4}$/", 'optional' => true)
            ),
            'old_password' => array('filter' => 'string'),
            'from' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_ERROR'
            ),//modify:修改密码  forget：忘记密码
            'idno' => array(
                'filter' => 'string',
                'option' => array('optional' => true)
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        if (!$data['verify']) {
            $this->setErr('ERR_VERIFY_EMPTY');
        }

        if ($data['from'] == 'modify') {
            $userinfo = $this->getUserByToken();
            if (!empty($userinfo['mobile_code']) && $userinfo['mobile_code'] != 86) {
                $this->setErr('ERR_MANUAL_REASON','国际手机用户请移步至电脑版修改密码');
            }

            // 旧密码输入错误频率限制
            $old_check_hours = Block::check('OLDPWD_CHECK_HOURS', $userinfo['id'], true);
            if ($old_check_hours === false) {
                $this->setErr('ERR_AUTH_FAIL','错误次数过多,请稍后重试');
            }

            // 旧密码验证
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
            }

            if ((!$userinfo || !$userinfo['mobile'] || $userinfo['is_effect'] != 1 || $userinfo['is_delete'] != 0)) {
                $this->setErr('ERR_MANUAL_REASON','用户不存在');
            }

            if(!empty($data['phone']) && $data['phone'] != $userinfo['mobile']){//4.6版本以后 修改密码手机号不传
                $this->setErr('ERR_MANUAL_REASON','手机号输入错误');
            }

            $ret['phone'] = '手机号正确';
        } else if ($data['from'] == 'forget') {
            if (empty($data['phone'])) {
                $this->setErr('ERR_PARAMS_ERROR','请输入现在绑定的手机号');
            }

            $userinfo = UserService::getUserByMobile($data['phone'],'id,mobile,idno,idcardpassed');
            if (empty($userinfo)) {
                $this->setErr('ERR_PARAMS_ERROR', '手机号未注册 直接注册即可');
            }

            // 未实名的用户不校验idno idcardpassed: 0未实名 1已实名
            if ($userinfo['idcardpassed'] == 1) {
                if (empty($data['idno'])) {
                    $this->setErr('ERR_PARAMS_ERROR','请输入绑定的证件号');
                }

                if (!IdnoFormatVerify::checkFormat($data['idno'])) {
                    $this->setErr('ERR_PARAMS_ERROR', '证件号码格式不正确');
                }

                if ($data['idno'] != $userinfo['idno']) {
                    $this->setErr('ERR_PARAMS_ERROR','手机号码和证件号码不匹配');
                }
            }

            $ret['phone'] = '手机号正确';
            setLog(array('uid'=>$userinfo['id']));
        }

        $phone = !empty($data['phone']) ? $data['phone'] : $userinfo['mobile'];
        // 校验验证码
        $verify = \SiteApp::init()->cache->get("verify_" . md5($phone));
        \SiteApp::init()->cache->delete("verify_" . md5($phone));

        // 没有验证码表示验证码过期
        if (empty($verify)) {
            $this->setErr('ERR_VERIFY_EXPIRED');
        }
        $data['verify'] = strtolower($data['verify']);
        if ($verify != md5($data['verify'])) {
            $this->setErr('ERR_VERIFY_ILLEGAL');
        }

        $ret['verify'] = '验证码正确';
        $this->json_data = $ret;
        return ture;
    }
}
