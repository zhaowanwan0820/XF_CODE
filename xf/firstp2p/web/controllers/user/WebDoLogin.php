<?php

/**
 * web防套利，身份验证中的，短信验证码验证
 * @author zhaohui<zhaohui3@ucfgroup.com>
 */

namespace web\controllers\user;

use core\service\MsgConfigService;
use web\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Block;

class WebDoLogin extends BaseAction
{
    private $_error = null;
    private $_flag = false;
    public function init()
    {
        $this->form = new Form("post");
        $this->form->rules = array(
                'code'=> array('filter'=>'string'),
                'mobile'=>array('filter'=>'string'),
                'type'=>array('filter'=>'int'),
                'smLoginToken'=>array('filter'=>'string'),
                );
        if (! $this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
            echo json_encode(array(
                        'errorCode' => 2,
                        'errorMsg' => $this->_error,
                        ));
            return ;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user_info['mobile']=$data['mobile'];
        $type = $data['type'];


        //检测用户输入验证码错误信息
        $ip = get_client_ip();
        //最多验证4次
        $check_ip_minute_result = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_IP', $ip,false);
        $check_phone_minute_result = Block::check('SM_LOGIN_CODE_VERIFY_RV_CN_PHONE', $data['mobile'],false);
        if($check_ip_minute_result === false || $check_phone_minute_result ==false) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg'] = "短信验证错误次数太多，请稍后再试";
            echo json_encode($loginResult);
            exit;
        }

        if($type == 16){
            //校验smLoginToken
            $smLoginToken = \es_session::get('smLoginToken');
            if( empty($smLoginToken) || $data['smLoginToken'] != $smLoginToken ){
                //非法请求
                $loginResult['errorCode'] = -1;
                $loginResult['errorMsg'] = "非法请求";
                echo json_encode($loginResult);
                exit;
            }
       }
        //短信验证码校验
        if ($data['code']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode',array($user_info['mobile']));
            if (empty($vcode) || $vcode != $data['code']) {
                setLog(array('restrict_vcode_verify'=>0));
                echo json_encode(array(
                            'errorCode' => 1,
                            'errorMsg' => '短信校验错误',
                            ));
                return;
            } else {
                setLog(array('restrict_vcode_verify'=>1));
                echo json_encode(array(
                            'errorCode' => 0,
                            'errorMsg' => '短信校验正确',
                            ));
                return;
            }
        }

    }
}
