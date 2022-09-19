<?php

/**
 * 新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use api\conf\Error;
use libs\web\Form;
use web\controllers\BaseAction;
use core\enum\DeviceEnum;
use core\service\user\UserService;
use core\service\bonus\H5UnionService;
use core\service\risk\RiskServiceFactory;
use core\service\open\OpenService;
use core\service\coupon\CouponService;
use core\service\user\RegisterService;
use core\service\user\UserLoginService;
use core\service\adunion\AdunionDealService;
use core\service\sms\MobileCodeService;
use libs\utils\Risk;
use libs\utils\Site;
use libs\utils\Monitor;
use libs\lock\LockFactory;
require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class DoRegister extends BaseAction {

    private $_error = null;
    private $_errorno = 0;
    private $_isH5 = false;
    private $_isHaveCountyCode = false;
    private $_auth_clients = array(
        '6d03d1ab2ac33258fb1b5fcf',
        'd3e9e24156be0f5b8e1100ac',
        'bb469276d5eb331f2cb7c451',
        '4f853a4df204ffcd00924517',
        '8365f78859915a7db00e37c6',
        'e01e4e865e87f57999f14fce',
        '3b5883c1f384f73007a0cb0c',
    );
    public function init() {
        $this->form = new Form('post');
        $this->_isH5 = (isset($_REQUEST['type']) && $_REQUEST['type'] == "h5") ? true : false;
        $this->form->rules = array(
            'username' => array('filter' => 'reg', 'message' => '请输入4-16位字母、数字、下划线、横线，首位只能为字母', "option" => array("regexp" => "/^([A-Za-z])[\w-]{3,15}$/", 'optional' => true)),
            'password' => array('filter' => 'length', 'message' => '密码应为6-20位数字/字母/标点', "option" => array("min" => 6, "max" => 20)),
//                'retype' => array('filter' => 'string'),
            'email' => array('filter' => 'email', 'option' => array("optional" => true)),
            'mobile' => array(
                'filter' => 'reg',
                "message" => "手机号码应为7-11为数字",
                "option" => array("regexp" => "/^1[3456789]\d{9}$/")
            ),
            'code' => array('filter' => 'string'),
            'invite' => array('filter' => 'string'),
            'agreement' => array('filter' => 'string'),
            'from_platform' => array('filter' => 'string'),
            'type' => array('filter' => 'string'),
            'src' => array('filter' => 'string'),
            'isAjax' => array('filter' => 'int'),
            "site_id" => array("filter" => 'string', "option" => array("optional"=>true)),
            'event_cn_hidden' => array('filter' => 'int'),
        );
        if (!empty($_REQUEST['country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['is_show']){
            $this->form->rules['mobile'] =  array('filter' => 'reg', "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['regex']}/"));
            $this->_isHaveCountyCode = true;
        }
        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
            return $this->showRegisterError();
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER, Risk::PF_WEB, $this->_isH5 ? DeviceEnum::DEVICE_WAP : DeviceEnum::DEVICE_WEB)->check($data, Risk::SYNC);
        if ($ret === false) {
            $this->_errorno = Error::getCode('ERR_SIGNUP');
            $this->_error = array('code' => '注册异常');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (!empty($data['invite'])) {
            $data['invite'] = htmlspecialchars(str_replace(' ', '', $data['invite']));
        } else {
            $data['invite'] = Site::getCoupon();
        }

        // add by wangfei5@ ,邀请码识别+转换
        $aliasInfo = CouponService::getShortAliasFormMobilOrAlias(trim($data['invite']));
        $data['invite'] = $aliasInfo['alias'];
        //end

        if (empty($data['code'])) {
            $this->_errorno = Error::getCode('ERR_VERIFY_ILLEGAL');
            $this->_error = array('code' => '验证码错误');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        //校验短信验证码是否与缓存中的一致，否则存在绕过短信验证码校验，将任意未注册的手机号注册成为网信平台用户的漏洞
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $mobileCodeObj = new MobileCodeService();
            $vcode = $mobileCodeObj->getMobilePhoneTimeVcode($data['mobile']);
            if ($vcode != $data['code']) {
                $this->_error = array('code' => '验证码不正确');
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
            $mobileCodeObj->delMobileCode($data['mobile']);
        }

        if (!empty($this->_error)) {
            empty($this->_errorno) && $this->_errorno = Error::getCode('ERR_SIGNUP');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (!$this->_isH5) {
            if ($data['agreement'] != '1') {
                $this->_errorno = Error::getCode('ERR_SIGNUP');
                $this->_error = array('agreement' => '不同意注册协议无法完成注册');
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
        }

        $euid = Site::getEuid();
        // 用户注册信息
        $registerInfo = [
            'from_platform' => 1, // 注册来源平台(1:Web|2:App)
            'username' => !empty($data['username']) ? $data['username'] : '',
            'password' => !empty($data['password']) ? $data['password'] : '',
            'email' => !empty($data['email']) ? $data['email'] : '',
            'phone' => !empty($data['mobile']) ? $data['mobile'] : '',
            'code' => !empty($data['code']) ? $data['code'] : '',
            'invite' => !empty($data['invite']) ? $data['invite'] : '',
            'site_id' => !empty($data['site_id']) ? $data['site_id'] : '',
            'euid' => $euid,
            'country_code' => !empty($_REQUEST['country_code']) ? $_REQUEST['country_code'] : '',
            'agreement' => !empty($data['agreement']) ? (int)$data['agreement'] : 0,
            'type' => !empty($data['type']) ? $data['type'] : '',
            'src' => !empty($data['src']) ? $data['src'] : '',
            'isAjax' => !empty($data['isAjax']) ? (int)$data['isAjax'] : 0,
            'agreement' => !empty($data['agreement']) ? $data['agreement'] : '',
            'event_cn_hidden' => !empty($data['event_cn_hidden']) ? $data['event_cn_hidden'] : '',
        ];
        // 调用wx注册接口
        $ret = UserService::userRegister($registerInfo);
        if (UserService::hasError()) {
            $this->_errorno = UserService::getErrorCode();
            $this->_error = ['errorMsg' => UserService::getErrorMsg()];
            return $this->showRegisterError();
        }

        $userId = (int)$ret['uid'];
        /**
         * register success
         */
        // 增加日志注册成功时候uid
        $this->setLog("uid", $userId);

        // refer为分站h5页面，注册成功后跳转APP下载地址
        if (!empty($data['type']) && $data['type'] == 'h5' && APP_SITE != 'firstp2p') {
            $downloadURL = app_conf('APP_DOWNLOAD_H5_URL');
        }

        //广告联盟落单
        //$adunionDealObj = new AdunionDealService();
        //$adunionDealObj->triggerAdRecord($userId, 1, 0, 0, 0, 0, $data['invite'], $euid);

        if (!empty($_REQUEST['client_id'])) {
            $GLOBALS['user_info'] = array('id' => $userId);
            //h5 web站处理 直接回调到redirect_uri
            if ($_REQUEST['client_id'] == '7b9bd46617b3f47950687351' || $_REQUEST['client_id'] == 'db6c30dddd42e4343c82713e') {
                $this->log();
                header("Location: " . urldecode($_REQUEST['redirect_uri']));
                return;
            } elseif (in_array($_REQUEST['client_id'], $this->_auth_clients)) { //对于注册后自动登录的case
                $this->log();
                $this->authorize();
                return;
            } else {
                if ($data['isAjax'] == 1) {
                    $redirect = PRE_HTTP . APP_HOST . '/user/login?' . $_SERVER['QUERY_STRING'];
                    echo json_encode(array(
                        'errorCode' => 0,
                        'errorMsg' => '',
                        'redirect' => $redirect,
                        'data' => ['downloadURL' => empty($downloadURL) ? $redirect : $downloadURL],
                    ));
                } else {
                    $this->tpl->assign('jump_url', PRE_HTTP . APP_HOST . '/user/login?' . $_SERVER['QUERY_STRING']);
                    $this->tpl->display('web/views/user/success.html');
                }
            }
            return;
        }

        // 设置登录标识
        $ret = UserLoginService::setUserLogin($userId);
        if ($ret) {
            $targetUrl = "/";
            if (!empty($data['type']) && $data['type'] == 'h5') {
                $targetUrl = '';
            } elseif (!empty($data['type']) && $data['type'] == 'bdh5') {
                if (isset($GLOBALS['h5Union'][$data['src']])) {
                    $h5UnionService = H5UnionService::getInstance($GLOBALS['h5Union'][$data['src']]);
                    $h5UnionService->addRedisCount();
                }
                $targetUrl = '';
            } else {
                $targetUrl = '';
            }

            if (isset($_GET['from']))
            {
                $targetUrl .='?from=reg';
            }
            // add by wangfei5@
            $registerObj = new RegisterService();
            $registerObj->afterRegister();

            if ($this->isModal()) {
                setcookie('modal_login_succ', 1, 0, '/', get_root_domain());
                $this->template = null;
                return true;
            }

            if ($data['isAjax'] == 1) {
                echo json_encode(array(
                    'errorCode' => 0,
                    'errorMsg' => '',
                    'redirect' => $targetUrl,
                    'data' => ['downloadURL' => empty($downloadURL) ? url($targetUrl) : $downloadURL],
                ));
            } else {
                return app_redirect(url($targetUrl));
            }
        } else {
            if ($data['isAjax'] == 1) {
                echo json_encode(array(
                    'errorCode' => -1,
                    'errorMsg' => $ret['msg'],
                ));
            } else {
                return $this->show_error($ret['msg']);
            }
        }
    }

    /**
     * 显示注册页面错误提示信息
     * 根据$this->_error 显示错误信息到对应的位置
     * */
    private function showRegisterError() {
        $data = $this->form->data;
        $register = new \web\controllers\user\Register();
        $agreement = $register->getAgreementAddress(app_conf('APP_SITE'));

        $this->tpl->assign('invite_money', $register->getInviteMoney());
        $this->tpl->assign("page_title", '注册');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("errorMsg", (!empty($this->_error) ? current($this->_error) : ''));
        $this->tpl->assign("data", $data);
        $this->tpl->assign("cn", $data['invite']);
        $this->tpl->assign("from_platform", $data['from_platform']);
        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
        $this->tpl->assign("event_cn_hidden", $data['event_cn_hidden']);
        if ($data['isAjax'] == 1) {
            $errorMsg = $this->_error;
            if (is_array($errorMsg)) {
                $errorMsg = array_pop($errorMsg);
            }
            $errorCode = $this->_errorno == -33 ? -33 : -1;
            echo json_encode(array(
                'errorCode' => $errorCode,
                'errorMsg' => $errorMsg,
            ));
        } else {
            if($this->_errorno == -33){//需要修改密码引导
                $this->tpl->assign('third_auto_reg_resetpwd', true);
            }
            if ($this->_isH5) {
                $agreement = '/register_terms_h5.html';
                $this->tpl->assign("agreement", $agreement);
                $this->tpl->assign("querystring", '?' . $_SERVER['QUERY_STRING']);
                $this->template = 'web/views/user/register_h5.html';
//                $this->tpl->display('web/views/user/register_h5.html');
                $clientId = $_REQUEST['client_id'];
                if (array_key_exists($clientId, $GLOBALS['sys_config']['OAUTH_SERVER_CONF']) && isset($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['tpl'])) {
                    $this->tpl->assign("from_site", @$_REQUEST['from_site']);
                    $this->tpl->assign('mobile', $data['mobile']);
                    $this->template = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['tpl']['register'];
                }

                if(isset($GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['js']['register'])){
                    $fzjs = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'][$clientId]['js']['register'];
                    $this->tpl->assign('fzjs', $fzjs);
                }
            } else if ($data['type'] === 'bdh5') {
                if (isset($GLOBALS['h5Union'][$data['src']])) {
                    $h5UnionService = H5UnionService::getInstance($GLOBALS['h5Union'][$data['src']]);
                    $agreement = '/register_terms_h5.html';
                    $this->tpl->assign("agreement", $agreement);
                    $this->tpl->assign("type", $data['type']);
                    $this->tpl->assign("src", $data['src']);
                    $this->tpl->assign("invite", $h5UnionService->getInvite());
                    $this->tpl->assign("headerDoc", $h5UnionService->getHeaderDoc());
                    $this->tpl->assign("buttonDoc", $h5UnionService->getButtonDoc());
                    $this->tpl->display('web/views/user/register_zq.html');
                }
            } else {
                $this->tpl->assign("agreement", $agreement);
                if (app_conf('TPL_REGISTER')) {
                    $this->template = app_conf('TPL_REGISTER');
                } else {
                    $this->template = $this->getShowTemplate();
                }
            }
        }
        return false;
    }

    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function getShowTemplate() {
        return $this->isModal() ? 'web/views/user/modal_register.html' : 'web/views/user/register.html';
    }
}
