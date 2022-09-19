<?php
namespace api\controllers\user;

use api\conf\Error;
use api\controllers\FundBaseAction;
use libs\web\Form;
use core\service\user\BOBase;
use libs\utils\Block;
/**
 * UsernameCheck
 * @abstract 检查用户名（或手机号）和密码是否存在
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 * @date 2015-06-18
 */
class UsernameCheck extends FundBaseAction 
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules=array(
                'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
                'usernum' => array('filter' =>"required", 'message'=> '用户名（或手机号）不能为空！'),
                'userpwd'=>array('filter'=>"required", 'message'=> '密码不能为空！'),
                'has_vcode'=>array('filter'=>"string","option" => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        } 
    }
    public function invoke()
    {
        $data = $this->form->data;
        $clientIp=get_client_ip();
        $check_client_ip_minute = Block::check('USERNAME_IP_CHECK_MINUTE', $clientIp, false);//启动client_Ip访问限制，30次/min，如果超过则需要验证码
        $check_client_ip_hour = Block::check('USERNAME_IP_CHECK_HOUR', $clientIp, false);//启动client_Ip访问限制，200次/h，如果超过则禁止访问

        $check_username_minute = Block::check('USERNAME_CHECK_MINUTE', $data['usernum'], false);//启动usernsme访问限制，10次/min，如果超过则需要验证码
        $check_username_hour = Block::check('USERNAME_CHECK_HOUR', $data['usernum'], false);//启动usernsme访问限制，20次/h，如果超过则禁止访问

        //判断1h内访问次数是否超过限制次数200次/h
        if ($check_client_ip_hour == false || $check_username_hour==false) {
            $this->setErr('ERR_MANUAL_REASON', '你的请求已经超过了请求次数，不可以再请求！');
            return false;
        }

        //没有验证码
        //判断1min内,没有验证码时，访问次数是否超过限制次数
        if ($check_client_ip_minute == false || $check_username_minute==false) {
            if (!$data[has_vcode] || $data[has_vcode] !== 1) {
                $this->setErr('ERR_VERIFY', '你请求过于频繁，请输入验证码！');
                return false;
            }
        }

        //有验证码且验证码验证通过
        if ($data[has_vcode]==1) {
            $check_username_vcode_minute = Block::check('USERNAME_CHECK_VCODE_MINUTE', $clientIp, false);//启动username验证码访问限制，10次/min
            $check_client_ip_vcode_minute = Block::check('USERNAME_IP_CHECK_VCODE_MINUTE', $clientIp, false);//启动client_Ip验证码访问限制，30次/min
            if ($check_client_ip_vcode_minute==false || $check_username_vcode_minute==false) {
                $this->setErr('ERR_MANUAL_REASON', '你的请求已经超过了请求次数，不可以再请求！');
                return false;
            }
        }

        if (!$data['usernum'] || !$data['userpwd']) {
            $this->setErr('ERR_PARAMS_ERROR', '输入的参数中有空值，请输入！');
            return false;
        } elseif ($data['usernum']) {
            $result = $this->rpc->local('UserService\getUserinfoByUsername', array($data['usernum']));
        }
        if ($result) {
            $pwdCom=new BOBase();
            $data['userpwd']=$pwdCom->compilePassword($data['userpwd']);
            if ($result['user_pwd']===$data['userpwd']) {
                $res[isUserPwdExist]=true;
                $res[userId]=$result[id];
            } else {
                $msg = '用户名和密码不匹配！';
                $this->setErr('ERR_AUTH_FAIL', $msg);
                return false;
            }

        } else {
            $msg = '用户名不存在！';
            $this->setErr('ERR_USERNAME_ILLEGAL', $msg);
            return false;
        }
      //$res = ($result['user_pwd']===$data['userpwd']? true :false);
      $this->json_data = $res;
      return true;
    }
}
