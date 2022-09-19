<?php

/**
 * 新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use core\service\H5UnionService;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;
use libs\utils\Logger;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use libs\utils\Site;

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
        $this->_isH5 = ($_REQUEST['type'] == "h5") ? true : false;
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
            // 信力注册来源
            "f" => array("filter" => 'string', "option" => array("optional"=>true)),

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
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER,Risk::PF_WEB,$this->_isH5?DeviceEnum::DEVICE_WAP:DeviceEnum::DEVICE_WEB)->check($data,Risk::SYNC);
        if ($ret === false) {
            $this->_error = array('code' => '注册异常');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (!empty($data['invite'])) {
            $data['invite'] = htmlspecialchars(str_replace(' ', '', $data['invite']));
        } else {
            $data['invite'] = Site::getCoupon();
        }

        // 邀请码未填情况从红包中获取
        if (empty($data['invite']))
            $data['invite'] = $this->rpc->local('BonusService\getReferCN', array(trim($data['mobile'])));

        // add by wangfei5@ ,邀请码识别+转换
        $aliasInfo = $this->rpc->local('CouponService\getShortAliasFormMobilOrAlias', array(trim($data['invite'])));
        $data['invite'] = $aliasInfo['alias'];
        //end

        if (empty($data['code'])) {
            $this->_error = array('code' => '验证码错误');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (!empty($this->_error)) {
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (!$this->_isH5) {
            if ($data['agreement'] != '1') {
                $this->_error = array('agreement' => '不同意注册协议无法完成注册');
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
        }


        // 是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($data['mobile']));
            if ($vcode != $data['code']) {
                $this->_error = array('code' => '验证码不正确');
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
            $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile']));
        }
        //密码检查
        //基本规则判断
        if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {
            $len = strlen($data['password']);
            $mobile = $data['mobile'];
            $password = $data['password'];
            $password = stripslashes($password);
            \FP::import("libs.common.dict");
            $blacklist=\dict::get("PASSWORD_BLACKLIST");//获取密码黑名单
            $base_rule_result=login_pwd_base_rule($len,$mobile,$password);
            if ($base_rule_result){
                $this->_error = array('password' => $base_rule_result['errorMsg']);
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password,$blacklist,$mobile);
            if ($forbid_black_result) {
                $this->_error = array('password' => $forbid_black_result['errorMsg']);
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
        }

        // 分站优惠购活动
        //$euid = \es_cookie::get('euid');
        //$ticketInfo  = \core\service\OpenService::toCheckTicket($this->appInfo, $euid);
        //if ($ticketInfo['status']) {
        //    $this->_error = array('euid' => $ticketInfo['msg']);
        //    return $this->showRegisterError();
        //}

        $bo = BOFactory::instance('web');
        $data['username'] = trim($data['username']);
        $data['mobile'] = trim($data['mobile']);
        $userInfo = array(
            'username' => $data['username'],
            'password' => $data['password'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
        );
        if ($this->_isHaveCountyCode){
            $userInfo['country_code'] = trim($_REQUEST['country_code']);
            $userInfo['mobile_code'] = $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['code'];
        }
        if (!empty($data['invite'])){
            $userInfo['invite_code'] = strtoupper($data['invite']);
        }
         //如果相应的分站用户注册时，在用户表里没有正确记录site_id，可以手动在请求连接中加上相对应的site_id，不是必传参数
        if ($_REQUEST['site_id'] && ($site_key=array_search($_REQUEST['site_id'], $GLOBALS['sys_config']['TEMPLATE_LIST']))) {
            $userInfo['group_id'] = $GLOBALS['sys_config']['SITE_USER_GROUP'][$site_key];
            $userInfo['site_id']=$_REQUEST['site_id'];
        }

        // 渠道来源信息
        $euid = Site::getEuid();
        if (!empty($euid)) {
            $userInfo['euid'] = $euid;
        }

        // 分站优惠购活动
        //if (isset($ticketInfo['data']['actType']) && $ticketInfo['data']['actType'] == 1) {
        //    $userInfo['open_ticket'] = $ticketInfo['data'];
        //}

        $userInfo['referer'] = DeviceEnum::DEVICE_WEB; //记录来源PC端
        if ($data['type'] == 'h5') {
            $userInfo['referer'] = DeviceEnum::DEVICE_WAP;
            if ($_REQUEST['os']) {
                if (strpos($_REQUEST['os'], 'Android') !== false) {
                    $userInfo['referer'] = DeviceEnum::DEVICE_ANDROID;//Android
                } elseif (strpos($_REQUEST['os'], 'iOS') !== false) {
                    $userInfo['referer'] = DeviceEnum::DEVICE_IOS;//iOS
                }
            }
        }
        // 加锁，防用户注册重复提交
        $lockKey = "register-user-".$userInfo['mobile'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey)) {
            $this->_error = array('mobile'=>'该手机号已经注册，如有疑问请联系客服');
            return $this->showRegisterError();
        }
        $upResult = $bo->insertInfo($userInfo, $this->_isH5);
        $lock->releaseLock($lockKey);
        if ($upResult['status'] < 0) {
            $this->_error = $upResult['data'];
            $this->_errorno = $upResult['status'];
            return $this->showRegisterError();
        } else {
            RiskServiceFactory::instance(Risk::BC_REGISTER)->notify(array('userId'=>$upResult['user_id']), $data);

            /**
             * register success
             */
            // 增加日志注册成功时候uid
            $this->setLog("uid",$upResult['user_id']);

            //生产用户访问日志
            UserAccessLogService::produceLog($upResult['user_id'], UserAccessLogEnum::TYPE_REGISTER, '注册成功', $data, '', DeviceEnum::DEVICE_WEB);

            // refer为分站h5页面，注册成功后跳转APP下载地址
            if (!empty($data['type']) && $data['type'] == 'h5' && APP_SITE != 'firstp2p') {
                $downloadURL = app_conf('APP_DOWNLOAD_H5_URL');
            }

            //分站优惠购活动
            //\core\service\OpenService::setTicketStatus($this->appInfo, $euid, $upResult['user_id']);

            //广告联盟落单
            //$this->rpc->local('AdunionDealService\triggerAdRecord', [$upResult['user_id'], 1, 0, 0, 0.00, 0, $userInfo['invite_code'] ? $userInfo['invite_code'] : '']); //广告联盟
            $logRegLoginService = new LogRegLoginService();

            if (!empty($_REQUEST['client_id'])) {
                $GLOBALS['user_info'] = array('id' => $upResult['user_id']);
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

                $logRegLoginService->insert($data['mobile'], $upResult['user_id'], 1, 0, 1, $data['invite']);
                return;
            }

            $userInfo = array('user_name' => $userInfo['mobile'], 'password' => $userInfo['password']);
            $ret = $bo->doLogin($userInfo, '');
            if ($ret['code'] == 0) {
                $logRegLoginService->insert($data['mobile'], $upResult['user_id'], 1, $ret['code'] == 0 ? 1 : 0, 1, $data['invite']);
                $targetUrl = "/";
                if (!empty($data['type']) && $data['type'] == 'h5') {
                    $targetUrl = 'account/addbank';
                } elseif (!empty($data['type']) && $data['type'] == 'bdh5') {
                    if (isset($GLOBALS['h5Union'][$data['src']])) {
                        $h5UnionService = H5UnionService::getInstance($GLOBALS['h5Union'][$data['src']]);
                        $h5UnionService->addRedisCount();
                    }
                    $targetUrl = 'account';
                } else {
                    $targetUrl = 'account/addbank';
                }

                if (isset($data['f']) && !empty($data['f'])) {
                    $targetUrl = 'app';
                }

                if (isset($_GET['from']))
                {
                    $targetUrl .='?from=reg';
                }
                // add by wangfei5@
                $this->rpc->local('RegisterService\afterRegister', array());

                if ($this->isModal()) {
                    setcookie('modal_login_succ', 1, 0, '/', get_root_domain());
                    $this->template = null;
                    return true;
                }

                //生产用户访问日志
                UserAccessLogService::produceLog($upResult['user_id'], UserAccessLogEnum::TYPE_LOGIN, '登陆成功', $userInfo, '', DeviceEnum::DEVICE_WEB);

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
        $this->tpl->assign("errorMsg", current($this->_error));
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
                    // var_dump($_POST);
                    $this->template = $this->getShowTemplate();
                }
            }
        }
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert(trim($data['username']), '', 2, 0, 1, $data['invite']);
        return false;
    }

    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function getShowTemplate() {
        return $this->isModal() ? 'web/views/v3/user/modal_register.html' : 'web/views/user/register.html';
    }


}
