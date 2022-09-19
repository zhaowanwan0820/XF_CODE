<?php

class PhoneController extends CommonController
{
    private $sms_type ;
    /**
     * [actionGetSmsVcode 新版注册获取短信验证码]
     * 规则：
     * 1、每 IP 最多每天请求 100 次
     * 2、每 手机号 最多每天请求 30 次，10次后出图形验证码
     *
     * @[type] [description]
     */
    public function actionGetSmsVcode()
    {
        if (!FunctionUtil::IsMobile($_POST['phone'])) {
            return $this->echoJson([], 1000, '手机号码格式不正确');
        }
        
        if (!isset($_POST['type']) || !in_array($_POST['type'], [0, 1])) {
            return $this->echoJson([], 1000, '参数type错误');
        }

        $cc_by_ip = BlockCC::getInstance()->getNew('IpAction')->SetAndCheck(['total']);
        if (!$cc_by_ip) {
            return $this->echoJson([], 2044, '您操作过于频繁，请稍后重试');
        }

        $ip = FunctionUtil::ip_address();
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $key = md5($ip.'_'.$ua);
        $value = RedisService::getInstance()->get($key);
        if ((int) $value == 2) {
            return $this->echoJson([], 1025,'系统判定请求异常，请稍后重试');
        }

        if (isset($_SERVER['HTTP_REFERER']) && false === stripos($_SERVER['HTTP_REFERER'], '.xfuser.com/')) {
            Yii::log('Robot request:PHONE:'.$_POST['phone'].',IP:'.$ip.',REFERER:'.$_SERVER['HTTP_REFERER'].',UA:'.$ua.',KEY:'.$key, 'error', 'actionGetSmsVcode');
            RedisService::getInstance()->set($key, 2, 7 * 24 * 60 * 60);
        }

        //不同的业务员逻辑
        $type = 'wx_login';

        if ($_POST['type']==1) {
            $type = 'xf_auth_login';
            if (empty($_POST['appid'])) {
                return $this->echoJson([], 100, 'appid 不能为空');
            }
            if (!XfDebtExchangeUserAllowList::checkUserAllowByPhone($_POST['phone'], $_POST['appid'])) {
                return $this->echoJson([], 100, '您暂时未获得该商城授权登录资格');
            }
        }

        //手机号 短信验证码获取次数限制
//        $cc_by_phone = BlockCC::getInstance()->getNew('UserKey')->SetAndCheck(['get_sms_vcode:'.$type, $_POST['phone']]);
//        if (!$cc_by_phone) {
//            return $this->echoJson([], 2044, '您操作次数过多，请明日重试');
//        }

        $result = SmsIdentityUtils::SendCode($_POST['phone'], $type); //发送验证码
        if ($result['code']) {
            return  $this->echoJson([], $result['code']);
        }

        return $this->echoJson([], 0);
    }

    /**
     * 验证验证码
     * TODO 废弃
     */
    public function actionVerifySmsCode()
    {
        $returnData = [];
        $phone = trim($_POST['phone']);
        $verification_code = $_POST['verification_code'];
        if (!FunctionUtil::IsMobile($phone)) {
            $this->echoJson($returnData, 1000);
        }
        if (empty($verification_code)) {
            $this->echoJson($returnData, 1002);
        }
        $verify_result = SmsIdentityUtils::ValidateCode($phone, $verification_code, '');
        if ($verify_result['code']) {
            $this->echoJson($returnData, $verify_result['code']);
        }
        $this->echoJson($returnData, 0);
    }

    public function actionLoginSmsCodeApi()
    {
        if (empty($_POST['token']) || $_POST['token'] !== '848FAC197B2594FB407BE88673EB1E8D') {
            $this->echoJson([], 100, '未知错误');
        }
        if (empty($_POST['mobile'])) {
            $this->echoJson([], 100, '手机号码不存在');
        }
        if (!XfDebtExchangeUserAllowList::checkUserAllowByPhone($_POST['mobile'], $_POST['appid'])) {
            $this->echoJson([], 100, '您暂时未获得该商城登录资格');
        }
        if (empty($_POST['code'])) {
            $this->echoJson([], 100, '验证码不存在');
        }
        $remind['phone'] = $_POST['mobile'];
        $remind['data']['vcode'] = $_POST['code'];
        $remind['code'] ='wx_login';
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];
        if(in_array($_POST['mobile'], $xf_test_number)){
        //if ($_POST['mobile']=='13716970622') {
            $send_SMS['code']=0;
        } else {
            $send_SMS = (new XfSmsClass())->sendToUserByPhone($remind);
        }
        if ($send_SMS['code']) {
            $this->echoJson([], 100, '发送失败');
        }
        $this->echoJson([], 0, '发送成功');
    }
}
