<?php
/**
 * 用户登录
 * @author 杨庆<yangqing@ucfgroup.com>
 */
namespace web\controllers\user;

use core\service\user\UserService;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;
use core\service\risk\RiskServiceFactory;
use core\service\user\UserTrackService;
use core\service\user\UserLoginService;
use core\service\sms\MobileCodeService;
use core\service\coupon\CouponService;
use libs\utils\Risk;
use libs\utils\Monitor;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";
\FP::import("libs.common.dict");
class DoLogin extends BaseAction {

    private $_error = null;
    private $smLogin = 0; //短信登录标记

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'username' => array('filter' => 'string'),
            'password' => array('filter' => 'string'),
            'captcha' => array('filter' => 'string'),
            'country_code' => array('filter' => 'string'),
            'csessionid' => array('filter' => 'string'),
            'sig' => array('filter' => 'string'),
            'risk_token' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->_error = $this->form->getErrorMsg();
        }
    }

    public function invoke() {
        $data = $this->form->data;
	//var_dump($data);die;
        // 为防止跳过login，直接登录，设置短信码校验
        $tpl_query = array();
        !empty($_GET['tpl']) ? $tpl_query['tpl'] = trim($_GET['tpl']) : null;
        if (!empty($_GET['backurl'])) {
            if (isMainDomain($_GET['backurl'])) {
                $tpl_query['backurl'] = urldecode(trim($_GET['backurl']));
            } else {
                $_GET['backurl'] = null;
            }
        }

        $tpl_str = empty($tpl_query) ? '' : '?'.http_build_query($tpl_query);
        if (!empty($_REQUEST['code']) && strlen($_REQUEST['code']) == 6) {
            $mobileCodeObj = new MobileCodeService();
            $vcode = $mobileCodeObj->getMobilePhoneTimeVcode($_REQUEST['valid_phone']);
            if ($vcode != $_REQUEST['code']) {
                Monitor::add('LOGIN_FAIL');
                //return app_redirect(url('user/login' . $tpl_str));
            }
        }

        // 验证表单令牌
        if (!check_token()) {
            Monitor::add('LOGIN_FAIL');
            return app_redirect(url('user/login'.$tpl_str));
        }

        if (!empty($this->_error) || empty($data['username']) || empty($data['password'])) {
            Monitor::add('LOGIN_FAIL');
            //return app_redirect(url('index'));
        }

        // 添加smLogin
        if (1 == intval($GLOBALS['sys_config']['SM_LOGIN_SWITCH'])) {
            //根据分站开关，是否开启短信验证码登录和注册
            if (!empty($this->appInfo)) {
                $setParams = (array) json_decode($this->appInfo['setParams'], true);
                if (!empty($setParams['smLogin']) || !empty($_GET['smLogin'])) {
                    $this->smLogin = 1;
                }
            }
        }

        // 先检查是否需要校验验证码
        $verify = \es_session::get('verify');
        // 先检查是否需要校验验证码
        $loginVerifyWhiteList = \dict::get("LOGIN_VERIFY_WHITELIST");
        $needVerify = true;
        if ((!empty($loginVerifyWhiteList) && in_array(get_real_ip(), $loginVerifyWhiteList))) {
            $needVerify = false;
        }

        if (app_conf('VERIFY_SWITCH') && $needVerify && empty($verify) && empty($_REQUEST['tpl'])) {//无验证码情况调用阿里云的滑块验证码
            $rs = $this->rpc->local('AliyuanService\verify',array(array(
                'from'=>0,
                'scene'=>'login',
                'username'=>$data['username'],
                'csessionid'=>$data['csessionid'],
                'sig'=>$data['sig'],
                'token'=>$data['risk_token'],
            )));

            if ($rs['status'] !== 0) {//非0验证不通过
                Monitor::add('LOGIN_FAIL');
                if ($rs['status']===1) {//逻辑上验证失败，返回登录页并提示
                    if ($_GET['client_id'] || $_GET['backurl']) {
                        $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
                    }
                    $this->tpl->assign("mobile_codes", $GLOBALS['dict']['MOBILE_CODE']);
                    $this->_error = '验证码校验失败';
                    return $this->showLoginError();
                }

                if ($rs['status'] === 3) {//阿里云服务异常，切换到我们的验证码
                    $this->tpl->assign("show_vcode", '1');
                    $this->_error = '验证码校验失败';
                    if ($_GET['client_id'] || $_GET['backurl']) {
                        $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
                    }
                    $this->tpl->assign("mobile_codes",$GLOBALS['dict']['MOBILE_CODE']);
                    return $this->showLoginError();
                }
            }
        }

        // 图形验证码存在则验证图形验证码，对于自定义的模板，需要图形验证码
        if (($needVerify || !empty($_REQUEST['tpl'])) && !empty($verify)) {
            // 验证码校验失败，立刻将session中verify设置成非MD5值
             \es_session::set('verify','xxx removeVerify xxx');
             $captcha = $data['captcha'];
             if (md5($captcha) !== $verify) {
                 if (isset($_GET['client_id']) || isset($_GET['backurl'])) {
                     $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
                 }

                 $this->_error = '您输入的验证码错误 ';
                 $this->tpl->assign("show_vcode", '1');
                 Monitor::add('LOGIN_FAIL');
                 return $this->showLoginError();
             }
        }

        //请求风控在图形验证码之后
        RiskServiceFactory::instance(Risk::BC_LOGIN)->check($data, Risk::SYNC);

        // 对于pc做特殊处理
        $isEnterpriseSite = is_qiye_site();

        $ret = UserService::login($data['username'], $data['password'], $data['country_code']);
        // 通行证弹框逻辑
        //if ($ret['code'] == '0' && !empty($ret['isPassport']) && $ret['data']['authPass']) {
        //    \es_session::set('ppId', $ret['data']['ppUserInfo']['ppId']);
        //    $passportService = new PassportService();
        //    $passportService->savePPSession($ret['data']['ppUserInfo']['ppId'], session_id(), 'web');
        //    if ($ret['data']['needVerify'] || $ret['data']['showAuth']) {
        //        $ret['data']['needVerify'] ? \es_session::set('passportNeedVerify', 1) : \es_session::set('passportNeedVerify', 0);
        //        $this->tpl->assign('paAuthRes', $ret['data']);
        //        return $this->showPassport();
        //    }
        //}

        //if ($ret['code'] == '-20') {
        //    \es_session::set('localNeedVerify', 1);
        //    $this->tpl->assign('mobileNeedVerify', $data['username']);
        //    return $this->showPassport();
        //}

        //$logRegLoginService = new LogRegLoginService();
        //$logRegLoginService->insert($ret['user_name'], $ret['user_id'], 0, $ret['code'] == '0' ? 1 : 2, 1);

        if ( ! UserService::hasError()) {
            // 记录用户登录站点
            $userTrackService = new UserTrackService();
            $userTrackService->setLoginSite($ret['uid']);
            // 获取用户邀请码
            $user_id = $ret['uid'];
            $couponLatest = CouponService::getCouponLatest($user_id);
            $invite_code = !empty($couponLatest) ? $couponLatest['coupon']['short_alias'] : '';

            //euid如果非空，则进行track
            $isEuid = false;
            $cEuid = \es_cookie::get('euid');
            if(!empty($_GET['euid']) || !empty($cEuid)){
                $isEuid = true;
            }

            //用户行为追踪，根据配置邀请码，用户是否需要种 trackId
            $track_id = \es_session::get('track_id');
            if (!$track_id && (in_array(strtoupper($invite_code), get_adunion_order_coupon()) || $isEuid)) {
                $track_id = hexdec(Logger::getLogId());
                \es_session::set('track_id', $track_id);
                \es_session::set('track_on', 1);
            }

            RiskServiceFactory::instance(Risk::BC_LOGIN)->notify(array('userId'=>$ret['uid']));
            // 回跳URL
            $jumpUrl = get_login_gopreview();
            // 登陆成功 session设置
            UserLoginService::setUserLogin($ret['uid']);

            if ($this->isModal()) {
                setcookie('modal_login_succ', 1, 0, '/', get_root_domain());
                $this->template = null;
                return true;
            }
            if (!empty($_GET['backurl'])) {
                if (substr(urldecode($_GET['backurl']), 0, 7) == 'http://' || substr(urldecode($_GET['backurl']), 0, 8) == 'https://') {
                    header("Location: ". urldecode($_GET['backurl']));
                    exit();
                }
                //return app_redirect(url(trim($_GET['backurl'], '/')));
            }
            if (!empty($_REQUEST['client_id'])) {
                $this->authorize();
            }

            if (!empty($GLOBALS['user_info']['force_new_passwd']) && !$this->isEnterprise) {
                //return app_redirect(url('user/editpwd'));
            }

            if (empty($jumpUrl)) {
                //return app_redirect($jumpUrl);
            } else {
                return header("location:/");
            }
            return;
        } else {
            // 登录失败且滑块降级的时候显示验证码
            if (app_conf('VERIFY_SWITCH') == 0 || !empty($_REQUEST['tpl'])) {
                $this->tpl->assign("show_vcode", '1');
            }

            $this->_error = UserService::getErrorMsg();
            if (!empty($_GET['client_id']) || !empty($_GET['backurl'])) {
                $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
            }

            $this->tpl->assign("mobile_codes", $GLOBALS['dict']['MOBILE_CODE']);
            // 非企业投资户请使用个人用户入口登录
            if ($ret['code'] == -10) {
                $this->tpl->assign('showErrorLoginTips', true);
                $this->tpl->assign('loginRedirectUrl', '//'.app_conf('WXLC_DOMAIN').'/user/login');
                $this->tpl->assign('siteTitleTips', '网信个人站');
            } else if ($ret['code'] == -11) {
                // 企业投资户请使用企业投资入口登录
                $this->tpl->assign('showErrorLoginTips', true);
                $this->tpl->assign('loginRedirectUrl', '//'.app_conf('FIRSTP2P_QIYE_DOMAIN').'/user/login');
                $this->tpl->assign('siteTitleTips', '网信企业站');
            } else if ($ret['code'] == -33 ){
                //引导用户去修改密码
                if (!$this->isEnterprise) {
                    $this->tpl->assign('third_auto_reg_resetpwd', true);
                }
            }

            return $this->showLoginError();
        }
    }

    /**
     *
     * 显示错误提示页面
     * 根据$this->_error显示对应错误
     *
     * */
    private function showLoginError() {
        $this->tpl->assign("page_title", '登录');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("error", $this->_error);
        $this->tpl->assign("data", $this->form->data);
        if (app_conf('TPL_LOGIN')) {
            $this->template = app_conf('TPL_LOGIN');
        } elseif (!empty($_GET['tpl'])) {
            $this->template = 'web/views/user/login_'.trim($_GET['tpl']).'.html';
        } else {
            $this->template = $this->getShowTemplate();
        }
        //添加smLogin
        if($this->smLogin == 1){
             $this->tpl->assign("smLogin",  1);
             $this->setSmLoginToken();
        }

        if (!empty($_REQUEST['modal']) || $this->smLogin==1) {
            $this->tpl->assign('querystring', '?' . $_SERVER['QUERY_STRING']);
        }
        //TODO 用户登录日志,改成消息队列
        //$logRegLoginService = new LogRegLoginService();
        //$logRegLoginService->insert($this->form->data['username'], '', 0, 2, 1);
    }

    private function showPassport() {
        $this->tpl->assign("page_title", '登录');
        $this->tpl->assign("website", app_conf('APP_NAME'));
        $this->tpl->assign("data", $this->form->data);
        if (app_conf('TPL_LOGIN')) {
            $this->template = app_conf('TPL_LOGIN');
        } elseif (!empty($_GET['tpl'])) {
            $this->template = 'web/views/user/login_'.trim($_GET['tpl']).'.html';
        } else {
            $this->template = $this->getShowTemplate();
        }
    }

    /**
     *
     */
    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function getShowTemplate() {
        return $this->isModal() ? 'web/views/user/modal_login.html' : 'web/views/user/login.html';
    }

    private function setSmLoginToken(){
        $smLoginToken = \es_session::get('smLoginToken');
        if(empty($smLoginToken)){
            \es_session::set('smLoginToken', md5(session_id().mt_rand(10000, 1000000)));}
        $this->tpl->assign('smLoginToken', \es_session::get('smLoginToken'));
    }


}
