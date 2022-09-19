<?php

/**
 * 新用户注册页面
 * @author 杨庆<yangqing@ucfgroup.com>
 */

namespace web\controllers\enterprise;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use core\service\H5UnionService;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;
use core\dao\EnterpriseModel;
use core\dao\UserModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";

class DoRegister extends BaseAction {

    private $_error = null;
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
        $this->form->rules = array(
            // 企业用户登录名
            'user_name' => array(
                'filter' => 'reg',
                'message' => '请输入4-20位字母、数字、下划线、横线，首位只能为字母',
                "option" => array("regexp" => "/^([A-Za-z])[\w-]{3,19}$/", 'optional' => true)
            ),
            // 密码
            'password' => array(
                'filter' => 'length',
                'message' => '密码应为6-20位数字/字母/标点',
                "option" => array("min" => 6, "max" => 20)
            ),
            // 接受短信手机号
            'sms_phone' => array(
                'filter' => 'reg',
                "message" => "接收短信通知手机号码应为7-11为数字",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['regex']}/")
            ),
            // 接收短信号码国别码
            'sms_country_code' => array('filter' => 'string'),
            // 推荐人姓名
            'inviter_name' => array('filter' => 'string'),
            // 图形验证码
            'captcha' => array('filter' => 'required', 'message' => '图形验证码不能为空'),
            // 短信验证码
            'code' => array('filter' => 'required', 'message' => '短信验证码不能为空'),

            //注册协议
            'agreement' => array('filter' => 'string'),
            // 来源
            'from_platform' => array('filter' => 'string'),
            // ？？？
            'type' => array('filter' => 'string'),
            // ???
            'src' => array('filter' => 'string'),
            // ？？？
            'isAjax' => array('filter' => 'int'),
            // 分站id
            "site_id" => array("filter" => 'string', "option" => array("optional"=>true)),
            // ？？？
            'event_cn_hidden' => array('filter' => 'int'),
            // 用户类别
            'purpose' => array('filter' => 'int'),
        );

        if (!empty($_REQUEST['sms_country_code'])
            && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']])
            && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['is_show']) {
            $this->form->rules['sms_phone'] =  array(
                'filter' => 'reg',
                "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['regex']}/")
            );
            $this->_isHaveCountyCode = true;
        }

        if (!$this->form->validate()) {
            $this->_error = $this->form->getError();
            return $this->showRegisterError();
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER, Risk::PF_WEB,
            $this->_isH5 ? DeviceEnum::DEVICE_WAP : DeviceEnum::DEVICE_WEB)->check($data, Risk::SYNC);

        if ($ret === false) {
            $this->_error = array('captcha' => '注册异常');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        if (empty($data['captcha'])) {
            $this->_error = array('captcha' => '验证码错误');
            Monitor::add('REGISTER_FAIL');
            return $this->showRegisterError();
        }

        // 检查参数是否已经过校验
        $sessionKey = sprintf('validEnterprise_%s', trim($data['sms_phone']));
        $isValid = \es_session::get($sessionKey);
        if (empty($isValid)) {
            Monitor::add('REGISTER_FAIL');
            $this->_error = array('captcha' => '请勿提交非法数据');
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
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($data['sms_phone']));
            if ($vcode != $data['code']) {
                $this->_error = array('code' => '验证码不正确');
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
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
            $base_rule_result = login_pwd_base_rule($len, $mobile, $password);
            if ($base_rule_result){
                $this->_error = array('password' => $base_rule_result['errorMsg']);
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }

            //黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
            if ($forbid_black_result) {
                $this->_error = array('password' => $forbid_black_result['errorMsg']);
                Monitor::add('REGISTER_FAIL');
                return $this->showRegisterError();
            }
        }

        // 默认是投资户
        $purpose = UserAccountEnum::ACCOUNT_INVESTMENT;
        if (isset($data['purpose']) && array_key_exists($data['purpose'], UserAccountEnum::$accountWangxinMap)) {
            $purpose = $data['purpose'];
        }

        $bo = BOFactory::instance('web');
        $data['username'] = trim($data['user_name']);
        $data['mobile'] = trim($data['sms_phone']);
        // 企业投资户和融资户使用该注册方式
        $userInfo = array(
            'username' => $data['username'],
            'password' => $data['password'],
            'email' => $data['email'],
            'mobile' => '', // 企业用户不覆盖手机号
            'usertype' => UserModel::USER_TYPE_ENTERPRISE,  // 企业用户类型
            'user_purpose' => $purpose
        );

        if ($this->_isHaveCountyCode){
            $userInfo['country_code'] = trim($_REQUEST['country_code']);
            $userInfo['mobile_code'] = $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['country_code']]['code'];
        }

         //如果相应的分站用户注册时，在用户表里没有正确记录site_id，可以手动在请求连接中加上相对应的site_id，不是必传参数
        if ($_REQUEST['site_id'] && ($site_key=array_search($_REQUEST['site_id'], $GLOBALS['sys_config']['TEMPLATE_LIST']))) {
            $userInfo['group_id'] = $GLOBALS['sys_config']['SITE_USER_GROUP'][$site_key];
            $userInfo['site_id']=$_REQUEST['site_id'];
        }

        // 不管主站/分站，会员所属网站都读取配置[主站-平台-企业投资户]
        $enterpriseDefaultGroupId = (int)app_conf('ENTERPRISE_DEFAULT_GROUPID');
        if (!empty($enterpriseDefaultGroupId)) {
            $userInfo['group_id'] = $enterpriseDefaultGroupId;
        }

        // 如果是融资户，直接归到组1
        if ($purpose == UserAccountEnum::ACCOUNT_FINANCE) {
            $userInfo['group_id'] = 1;
        }

        $userInfo['referer'] = DeviceEnum::DEVICE_WEB; //记录来源PC端
        $upResult = $bo->insertInfo($userInfo, $this->_isH5);
        if ($upResult['status'] < 0) {
            $this->_error = $upResult['data'];
            return $this->showRegisterError();
        } else {
            RiskServiceFactory::instance(Risk::BC_REGISTER)->notify(array('userId'=>$upResult['user_id']), $this->form->data);

            /**
             * register success
             */
            // 增加日志注册成功时候uid
            $this->setLog("uid", $upResult['user_id']);

            // 企业证件号码是否长期有效
            $isPermanent = (int)$data['is_permanent'];
            if ($isPermanent === 1) {
                $credentialsExpireAt = EnterpriseModel::$credentialsExpireAtDefault;
            } else {
                $credentialsExpireAt = addslashes($data['credentials_expire_at']);
            }


            $inviter_phone = '';
            $inviter_country_code = '';
            if (!empty($userInfo['invite_code'])) {
                $coupon = $this->rpc->local('CouponService\checkCoupon', array($userInfo['invite_code']));
                if ($coupon === FALSE || $coupon['coupon_disable']) {
                    $this->_error = array('invite'=>$GLOBALS['lang']['COUPON_DISABLE']);
                    return $this->showRegisterError();
                }

                if ($coupon['refer_user_id']) {
                    $referUser = $this->rpc->local('UserService\getUserArray', array($coupon['refer_user_id'], 'mobile, real_name'));
                    if (empty($referUser) || (!empty($this->form->data['inviter_name']) && $referUser['real_name'] != $this->form->data['inviter_name'])) {
                        $this->_error = array('invite'=>'输入的邀请人姓名与邀请码不符，请核对后重新填写');
                        return $this->showRegisterError();
                    }

                    $inviter_country_code = $referUser['mobile_code'];
                    $inviter_phone = $referUser['mobile'];
                }
            }

            // 企业用户注册相关
            $enterpriseRegisterData = [
                'user_id' => intval($upResult['user_id']),
                'credentials_type' => intval($data['credentials_type']),
                'credentials_expire_date' => addslashes($data['credentials_expire_date']),
                'credentials_expire_at' => $credentialsExpireAt,
                'credentials_no' => addslashes($data['credentials_no']),
                'name' => addslashes($data['name']),
                'user_name' => addslashes($data['user_name']),
                'consignee_phone' => addslashes($data['consignee_phone']),
                'consignee_country_code' => addslashes($GLOBALS['dict']['MOBILE_CODE'][$data['consignee_country_code']]['code']),
                'sms_phone' => addslashes($data['sms_phone']),
                'sms_country_code' => addslashes($GLOBALS['dict']['MOBILE_CODE'][$data['sms_country_code']]['code']),
                'inviter_name' => addslashes($data['inviter_name']),
                'inviter_country_code' => '',
                'inviter_phone' => '',
                'create_time' => get_gmtime(),
            ];
            // 企业用户基础相关
            $enterpriseBaseData = [
                'company_name' => addslashes($data['name']),
                'company_purpose' => '1',
                'user_id' => intval($upResult['user_id']),
                'credentials_type' => intval($data['credentials_type']),
                'credentials_expire_date' => addslashes($data['credentials_expire_date']),
                'credentials_expire_at' => $credentialsExpireAt,
                'is_permanent' => $isPermanent,
                'credentials_no' => addslashes($data['credentials_no']),
                'create_time' => get_gmtime(),
            ];
            // 企业联系人相关
            // 注册未开户时 使用注册的联系方式作为企业联系人手机号
            $contactInfo = [
                'user_id' => intval($upResult['user_id']),
                'receive_msg_mobile' => $GLOBALS['dict']['MOBILE_CODE'][$data['sms_country_code']]['code'].'-'.$data['sms_phone'],
                'consignee_phone' => addslashes($data['sms_phone']),
                'consignee_phone_code' => addslashes($GLOBALS['dict']['MOBILE_CODE'][$data['sms_country_code']]['code']),
                'inviter_name' => addslashes($data['inviter_name']),
                'inviter_country_code' => '',
                'inviter_phone' => '',
            ];
            $this->rpc->local('EnterpriseService\registerSimpleData', [$enterpriseRegisterData, $enterpriseBaseData, $contactInfo]);
            // 删除参数通过校验的session
            \es_session::delete($sessionKey);

            // refer为分站h5页面，注册成功后跳转APP下载地址
            if (!empty($data['type']) && $data['type'] == 'h5' && APP_SITE != 'firstp2p') {
                $downloadURL = app_conf('APP_DOWNLOAD_H5_URL');
            }

            $logRegLoginService = new LogRegLoginService();
            //$this->rpc->local('AdunionDealService\triggerAdRecord', array($upResult['user_id'], 1)); //广告联盟
            if (!empty($_REQUEST['client_id'])) {
                $GLOBALS['user_info'] = array('id' => $upResult['user_id']);
                //h5 web站处理 直接回调到redirect_uri
                if ($_REQUEST['client_id'] == '7b9bd46617b3f47950687351') {
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

                $logRegLoginService->insert($data['username'], $upResult['user_id'], 1, 0, 1, '');
                return;
            }

            // 企业必须使用用户名登陆，不支持用手机号登陆
            $userInfo = array('user_name' => $userInfo['username'], 'password' => $userInfo['password']);
            $ret = $bo->doLogin($userInfo, '');
            if ($ret['code'] == 0) {
                $logRegLoginService->insert($data['username'], $upResult['user_id'], 1, $ret['code'] == 0 ? 1 : 0, 1, '');
                $targetUrl = "/";
                if (!empty($data['type']) && $data['type'] == 'h5') {
                    $targetUrl = 'user/registerCompany';
                } elseif (!empty($data['type']) && $data['type'] == 'bdh5') {
                    if (isset($GLOBALS['h5Union'][$data['src']])) {
                        $h5UnionService = H5UnionService::getInstance($GLOBALS['h5Union'][$data['src']]);
                        $h5UnionService->addRedisCount();
                    }
                    $targetUrl = 'account';
                } else {
                    $targetUrl = 'user/registerCompany';
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
        $this->tpl->assign("data", $_POST);
        $this->tpl->assign("from_platform", $_POST['from_platform']);
        $this->tpl->assign("isMicroMessengerUserAgent", strpos($_SERVER['HTTP_USER_AGENT'],"MicroMessenger") ? true : false);
        $this->tpl->assign("event_cn_hidden", $data['event_cn_hidden']);
        if ($_POST['isAjax'] == 1) {
            $errorMsg = $this->_error;
            if (is_array($errorMsg)) {
                $errorMsg = array_pop($errorMsg);
            }
            echo json_encode(array(
                'errorCode' => -1,
                'errorMsg' => $errorMsg,
            ));
        } else {
                $this->tpl->assign("agreement", $agreement);
                if (app_conf('TPL_REGISTER')) {
                    $this->template = app_conf('TPL_REGISTER');
                } else {
                    $this->template = $this->getShowTemplate();
                }
        }
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert(trim($data['username']), '', 2, 0, 1, '');
        return false;
    }

    private function authorize() {
        $oauth = new \PDOOAuth2();
        $params = $oauth->getAuthorizeParams();
        $oauth->finishClientAuthorization(true, $params);
    }

    public function getShowTemplate() {
        return 'web/views/v3/user/registercompany.html';
    }
}
