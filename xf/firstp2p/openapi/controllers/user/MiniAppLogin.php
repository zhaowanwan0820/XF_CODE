<?php
/**
 * @abstract weixin小程序登录登录接口
 * @author zhaohui <zhaohui3@ucfgroup.com>
 * @date 2016.12.19
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\LogRegLoginService;
use NCFGroup\Protos\Ptp\RequestUserLogin;
use NCFGroup\Protos\Ptp\RequestOauth;
use libs\utils\Monitor;

class MiniAppLogin extends BaseAction {

    private static $_url = 'https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code';
    public function init() {
        parent::init();

        $this->form = new Form('post');
        $this->form->rules = array(
            "account" => array("filter" => "required", "message" => '用户名不能为空'),
            "password" => array("filter" => "reg", "message" => '用户名密码不匹配', "option" => array("regexp" => "/^.{5,25}$/")),
            "code" => array("filter" => "required", "message" => '用户名code不能为空'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR",$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        if (!$data['account'] || !$data['code']) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
        //验证微信code的有效性
        $url = self::$_url;
        //请求参数
        $postParam = array(
                'appid' => 'wx0700eac8c3422c69',
                'secret' => 'ccee8bac476f8fb00b9cc1323cb131be',
                'js_code' => $data['code'],
                'grant_type' => 'authorization_code',
        );
        $resultSend = \libs\utils\Curl::post($url,$postParam);
        \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $resultSend,user_name_format($data['account'],3),$data['code'])));
        $res = json_decode($resultSend,true);
        if ($res && isset($res['errcode']) && !isset($res['openid'])) {
            \libs\utils\Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, user_name_format($data['account'],3),'微信小程序code无效'.'|errcode:|'.$res['errcode'].'|errmsg:|'.$res['errmsg'])));
            $this->setErr("ERR_LOGIN_FAIL",'code无效');
            return false;
        }
        $request = new RequestUserLogin();
        try {
            $request->setAccount($data['account']);
            $request->setPassword($data['password']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            Monitor::add('LOGIN_FAIL');
            return false;
        }
        $userLoginResponse = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpUser',
                'method' => 'login',
                'args' => $request
        ));

        $logRegLoginService = new LogRegLoginService();
        if ($userLoginResponse->resCode) {
            $logRegLoginService->insert($this->form->data['account'], '', 0, 2, 'MiniApp');
            $this->setErr("ERR_LOGIN_FAIL",$userLoginResponse->errorMsg);
            return false;
        }
        $logRegLoginService->insert($this->form->data['account'], $userLoginResponse->userId, 0, 1, 'MiniApp');
        $res = $this->authorize();

        $this->json_data = $res;
        return true;
    }

    /**
     * oauth2 认证，
     */
    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $this->getAuthorizeParams();
        if (!$params) {
            $this->setErr("ERR_PARAMS_ERROR",'无效的client_id');
            return false;
        }
        //返回code码
        $res = $oauth->finishClientAuthorization(true, $params,false);
        //返回token
        $params['response_type'] = 'token';
        $_REQUEST['code'] = $res['query']['code'];
        $token = $oauth->finishClientAuthorization(true, $params,false);
        return $token;
    }

    private function getAuthorizeParams() {
        $filters = array(
                "client_id" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
        );
        $input = filter_input_array(INPUT_POST, $filters);
        $input["response_type"] = 'code';
        $client_conf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        if (!$input["client_id"] || !isset($client_conf[$input["client_id"]]) || $input["client_id"] != 'b4a4fbd1c2049167fee6f635') {
            return false;
        }
        return $input;
    }

}
