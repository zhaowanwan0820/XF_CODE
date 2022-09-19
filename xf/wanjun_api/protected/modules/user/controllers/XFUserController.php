<?php
class XFUserController extends XianFengExtendsController
{
    /**
     * 首页
     */
    public function actionindex()
    {
        echo '欢迎使用<br>123';
    }

    /**
     * 礼包专区appid
     * @var int
     */
    private $special_area_app_id = 4;

    /**
     * 登录发送短信
     * limit 发送短信限制数（次）
     * reset 发送短信限制数重置时间（秒）
     */
    protected $login_send_SMS_limit_by_IP = array( // IP限制
                                                0 => array('limit' => 200, 'reset' => 86400),
                                                1 => array('limit' => 20, 'reset' => 3600),
                                                2 => array('limit' => 5, 'reset' => 60),
                                            );
    protected $login_send_SMS_limit_by_number = array( // 手机号限制
                                                0 => array('limit' => 20, 'reset' => 86400),
                                            );

    /**
     * 重置交易密码
     * limit 发送短信限制数（次）
     * reset 发送短信限制数重置时间（秒）
     */
    protected $password_send_SMS_limit_by_IP = array( // IP限制
                                                0 => array('limit' => 200, 'reset' => 86400),
                                                1 => array('limit' => 20, 'reset' => 3600),
                                                2 => array('limit' => 5, 'reset' => 60),
                                            );
    protected $password_send_SMS_limit_by_number = array( // 手机号限制
                                                0 => array('limit' => 20, 'reset' => 86400),
                                            );

    /**
     * 修改手机号
     * limit 发送短信限制数（次）
     * reset 发送短信限制数重置时间（秒）
     */
    protected $mobile_send_SMS_limit_by_IP = array( // IP限制
                                                0 => array('limit' => 200, 'reset' => 86400),
                                                1 => array('limit' => 20, 'reset' => 3600),
                                                2 => array('limit' => 5, 'reset' => 60),
                                            );
    protected $mobile_send_SMS_limit_by_number = array( // 手机号限制
                                                0 => array('limit' => 20, 'reset' => 86400),
                                            );

    protected $send_SMS_limit_by_CD = 60; // 单个手机号码发送短信冷却时间（秒）

    // 登录其他限制
    protected $login_limit_by_number       = 3; // 单个用户登录限制数（次）
    protected $login_limit_by_number_reset = 60; // 单个用户登录限制数重置时间（秒）
    protected $login_limit_by_number_CD    = 60; // 单个用户登录冷却时间（秒）
    protected $login_limit                 = false; // 对二取模限制限制登录

    // 重置交易密码限制
    protected $check_password_limit_by_ID       = 6; // 单个用户校验交易密码错误限制数（次）
    protected $check_password_limit_by_ID_reset = 86400; // 单个用户校验交易密码错误限制数重置时间（秒）

    // 提交意见反馈限制
    protected $add_feedback_limit_by_IP                 = 100; // 单个IP地址提交意见反馈限制数（次）
    protected $add_feedback_limit_by_IP_reset           = 86400; // 单个IP地址提交意见反馈限制数重置时间（秒）
    protected $add_feedback_limit_by_ID                 = 3; // 单个用户提交意见反馈短时间限制数（次）
    protected $add_feedback_limit_by_ID_reset           = 60; // 单个用户提交意见反馈短时间限制数重置时间（秒）
    protected $add_feedback_limit_by_ID_total           = 20; // 单个用户提交意见反馈长时间限制数（次）
    protected $add_feedback_limit_by_ID_total_reset     = 86400; // 单个用户提交意见反馈长时间限制数重置时间（秒）
    protected $check_feedback_content_percent           = 0.9000; // 检验意见反馈内容不通过相似度百分比：0.0000至1.0000
    protected $check_feedback_content_limit_by_ID       = 5; // 单个用户检验意见反馈内容留存数量
    protected $check_feedback_content_limit_by_ID_reset = 600; // 单个用户检验意见反馈内容保存时间（秒）
    
    // 修改手机号限制
    protected $check_user_info_limit_by_IP       = 10; // 单个IP地址校验用户信息失败限制数（次）
    protected $check_user_info_limit_by_IP_reset = 86400; // 单个IP地址校验用户信息失败限制数重置时间（秒）
    protected $change_mobile_limit_by_ID         = 3; // 单个用户修改手机号限制数（次）
    protected $change_mobile_limit_by_ID_reset   = 86400; // 单个用户修改手机号限制数重置时间（秒）




    /**
     * 上传图片
     * @param content   string  图片的base64内容
     * @return array
     */
    private function upload_base64($content)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)) {
            $pic_type    = $result[2]; // 匹配出图片后缀名
            $dir_address = "uploads/";
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            $pic_name    = time() . rand(10000, 99999) . ".{$pic_type}";
            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address, base64_decode(str_replace($result[1], '', $content)))) {
                return array('pic_address' => $pic_address , 'pic_name' => $pic_name);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 文件上传OSS
     * @param $filePath
     * @param $ossPath
     * @return bool
     */
    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            unlink($filePath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * 先锋服务协议地址&商城积分兑换协议地址
     */
    public function actionContractAddress()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = {$user_info['id']} AND platform_no = 0 AND type = 1";
        $xf  = Yii::app()->db->createCommand($sql)->queryRow();
        if ($xf) {
            $result_data['xf_status']       = $xf['status'];
            $result_data['xf_oss_download'] = $xf['oss_download'];
        } else {
            $this->echoJson(array(), 1048, $XF_error_code_info[1048]);
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
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }


    /**
     * 合同签署
     *
     */
    public function actionSignContract()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, $XF_error_code_info[2005]);
        }
        //提测时放开
        // $this->user_id = 123;
        //校验用户登录状态
        if (!$this->user_id) {
            $this->echoJson([], 1016, $XF_error_code_info[1016]);
        }

        //用户信息
        $user_info = User::model()->find(" id=$this->user_id ");
        if (!$user_info) {
            $this->echoJson([], 2007, $XF_error_code_info[2007]);
        }
        if ($user_info['is_online'] != 1 && !in_array($user_info['id'], $itouzi['debt_buyer_white_list'])) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }

        //合同类型校验
        if (!isset($_POST['type']) || !in_array($_POST['type'], Yii::app()->c->xf_config['xf_contract_type'])) {
            $this->echoJson([], 2000, $XF_error_code_info[2000]);
        }

        //所属渠道校验
        $platform_con = '';
        $platform_no = 0;
        if ($_POST['type'] == 2) {
            if (!isset($_POST['platform_no']) || !in_array($_POST['platform_no'], Yii::app()->c->xf_config['xf_shop'])) {
                $this->echoJson([], 2000, $XF_error_code_info[2000]);
            }
            $platform_con = " and platform_no={$_POST['platform_no']} " ;
            $platform_no = $_POST['platform_no'];
        }

        //校验是否已经签署相关协议
        $is_sign = XfUserContract::model()->find("user_id=$this->user_id and type={$_POST['type']} $platform_con ");
        if ($is_sign) {
            $code = $_POST['type'] == 1 ? 2001 : 2002;
            $this->echoJson([], $code, $XF_error_code_info[$code]);
        }

        //签署信息保存
        $sign_model = new XfUserContract();
        $sign_model->user_id = $this->user_id;
        $sign_model->status = 0;
        $sign_model->platform_no = $platform_no;
        $sign_model->type = $_POST['type'];
        $sign_model->addtime = time();
        $sign_model->addip = FunctionUtil::ip_address();
        if (false == $sign_model->save()) {
            Yii::log("user_id:$this->user_id sign xf_contract error, error_info:".$sign_model->getErrors());
            $this->echoJson([], 2004, $XF_error_code_info[2004]);
        }

        //签署成功
        $this->echoJson([], 0);
    }

    private function getPhone($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            return false;
        }
        //用户信息
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo)){
            return false;
        }

        return GibberishAESUtil::dec($userInfo->mobile, Yii::app()->c->contract['idno_key']);
    }

    private function getDealname($deal_id){
        if(empty($deal_id) || !is_numeric($deal_id)){
            return false;
        }
        //标的信息
        $dealInfo = RcmsDeal::model()->findByPk($deal_id);
        if(empty($dealInfo)){
            return false;
        }

        return $dealInfo->name;
    }

    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key=>$value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    /**
     * 获取登录短信验证码
     */
    public function actionGetSMSFromLogin()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 测试用手机号
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        // 校验IP发送限制
        $ip = ip2long(Yii::app()->request->userHostAddress);
        $redis = Yii::app()->rcache;
        $time = time();
        foreach ($this->login_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "WJ_login_send_SMS_limit_by_IP_{$key}_{$ip}";
            $check_IP  = $redis->get($redis_key);
            if ($check_IP >= $value['limit']) {
                $this->echoJson(array(), 1000, $XF_error_code_info[1000]);
            }
        }
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array(), 1001, $XF_error_code_info[1001]);
        }
        $number = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/', $number);
        if ($check_number === 0) {
            $this->echoJson(array(), 1002, $XF_error_code_info[1002]);
        }
        // 对二取模限制限制登录
        $num = ($number + (time() / 60)) % 2;
        if ($num == 0 && $this->login_limit === true) {
            $this->echoJson(array(), 1066, $XF_error_code_info[1066]);
        }
        // 校验用户
        $redis_key  = "WJ_is_not_online_{$number}";
        $check_user = $redis->get($redis_key);
        if ($check_user) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }
        $mobile = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1014, $XF_error_code_info[1014]);
        }

        if ($_POST['from'] != 'special_area' && $user_info['is_online'] != 1 && !in_array($user_info['id'], $itouzi['debt_buyer_white_list'])) {
            $set_user = $redis->set($redis_key, $number);
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }
        // 校验手机号发送限制
        if (!in_array($number, $xf_test_number) && !in_array($user_info['id'], $itouzi['debt_buyer_white_list'])) {
            foreach ($this->login_send_SMS_limit_by_number as $key => $value) {
                $redis_key = "WJ_login_send_SMS_limit_by_number_{$key}_{$number}";
                $check_number = $redis->get($redis_key);
                if ($check_number >= $value['limit']) {
                    $this->echoJson(array(), 1003, $XF_error_code_info[1003]);
                }
            }
        }
        // 校验冷却时间
        $redis_key = "WJ_send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1004, $XF_error_code_info[1004]);
        }
        // 保存验证码
        $redis_key = "WJ_user_mobile_login_SMS_{$number}";
        if (in_array($number, $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis = $redis->set($redis_key, $redis_value, $redis_time);
        if (!$set_redis) {
            $this->echoJson(array(), 1005, $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number, $xf_test_number)) {
            $remind['phone'] = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code'] = "wj_login";
            $send = new WjSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 0) {
                $this->echoJson(array(), 1006, $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        foreach ($this->login_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "WJ_login_send_SMS_limit_by_IP_{$key}_{$ip}";
            $check_IP  = $redis->exists($redis_key);
            if ($check_IP) {
                $set_IP = $redis->incr($redis_key);
            } else {
                $set_IP = $redis->incr($redis_key);
                $set_IP = $redis->expireAt($redis_key, ($time + $value['reset']));
            }
            if (!$set_IP) {
                $this->echoJson(array(), 1007, $XF_error_code_info[1007]);
            }
        }
        // 增加手机号计数
        foreach ($this->login_send_SMS_limit_by_number as $key => $value) {
            $redis_key    = "WJ_login_send_SMS_limit_by_number_{$key}_{$number}";
            $check_number = $redis->exists($redis_key);
            if ($check_number) {
                $set_number = $redis->incr($redis_key);
            } else {
                $set_number = $redis->incr($redis_key);
                $set_number = $redis->expireAt($redis_key, ($time + $value['reset']));
            }
            if (!$set_number) {
                $this->echoJson(array(), 1008, $XF_error_code_info[1008]);
            }
        }
        // 增加冷却时间
        $redis_key = "WJ_send_SMS_limit_by_CD_{$number}";
        $set_CD    = $redis->set($redis_key, $redis_value, $this->send_SMS_limit_by_CD);
        if (!$set_CD) {
            $this->echoJson(array(), 1009, $XF_error_code_info[1009]);
        }
        $this->echoJson(array('ttl' => 60), 0, $XF_error_code_info[0]);
    }

    /**
     * 登录
     */
    public function actionLogin()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $time = time();
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array(), 1001, $XF_error_code_info[1001]);
        }
        $number = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/', $number);
        if ($check_number === 0) {
            $this->echoJson(array(), 1002, $XF_error_code_info[1002]);
        }
        // 校验验证码
        if (empty($_POST['code']) || !is_numeric($_POST['code'])) {
            $this->echoJson(array(), 1010, $XF_error_code_info[1010]);
        }
        $code = trim($_POST['code']);
        $check_code = preg_match('/^\d{6}$/', $code);
        if ($check_code === 0) {
            $this->echoJson(array(), 1011, $XF_error_code_info[1011]);
        }
        $redis = Yii::app()->rcache;
        $redis_key = "WJ_user_mobile_login_SMS_{$number}";
        $data = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array(), 1012, $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array(), 1013, $XF_error_code_info[1013]);
        }
        // 校验用户
        $redis_key  = "WJ_is_not_online_{$number}";
        $check_user = $redis->get($redis_key);
        if ($check_user) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }
        // 校验登录冷却
        $redis_key = "WJ_login_limit_by_number_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1056, $XF_error_code_info[1056]);
        }
        $mobile = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql = "SELECT id AS user_id , is_online,idno,real_name FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1014, $XF_error_code_info[1014]);
        }
        if ($_POST['from'] != 'special_area' && $user_info['is_online'] != 1 && !in_array($user_info['user_id'], $itouzi['debt_buyer_white_list'])) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }

        $user_info['sign_agreement'] = 1;
        $sql = "SELECT * FROM wj_user_contract WHERE user_id = '{$user_info['user_id']}' AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->phdb->createCommand($sql)->queryRow();
        //登录默认同意协议
        if (!$contract) {
            $userContract = new WjUserContract();
            $userContract->type = 1;
            $userContract->addtime = time();
            $userContract->user_id = $user_info['user_id'];
            $userContract->addip = FunctionUtil::ip_address();
            $userContract->data_json ='';
            $userContract->fdd_download ='';
            $userContract->oss_download ='';
            $userContract->status =0;
            if ($userContract->save()==false) {
                $this->echoJson(array(), 5000, $XF_error_code_info[5000]);
            }
        }
        // 增加登录计数
        $redis_key    = "WJ_login_limit_by_number_{$number}";
        $check_number = $redis->exists($redis_key);
        if ($check_number) {
            $set_number = $redis->incr($redis_key);
        } else {
            $set_number = $redis->incr($redis_key);
            $set_time   = $redis->expireAt($redis_key, ($time + $this->login_limit_by_number_reset));
        }
        if (!$set_number) {
            $this->echoJson(array(), 1057, $XF_error_code_info[1057]);
        }
        if ($set_number >= $this->login_limit_by_number) {
            $redis_key = "WJ_login_limit_by_number_CD_{$number}";
            $set_CD    = $redis->set($redis_key, $set_number, $this->login_limit_by_number_CD);
            if (!$set_CD) {
                $this->echoJson(array(), 1058, $XF_error_code_info[1058]);
            }
        }
        $token = JwtClass::getToken($user_info);
        $this->user_id = $user_info['user_id'];
        $data = ['token' => $token,'auth_code'=>null,'notice'=>'您不在活动范围,将跳转至用户中心'];

        //登录成功记录数据
        $log_idno = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
        $log_data = [
            'login_device' => trim($_POST['add_device']),
            'login_browser' => trim($_POST['add_browser']),
            'login_type' => 0,
            'user_id' => $this->user_id,
            'real_name' => $user_info['real_name'],
            'mobile_phone' => $number,
            'idno' => $log_idno,
        ];
        $log_ret = DisplaceService::getInstance()->addLoginLog($log_data);
        if(!$log_ret){
            Yii::log("/user/XFUser/Login user_id:[$this->user_id] error: ".print_r($log_data, true), 'error');
        }
        $this->echoJson($data, 0, $XF_error_code_info[0]);
    }

    /**
     * 个人基础信息
     */
    public function actionUserInfo()
    {
        //提测试注释
        //$this->user_id = 12130848;

        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql = "SELECT id,real_name,mobile,idno FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }

        $result_data = $user_info;
        //证件号解密
        $result_data['mobile'] = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
        $result_data['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
        if ($result_data['idno'] === false) {
            $result_data['idno'] = '';
        }
        $sql  = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$user_info['id']} AND verify_status = 1";
        $card = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!empty($card['bankcard'])) {
            $result_data['bankcard'] = GibberishAESUtil::dec($card['bankcard'], Yii::app()->c->idno_key);
        } else {
            $result_data['bankcard'] = '';
        }

        $see_time = time()-864000;
        $sql = "SELECT SUM(wait_capital) AS wait_capital ,count(1) as c_deal_loan  FROM firstp2p_deal_load WHERE user_id = '{$user_id}' and status=1 and create_time<=$see_time ";
        $load_ret = Yii::app()->phdb->createCommand($sql)->queryRow();
        $result_data['wait_capital']  = $load_ret['wait_capital'] ?: '0.00';
        $result_data['c_deal_loan']  = $load_ret['c_deal_loan'] ?: '0.00';

        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }

    /**
     * 获取用户在途债权记录
     */
    public function actionLoanList()
    {
        //提测试注释
        //$this->user_id = 12130848;

        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        //校验用户登录状态
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }

        // 校验服务协议
        $sql = "SELECT * FROM wj_user_contract WHERE user_id = {$user_id} AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$contract) {
            $this->echoJson(array(), 1048, $XF_error_code_info[1048]);
        }
        //limit最大值50
        if (isset($_POST['limit']) && $_POST['limit']>50) {
            $this->echoJson([], 1032, $XF_error_code_info[1032]);
        }

        //默认值赋值
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;

        //where条件
        $see_time = time()-864000;
        $condition  = " left join firstp2p_deal dd on dd.id=dl.deal_id where dl.user_id=:user_id and dd.is_zdx=0  and dl.status = 1 and dd.deal_status = 4 and dl.create_time<=$see_time ";
        $count_loan_sql = "select count(1) from firstp2p_deal_load dl $condition ";
        $total_loan = Yii::app()->phdb->createCommand($count_loan_sql)->bindValues([':user_id'=>$this->user_id])->queryScalar();
        if ($total_loan == 0) {
            $this->echoJson([], 0);
        }

        //投资记录sql
        $offset = ($page - 1) * $limit;
        $loan_list_sql = "select dl.id,  dd.name as deal_name,  dl.wait_capital, dl.wait_interest, uu.real_name, FROM_UNIXTIME(dd.max_repay_time,'%Y-%m-%d') as max_repay_time ,a.name as agency_name,b.name as advisory_name
                            from firstp2p_deal_load dl 
                            left join firstp2p_deal dd on dd.id=dl.deal_id    
                            LEFT JOIN firstp2p_user uu on uu.id=dd.user_id 
                            LEFT JOIN firstp2p_deal_agency a on a.id=dd.agency_id   
                            LEFT JOIN firstp2p_deal_agency b on b.id=dd.advisory_id 
                            where dl.user_id=:user_id and dd.is_zdx=0  and dl.status = 1 and dd.deal_status = 4 and dl.create_time<=$see_time
                            group by dl.id order by  dl.create_time desc limit $offset,$limit ";
        $loan_list = Yii::app()->phdb->createCommand($loan_list_sql)->bindValues([':user_id'=>$this->user_id])->queryAll();
        header("Content-type:application/json; charset=utf-8");
        $result_data['data'] = $loan_list;
        $result_data['count'] = $total_loan;
        $result_data['page_count'] = ceil($total_loan / $limit);
        $result_data['code'] = 0;
        $result_data['info'] = '';
        echo exit(json_encode($result_data));
    }

}
