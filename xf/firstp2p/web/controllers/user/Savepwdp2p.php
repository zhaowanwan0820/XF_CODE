<?php
/**
 * 修改密码
 * @author zhaohui3@ucfgroup.com
 */
namespace web\controllers\user;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use libs\utils\Logger;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskService;

class Savepwdp2p extends BaseAction
{

    private $_error = null;

    public function init ()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
                 'new_password' => array(
                        'filter' => 'length',
                        'message' => '新密码长度为6-20位',
                        "option" => array(
                                "min" => 6,
                                "max" => 20
                        )
                ),
                'confirmPassword' => array(
                        'filter' => 'length',
                        'message' => '确认密码长度为6-20位',
                        "option" => array(
                                "min" => 6,
                                "max" => 20,
                                "optional"=>true
                        )
                ),
                'ajax' => array('filter' => 'string')//1：异步校验新密码是否和旧密码相同 
        );
        if (! $this->form->validate()) {
            $this->_error = $this->form->getError();
        }
    }

    public function invoke ()
    {
        if (!empty($this->_error)) {
            return $this->_show_error();
        }

        $data = $this->form->data;
        $bo = BOFactory::instance('web');
        if ($data['ajax'] == 1) {
            $user_id = intval($GLOBALS['user_info']['id']);
            $ret = $bo->verifyPwd($user_id,$data['new_password']);
            if ($ret['code'] == '0') {
                $ret['msg'] = '新密码不能和旧密码相同';
                return $this->show_error($ret,'',1,0);
            } else {
                $ret['code'] = '1';
                $ret['msg'] = '旧密码和新密码不同';
                return $this->show_success($ret,'',1,0);
            }
        }
        if (\es_session::get('DoModifyPwd') != '2') {
            $this->show_error('修改失败','',0,0,url('user/editpwd'));
            return false;
        }
        // 验证表单令牌
        if(!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], "", 0, 0,url('user/editpwd'));
        }
        $switch = app_conf('TURN_ON_FIRSTLOGIN');
        if ($switch == 3) {
            if ($data['new_password'] !== $data['confirmPassword']) {
                $this->_error = array(
                        'confirmPassword' => '新密码和确认密码不一致'
                );
                $this->_show_error();
            }
            //密码安全规则检查
            if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {
                $len = strlen($this->form->data['new_password']);
                $mobile = $GLOBALS['user_info']['mobile'];
                $password = $this->form->data['new_password'];
                $password = stripslashes($password);
                \FP::import("libs.common.dict");
                $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
                $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
                if ($base_rule_result){
                    $this->_error = array('erro_msg' => $base_rule_result['errorMsg']);
                    return $this->_show_error();
                }
                //黑名单判断,禁用密码判断
                $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
                if ($forbid_black_result) {
                    $this->_error = array('erro_msg' => $forbid_black_result['errorMsg']);
                    return $this->_show_error();
                }
            }

            //风控检查
            $extraData = [
                'user_id' => $GLOBALS['user_info']['id'],
                'user_name' => $GLOBALS['user_info']['user_name'],
                'mobile' => $GLOBALS['user_info']['mobile'],
                'change_password_verify' => 'phone',
            ];
            $checkRet = RiskService::check('CPWD', $extraData);
            if (false === $checkRet) {
                return $this->show_error('操作失败，请稍后再试','',0,0,url('user/editpwd'));
            }

            $user_id = intval($GLOBALS['user_info']['id']);
            $save = $bo->updateNewPwd($user_id, $data['new_password']);
            if ($save['code'] == 0) {
                $this->_error = array(
                        'confirmPassword' => $save['msg']
                );
                $this->_show_error();
            } else {
                // 增加短信提示
                if (app_conf("SMS_ON")==1){
                    $msg_content = array(
                                    'modify_time' => date("m-d H:i")
                                    );
                    // SMSSend 用户修改密码短信通知
                    $_mobile = $GLOBALS['user_info']['mobile'];
                    if ($GLOBALS['user_info']['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                    {
                        $_mobile = 'enterprise';
                    }
                    \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_SMS_MODIFY_PASSWORD', $msg_content, $user_id);
                }
                \es_session::delete('DoModifyPwd');//删除session，防止刷新多次请求
                //生产用户访问日志
                UserAccessLogService::produceLog($user_id, UserAccessLogEnum::TYPE_UPDATE_PASSWORD, '修改密码成功', '', '', DeviceEnum::DEVICE_WEB);
                return $this->show_success('修改成功', '', 0, 0, url("account/setup"));
            }
        } else {
            $this->_error = array(
                    'confirmPassword' => '系统维护中'
            );
            $this->_show_error();
        }
    }

    private function _show_error ()
    {
        setLog(array('error_msg' => array('error' => json_encode($this->_error))));
        //$this->tpl->assign('title', app_conf('SHOP_TITLE'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("data", $this->form->data);
        $this->template = "web/views/v2/user/renewpwd.html";
        return false;
    }
}
