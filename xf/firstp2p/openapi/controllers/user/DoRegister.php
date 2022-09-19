<?php

/**
 * 
 * openapi新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace openapi\controllers\user;

use libs\web\Form;
use libs\utils\Monitor;
use libs\utils\Logger;
use openapi\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;


require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class DoRegister extends BaseAction {

    private $_error = null;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'username' => array('filter' => 'length', 'message' => '请输入4-16个字符', "option" => array("min" => 4, "max" => 16)),
            'password' => array('filter' => 'length', 'message' => '请输入5-25个字符', "option" => array("min" => 5, "max" => 25)),
//                'retype' => array('filter' => 'string'),
            'email' => array('filter' => 'email'),
            'mobile' => array('filter' => 'reg', "message" => "手机号码应为7-11为数字",
                'option' => array("regexp" => "/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/")),
            'code' => array('filter' => 'string'),
            'invite' => array('filter' => 'string'),
            //'agreement' => array('filter' => 'string'),
            'type' => array('filter' => 'string'),
            'src' => array('filter' => 'string'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
            $this->showRegisterError();
            return false;
        }
    }

    public function invoke() {
        $logRegLoginService = new LogRegLoginService();
        if (empty($this->form->data['code'])) {
            $this->_error = array('code' => '手机验证码错误');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (!empty($this->_error)) {
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }
//        if ($this->form->data['agreement'] != '1') {
//            $this->_error = array('agreement' => '不同意注册协议无法完成注册');
//            return $this->showRegisterError();
//        }
        // 是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($this->form->data['mobile']));
            if ($vcode != $this->form->data['code']) {
                $this->_error = array('code' => '手机验证码不正确');
                $logRegLoginService->insert(trim($this->form->data['username']), '', 2, 0, 1, $this->form->data['invite']);
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
        }
        $bo = BOFactory::instance('web');
        $this->form->data['username'] = trim($this->form->data['username']);
        $this->form->data['mobile'] = trim($this->form->data['mobile']);
        $userInfo = array(
            'username' => $this->form->data['username'],
            'password' => $this->form->data['password'],
            'email' => $this->form->data['email'],
            'mobile' => $this->form->data['mobile'],
        );
        if (!empty($this->form->data['invite'])) {
            $userInfo['invite_code'] = $this->form->data['invite'];
        }

        $userInfo['referer'] = $this->device; //记录来源mobile web端
        $upResult = $bo->insertInfo($userInfo);
        if ($upResult['status'] < 0) {
            $logRegLoginService->insert($this->form->data['username'], '', 2, 0, 1, $this->form->data['invite']);
            $this->_error = $upResult['data'];
            return $this->showRegisterError();
        } else {
            $logRegLoginService->insert($this->form->data['username'], $upResult['user_id'], 1, $ret['code'] == 0 ? 1 : 0, 1, $this->form->data['invite']);
            $track_id = hexdec(\libs\utils\Logger::getLogId());
            \es_session::set('track_id', $track_id);
            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($upResult['user_id'], 1)); //广告联盟

            if (!empty($_REQUEST['client_id'])) {
                $jumpUrl = $this->clientConf['redirect_uri'];
                if (!empty($jumpUrl)) {
                    header("Location: " . $jumpUrl);
                    return true;
                }

//                $this->tpl->assign('jump_url', PRE_HTTP . APP_HOST . '/user/login?' . $_SERVER['QUERY_STRING']);
//                $this->tpl->display('web/views/user/success.html');
            }

            /*
              $request = new RequestUserLogin();
              try {
              $request->setAccount($this->form->data['username']);
              $request->setPassword($this->form->data['password']);
              } catch (\Exception $exc) {
              $this->errorCode = -99;
              $this->errorMsg = "param set ERROR";
              return false;
              }
              $userLoginResponse = $GLOBALS['rpc']->callByObject(array(
              'service' => 'NCFGroup\Ptp\services\PtpUser',
              'method' => 'login',
              'args' => $request
              ));
              $this->authorize();
             * 
             */
            return true;
        }
    }

    /**
     * oauth2 认证，返回code码
     */
    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    /**
     * 显示注册页面错误提示信息
     * 根据$this->_error 显示错误信息到对应的位置
     * */
    private function showRegisterError() {
        $data = $this->form->data;
        $register = new \openapi\controllers\user\Register();
        $agreement = $register->getAgreementAddress(app_conf('APP_SITE'));
        $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
//        $this->tpl->assign('invite_money', $register->getInviteMoney());
        $this->tpl->assign('agreement', $agreement);
        $this->tpl->assign("page_title", '注册');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("data", $data);
        $this->tpl->assign("cn", $data['invite']);
        $this->template = "openapi/views/user/register.html";

        return false;
    }

    public function _after_invoke() {
        if (!empty($this->template)) {
            $this->tpl->display($this->template);
            return true;
        }
        parent::_after_invoke();
    }

    public function authCheck() {
        return true;
    }

}
