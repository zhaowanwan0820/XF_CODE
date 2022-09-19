<?php
class PhoneController extends CommonController {

	/**
	 * [actionIsCertified 验证手机号是否可以注册]
	 * cc 规则：每 IP 每分钟 10 次，每天 100 次
	 * @param  [type] $phone [description]
	 * @param  string $type  [description]
	 * @[type]        [description]
	 */
	public function actionIsCertified($phone) {
		$cc_by_ip = BlockCC::getInstance()->getNew('IpAction')->Check(['total']);
		$cc_by_ip_set = BlockCC::getInstance()->getNew('IpAction')->Set(['total']);
		if (!$cc_by_ip) {
			$audit_logs['parameters']['info'] = '您操作过于频繁，请稍后重试';
			AuditLog::getInstance()->method('add', $audit_logs);
			$this->echoJson( array() ,2044 ,"您操作过于频繁，请稍后重试" );
		}
		$trimPhone = trim($phone);
		if ($trimPhone == "") {
			$this->echoJson([], 2006, "手机号不能为空");
		}
		$result = UserService::getInstance()->PhoneCheck($trimPhone);
        if ($result) {
            $user = User::model()->findByAttributes(['phone' => $trimPhone, 'phone_status' => 1]);
            $position = strpos($user->user_src, 'isee_');
            if ($position === 0) {
                if ($this->deviceType == 'phone') { // 为了兼容前端写死不需要改动只能修改后端
                    $this->echoJson([], 2019, "您已在安见资本注册，可直接登录爱投资<a href=\"/newuser/index/loginWap\">登录</a>");
                } else {
                    $this->echoJson([], 2019, "您已在安见资本注册，可直接登录爱投资<a href=\"/login\">登录</a>");
                }
            } else {
                $this->echoJson([], 2018, "该手机号已被注册，请重新输入或用此手机号<a href=\"/login\">登录</a>");
            }
        }
		$this->echoJson([], 0, "");
	}

	/**
	 * [actionGetSmsVcode 新版注册获取短信验证码]
	 * 规则：
	 * 1、每 IP 最多每天请求 100 次
	 * 2、每 手机号 最多每天请求 30 次，10次后出图形验证码
	 * @[type] [description]
	 */
	public function actionGetSmsVcode() {
		if (!FunctionUtil::IsMobile($_POST['phone'])) {
			$this->echoJson([], 1000);
		}
		$ip = FunctionUtil::ip_address();
		$ua = $_SERVER['HTTP_USER_AGENT'];
		$key = md5($ip . '_' . $ua);
		$value = RedisService::getInstance()->get($key);
        if ((int)$value > 0) {
            //$this->echoJson([],1025);
        }
        //$this->echoJson([],5000);


        if (isset($_SERVER['HTTP_REFERER']) && stripos($_SERVER['HTTP_REFERER'], '.zichanhuayuan.com/') === false) {
			Yii::log('Robot request:PHONE:'.$_POST['phone'].',IP:'.$ip.',REFERER:'.$_SERVER['HTTP_REFERER'].',UA:'.$ua.',KEY:'.$key, 'error', 'actionGetSmsVcode');
			RedisService::getInstance()->set($key, 1, 7 * 24 * 60 * 60);
        }

		$result = SmsIdentityUtils::SendCode($_POST['phone'], false);//发送验证码
		if ($result['code']) {
            $this->echoJson([],$result['code']);
        }
		$_SESSION['reg_phone'] = $_POST['phone'];
		$this->echoJson([] ,0);
	}

    /**
     * 验证验证码
     */
    public function actionVerifySmsCode(){
        $returnData = [];
        $phone             = trim($_POST['phone']);
        $verification_code = $_POST['verification_code'];
        if(!FunctionUtil::IsMobile($phone)){
            $this->echoJson($returnData, 1000);
        }
        if(empty($verification_code)){
            $this->echoJson($returnData, 1002);
        }
        $verify_result = SmsIdentityUtils::ValidateCode($phone, $verification_code);
        if ($verify_result['code']) {
            $this->echoJson($returnData, $verify_result['code']);
        }
        $this->echoJson($returnData, 0);
    }

}
