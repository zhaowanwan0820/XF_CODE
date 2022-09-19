<?php
class XFUserController extends XianFengExtendsController
{
    /**
     * 首页
     */
    public function actionindex()
    {
        echo '欢迎使用<br>';
    }

    protected $send_SMS_limit_by_IP = 200; // 单个IP地址发送短信限制数（次）
    protected $send_SMS_limit_by_IP_reset = 86400; // 单个IP地址发送短信限制数重置时间（秒）
    protected $send_SMS_limit_by_number = 20; // 单个手机号码发送短信限制数（次）
    protected $send_SMS_limit_by_number_reset = 86400; // 单个手机号码发送短信限制数重置时间（秒）
    protected $send_SMS_limit_by_CD = 60; // 单个手机号码发送短信冷却时间（秒）
    protected $add_feedback_limit_by_IP = 100; // 单个IP地址提交意见反馈限制数（次）
    protected $add_feedback_limit_by_IP_reset = 86400; // 单个IP地址提交意见反馈制数重置时间（秒）
    protected $add_feedback_limit_by_ID = 5; // 单个用户提交意见反馈短时间限制数（次）
    protected $add_feedback_limit_by_ID_reset = 60; // 单个用户提交意见反馈短时间限制数重置时间（秒）
    protected $add_feedback_limit_by_ID_total = 20; // 单个用户提交意见反馈长时间限制数（次）
    protected $add_feedback_limit_by_ID_total_reset = 86400; // 单个用户提交意见反馈长时间限制数重置时间（秒）
    protected $login_limit_by_number = 3; // 单个用户登录限制数（次）
    protected $login_limit_by_number_reset = 60; // 单个用户登录限制数重置时间（秒）
    protected $login_limit_by_number_CD = 60; // 单个用户登录冷却时间（秒）
    protected $check_password_limit_by_ID = 6; // 单个用户校验交易密码错误限制数（次）
    protected $check_password_limit_by_ID_reset = 86400; // 单个用户校验交易密码错误限制数重置时间（秒）

    /**
     * 校验交易密码
     * @param   old_password    用户原有的交易密码
     * @param   new_password    用户输入的交易密码
     * @return  bool
     */
    protected function checkPassWord($old_password , $new_password)
    {
        $strlen = strlen($old_password);
        if ($strlen == 24) {
            if ($old_password != GibberishAESUtil::enc($new_password, Yii::app()->c->idno_key)) {
                return false;
            } else {
                return true;
            }
        } else if ($strlen == 32) {
            if ($old_password != md5($new_password)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 设置、校验交易密码所需的解密方法
     * @param   string
     * @return  array
     */
    protected function Token2ArrayDecode($token)
    {
        $Array2TokenKey = ConfUtil::get('Array2TokenKey');
        if (!$Array2TokenKey) {
            return false;
        }
        $token  = str_replace('_' , '+' , $token);
        $token  = str_replace('.' , '/' , $token);
        $token  = openssl_decrypt($token , 'AES-128-CBC' , $Array2TokenKey);
        $string = base64_decode($token);
        $data   = json_decode($string , true);
        if (!$token || !$string || !$data) {
            return false;
        }
        if (empty($data['uid']) || empty($data['redirect_url']) || empty($data['exp']) || empty($data['sign'])) {
            return false;
        }
        $temp = $data;
        unset($temp['sign']);
        $sign = md5('%*'.md5('#)'.md5('!+'.json_encode($temp).'_@').'($').'&^');
        if ($sign != $data['sign']) {
            return false;
        }
        return $data;
    }

    /**
     * 获取登录短信验证码
     */
    public function actionGetSMSFromLogin()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 测试用手机号
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];
        // 校验IP
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $time      = time();
        $redis_key = "send_SMS_limit_by_IP_{$ip}";
        $check_IP  = $redis->get($redis_key);
        if ($check_IP > $this->send_SMS_limit_by_IP) {
            $this->echoJson(array() , 1000 , $XF_error_code_info[1000]);
        }
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array() , 1001 , $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/' , $number);
        if ($check_number === 0) {
            $this->echoJson(array() , 1002 , $XF_error_code_info[1002]);
        }
        $redis_key    = "send_SMS_limit_by_number_{$number}";
        $check_number = $redis->get($redis_key);
        if ($check_number > $this->send_SMS_limit_by_number) {
            $this->echoJson(array() , 1003 , $XF_error_code_info[1003]);
        }
        // 校验冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD) , 1004 , $XF_error_code_info[1004]);
        }
        // 校验用户
        $redis_key  = "XF_is_not_online_{$number}";
        $check_user = $redis->get($redis_key);
        if ($check_user) {
            $this->echoJson(array() , 1015 , $XF_error_code_info[1015]);
        }
        $mobile    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1014 , $XF_error_code_info[1014]);
        }
        if ($user_info['is_online'] != 1) {
            $set_user = $redis->set($redis_key , $number);
            $this->echoJson(array() , 1015 , $XF_error_code_info[1015]);
        }
        // 保存验证码
        $redis_key   = "XF_user_mobile_login_SMS_{$number}";
        if (in_array($number , $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis   = $redis->set($redis_key , $redis_value , $redis_time);
        if (!$set_redis) {
            $this->echoJson(array() , 1005 , $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number , $xf_test_number)) {
            $remind['phone']         = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code']          = "wx_login";
            $send     = new XfSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 100) {
                $this->echoJson(array() , 1006 , $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        $redis_key = "send_SMS_limit_by_IP_{$ip}";
        $check_IP  = $redis->exists($redis_key);
        if ($check_IP) {
            $set_IP = $redis->incr($redis_key);
        } else {
            $set_IP = $redis->incr($redis_key);
            $set_IP = $redis->expireAt($redis_key , ($time + $this->send_SMS_limit_by_IP_reset));
        }
        if (!$set_IP) {
            $this->echoJson(array() , 1007 , $XF_error_code_info[1007]);
        }
        // 增加手机号计数
        $redis_key    = "send_SMS_limit_by_number_{$number}";
        $check_number = $redis->exists($redis_key);
        if ($check_number) {
            $set_number = $redis->incr($redis_key);
        } else {
            $set_number = $redis->incr($redis_key);
            $set_number = $redis->expireAt($redis_key , ($time + $this->send_SMS_limit_by_number_reset));
        }
        if (!$set_number) {
            $this->echoJson(array() , 1008 , $XF_error_code_info[1008]);
        }
        // 增加冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $set_CD    = $redis->set($redis_key , $redis_value , $this->send_SMS_limit_by_CD);
        if (!$set_CD) {
            $this->echoJson(array() , 1009 , $XF_error_code_info[1009]);
        }
        $this->echoJson(array('ttl' => 60) , 0 , $XF_error_code_info[0]);
    }

    /**
     * 登录
     */
    public function actionLogin()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $time = time();
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array() , 1001 , $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/' , $number);
        if ($check_number === 0) {
            $this->echoJson(array() , 1002 , $XF_error_code_info[1002]);
        }
        // 校验验证码
        if (empty($_POST['code']) || !is_numeric($_POST['code'])) {
            $this->echoJson(array() , 1010 , $XF_error_code_info[1010]);
        }
        $code       = trim($_POST['code']);
        $check_code = preg_match('/^\d{6}$/' , $code);
        if ($check_code === 0) {
            $this->echoJson(array() , 1011 , $XF_error_code_info[1011]);
        }
        $redis     = Yii::app()->rcache;
        $redis_key = "XF_user_mobile_login_SMS_{$number}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array() , 1012 , $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array() , 1013 , $XF_error_code_info[1013]);
        }
        // 校验用户
        $redis_key  = "XF_is_not_online_{$number}";
        $check_user = $redis->get($redis_key);
        if ($check_user) {
            $this->echoJson(array() , 1015 , $XF_error_code_info[1015]);
        }
        // 校验登录冷却
        $redis_key = "login_limit_by_number_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD) , 1056 , $XF_error_code_info[1056]);
        }
        $mobile    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT id AS user_id , is_online FROM firstp2p_user WHERE mobile = '{$mobile}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1014 , $XF_error_code_info[1014]);
        }
        if ($user_info['is_online'] != 1) {
            $this->echoJson(array() , 1015 , $XF_error_code_info[1015]);
        }
        // 增加登录计数
        $redis_key    = "login_limit_by_number_{$number}";
        $check_number = $redis->exists($redis_key);
        if ($check_number) {
            $set_number = $redis->incr($redis_key);
        } else {
            $set_number = $redis->incr($redis_key);
            $set_time   = $redis->expireAt($redis_key , ($time + $this->login_limit_by_number_reset));
        }
        if (!$set_number) {
            $this->echoJson(array() , 1057 , $XF_error_code_info[1057]);
        }
        if ($set_number >= $this->login_limit_by_number) {
            $redis_key = "login_limit_by_number_CD_{$number}";
            $set_CD    = $redis->set($redis_key , $set_number , $this->login_limit_by_number_CD);
            if (!$set_CD) {
                $this->echoJson(array() , 1058 , $XF_error_code_info[1058]);
            }
        }
        $token = JwtClass::getToken($user_info);
        $this->echoJson(array('token' => $token) , 0 , $XF_error_code_info[0]);
    }

    /**
     * 个人基础信息
     */
    public function actionUserInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        if (!in_array($_POST['type'] , array(0 , 1 , 2))) {
            $this->echoJson(array() , 1017 , $XF_error_code_info[1017]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }

        $result_data['head_portrait'] = '';
        $result_data['real_name']     = $user_info['real_name'];
        $result_data['mobile']        = GibberishAESUtil::dec($user_info['mobile'] , Yii::app()->c->idno_key);
        $result_data['idno']          = GibberishAESUtil::dec($user_info['idno'] , Yii::app()->c->idno_key);
        $sql  = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$user_info['id']} AND verify_status = 1";
        $card = Yii::app()->db->createCommand($sql)->queryRow();
        if (!empty($card['bankcard'])) {
            $result_data['bankcard'] = GibberishAESUtil::dec($card['bankcard'] , Yii::app()->c->idno_key);
        } else {
            $result_data['bankcard'] = '';
        }
        $sql = "SELECT SUM(wait_capital) AS wait_capital , SUM(wait_interest) AS wait_interest FROM firstp2p_deal_load WHERE user_id = '{$user_id}' AND status = 1 ";
        if ($_POST['type'] == 0) {
            $result_a = Yii::app()->db->createCommand($sql)->queryRow();
            $result_b = Yii::app()->phdb->createCommand($sql)->queryRow();
            $result_data['wait_capital']  = $result_a['wait_capital'] + $result_b['wait_capital'];
            $result_data['wait_interest'] = $result_a['wait_interest'] + $result_b['wait_interest'];
            $result_data['money']         = $user_info['money'] + $user_info['ph_money'];
            $result_data['lock_money']    = $user_info['lock_money'] + $user_info['ph_lock_money'];
        } else if ($_POST['type'] == 1) {
            $result = Yii::app()->db->createCommand($sql)->queryRow();
            $result_data['wait_capital']  = $result['wait_capital'];
            $result_data['wait_interest'] = $result['wait_interest'];
            $result_data['money']         = $user_info['money'];
            $result_data['lock_money']    = $user_info['lock_money'];
        } else if ($_POST['type'] == 2) {
            $result = Yii::app()->phdb->createCommand($sql)->queryRow();
            $result_data['wait_capital']  = $result['wait_capital'];
            $result_data['wait_interest'] = $result['wait_interest'];
            $result_data['money']         = $user_info['ph_money'];
            $result_data['lock_money']    = $user_info['ph_lock_money'];
        }
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = '{$user_id}' AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->db->createCommand($sql)->queryRow();
        if ($contract) {
            $result_data['sign_agreement'] = 1;
        } else {
            $result_data['sign_agreement'] = 2;
        }
        if (!empty($user_info['transaction_password'])) {
            $result_data['set_password'] = 1;
        } else {
            $result_data['set_password'] = 2;
        }
        $this->echoJson($result_data , 0 , $XF_error_code_info[0]);
    }

    /**
     * 设置交易密码
     */
    public function actionSetPassWord()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 外部设置
        if (!empty($_POST['token'])) {
            $data = $this->Token2ArrayDecode($_POST['token']);
            if (!$data) {
                $this->echoJson(array() , 1023 , $XF_error_code_info[1023]);
            }
            if ($data['exp'] < time()) {
                $this->echoJson(array() , 1024 , $XF_error_code_info[1024]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
            }
            if (empty($_POST['new_password'])) {
                $this->echoJson(array() , 1019 , $XF_error_code_info[1019]);
            }
            $new_password = trim($_POST['new_password']);
            $check_password = preg_match('/^\d{6}$/', $new_password);
            if (!$check_password) {
                $this->echoJson(array() , 1020 , $XF_error_code_info[1020]);
            }
            if (!empty($user_info['transaction_password'])) {
                $this->echoJson(array() , 1021 , $XF_error_code_info[1021]);
            }
            $new_password = md5($new_password);
            $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
            $res_a = Yii::app()->db->createCommand($sql)->execute();
            $res_b = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res_a && $res_b) {
                $this->echoJson(array('url'=>$data['redirect_url']) , 0 , $XF_error_code_info[0]);
            } else {
                $this->echoJson(array() , 1022 , $XF_error_code_info[1022]);
            }
        }

        // 内部设置
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        if (empty($_POST['new_password'])) {
            $this->echoJson(array() , 1019 , $XF_error_code_info[1019]);
        }
        $new_password = trim($_POST['new_password']);
        $check_password = preg_match('/^\d{6}$/', $new_password);
        if (!$check_password) {
            $this->echoJson(array() , 1020 , $XF_error_code_info[1020]);
        }
        if (!empty($user_info['transaction_password'])) {
            $this->echoJson(array() , 1021 , $XF_error_code_info[1021]);
        }
        $new_password = md5($new_password);
        $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
        $res_a = Yii::app()->db->createCommand($sql)->execute();
        $res_b = Yii::app()->phdb->createCommand($sql)->execute();
        if ($res_a && $res_b) {
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        } else {
            $this->echoJson(array() , 1022 , $XF_error_code_info[1022]);
        }
    }

    /**
     * 外部设置交易密码页面
     */
    public function actionSetPassWordPage()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        if (!empty($_POST['token'])) {
            $data = $this->Token2ArrayDecode($_POST['token']);
            if (!$data) {
                $this->echoJson(array() , 1023 , $XF_error_code_info[1023]);
            }
            if ($data['exp'] < time()) {
                $this->echoJson(array() , 1024 , $XF_error_code_info[1024]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
            }
            if (!empty($user_info['transaction_password'])) {
                $this->echoJson(array() , 1021 , $XF_error_code_info[1021]);
            }
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        }
    }

    /**
     * 外部校验交易密码页面
     */
    public function actionCheckPassWordPage()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        if (!empty($_POST['token'])) {
            $data = $this->Token2ArrayDecode($_POST['token']);
            if (!$data) {
                $this->echoJson(array() , 1023 , $XF_error_code_info[1023]);
            }
            if ($data['exp'] < time()) {
                $this->echoJson(array() , 1024 , $XF_error_code_info[1024]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
            }
            if (empty($user_info['transaction_password'])) {
                $this->echoJson(array() , 1021 , $XF_error_code_info[1021]);
            }
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        }
    }

    /**
     * 外部校验交易密码
     */
    public function actionCheckPassWord()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        if (!empty($_POST['token'])) {
            $data = $this->Token2ArrayDecode($_POST['token']);
            if (!$data) {
                $this->echoJson(array() , 1023 , $XF_error_code_info[1023]);
            }
            $time = time();
            if ($data['exp'] < $time) {
                $this->echoJson(array() , 1024 , $XF_error_code_info[1024]);
            }
            $redis     = Yii::app()->rcache;
            $redis_key = "check_password_limit_by_ID_{$data['uid']}";
            $check_ID  = $redis->get($redis_key);
            if ($check_ID >= $this->check_password_limit_by_ID) {
                $this->echoJson(array() , 1059 , $XF_error_code_info[1059]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
            }
            if (empty($_POST['password'])) {
                $this->echoJson(array() , 1019 , $XF_error_code_info[1019]);
            }
            $password = trim($_POST['password']);
            $check_password = preg_match('/^\d{6}$/', $password);
            if (!$check_password) {
                $this->echoJson(array() , 1020 , $XF_error_code_info[1020]);
            }
            if (empty($user_info['transaction_password'])) {
                $this->echoJson(array() , 1027 , $XF_error_code_info[1027]);
            }
            $checkPassWord = $this->checkPassWord($user_info['transaction_password'] , $password);
            if (!$checkPassWord) {
                $check_ID  = $redis->exists($redis_key);
                if ($check_ID) {
                    $set_IP = $redis->incr($redis_key);
                } else {
                    $set_IP = $redis->incr($redis_key);
                    $set_IP = $redis->expireAt($redis_key , ($time + $this->check_password_limit_by_ID_reset));
                }
                if (!$set_IP) {
                    $this->echoJson(array() , 1060 , $XF_error_code_info[1060]);
                }
                $this->echoJson(array() , 1025 , $XF_error_code_info[1025]);
            }

            //兑换预处理验密
            if(!empty($data['order_id'])){
                $ret = $this->editDebtOrder($data['order_id']);
                if($ret == false){
                    $this->echoJson(array() , 1000 , $XF_error_code_info[1000]);
                }
            }

            $this->echoJson(array('url'=>$data['redirect_url']) , 0 , $XF_error_code_info[0]);
        }
    }


    /**
     * 更新订单临时表状态为待处理
     * @param $order_id
     * @return bool
     */
    private  function  editDebtOrder($order_id){
        $edit_sql = "update firstp2p_debt_exchange_log set status=1 where orderNumber='$order_id' and status=0";
        //尊享订单信息
        $zx_log = DebtExchangeLog::model()->find("order_id='$order_id' and status=0");
        if($zx_log){
            $zx_ret = Yii::app()->db->createCommand($edit_sql)->execute();
            if(!$zx_ret){
                Yii::log("editDebtOrder return false, zx_edit_sql:$edit_sql", 'error');
                return false;
            }
        }

        //普惠订单信息
        $ph_log = PHDebtExchangeLog::model()->find("order_id='$order_id' and status=0 ");
        if($ph_log){
            $ph_ret = Yii::app()->phdb->createCommand($edit_sql)->execute();
            if(!$ph_ret){
                Yii::log("editDebtOrder return false, ph_edit_sql:$edit_sql", 'error');
                return false;
            }
        }

        return true;
    }


    /**
     * 修改交易密码
     */
    public function actionEditPassWord()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        if (empty($_POST['old_password'])) {
            $this->echoJson(array() , 1026 , $XF_error_code_info[1026]);
        }
        $old_password = trim($_POST['old_password']);
        $check_password = preg_match('/^\d{6}$/', $old_password);
        if (!$check_password) {
            $this->echoJson(array() , 1020 , $XF_error_code_info[1020]);
        }
        if (empty($_POST['new_password'])) {
            $this->echoJson(array() , 1019 , $XF_error_code_info[1019]);
        }
        $new_password = trim($_POST['new_password']);
        $check_password = preg_match('/^\d{6}$/', $new_password);
        if (!$check_password) {
            $this->echoJson(array() , 1020 , $XF_error_code_info[1020]);
        }
        if (empty($user_info['transaction_password'])) {
            $this->echoJson(array() , 1027 , $XF_error_code_info[1027]);
        }
        $checkPassWord = $this->checkPassWord($user_info['transaction_password'] , $old_password);
        if (!$checkPassWord) {
            $this->echoJson(array() , 1028 , $XF_error_code_info[1028]);
        }
        if ($old_password == $new_password) {
            $this->echoJson(array() , 1029 , $XF_error_code_info[1029]);
        }
        $new_password = md5($new_password);
        $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
        $res_a = Yii::app()->db->createCommand($sql)->execute();
        $res_b = Yii::app()->phdb->createCommand($sql)->execute();
        if ($res_a && $res_b) {
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        } else {
            $this->echoJson(array() , 1030 , $XF_error_code_info[1030]);
        }
    }

    /**
     * 获取交易密码重置短信验证码
     */
    public function actionGetSMSFromResetPassword()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 测试用手机号
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        // 校验IP
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $time      = time();
        $redis_key = "send_SMS_limit_by_IP_{$ip}";
        $check_IP  = $redis->get($redis_key);
        if ($check_IP > $this->send_SMS_limit_by_IP) {
            $this->echoJson(array() , 1000 , $XF_error_code_info[1000]);
        }
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array() , 1001 , $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/' , $number);
        if ($check_number === 0) {
            $this->echoJson(array() , 1002 , $XF_error_code_info[1002]);
        }
        $redis_key    = "send_SMS_limit_by_number_{$number}";
        $check_number = $redis->get($redis_key);
        if ($check_number > $this->send_SMS_limit_by_number) {
            $this->echoJson(array() , 1003 , $XF_error_code_info[1003]);
        }
        // 校验冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD) , 1004 , $XF_error_code_info[1004]);
        }
        // 校验用户手机号
        $mobile = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        if ($user_info['mobile'] != $mobile) {
            $this->echoJson(array() , 1055 , $XF_error_code_info[1055]);
        }
        if ($user_info['is_online'] != 1) {
            $this->echoJson(array() , 1015 , $XF_error_code_info[1015]);
        }
        // 保存验证码
        $redis_key   = "XF_user_reset_password_SMS_{$number}";
        if (in_array($number , $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis   = $redis->set($redis_key , $redis_value , $redis_time);
        if (!$set_redis) {
            $this->echoJson(array() , 1005 , $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number , $xf_test_number)) {
            $remind['phone']         = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code']          = "wx_login";
            $send     = new XfSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 100) {
                $this->echoJson(array() , 1006 , $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        $redis_key = "send_SMS_limit_by_IP_{$ip}";
        $check_IP  = $redis->exists($redis_key);
        if ($check_IP) {
            $set_IP = $redis->incr($redis_key);
        } else {
            $set_IP = $redis->incr($redis_key);
            $set_IP = $redis->expireAt($redis_key , ($time + $this->send_SMS_limit_by_IP_reset));
        }
        if (!$set_IP) {
            $this->echoJson(array() , 1007 , $XF_error_code_info[1007]);
        }
        // 增加手机号计数
        $redis_key    = "send_SMS_limit_by_number_{$number}";
        $check_number = $redis->exists($redis_key);
        if ($check_number) {
            $set_number = $redis->incr($redis_key);
        } else {
            $set_number = $redis->incr($redis_key);
            $set_number = $redis->expireAt($redis_key , ($time + $this->send_SMS_limit_by_number_reset));
        }
        if (!$set_number) {
            $this->echoJson(array() , 1008 , $XF_error_code_info[1008]);
        }
        // 增加冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $set_CD    = $redis->set($redis_key , $redis_value , $this->send_SMS_limit_by_CD);
        if (!$set_CD) {
            $this->echoJson(array() , 1009 , $XF_error_code_info[1009]);
        }
        $this->echoJson(array('ttl' => 60) , 0 , $XF_error_code_info[0]);
    }

    /**
     * 交易密码重置
     */
    public function actionResetPassword()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        // 校验交易密码
        if (empty($_POST['new_password'])) {
            $this->echoJson(array() , 1019 , $XF_error_code_info[1019]);
        }
        $new_password = trim($_POST['new_password']);
        $check_password = preg_match('/^\d{6}$/', $new_password);
        if (!$check_password) {
            $this->echoJson(array() , 1020 , $XF_error_code_info[1020]);
        }
        if (empty($user_info['transaction_password'])) {
            $this->echoJson(array() , 1027 , $XF_error_code_info[1027]);
        }
        // 校验验证码
        if (empty($_POST['code']) || !is_numeric($_POST['code'])) {
            $this->echoJson(array() , 1010 , $XF_error_code_info[1010]);
        }
        $code       = trim($_POST['code']);
        $check_code = preg_match('/^\d{6}$/' , $code);
        if ($check_code === 0) {
            $this->echoJson(array() , 1011 , $XF_error_code_info[1011]);
        }
        $number    = GibberishAESUtil::dec($user_info['mobile'] , Yii::app()->c->idno_key);
        $redis     = Yii::app()->rcache;
        $redis_key = "XF_user_reset_password_SMS_{$number}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array() , 1012 , $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array() , 1013 , $XF_error_code_info[1013]);
        }
        $new_password = md5($new_password);
        if ($user_info['transaction_password'] == $new_password) {
            $this->echoJson(array() , 1029 , $XF_error_code_info[1029]);
        }
        $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
        $res_a = Yii::app()->db->createCommand($sql)->execute();
        $res_b = Yii::app()->phdb->createCommand($sql)->execute();
        if ($res_a && $res_b) {
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        } else {
            $this->echoJson(array() , 1031 , $XF_error_code_info[1031]);
        }
        $this->echoJson(array() , 0 , $XF_error_code_info[0]);
    }

    /**
     * 公告列表
     */
    public function actionNoticeList()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $model = Yii::app()->db;
        $time  = time();
        // 条件筛选
        $where = " WHERE start_time <= {$time} AND status = 1 ";
        // 校验每页数据显示量
        if (!empty($_POST['limit'])) {
            $limit = intval($_POST['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if ($limit > 50) {
            $this->echoJson(array() , 1032 , $XF_error_code_info[1032]);
        }
        // 校验当前页数
        if (!empty($_POST['page'])) {
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        $sql   = "SELECT count(id) AS count FROM xf_notice {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        }
        // 查询数据
        $sql = "SELECT * FROM xf_notice {$where} ORDER BY start_time DESC ";
        $page_count = ceil($count / $limit);
        if ($page > $page_count) {
            $page = $page_count;
        }
        if ($page < 1) {
            $page = 1;
        }
        $pass = ($page - 1) * $limit;
        $sql .= " LIMIT {$pass} , {$limit} ";
        $list = $model->createCommand($sql)->queryAll();
        $listInfo = array();
        foreach ($list as $key => $value) {
            $temp               = array();
            $temp['id']         = $value['id'];
            $temp['title']      = $value['title'];
            $temp['abstract']   = $value['abstract'];
            $temp['start_time'] = $value['start_time'];

            $listInfo[] = $temp;
        }

        header ( "Content-type:application/json; charset=utf-8" );
        $result_data['data']       = $listInfo;
        $result_data['count']      = $count;
        $result_data['page_count'] = $page_count;
        $result_data['code']       = 0;
        $result_data['info']       = $XF_error_code_info[0];
        echo exit(json_encode($result_data));
    }

    /**
     * 公告详情
     */
    public function actionNoticeInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        if (empty($_POST['notice_id']) || !is_numeric($_POST['notice_id'])) {
            $this->echoJson(array() , 1034 , $XF_error_code_info[1034]);
        }
        $model = Yii::app()->db;
        $time  = time();
        $sql   = "SELECT * FROM xf_notice WHERE id = '{$_POST['notice_id']}' ";
        $res   = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array() , 1035 , $XF_error_code_info[1035]);
        }
        if ($res['status'] != 1) {
            $this->echoJson(array() , 1036 , $XF_error_code_info[1036]);
        }
        if ($res['start_time'] > $time) {
            $this->echoJson(array() , 1037 , $XF_error_code_info[1037]);
        }
        $sql    = "UPDATE xf_notice SET pageview = (pageview + 1) WHERE id = {$res['id']}";
        $update = $model->createCommand($sql)->execute();
        $result_data['id']         = $res['id'];
        $result_data['title']      = $res['title'];
        $result_data['abstract']   = $res['abstract'];
        $result_data['content']    = $res['content'];
        $result_data['start_time'] = $res['start_time'];
        $this->echoJson($result_data , 0 , $XF_error_code_info[0]);
    }

    /**
     * 新消息&新意见反馈回复状态
     */
    public function actionNewMessageFeedback()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        $time = time();
        $sql  = "SELECT * FROM xf_message AS m INNER JOIN xf_message_detail AS d ON m.id = d.message_id WHERE m.user_scope = 2 AND d.user_id = {$user_info['id']} AND d.status = 2 AND m.status = 1 AND m.start_time <= {$time} ";
        $message = Yii::app()->db->createCommand($sql)->queryRow();
        $message_a = false;
        $sql = "SELECT id FROM xf_message WHERE user_scope = 1 AND status = 1 AND start_time <= {$time} ";
        $message_id = Yii::app()->db->createCommand($sql)->queryColumn();
        if ($message_id) {
            $message_id_str = implode(',' , $message_id);
            $sql = "SELECT message_id FROM xf_message_detail WHERE message_id IN ({$message_id_str}) AND user_id = {$user_info['id']}";
            $detail_res = Yii::app()->db->createCommand($sql)->queryColumn();
            if ($detail_res) {
                foreach ($message_id as $key => $value) {
                    if (!in_array($value , $detail_res)) {
                        $message_a = true;
                    }
                }
            } else {
                $message_a = true;
            }
        }
        $sql = "SELECT * FROM xf_feedback WHERE user_id = {$user_info['id']} AND status IN (2,3) AND re_status = 2";
        $feedback = Yii::app()->db->createCommand($sql)->queryRow();
        if ($message || $message_a) {
            $result_data['new_message'] = 1;
        } else {
            $result_data['new_message'] = 2;
        }
        if ($feedback) {
            $result_data['new_feedback'] = 1;
        } else {
            $result_data['new_feedback'] = 2;
        }
        $this->echoJson($result_data , 0 , $XF_error_code_info[0]);
    }

    /**
     * 消息列表
     */
    public function actionMessageList()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        $model = Yii::app()->db;
        $time  = time();
        // 条件筛选
        $where = " WHERE m.start_time <= {$time} AND m.status = 1 AND ((m.user_scope = 1 AND (d.user_id = 0 OR d.user_id = {$user_info['id']})) OR (m.user_scope = 2 AND d.user_id = {$user_info['id']})) ";
        // 校验每页数据显示量
        if (!empty($_POST['limit'])) {
            $limit = intval($_POST['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if ($limit > 50) {
            $this->echoJson(array() , 1032 , $XF_error_code_info[1032]);
        }
        // 校验当前页数
        if (!empty($_POST['page'])) {
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        $sql   = "SELECT count(DISTINCT m.id) AS count FROM xf_message AS m LEFT JOIN xf_message_detail AS d ON m.id = d.message_id {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        }
        // 查询数据
        $sql = "SELECT m.* , MAX(d.status) AS detail_status FROM xf_message AS m LEFT JOIN xf_message_detail AS d ON m.id = d.message_id {$where} GROUP BY m.id ORDER BY m.start_time DESC ";
        $page_count = ceil($count / $limit);
        if ($page > $page_count) {
            $page = $page_count;
        }
        if ($page < 1) {
            $page = 1;
        }
        $pass = ($page - 1) * $limit;
        $sql .= " LIMIT {$pass} , {$limit} ";
        $list = $model->createCommand($sql)->queryAll();
        $listInfo = array();
        foreach ($list as $key => $value) {
            $temp               = array();
            $temp['id']         = $value['id'];
            $temp['title']      = $value['title'];
            $temp['abstract']   = $value['abstract'];
            $temp['start_time'] = $value['start_time'];
            if ($value['user_scope'] == 1) {
                if ($value['detail_status'] == 1) {
                    $temp['status'] = '1';
                } else {
                    $temp['status'] = '2';
                }
            } else if ($value['user_scope'] == 2) {
                $temp['status'] = $value['detail_status'];
            }

            $listInfo[] = $temp;
        }

        header ( "Content-type:application/json; charset=utf-8" );
        $result_data['data']       = $listInfo;
        $result_data['count']      = $count;
        $result_data['page_count'] = $page_count;
        $result_data['code']       = 0;
        $result_data['info']       = $XF_error_code_info[0];
        echo exit(json_encode($result_data));
    }

    /**
     * 消息详情
     */
    public function actionMessageInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        if (empty($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
            $this->echoJson(array() , 1039 , $XF_error_code_info[1039]);
        }
        $model = Yii::app()->db;
        $time  = time();
        $sql   = "SELECT * FROM xf_message WHERE id = '{$_POST['message_id']}' ";
        $res   = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array() , 1040 , $XF_error_code_info[1040]);
        }
        if ($res['status'] != 1) {
            $this->echoJson(array() , 1041 , $XF_error_code_info[1041]);
        }
        if ($res['start_time'] > $time) {
            $this->echoJson(array() , 1042 , $XF_error_code_info[1042]);
        }
        $add_ip = Yii::app()->request->userHostAddress;
        $sql    = "SELECT * FROM xf_message_detail WHERE message_id = {$res['id']} AND user_id = {$user_info['id']} ";
        $detail = $model->createCommand($sql)->queryRow();
        if ($detail['status'] == 2) {
            $sql    = "UPDATE xf_message_detail SET status = 1 , read_time = {$time} , read_ip = '{$add_ip}' WHERE id = {$detail['id']}";
            $update = $model->createCommand($sql)->execute();
        } else if (empty($detail)) {
            $sql = "INSERT INTO xf_message_detail (message_id , user_id , status , read_time , read_ip) VALUES ({$res['id']} , {$user_info['id']} , 1 , {$time} , '{$add_ip}') ";
            $update = $model->createCommand($sql)->execute();
        }
        $result_data['id']         = $res['id'];
        $result_data['title']      = $res['title'];
        $result_data['abstract']   = $res['abstract'];
        $result_data['content']    = $res['content'];
        $result_data['start_time'] = $res['start_time'];
        $this->echoJson($result_data , 0 , $XF_error_code_info[0]);
    }

    /**
     * 提交意见反馈
     */
    public function actionAddFeedback()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        if (empty($_POST['content'])) {
            $this->echoJson(array() , 1043 , $XF_error_code_info[1043]);
        }
        // 校验IP
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $redis_key = "add_feedback_limit_by_IP_{$ip}";
        $check_IP  = $redis->get($redis_key);
        if ($check_IP > $this->add_feedback_limit_by_IP) {
            $this->echoJson(array() , 1049 , $XF_error_code_info[1049]);
        }
        // 检验总数
        $redis_key = "add_feedback_limit_by_ID_total_{$user_info['id']}";
        $check_ID  = $redis->get($redis_key);
        if ($check_ID > $this->add_feedback_limit_by_ID_total) {
            $this->echoJson(array() , 1050 , $XF_error_code_info[1050]);
        }
        // 校验频率
        $redis_key = "add_feedback_limit_by_ID_{$user_info['id']}";
        $check_ID  = $redis->get($redis_key);
        if ($check_ID > $this->add_feedback_limit_by_ID) {
            $this->echoJson(array() , 1051 , $XF_error_code_info[1051]);
        }
        $mobile = GibberishAESUtil::dec($user_info['mobile'] , Yii::app()->c->idno_key);
        $idno   = GibberishAESUtil::dec($user_info['idno'] , Yii::app()->c->idno_key);
        $sql    = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$user_info['id']} AND verify_status = 1";
        $card   = Yii::app()->db->createCommand($sql)->queryRow();
        if (!empty($card['bankcard'])) {
            $bankcard = GibberishAESUtil::dec($card['bankcard'] , Yii::app()->c->idno_key);
        } else {
            $bankcard = '';
        }
        $time = time();
        $add_ip = Yii::app()->request->userHostAddress;
        $sql = "INSERT INTO xf_feedback (user_id , user_real_name , user_mobile , user_idno , user_bankcard , add_time , add_ip , content , status , re_content) VALUES ({$user_info['id']} , '{$user_info['real_name']}' , '{$mobile}' , '{$idno}' , '{$bankcard}' , {$time} , '{$add_ip}' , '{$_POST['content']}' , 1 , '')";
        $result = Yii::app()->db->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson(array() , 1044 , $XF_error_code_info[1044]);
        }
        // 增加IP计数
        $redis_key = "add_feedback_limit_by_IP_{$ip}";
        $check_IP  = $redis->exists($redis_key);
        if ($check_IP) {
            $set_IP = $redis->incr($redis_key);
        } else {
            $set_IP = $redis->incr($redis_key);
            $set_IP = $redis->expireAt($redis_key , ($time + $this->add_feedback_limit_by_IP_reset));
        }
        if (!$set_IP) {
            $this->echoJson(array() , 1052 , $XF_error_code_info[1052]);
        }
        // 增加总数计数
        $redis_key = "add_feedback_limit_by_ID_total_{$user_info['id']}";
        $check_ID  = $redis->exists($redis_key);
        if ($check_ID) {
            $set_ID = $redis->incr($redis_key);
        } else {
            $set_ID = $redis->incr($redis_key);
            $set_ID = $redis->expireAt($redis_key , ($time + $this->add_feedback_limit_by_ID_total_reset));
        }
        if (!$set_ID) {
            $this->echoJson(array() , 1053 , $XF_error_code_info[1053]);
        }
        // 增加频率计数
        $redis_key = "add_feedback_limit_by_ID_{$user_info['id']}";
        $check_ID  = $redis->exists($redis_key);
        if ($check_ID) {
            $set_ID = $redis->incr($redis_key);
        } else {
            $set_ID = $redis->incr($redis_key);
            $set_ID = $redis->expireAt($redis_key , ($time + $this->add_feedback_limit_by_ID_reset));
        }
        if (!$set_ID) {
            $this->echoJson(array() , 1054 , $XF_error_code_info[1054]);
        }
        $this->echoJson(array() , 0 , $XF_error_code_info[0]);
    }

    /**
     * 意见反馈列表
     */
    public function actionFeedbackList()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        $model = Yii::app()->db;
        // 条件筛选
        $where = " WHERE user_id = {$user_info['id']} ";
        // 校验每页数据显示量
        if (!empty($_POST['limit'])) {
            $limit = intval($_POST['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        if ($limit > 50) {
            $this->echoJson(array() , 1032 , $XF_error_code_info[1032]);
        }
        // 校验当前页数
        if (!empty($_POST['page'])) {
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        $sql   = "SELECT count(id) AS count FROM xf_feedback {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            $this->echoJson(array() , 0 , $XF_error_code_info[0]);
        }
        // 查询数据
        $sql = "SELECT * FROM xf_feedback {$where} ORDER BY re_status DESC , operation_time DESC , add_time DESC ";
        $page_count = ceil($count / $limit);
        if ($page > $page_count) {
            $page = $page_count;
        }
        if ($page < 1) {
            $page = 1;
        }
        $pass = ($page - 1) * $limit;
        $sql .= " LIMIT {$pass} , {$limit} ";
        $list = $model->createCommand($sql)->queryAll();
        $listInfo = array();
        foreach ($list as $key => $value) {
            $temp               = array();
            $temp['id']         = $value['id'];
            $temp['content']    = $value['content'];
            $temp['re_content'] = $value['re_content'];
            $temp['add_time']   = $value['add_time'];
            $temp['status']     = $value['status'];
            $temp['re_status']  = $value['re_status'];

            $listInfo[] = $temp;
        }

        header ( "Content-type:application/json; charset=utf-8" );
        $result_data['data']       = $listInfo;
        $result_data['count']      = $count;
        $result_data['page_count'] = $page_count;
        $result_data['code']       = 0;
        $result_data['info']       = $XF_error_code_info[0];
        echo exit(json_encode($result_data));
    }

    /**
     * 意见反馈详情
     */
    public function actionFeedbackInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        if (empty($_POST['feedback_id']) || !is_numeric($_POST['feedback_id'])) {
            $this->echoJson(array() , 1046 , $XF_error_code_info[1046]);
        }
        $model = Yii::app()->db;
        $time  = time();
        $sql   = "SELECT * FROM xf_feedback WHERE id = '{$_POST['feedback_id']}' AND user_id = {$user_info['id']} ";
        $res   = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array() , 1047 , $XF_error_code_info[1047]);
        }
        if ($res['re_status'] == 2) {
            $add_ip = Yii::app()->request->userHostAddress;
            $sql    = "UPDATE xf_feedback SET re_status = 1 , re_time = {$time} , re_ip = '{$add_ip}' WHERE id = {$res['id']}";
            $update = $model->createCommand($sql)->execute();
        }
        $result_data['id']         = $res['id'];
        $result_data['content']    = $res['content'];
        $result_data['re_content'] = $res['re_content'];
        $result_data['add_time']   = $res['add_time'];
        $result_data['status']     = $res['status'];
        $result_data['re_status']  = $res['re_status'];
        $this->echoJson($result_data , 0 , $XF_error_code_info[0]);
    }

    /**
     * 先锋服务协议地址&商城积分兑换协议地址
     */
    public function actionContractAddress()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array() , 1016 , $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array() , 1018 , $XF_error_code_info[1018]);
        }
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = {$user_info['id']} AND platform_no = 0 AND type = 1";
        $xf  = Yii::app()->db->createCommand($sql)->queryRow();
        if ($xf) {
            $result_data['xf_status']       = $xf['status'];
            $result_data['xf_oss_download'] = $xf['oss_download'];
        } else {
            $this->echoJson(array() , 1048 , $XF_error_code_info[1048]);
        }
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = {$user_info['id']} AND platform_no = 1 AND type = 2";
        $youjie = Yii::app()->db->createCommand($sql)->queryRow();
        if ($youjie) {
            $result_data['yj_status']       = $youjie['status'];
            $result_data['yj_oss_download'] = $youjie['oss_download'];
        } else {
            $result_data['yj_status']       = '';
            $result_data['yj_oss_download'] = '';
        }
        $this->echoJson($result_data , 0 , $XF_error_code_info[0]);
    }

    /**
     * 获取用户兑换记录
     */
    public function actionExchangeList(){
        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, Yii::app()->c->XF_error_code_info[2005]);
        }
        //提测时放开
        // $this->user_id = 12131543;
        //校验用户登录状态
        if(!$this->user_id){
             $this->echoJson([], 1016, Yii::app()->c->XF_error_code_info[1016]);
        }

        //所属平台信息校验
        if(isset($_POST['type']) && !in_array($_POST['type'], Yii::app()->c->xf_config['platform_type'])){
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //所属渠道校验
        if(!empty($_POST['platform_no']) && !in_array($_POST['platform_no'], Yii::app()->c->xf_config['xf_shop'])){
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //limit最大值50
        if(isset($_POST['limit']) && $_POST['limit']>50){
            $this->echoJson([], 1032, Yii::app()->c->XF_error_code_info[1032]);
        }

        //默认值赋值
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
        $type = isset($_POST['type']) ? (int)$_POST['type'] : 1;
        $db_name = $type == 1 ? 'db' : 'phdb';


        //where条件
        $condition  = " where el.status=2 and el.user_id=:user_id ";
        if(!empty($_POST['platform_no'])){
            $condition .= " and el.platform_no = {$_POST['platform_no']} ";
        }
        //总条数
        $count_exchange_sql = "select count(1) from firstp2p_debt_exchange_log el $condition ";
        $total_log = Yii::app()->{$db_name}->createCommand($count_exchange_sql)->bindValues([':user_id'=>$this->user_id])->queryScalar();
        if($total_log == 0){
            $this->echoJson([], 0 );
        }

        //兑换记录sql
        $offset = ($page - 1) * $limit;
        $list_exchange_sql = "select el.id, el.debt_account,dl.name as deal_name,el.addtime as exchange_time,el.platform_no, case el.platform_no
                              when 1 then '有解' else '其他' end as platform_no_tips 
                              from firstp2p_debt_exchange_log el 
                              left join firstp2p_deal dl on el.borrow_id=dl.id 
                              $condition order by el.id desc limit $offset,$limit ";
        $exchange_list = Yii::app()->{$db_name}->createCommand($list_exchange_sql)->bindValues([':user_id'=>$this->user_id])->queryAll();

        header ( "Content-type:application/json; charset=utf-8" );
        $result_data['data'] = $exchange_list;
        $result_data['count'] = $total_log;
        $result_data['page_count'] = ceil($total_log / $limit);
        $result_data['code'] = 0;
        $result_data['info'] = '';
        echo exit(json_encode($result_data));
    }

    /**
     * 获取用户在途债权记录(不包含智多鑫)
     */
    public function actionLoanList(){
        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, Yii::app()->c->XF_error_code_info[2005]);
        }
        //提测时放开
        // $this->user_id = 7199066;
        //校验用户登录状态
        if(!$this->user_id){
            $this->echoJson([], 1016, Yii::app()->c->XF_error_code_info[1016]);
        }

        //所属平台信息校验
        if(isset($_POST['type']) && !in_array($_POST['type'], Yii::app()->c->xf_config['platform_type'])){
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //债转状态校验
        if(!empty($_POST['status']) && !in_array($_POST['status'], [1, 2])){
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //limit最大值50
        if(isset($_POST['limit']) && $_POST['limit']>50){
            $this->echoJson([], 1032, Yii::app()->c->XF_error_code_info[1032]);
        }

        //默认值赋值
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
        $type = isset($_POST['type']) ? (int)$_POST['type'] : 1;
        $db_name = $type == 1 ? 'db' : 'phdb';


        //where条件
        $condition  = " left join firstp2p_deal dd on dd.id=dl.deal_id where dl.user_id=:user_id and dd.is_zdx=0 and dd.deal_status!=3  ";
        if(in_array($_POST['status'], [1, 2])){
            $condition .=  $_POST['status'] == 1 ? " and dl.status = 1 and dd.deal_status=4  " : " and dl.status != 1 " ;
        }

        //总条数
        $count_loan_sql = "select count(1) from firstp2p_deal_load dl $condition ";
        $total_loan = Yii::app()->{$db_name}->createCommand($count_loan_sql)->bindValues([':user_id'=>$this->user_id])->queryScalar();
        if($total_loan == 0){
            $this->echoJson([], 0 );
        }

        //兑换记录sql
        $offset = ($page - 1) * $limit;
        $loan_list_sql = "select dl.id, dl.create_time,dl.money,dl.wait_interest ,dd.name as deal_name,dd.rate, dl.wait_capital, dl.status
                          from firstp2p_deal_load dl $condition group by dl.id order by dl.status = 1 desc,dl.create_time desc limit $offset,$limit ";
        $loan_list = Yii::app()->{$db_name}->createCommand($loan_list_sql)->bindValues([':user_id'=>$this->user_id])->queryAll();

        header ( "Content-type:application/json; charset=utf-8" );
        $result_data['data'] = $loan_list;
        $result_data['count'] = $total_loan;
        $result_data['page_count'] = ceil($total_loan / $limit);
        $result_data['code'] = 0;
        $result_data['info'] = '';
        echo exit(json_encode($result_data));
    }

    /**
     * 合同签署
     *
     */
    public function actionSignContract(){
        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, Yii::app()->c->XF_error_code_info[2005]);
        }
        //提测时放开
        // $this->user_id = 123;
        //校验用户登录状态
        if(!$this->user_id){
            $this->echoJson([], 1016, Yii::app()->c->XF_error_code_info[1016]);
        }

        //用户信息
        $user_info = User::model()->find(" id=$this->user_id and is_online=1 ");
        if(!$user_info){
            $this->echoJson([], 2007, Yii::app()->c->XF_error_code_info[2007]);
        }

        //合同类型校验
        if(!isset($_POST['type']) || !in_array($_POST['type'], Yii::app()->c->xf_config['xf_contract_type'])){
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //所属渠道校验
        $platform_con = '';
        $platform_no = 0;
        if($_POST['type'] == 2 ){
            if(!isset($_POST['platform_no']) || !in_array($_POST['platform_no'], Yii::app()->c->xf_config['xf_shop'])){
                $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
            }
            $platform_con = " and platform_no={$_POST['platform_no']} " ;
            $platform_no = $_POST['platform_no'];
        }

        //校验是否已经签署相关协议
        $is_sign = XfUserContract::model()->find("user_id=$this->user_id and type={$_POST['type']} $platform_con ");
        if($is_sign){
            $code = $_POST['type'] == 1 ? 2001 : 2002;
            $this->echoJson([], $code, Yii::app()->c->XF_error_code_info[$code]);
        }

        //签署信息保存
        $sign_model = new XfUserContract();
        $sign_model->user_id = $this->user_id;
        $sign_model->status = 0;
        $sign_model->platform_no = $platform_no;
        $sign_model->type = $_POST['type'];
        $sign_model->addtime = time();
        $sign_model->addip = FunctionUtil::ip_address();
        if(false == $sign_model->save()){
            Yii::log("user_id:$this->user_id sign xf_contract error, error_info:".$sign_model->getErrors());
            $this->echoJson([], 2004, Yii::app()->c->XF_error_code_info[2004]);
        }

        //签署成功
        $this->echoJson([], 0);
    }

}