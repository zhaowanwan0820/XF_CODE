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
     * 意见反馈内容字符串转数组
     */
    protected function FeedbackContent2array($content)
    {
        $content_array = array();
        $count = mb_strlen($content, 'utf8');
        for ($i = 1; $i < $count; $i++) {
            for ($j = 0; $j <= $count-$i; $j++) {
                $content_array[mb_substr($content, $j, $i, 'utf8')]++;
            }
        }
        return $content_array;
    }

    /**
     * 校验意见反馈内容
     */
    protected function checkFeedbackContent($content, $old_data_array)
    {
        $content_array = $this->FeedbackContent2array($content);
        $percent = array();
        if (!empty($old_data_array)) {
            foreach ($old_data_array as $key => $value) {
                $same = 0;
                $diff = 0;
                foreach ($content_array as $k => $v) {
                    if (!empty($value[$k])) {
                        if ($value[$k] == $v) {
                            $same += $v * mb_strlen($k, 'utf8');
                        } else {
                            $diff = abs(($value[$k] - $v)) * mb_strlen($k, 'utf8');
                            $same = min($value[$k], $v) * mb_strlen($k, 'utf8');
                        }
                    } else {
                        $diff += $v * mb_strlen($k, 'utf8');
                    }
                }
                $percent[$key] = bcdiv($same, ($same + $diff), 4);
            }
            foreach ($percent as $key => $value) {
                if (bccomp($value, $this->check_feedback_content_percent, 4) === 1) {
                    return false;
                }
            }
            return true;
        } else {
            return true;
        }
    }

    /**
     * 校验交易密码
     * @param   old_password    用户原有的交易密码
     * @param   new_password    用户输入的交易密码
     * @return  bool
     */
    protected function checkPassWord($old_password, $new_password)
    {
        $strlen = strlen($old_password);
        if ($strlen == 24) {
            if ($old_password != GibberishAESUtil::enc($new_password, Yii::app()->c->idno_key)) {
                return false;
            } else {
                return true;
            }
        } elseif ($strlen == 32) {
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
        $token  = str_replace('_', '+', $token);
        $token  = str_replace('.', '/', $token);
        $token  = openssl_decrypt($token, 'AES-128-CBC', $Array2TokenKey);
        $string = base64_decode($token);
        $data   = json_decode($string, true);
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
     * 上传图片
     * @param content   string  图片的base64内容
        * @params user_id int
     * @return array
     */
    private function intensive_upload_base64($content, $pic_name='')
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)) {
            $pic_type    = $result[2]; // 匹配出图片后缀名
            $dir_address = "uploads/intensive_sign_idcard/";
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            if(empty($pic_name)){
                $pic_name    = time() . rand(10000, 99999) . ".{$pic_type}";
            }else{
                $pic_name    = $pic_name . ".{$pic_type}";
            }

            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address, base64_decode(str_replace($result[1], '', $content)))) {
                //$img_url = APP_DIR.'/public/'.$pic_address;
                //$this->imgWrite($img_url, $img_url);
                return array('pic_address' => $pic_address , 'pic_name' => $pic_name);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function imgWrite($old, $new) {
        $maxsize=400;
        $image = new \Imagick($old);
        if($image->getImageHeight() <= $image->getImageWidth())
        {
            $image->resizeImage($maxsize,0,Imagick::FILTER_LANCZOS,1);
        }
        else
        {
            $image->resizeImage(0,$maxsize,Imagick::FILTER_LANCZOS,1);
        }
        $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality(90);
        $image->stripImage();
        $image->writeImage($new);
        $image->destroy();
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
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $time      = time();
        foreach ($this->login_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "login_send_SMS_limit_by_IP_{$key}_{$ip}";
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
        $redis_key  = "XF_is_not_online_{$number}";
        $check_user = $redis->get($redis_key);
        if ($check_user) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }
        $mobile    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
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
                $redis_key    = "login_send_SMS_limit_by_number_{$key}_{$number}";
                $check_number = $redis->get($redis_key);
                if ($check_number >= $value['limit']) {
                    $this->echoJson(array(), 1003, $XF_error_code_info[1003]);
                }
            }
        }
        // 校验冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1004, $XF_error_code_info[1004]);
        }
        // 保存验证码
        $redis_key   = "XF_user_mobile_login_SMS_{$number}";
        if (in_array($number, $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis   = $redis->set($redis_key, $redis_value, $redis_time);
        if (!$set_redis) {
            $this->echoJson(array(), 1005, $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number, $xf_test_number)) {
            $remind['phone']         = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code']          = "wx_login";
            $send     = new XfSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 0) {
                $this->echoJson(array(), 1006, $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        foreach ($this->login_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "login_send_SMS_limit_by_IP_{$key}_{$ip}";
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
            $redis_key    = "login_send_SMS_limit_by_number_{$key}_{$number}";
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
        $redis_key = "send_SMS_limit_by_CD_{$number}";
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
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/', $number);
        if ($check_number === 0) {
            $this->echoJson(array(), 1002, $XF_error_code_info[1002]);
        }
        // 校验验证码
        if (empty($_POST['code']) || !is_numeric($_POST['code'])) {
            $this->echoJson(array(), 1010, $XF_error_code_info[1010]);
        }
        $code       = trim($_POST['code']);
        $check_code = preg_match('/^\d{6}$/', $code);
        if ($check_code === 0) {
            $this->echoJson(array(), 1011, $XF_error_code_info[1011]);
        }
        $redis     = Yii::app()->rcache;
        $redis_key = "XF_user_mobile_login_SMS_{$number}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array(), 1012, $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array(), 1013, $XF_error_code_info[1013]);
        }
        // 校验用户
        $redis_key  = "XF_is_not_online_{$number}";
        $check_user = $redis->get($redis_key);
        if ($check_user) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }
        // 校验登录冷却
        $redis_key = "login_limit_by_number_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1056, $XF_error_code_info[1056]);
        }
        $mobile    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT id AS user_id , is_online,real_name,idno FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1014, $XF_error_code_info[1014]);
        }
        if ($_POST['from'] != 'special_area' && $user_info['is_online'] != 1 && !in_array($user_info['user_id'], $itouzi['debt_buyer_white_list'])) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }

        $user_info['sign_agreement'] = 1;
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = '{$user_info['user_id']}' AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->db->createCommand($sql)->queryRow();
        //登录默认同意协议
        if (!$contract) {
            $userContract = new XfUserContract();
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
        $redis_key    = "login_limit_by_number_{$number}";
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
            $redis_key = "login_limit_by_number_CD_{$number}";
            $set_CD    = $redis->set($redis_key, $set_number, $this->login_limit_by_number_CD);
            if (!$set_CD) {
                $this->echoJson(array(), 1058, $XF_error_code_info[1058]);
            }
        }
        $token = JwtClass::getToken($user_info);
        $this->user_id = $user_info['user_id'];

        /****礼包专区*****/
        $data = ['token' => $token,'auth_code'=>null,'notice'=>'您不在活动范围,将跳转至用户中心'];
        if (XfDebtExchangeUserAllowList::checkUserAllowByOpenid($user_info['user_id'], $this->special_area_app_id)) {
            $debtInfo = (new AboutUserDebtV2($this->user_id, $this->special_area_app_id))->getUserXcAmount($this->user_id);
            $sql = 'select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status in (1,2) AND platform_no=:platform_no';
            $exchangeOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->bindValues([':platform_no' => $this->special_area_app_id])->queryRow();
            if ($debtInfo['code']==0 || $exchangeOrders) {
                $data['auth_code'] = AuthCodeUtil::makeCode($user_info['user_id'], 'AL');
                $data['notice']='';
            }
        }
        /****礼包专区*****/

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
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        /****礼包专区*****/
        $result_data['is_show_special_area'] = false;
        if (false && XfDebtExchangeUserAllowList::checkUserAllowByOpenid($this->user_id, $this->special_area_app_id)) {
            $debtInfo = (new AboutUserDebtV2($this->user_id, $this->special_area_app_id))->getUserXcAmount($this->user_id);
            $sql = 'select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status in (1,2) AND platform_no=:platform_no';
            $exchangeOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->bindValues([':platform_no' => $this->special_area_app_id])->queryRow();
            if ($debtInfo['code']==0 || $exchangeOrders) {
                $result_data['is_show_special_area'] = true;
            }
        }
        /****礼包专区*****/

        $result_data['head_portrait'] = '';
        $result_data['real_name']     = $user_info['real_name'];
        $result_data['mobile']        = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
        $result_data['idno']          = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
        if ($result_data['idno'] === false) {
            $result_data['idno'] = '';
        }
        $sql  = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$user_info['id']} AND verify_status = 1";
        $card = Yii::app()->db->createCommand($sql)->queryRow();
        if (!empty($card['bankcard'])) {
            $user_info['bankcard'] = $result_data['bankcard'] = GibberishAESUtil::dec($card['bankcard'], Yii::app()->c->idno_key);
        } else {
            $result_data['bankcard'] = '';
        }
        $sql = "SELECT SUM(CASE WHEN status = 1 THEN wait_capital ELSE 0 END) AS wait_capital , SUM(CASE WHEN status = 1 THEN wait_interest ELSE 0 END) AS wait_interest , SUM(CASE WHEN status != 1 THEN money ELSE 0 END) AS money FROM firstp2p_deal_load WHERE user_id = '{$user_id}' ";
        $result_a = Yii::app()->db->createCommand($sql)->queryRow();
        $result_b = Yii::app()->phdb->createCommand($sql)->queryRow();
        $sql = "SELECT SUM(CASE WHEN status = 1 THEN wait_capital ELSE 0 END) AS wait_capital , SUM(CASE WHEN status = 1 THEN wait_interest ELSE 0 END) AS wait_interest , SUM(CASE WHEN status != 1 THEN money ELSE 0 END) AS money FROM offline_deal_load WHERE user_id = '{$user_id}' AND platform_id = 3 ";
        $result_c = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        $sql = "SELECT SUM(CASE WHEN status = 1 THEN wait_capital ELSE 0 END) AS wait_capital , SUM(CASE WHEN status = 1 THEN wait_interest ELSE 0 END) AS wait_interest , SUM(CASE WHEN status != 1 THEN money ELSE 0 END) AS money FROM offline_deal_load WHERE user_id = '{$user_id}' AND platform_id = 4 ";
        $result_d = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        $sql = "SELECT SUM(CASE WHEN status = 1 THEN wait_capital ELSE 0 END) AS wait_capital , SUM(CASE WHEN status = 1 THEN wait_interest ELSE 0 END) AS wait_interest , SUM(CASE WHEN status != 1 THEN money ELSE 0 END) AS money FROM offline_deal_load WHERE user_id = '{$user_id}' AND platform_id = 5 ";
        $result_e = Yii::app()->offlinedb->createCommand($sql)->queryRow();
        // 尊享
        $result_data['zx_wait_capital']  = $result_a['wait_capital'] ? $result_a['wait_capital'] : '0.00';
        $result_data['zx_wait_interest'] = $result_a['wait_interest'] ? $result_a['wait_interest'] : '0.00';
        $result_data['zx_money']         = $user_info['money'];
        $result_data['zx_lock_money']    = $user_info['lock_money'];
        if ($result_a['money'] != null && $result_a['money'] > 0) {
            $result_data['is_zx_had']    = 1;
        } elseif ($result_a['wait_capital'] > 0 || $result_a['wait_interest'] > 0) {
            $result_data['is_zx_had']    = 1;
        } else {
            $result_data['is_zx_had']    = 0;
        }
        // 普惠
        $result_data['ph_wait_capital']  = $result_b['wait_capital'] ? $result_b['wait_capital'] : '0.00';
        $result_data['ph_wait_interest'] = $result_b['wait_interest'] ? $result_b['wait_interest'] : '0.00';
        $result_data['ph_money']         = $user_info['ph_money'];
        $result_data['ph_lock_money']    = $user_info['ph_lock_money'];
        if ($result_b['money'] != null && $result_b['money'] > 0) {
            $result_data['is_ph_had']    = 1;
        } elseif ($result_b['wait_capital'] > 0 || $result_b['wait_interest'] > 0) {
            $result_data['is_ph_had']    = 1;
        } else {
            $result_data['is_ph_had']    = 0;
        }
        // 金融工场
        $result_data['jrgc_wait_capital']  = $result_c['wait_capital'] ? $result_c['wait_capital'] : '0.00';
        $result_data['jrgc_wait_interest'] = $result_c['wait_interest'] ? $result_c['wait_interest'] : '0.00';
        $result_data['jrgc_money']         = '0.00';
        $result_data['jrgc_lock_money']    = '0.00';
        if ($result_c['money'] != null && $result_c['money'] > 0) {
            $result_data['is_jrgc_had']    = 1;
        } elseif ($result_c['wait_capital'] > 0 || $result_c['wait_interest'] > 0) {
            $result_data['is_jrgc_had']    = 1;
        } else {
            $result_data['is_jrgc_had']    = 0;
        }
        // 智多新
        $result_data['zdx_wait_capital']  = $result_d['wait_capital'] ? $result_d['wait_capital'] : '0.00';
        $result_data['zdx_wait_interest'] = $result_d['wait_interest'] ? $result_d['wait_interest'] : '0.00';
        $sql = "SELECT wait_join_money FROM offline_user_platform WHERE user_id = '{$user_id}' ";
        $zdx_money = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
        if ($zdx_money) {
            $result_data['zdx_money']  = $zdx_money;
        } else {
            $result_data['zdx_money']  = '0.00';
        }
        $result_data['zdx_lock_money'] = '0.00';
        if ($result_d['money'] != null && $result_d['money'] > 0) {
            $result_data['is_zdx_had'] = 1;
        } elseif ($result_d['wait_capital'] > 0 || $result_d['wait_interest'] > 0) {
            $result_data['is_zdx_had'] = 1;
        } else {
            $result_data['is_zdx_had'] = 0;
        }
        // 交易所
        $result_data['jys_wait_capital']  = $result_e['wait_capital'] ? $result_e['wait_capital'] : '0.00';
        $result_data['jys_wait_interest'] = $result_e['wait_interest'] ? $result_e['wait_interest'] : '0.00';
        $result_data['jys_money']         = '0.00';
        $result_data['jys_lock_money']    = '0.00';
        if ($result_e['money'] != null && $result_e['money'] > 0) {
            $result_data['is_jys_had'] = 1;
        } elseif ($result_e['wait_capital'] > 0 || $result_e['wait_interest'] > 0) {
            $result_data['is_jys_had'] = 1;
        } else {
            $result_data['is_jys_had'] = 0;
        }
        // 总和
        $result_data['wait_capital']  = $result_a['wait_capital'] + $result_b['wait_capital'] + $result_c['wait_capital'] + $result_d['wait_capital'] + $result_e['wait_capital'];
        $result_data['wait_interest'] = $result_a['wait_interest'] + $result_b['wait_interest'] + $result_c['wait_interest'] + $result_d['wait_interest'] + $result_e['wait_interest'];
        $result_data['money']         = $user_info['money'] + $user_info['ph_money'];
        $result_data['lock_money']    = $user_info['lock_money'] + $user_info['ph_lock_money'];

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


        //净本金
        $result_data['clean_capital'] = 0;
        $user_sql = "SELECT * FROM xf_user_recharge_withdraw WHERE user_id = {$user_id}  ";
        $user_recharge_info = Yii::app()->db->createCommand($user_sql)->queryRow();
        if($user_recharge_info){
            $result_data['clean_capital'] = bcadd($user_recharge_info['zx_increase_reduce'], $user_recharge_info['ph_increase_reduce'], 2);
        }

        //是否有未交易完成专区求购
        $result_data['exclusive_purchase_id'] = 0;
        $result_data['purchase_status'] = -1;
        $purchase_sql = "SELECT * FROM xf_exclusive_purchase WHERE user_id = {$user_id} and status in (0,1,2,3) ";
        $purchase_info = Yii::app()->phdb->createCommand($purchase_sql)->queryRow();
        if($purchase_info){
            $result_data['exclusive_purchase_id'] = $purchase_info['id'];
            $result_data['purchase_status'] = $purchase_info['status'];
        }

        //法大大实名认证状态
        $result_data['fdd_real_status'] = $user_info['fdd_real_status'] == 1 ? $user_info['fdd_real_status'] : 0;
        $result_data['intensive_sign_status'] = $user_info['intensive_sign_status'] == 1 ? $user_info['intensive_sign_status'] : 0;
        $result_data['is_displace'] = $user_info['is_displace'];
        $result_data['fdd_real_url'] = '';
        $result_data['displace_url'] = '';
        $return_url = !empty($_POST['return_url']) ? trim($_POST['return_url']) : '';
        $real_src = '';
        if($return_url){
            $smrz_fdd_url = Yii::app()->c->contract['smrz_fdd_url'];
            if(in_array($return_url, $smrz_fdd_url)){
                $real_src = $return_url;
            }else{
                $p_data = explode("?", $return_url);
                $url_params = explode("&", $p_data[1]);
                $new_params = [];
                foreach($url_params as $value){
                    $v1 = explode("=", $value);
                    $new_params[$v1[0]] = $v1[1];
                }
                if($this->checkLegal($new_params)){
                    $real_src = $new_params['redirect_url'];
                }else{
                    $real_src = $smrz_fdd_url[1];
                }
            }
        }

        if($result_data['fdd_real_status'] == 0 && !empty($real_src)){
            #测试环境
            //$result_data['bankcard'] = '6214830103513353';

            $customer_id = 0;
            $id_type = DebtService::getInstance()->convertCardType($user_info['id_type']);
            $smrz_user_id = intval('99999999'.$user_info['id']);
            $result = XfFddService::getInstance()->invokeSyncVerifyUrl($user_info['real_name'], $smrz_user_id, $result_data['idno'], $id_type, $result_data['mobile'] , $result_data['bankcard'], $customer_id, $real_src);
            if (empty($result) || $result['code'] != 1 || $result['fdd_real_transaction_no'] == '' || $result['fdd_real_url'] == '') {
                Yii::log("user_id:[{$user_info['id']}]  user/XFUser/UserInfo er-01:  ".print_r($result, true), 'error');
            }
            //记录法大大客户编号及实名认证交易号
            $customer_id = $result['customer_id'];
            $edit_f =  ",yj_fdd_customer_id = '$customer_id' "  ;
            $fdd_real_transaction_no = $result['fdd_real_transaction_no'];
            $update_sql = "update firstp2p_user set  fdd_real_src='$real_src',fdd_real_status=2,fdd_real_transaction_no ='{$fdd_real_transaction_no}'  {$edit_f}  where id = {$user_id}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if (!$edit_fdd) {
                Yii::log("user_id:[{$user_info['id']}]  user/XFUser/UserInfo er-02:  sql:$update_sql", 'error');
            }

            //返回法大大实名认证地址
            $result_data['fdd_real_url'] = $result['fdd_real_url'];
        }

        //未实名认证必须先实名认证
        $result_data['fdd_real_suffix'] = '实名认证';
        $displace_white_list = Yii::app()->c->xf_config['white_list'];
        $ph_zdx_capital = bcadd($result_data['ph_wait_capital'], $result_data['zdx_wait_capital'], 2);
        if( $result_data['fdd_real_status'] != 1 ){
            if(!in_array($this->user_id, $displace_white_list) &&  $result_data['intensive_sign_status'] == 0 && FunctionUtil::float_bigger($ph_zdx_capital, 0, 2)){
                $result_data['fdd_real_suffix'] = '实名认证及委托授权';
            }
            if(in_array($this->user_id, $displace_white_list) &&  $result_data['is_displace'] == 0 && FunctionUtil::float_bigger($ph_zdx_capital, 0, 2)){
                $result_data['fdd_real_suffix'] = '实名认证及债权置换';
            }
            $result_data['intensive_sign_status'] = 2;
            $result_data['is_displace'] = 2;
        }else{//===================实名认证后签约中断，再次签约处理=====================
            //集约诉讼签约地址
            if(!in_array($this->user_id, $displace_white_list) ){
                if( $result_data['intensive_sign_status'] == 0 && !empty($real_src) && $result_data['is_displace'] != 1){
                    $intensive_sign_url = DisplaceService::getInstance()->getIntensiveContractUrl($user_info);
                    $result_data['intensive_sign_url'] = $intensive_sign_url ?: '';
                    $result_data['intensive_sign_status'] = $intensive_sign_url ? 0 : 2;
                }
                $result_data['is_displace'] = 2;
            }
            else{//置换信息签约地址获取
                $displace_key = $this->user_id.'_displace_key';
                $key_code = Yii::app()->rcache->get($displace_key) ?: 0;
                $user_info['ph_increase_reduce'] = $result_data['ph_increase_reduce'] = round($user_recharge_info['ph_increase_reduce'], 2);
                //$result_data['displace_type'] = $user_recharge_info['ph_increase_reduce'] > 0 ? 1 : 2;//默认全部法大大置换
                $result_data['displace_type'] = 1;//1法大大签约2接口签约
                //大于0直接获取法大大置换签署地址
                if($key_code == 0 && $result_data['intensive_sign_status'] != 1 && $result_data['displace_type'] == 1 && !empty($return_url)  && $user_info['is_displace'] == 0 ){
                    $displace_url = DisplaceService::getInstance()->getDisplaceContractUrl($user_info);
                    $result_data['displace_url'] = $displace_url ?: '';
                    $result_data['is_displace'] = $displace_url ? 0 : 2;
                    //置换签约获取地址后3分钟不弹窗
                    if(!empty($result_data['displace_url'])){
                        Yii::app()->rcache->set($displace_key, 1, 180);
                    }
                }
                if($key_code == 1){
                    $result_data['is_displace'] = 2;
                }
                $result_data['intensive_sign_status'] = 1;
            }
        }
        $result_data['intensive_idcard_time'] = $user_info['intensive_idcard_time'];
        $result_data['intensive_sign_status'] = (string)$result_data['intensive_sign_status'];
        $result_data['fdd_real_status'] = (string)$result_data['fdd_real_status'];
        $result_data['is_displace'] = (string)$result_data['is_displace'];
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }

    private static function checkLegal($data)
    {
        #上线时 注释
        //return true;

        Yii::log(' api checkLegal data:'.print_r($_REQUEST, true), 'info', __FUNCTION__);

        if (!isset($data['appid']) || !isset($data['signature']) || !isset($data['timestamp'])) {
            return false;
        }
        $signature = $data['signature'];
        unset($data['signature']);
        ksort($data);
        if ($data['timestamp']+300 <time()) {
            Yii::log(' api timestamp out time', 'error', __FUNCTION__);
            return  false;
        }
        $secret_key = self::getSecretByAppId($data['appid']);

        $str = md5(implode('', $data).$secret_key);
        Yii::log(' api checkLegal signature:'.$str, 'info', __FUNCTION__);

        if ($str === $signature) {
            return true;
        }

        return false;
    }

    private static function getSecretByAppId($appid)
    {
        $res = XfDebtExchangePlatform::model()->findByPk($appid);
        if (0 == $res->status) {
            $secret = '';
            self::$error_info='平台授权已经失效';
        } else {
            $secret = $res->secret;
        }
        return $secret;
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
                $this->echoJson(array(), 1023, $XF_error_code_info[1023]);
            }
            if ($data['exp'] < time()) {
                $this->echoJson(array(), 1024, $XF_error_code_info[1024]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' AND is_effect = 1 AND is_delete = 0 ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
            }
            if (empty($_POST['new_password'])) {
                $this->echoJson(array(), 1019, $XF_error_code_info[1019]);
            }
            $new_password = trim($_POST['new_password']);
            $check_password = preg_match('/^\d{6}$/', $new_password);
            if (!$check_password) {
                $this->echoJson(array(), 1020, $XF_error_code_info[1020]);
            }
            if (!empty($user_info['transaction_password'])) {
                $this->echoJson(array(), 1021, $XF_error_code_info[1021]);
            }
            $new_password = md5($new_password);
            $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
            $res_a = Yii::app()->db->createCommand($sql)->execute();
            if ($res_a) {
                RedisService::getInstance()->del('user_exchange_password:'.$user_info['id']);
                $this->echoJson(array('url'=>$data['redirect_url']), 0, $XF_error_code_info[0]);
            } else {
                $this->echoJson(array(), 1022, $XF_error_code_info[1022]);
            }
        }

        // 内部设置
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        if (empty($_POST['new_password'])) {
            $this->echoJson(array(), 1019, $XF_error_code_info[1019]);
        }
        $new_password = trim($_POST['new_password']);
        $check_password = preg_match('/^\d{6}$/', $new_password);
        if (!$check_password) {
            $this->echoJson(array(), 1020, $XF_error_code_info[1020]);
        }
        if (!empty($user_info['transaction_password'])) {
            $this->echoJson(array(), 1021, $XF_error_code_info[1021]);
        }
        $new_password = md5($new_password);
        $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
        $res_a = Yii::app()->db->createCommand($sql)->execute();
        if ($res_a) {
            RedisService::getInstance()->del('user_exchange_password:'.$user_info['id']);
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
        } else {
            $this->echoJson(array(), 1022, $XF_error_code_info[1022]);
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
                $this->echoJson(array(), 1023, $XF_error_code_info[1023]);
            }
            if ($data['exp'] < time()) {
                $this->echoJson(array(), 1024, $XF_error_code_info[1024]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' AND is_effect = 1 AND is_delete = 0 ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
            }
            if (!empty($user_info['transaction_password'])) {
                $this->echoJson(array(), 1021, $XF_error_code_info[1021]);
            }
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
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
                $this->echoJson(array(), 1023, $XF_error_code_info[1023]);
            }
            if ($data['exp'] < time()) {
                $this->echoJson(array(), 1024, $XF_error_code_info[1024]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' AND is_effect = 1 AND is_delete = 0 ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
            }
            if (empty($user_info['transaction_password'])) {
                $this->echoJson(array(), 1021, $XF_error_code_info[1021]);
            }
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
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
                $this->echoJson(array(), 1023, $XF_error_code_info[1023]);
            }
            $time = time();
            if ($data['exp'] < $time) {
                $this->echoJson(array(), 1024, $XF_error_code_info[1024]);
            }
            $redis     = Yii::app()->rcache;
            $redis_key = "check_password_limit_by_ID_{$data['uid']}";
            $check_ID  = $redis->get($redis_key);
            if ($check_ID >= $this->check_password_limit_by_ID) {
                $this->echoJson(array(), 1059, $XF_error_code_info[1059]);
            }
            $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$data['uid']}' AND is_effect = 1 AND is_delete = 0 ";
            $user_info = Yii::app()->db->createCommand($sql)->queryRow();
            if (!$user_info) {
                $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
            }
            if (empty($_POST['password'])) {
                $this->echoJson(array(), 1019, $XF_error_code_info[1019]);
            }
            $password = trim($_POST['password']);
            $check_password = preg_match('/^\d{6}$/', $password);
            if (!$check_password) {
                $this->echoJson(array(), 1020, $XF_error_code_info[1020]);
            }
            if (empty($user_info['transaction_password'])) {
                $this->echoJson(array(), 1027, $XF_error_code_info[1027]);
            }
            $checkPassWord = $this->checkPassWord($user_info['transaction_password'], $password);
            if (!$checkPassWord) {
                $check_ID  = $redis->exists($redis_key);
                if ($check_ID) {
                    $set_IP = $redis->incr($redis_key);
                } else {
                    $set_IP = $redis->incr($redis_key);
                    $set_IP = $redis->expireAt($redis_key, ($time + $this->check_password_limit_by_ID_reset));
                }
                if (!$set_IP) {
                    $this->echoJson(array(), 1060, $XF_error_code_info[1060]);
                }
                $this->echoJson(array(), 1025, $XF_error_code_info[1025]);
            }

            //兑换预处理验密
            if (!empty($data['order_id'])) {
                $ret = $this->editDebtOrder($data['order_id']);
                if ($ret == false) {
                    $this->echoJson(array(), 1000, $XF_error_code_info[1000]);
                }
            }

            $this->echoJson(array('url'=>$data['redirect_url']), 0, $XF_error_code_info[0]);
        }
    }


    /**
     * 更新订单临时表状态为待处理
     * @param $order_id
     * @return bool
     */
    private function editDebtOrder($order_id)
    {
        $edit_sql = "update firstp2p_debt_exchange_log set status=1 where orderNumber='$order_id' and status=0";
        //尊享订单信息
        $zx_log = DebtExchangeLog::model()->find("order_id='$order_id' and status=0");
        if ($zx_log) {
            $zx_ret = Yii::app()->db->createCommand($edit_sql)->execute();
            if (!$zx_ret) {
                Yii::log("editDebtOrder return false, zx_edit_sql:$edit_sql", 'error');
                return false;
            }
        }

        //普惠订单信息
        $ph_log = PHDebtExchangeLog::model()->find("order_id='$order_id' and status=0 ");
        if ($ph_log) {
            $ph_ret = Yii::app()->phdb->createCommand($edit_sql)->execute();
            if (!$ph_ret) {
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
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        if (empty($_POST['old_password'])) {
            $this->echoJson(array(), 1026, $XF_error_code_info[1026]);
        }
        $old_password = trim($_POST['old_password']);
        $check_password = preg_match('/^\d{6}$/', $old_password);
        if (!$check_password) {
            $this->echoJson(array(), 1020, $XF_error_code_info[1020]);
        }
        if (empty($_POST['new_password'])) {
            $this->echoJson(array(), 1019, $XF_error_code_info[1019]);
        }
        $new_password = trim($_POST['new_password']);
        $check_password = preg_match('/^\d{6}$/', $new_password);
        if (!$check_password) {
            $this->echoJson(array(), 1020, $XF_error_code_info[1020]);
        }
        if (empty($user_info['transaction_password'])) {
            $this->echoJson(array(), 1027, $XF_error_code_info[1027]);
        }
        $checkPassWord = $this->checkPassWord($user_info['transaction_password'], $old_password);
        if (!$checkPassWord) {
            $this->echoJson(array(), 1028, $XF_error_code_info[1028]);
        }
        if ($old_password == $new_password) {
            $this->echoJson(array(), 1029, $XF_error_code_info[1029]);
        }
        $new_password = md5($new_password);
        $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
        $res_a = Yii::app()->db->createCommand($sql)->execute();
        if ($res_a) {
            RedisService::getInstance()->del('user_exchange_password:'.$user_info['id']);
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
        } else {
            $this->echoJson(array(), 1030, $XF_error_code_info[1030]);
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
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        // 校验IP
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $time      = time();
        foreach ($this->password_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "password_send_SMS_limit_by_IP_{$key}_{$ip}";
            $check_IP  = $redis->get($redis_key);
            if ($check_IP >= $value['limit']) {
                $this->echoJson(array(), 1000, $XF_error_code_info[1000]);
            }
        }
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array(), 1001, $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/', $number);
        if ($check_number === 0) {
            $this->echoJson(array(), 1002, $XF_error_code_info[1002]);
        }
        foreach ($this->password_send_SMS_limit_by_number as $key => $value) {
            $redis_key    = "password_send_SMS_limit_by_number_{$key}_{$number}";
            $check_number = $redis->get($redis_key);
            if ($check_number >= $value['limit']) {
                $this->echoJson(array(), 1003, $XF_error_code_info[1003]);
            }
        }
        // 校验冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1004, $XF_error_code_info[1004]);
        }
        // 校验用户手机号
        $mobile = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        if ($user_info['mobile'] != $mobile) {
            $this->echoJson(array(), 1055, $XF_error_code_info[1055]);
        }
        if ($user_info['is_online'] != 1 && !in_array($user_info['id'], $itouzi['debt_buyer_white_list'])) {
            $this->echoJson(array(), 1015, $XF_error_code_info[1015]);
        }
        // 保存验证码
        $redis_key   = "XF_user_reset_password_SMS_{$number}";
        if (in_array($number, $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis   = $redis->set($redis_key, $redis_value, $redis_time);
        if (!$set_redis) {
            $this->echoJson(array(), 1005, $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number, $xf_test_number)) {
            $remind['phone']         = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code']          = "wx_reset_password";
            $send     = new XfSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 0) {
                $this->echoJson(array(), 1006, $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        foreach ($this->password_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "password_send_SMS_limit_by_IP_{$key}_{$ip}";
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
        foreach ($this->password_send_SMS_limit_by_number as $key => $value) {
            $redis_key    = "password_send_SMS_limit_by_number_{$key}_{$number}";
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
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $set_CD    = $redis->set($redis_key, $redis_value, $this->send_SMS_limit_by_CD);
        if (!$set_CD) {
            $this->echoJson(array(), 1009, $XF_error_code_info[1009]);
        }
        $this->echoJson(array('ttl' => 60), 0, $XF_error_code_info[0]);
    }

    /**
     * 交易密码重置
     */
    public function actionResetPassword()
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
        // 校验交易密码
        if (empty($_POST['new_password'])) {
            $this->echoJson(array(), 1019, $XF_error_code_info[1019]);
        }
        $new_password = trim($_POST['new_password']);
        $check_password = preg_match('/^\d{6}$/', $new_password);
        if (!$check_password) {
            $this->echoJson(array(), 1020, $XF_error_code_info[1020]);
        }
        if (empty($user_info['transaction_password'])) {
            $this->echoJson(array(), 1027, $XF_error_code_info[1027]);
        }
        // 校验验证码
        if (empty($_POST['code']) || !is_numeric($_POST['code'])) {
            $this->echoJson(array(), 1010, $XF_error_code_info[1010]);
        }
        $code       = trim($_POST['code']);
        $check_code = preg_match('/^\d{6}$/', $code);
        if ($check_code === 0) {
            $this->echoJson(array(), 1011, $XF_error_code_info[1011]);
        }
        $number    = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
        $redis     = Yii::app()->rcache;
        $redis_key = "XF_user_reset_password_SMS_{$number}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array(), 1012, $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array(), 1013, $XF_error_code_info[1013]);
        }
        $new_password = md5($new_password);
        if ($user_info['transaction_password'] == $new_password) {
            $this->echoJson(array(), 1029, $XF_error_code_info[1029]);
        }
        $sql   = "UPDATE firstp2p_user SET transaction_password = '{$new_password}' WHERE id = {$user_info['id']} ";
        $res_a = Yii::app()->db->createCommand($sql)->execute();
        if ($res_a) {
            RedisService::getInstance()->del('user_exchange_password:'.$user_info['id']);
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
        } else {
            $this->echoJson(array(), 1031, $XF_error_code_info[1031]);
        }
        $this->echoJson(array(), 0, $XF_error_code_info[0]);
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
            $this->echoJson(array(), 1032, $XF_error_code_info[1032]);
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
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
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

        header("Content-type:application/json; charset=utf-8");
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
            $this->echoJson(array(), 1034, $XF_error_code_info[1034]);
        }
        $model = Yii::app()->db;
        $time  = time();
        $sql   = "SELECT * FROM xf_notice WHERE id = '{$_POST['notice_id']}' ";
        $res   = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array(), 1035, $XF_error_code_info[1035]);
        }
        if ($res['status'] != 1) {
            $this->echoJson(array(), 1036, $XF_error_code_info[1036]);
        }
        if ($res['start_time'] > $time) {
            $this->echoJson(array(), 1037, $XF_error_code_info[1037]);
        }
        $sql    = "UPDATE xf_notice SET pageview = (pageview + 1) WHERE id = {$res['id']}";
        $update = $model->createCommand($sql)->execute();
        $result_data['id']         = $res['id'];
        $result_data['title']      = $res['title'];
        $result_data['abstract']   = $res['abstract'];
        $result_data['content']    = $res['content'];
        $result_data['start_time'] = $res['start_time'];
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }

    /**
     * 新消息&新意见反馈回复状态
     */
    public function actionNewMessageFeedback()
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
        $time = time();
        $sql  = "SELECT * FROM xf_message AS m INNER JOIN xf_message_detail AS d ON m.id = d.message_id WHERE m.user_scope = 2 AND d.user_id = {$user_info['id']} AND d.status = 2 AND m.status = 1 AND m.start_time <= {$time} ";
        $message = Yii::app()->db->createCommand($sql)->queryRow();
        $message_a = false;
        $sql = "SELECT id FROM xf_message WHERE user_scope = 1 AND status = 1 AND start_time <= {$time} ";
        $message_id = Yii::app()->db->createCommand($sql)->queryColumn();
        if ($message_id) {
            $message_id_str = implode(',', $message_id);
            $sql = "SELECT message_id FROM xf_message_detail WHERE message_id IN ({$message_id_str}) AND user_id = {$user_info['id']}";
            $detail_res = Yii::app()->db->createCommand($sql)->queryColumn();
            if ($detail_res) {
                foreach ($message_id as $key => $value) {
                    if (!in_array($value, $detail_res)) {
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
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }

    /**
     * 消息列表
     */
    public function actionMessageList()
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
            $this->echoJson(array(), 1032, $XF_error_code_info[1032]);
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
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
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
            } elseif ($value['user_scope'] == 2) {
                $temp['status'] = $value['detail_status'];
            }

            $listInfo[] = $temp;
        }

        header("Content-type:application/json; charset=utf-8");
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
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        if (empty($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
            $this->echoJson(array(), 1039, $XF_error_code_info[1039]);
        }
        $model = Yii::app()->db;
        $time  = time();
        $sql   = "SELECT * FROM xf_message WHERE id = '{$_POST['message_id']}' ";
        $res   = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array(), 1040, $XF_error_code_info[1040]);
        }
        if ($res['status'] != 1) {
            $this->echoJson(array(), 1041, $XF_error_code_info[1041]);
        }
        if ($res['start_time'] > $time) {
            $this->echoJson(array(), 1042, $XF_error_code_info[1042]);
        }
        $add_ip = Yii::app()->request->userHostAddress;
        $sql    = "SELECT * FROM xf_message_detail WHERE message_id = {$res['id']} AND user_id = {$user_info['id']} ";
        $detail = $model->createCommand($sql)->queryRow();
        if ($detail['status'] == 2) {
            $sql    = "UPDATE xf_message_detail SET status = 1 , read_time = {$time} , read_ip = '{$add_ip}' WHERE id = {$detail['id']}";
            $update = $model->createCommand($sql)->execute();
        } elseif (empty($detail)) {
            $sql = "INSERT INTO xf_message_detail (message_id , user_id , status , read_time , read_ip) VALUES ({$res['id']} , {$user_info['id']} , 1 , {$time} , '{$add_ip}') ";
            $update = $model->createCommand($sql)->execute();
        }
        $result_data['id']         = $res['id'];
        $result_data['title']      = $res['title'];
        $result_data['abstract']   = $res['abstract'];
        $result_data['content']    = $res['content'];
        $result_data['start_time'] = $res['start_time'];
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }

    /**
     * 提交意见反馈
     */
    public function actionAddFeedback()
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
        if (empty($_POST['content'])) {
            $this->echoJson(array(), 1043, $XF_error_code_info[1043]);
        }
        // 校验IP
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $redis_key = "add_feedback_limit_by_IP_{$ip}";
        $check_IP  = $redis->get($redis_key);
        if ($check_IP >= $this->add_feedback_limit_by_IP) {
            $this->echoJson(array(), 1049, $XF_error_code_info[1049]);
        }
        // 检验总数
        $redis_key = "add_feedback_limit_by_ID_total_{$user_id}";
        $check_ID  = $redis->get($redis_key);
        if ($check_ID >= $this->add_feedback_limit_by_ID_total) {
            $this->echoJson(array(), 1050, $XF_error_code_info[1050]);
        }
        // 校验频率
        $redis_key = "add_feedback_limit_by_ID_{$user_id}";
        $check_ID  = $redis->get($redis_key);
        if ($check_ID >= $this->add_feedback_limit_by_ID) {
            $this->echoJson(array(), 1051, $XF_error_code_info[1051]);
        }
        // 校验内容
        $content = trim($_POST['content']);
        $old_data_array = array();
        for ($i = 0; $i < $this->check_feedback_content_limit_by_ID; $i++) {
            $redis_key = "check_feedback_content_limit_by_ID_{$user_id}_{$i}";
            $check_con = $redis->get($redis_key);
            if ($check_con) {
                $old_data_array[$i] = json_decode($check_con, true);
            }
        }
        $check_content = $this->checkFeedbackContent($content, $old_data_array);
        if (!$check_content) {
            $this->echoJson(array(), 1064, $XF_error_code_info[1064]);
        }
        $mobile = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
        $idno   = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
        $sql    = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$user_info['id']} AND verify_status = 1";
        $card   = Yii::app()->db->createCommand($sql)->queryRow();
        if (!empty($card['bankcard'])) {
            $bankcard = GibberishAESUtil::dec($card['bankcard'], Yii::app()->c->idno_key);
        } else {
            $bankcard = '';
        }
        $time = time();
        $add_ip = Yii::app()->request->userHostAddress;
        $sql = "INSERT INTO xf_feedback (user_id , user_real_name , user_mobile , user_idno , user_bankcard , add_time , add_ip , content , status , re_content) VALUES ({$user_info['id']} , '{$user_info['real_name']}' , '{$mobile}' , '{$idno}' , '{$bankcard}' , {$time} , '{$add_ip}' , '{$content}' , 1 , '')";
        $result = Yii::app()->db->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson(array(), 1044, $XF_error_code_info[1044]);
        }
        // 增加IP计数
        $redis_key = "add_feedback_limit_by_IP_{$ip}";
        $check_IP  = $redis->exists($redis_key);
        if ($check_IP) {
            $set_IP = $redis->incr($redis_key);
        } else {
            $set_IP = $redis->incr($redis_key);
            $set_IP = $redis->expireAt($redis_key, ($time + $this->add_feedback_limit_by_IP_reset));
        }
        if (!$set_IP) {
            $this->echoJson(array(), 1052, $XF_error_code_info[1052]);
        }
        // 增加总数计数
        $redis_key = "add_feedback_limit_by_ID_total_{$user_id}";
        $check_ID  = $redis->exists($redis_key);
        if ($check_ID) {
            $set_ID = $redis->incr($redis_key);
        } else {
            $set_ID = $redis->incr($redis_key);
            $set_ID = $redis->expireAt($redis_key, ($time + $this->add_feedback_limit_by_ID_total_reset));
        }
        if (!$set_ID) {
            $this->echoJson(array(), 1053, $XF_error_code_info[1053]);
        }
        // 增加频率计数
        $redis_key = "add_feedback_limit_by_ID_{$user_id}";
        $check_ID  = $redis->exists($redis_key);
        if ($check_ID) {
            $set_ID = $redis->incr($redis_key);
        } else {
            $set_ID = $redis->incr($redis_key);
            $set_ID = $redis->expireAt($redis_key, ($time + $this->add_feedback_limit_by_ID_reset));
        }
        if (!$set_ID) {
            $this->echoJson(array(), 1054, $XF_error_code_info[1054]);
        }
        // 增加校验内容
        $have = array();
        $none = array();
        for ($i = 0; $i < $this->check_feedback_content_limit_by_ID; $i++) {
            $redis_key = "check_feedback_content_limit_by_ID_{$user_id}_{$i}";
            $check_con = $redis->ttl($redis_key);
            if ($check_con > 0) {
                $have[$i] = $check_con;
            } else {
                $none[] = $i;
            }
        }
        $set_content = false;
        if (empty($have)) {
            $redis_key   = "check_feedback_content_limit_by_ID_{$user_id}_0";
            $redis_value = json_encode($this->FeedbackContent2array($content));
            $set_content = $redis->set($redis_key, $redis_value, $this->check_feedback_content_limit_by_ID_reset);
        } elseif (count($have) == $this->check_feedback_content_limit_by_ID) {
            $min_ttl     = min($have);
            $have        = array_flip($have);
            $min_num     = $have[$min_ttl];
            $redis_key   = "check_feedback_content_limit_by_ID_{$user_id}_{$min_num}";
            $redis_value = json_encode($this->FeedbackContent2array($content));
            $set_content = $redis->set($redis_key, $redis_value, $this->check_feedback_content_limit_by_ID_reset);
        } elseif (!empty($none)) {
            $redis_key   = "check_feedback_content_limit_by_ID_{$user_id}_{$none[0]}";
            $redis_value = json_encode($this->FeedbackContent2array($content));
            $set_content = $redis->set($redis_key, $redis_value, $this->check_feedback_content_limit_by_ID_reset);
        }
        if (!$set_content) {
            $this->echoJson(array(), 1065, $XF_error_code_info[1065]);
        }
        $this->echoJson(array(), 0, $XF_error_code_info[0]);
    }

    /**
     * 意见反馈列表
     */
    public function actionFeedbackList()
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
            $this->echoJson(array(), 1032, $XF_error_code_info[1032]);
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
            $this->echoJson(array(), 0, $XF_error_code_info[0]);
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

        header("Content-type:application/json; charset=utf-8");
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
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        if (empty($_POST['feedback_id']) || !is_numeric($_POST['feedback_id'])) {
            $this->echoJson(array(), 1046, $XF_error_code_info[1046]);
        }
        $model = Yii::app()->db;
        $time  = time();
        $sql   = "SELECT * FROM xf_feedback WHERE id = '{$_POST['feedback_id']}' AND user_id = {$user_info['id']} ";
        $res   = $model->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array(), 1047, $XF_error_code_info[1047]);
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
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
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
     * 获取用户兑换记录
     */
    public function actionExchangeList()
    {
        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, Yii::app()->c->XF_error_code_info[2005]);
        }
        //提测时放开
        // $this->user_id = 12131543;
        //校验用户登录状态
        if (!$this->user_id) {
            $this->echoJson([], 1016, Yii::app()->c->XF_error_code_info[1016]);
        }

        // 校验服务协议
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = {$this->user_id} AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$contract) {
            $this->echoJson(array(), 1048, Yii::app()->c->XF_error_code_info[1048]);
        }

        //所属平台信息校验
        if (isset($_POST['type']) && !in_array($_POST['type'], Yii::app()->c->xf_config['platform_type'])) {
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //所属渠道校验
        if (!empty($_POST['platform_no']) && !in_array($_POST['platform_no'], Yii::app()->c->xf_config['xf_shop'])) {
            $this->echoJson([], 2000, Yii::app()->c->XF_error_code_info[2000]);
        }

        //limit最大值50
        if (isset($_POST['limit']) && $_POST['limit']>50) {
            $this->echoJson([], 1032, Yii::app()->c->XF_error_code_info[1032]);
        }

        //默认值赋值
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
        $type = isset($_POST['type']) ? (int)$_POST['type'] : 1;
        if ($type == 1) {
            $db_name = 'db';
            $table = 'firstp2p';
        } elseif ($type == 2) {
            $db_name = 'phdb';
            $table = 'firstp2p';
        } elseif (in_array($type, Yii::app()->c->xf_config['offline_products'])) {
            $db_name = 'offlinedb';
            $table = 'offline';
        }

        //where条件
        $condition  = " where el.status=2 and el.user_id=:user_id ";
        if (!empty($_POST['platform_no'])) {
            $condition .= " and el.platform_no = {$_POST['platform_no']} ";
        }
        //总条数
        $count_exchange_sql = "select count(1) from {$table}_debt_exchange_log el $condition ";
        $total_log = Yii::app()->{$db_name}->createCommand($count_exchange_sql)->bindValues([':user_id'=>$this->user_id])->queryScalar();
        if ($total_log == 0) {
            $this->echoJson([], 0);
        }

        //兑换记录sql
        $offset = ($page - 1) * $limit;
        $list_exchange_sql = "select el.id, el.debt_account,dl.name as deal_name,el.addtime as exchange_time,el.platform_no, case el.platform_no
                              when 1 then '有解' else '其他' end as platform_no_tips 
                              from {$table}_debt_exchange_log el 
                              left join {$table}_deal dl on el.borrow_id=dl.id 
                              $condition order by el.id desc limit $offset,$limit ";
        $exchange_list = Yii::app()->{$db_name}->createCommand($list_exchange_sql)->bindValues([':user_id'=>$this->user_id])->queryAll();

        header("Content-type:application/json; charset=utf-8");
        $result_data['data'] = $exchange_list;
        $result_data['count'] = $total_log;
        $result_data['page_count'] = ceil($total_log / $limit);
        $result_data['code'] = 0;
        $result_data['info'] = '';
        echo exit(json_encode($result_data));
    }

    /**
     * 获取用户在途债权记录
     */
    public function actionLoanList()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        //校验用户登录状态
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, $XF_error_code_info[2005]);
        }
        // 校验服务协议
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = {$user_id} AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$contract) {
            $this->echoJson(array(), 1048, $XF_error_code_info[1048]);
        }

        //所属平台信息校验
        if (isset($_POST['type']) && !in_array($_POST['type'], Yii::app()->c->xf_config['platform_type'])) {
            $this->echoJson([], 2000, $XF_error_code_info[2000]);
        }

        //债转状态校验
        if (!empty($_POST['status']) && !in_array($_POST['status'], [1, 2, 3])) {
            $this->echoJson([], 2000, $XF_error_code_info[2000]);
        }

        //limit最大值50
        if (isset($_POST['limit']) && $_POST['limit']>50) {
            $this->echoJson([], 1032, $XF_error_code_info[1032]);
        }

        //默认值赋值
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
        $type = isset($_POST['type']) ? (int)$_POST['type'] : 1;
        if (in_array($type, array(1 , 2))) {
            $db_name = $type == 1 ? 'db' : 'phdb';

            //where条件
            $condition  = " left join firstp2p_deal dd on dd.id=dl.deal_id where dl.user_id=:user_id and dd.is_zdx=0 and dd.deal_status!=3  ";
            if ($_POST['status'] == 1) {
                $condition .= " and dl.status = 1 and dd.deal_status = 4 ";
            } elseif ($_POST['status'] == 2) {
                $condition .= " and dl.status IN (0, 2) ";
            } elseif ($_POST['status'] == 3) {
                $condition .= " and dl.status = 3 ";
            }

            //总条数
            $count_loan_sql = "select count(1) from firstp2p_deal_load dl $condition ";
            $total_loan = Yii::app()->{$db_name}->createCommand($count_loan_sql)->bindValues([':user_id'=>$this->user_id])->queryScalar();
            if ($total_loan == 0) {
                $this->echoJson([], 0);
            }

            //兑换记录sql
            $offset = ($page - 1) * $limit;
            $loan_list_sql = "select dl.id, dl.create_time,dl.money,dl.wait_interest ,dd.name as deal_name, dd.rate, dd.jys_record_number, dl.wait_capital, dl.status
                              from firstp2p_deal_load dl $condition group by dl.id order by dl.status = 3 desc, dl.status = 1 desc,dl.create_time desc limit $offset,$limit ";
            $loan_list = Yii::app()->{$db_name}->createCommand($loan_list_sql)->bindValues([':user_id'=>$this->user_id])->queryAll();
        } elseif (in_array($type, Yii::app()->c->xf_config['offline_products'])) {
            $where = " AND deal_load.platform_id = {$type} AND deal.platform_id = {$type} ";
            if ($_POST['status'] == 1) {
                $where .= " AND deal_load.status = 1 ";
            } elseif ($_POST['status'] == 2) {
                $where .= " AND deal_load.status IN (0, 2) ";
            } elseif ($_POST['status'] == 3) {
                $where .= " AND deal_load.status = 3 ";
            }
            $sql = "SELECT COUNT(deal_load.id) AS count 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_deal AS deal ON deal_load.deal_id = deal.id 
                    LEFT JOIN offline_deal_project AS project ON deal.project_id = project.id 
                    WHERE deal_load.user_id = {$user_id} {$where} ";
            $total_loan = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
            if ($total_loan == 0) {
                $this->echoJson([], 0);
            }
            $offset = ($page - 1) * $limit;
            $sql = "SELECT deal_load.id , deal_load.create_time , deal_load.money , deal_load.wait_interest , deal.name AS deal_name , deal.rate , deal.jys_record_number , deal_load.wait_capital , deal_load.status , project.max_rate 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_deal AS deal ON deal_load.deal_id = deal.id 
                    LEFT JOIN offline_deal_project AS project ON deal.project_id = project.id 
                    WHERE deal_load.user_id = {$user_id} {$where} 
                    ORDER BY deal_load.status = 3 DESC , deal_load.status = 1 DESC , deal_load.create_time DESC LIMIT $offset , $limit ";
            $loan_list = Yii::app()->offlinedb->createCommand($sql)->queryAll();
        }

        header("Content-type:application/json; charset=utf-8");
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

    /**
     * 投资记录详情
     */
    public function actionDealLoadInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        // 校验服务协议
        $sql = "SELECT * FROM xf_user_contract WHERE user_id = {$user_id} AND platform_no = 0 AND type = 1 ";
        $contract = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$contract) {
            $this->echoJson(array(), 1048, $XF_error_code_info[1048]);
        }
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson(array(), 1061, $XF_error_code_info[1061]);
        }
        $id = intval($_POST['id']);
        if (empty($_POST['platform_id']) || !is_numeric($_POST['platform_id']) || !in_array($_POST['platform_id'], array(1 , 2 , 3 , 4 , 5))) {
            $this->echoJson(array(), 1062, $XF_error_code_info[1062]);
        }
        $platform_id = intval($_POST['platform_id']);
        if (in_array($platform_id, array(1 , 2))) {

            // 尊享 & 普惠
            if ($platform_id == 1) {
                $db = 'db';
            } elseif ($platform_id == 2) {
                $db = 'phdb';
            }
            $sql = "SELECT deal_load.id , deal_load.create_time , deal_load.money , deal_load.wait_interest , deal.name AS deal_name , deal.rate , deal.jys_record_number , deal_load.wait_capital , deal_load.status , deal.loantype , deal.repay_time , deal.user_id 
                    FROM firstp2p_deal_load AS deal_load 
                    LEFT JOIN firstp2p_deal AS deal ON deal_load.deal_id = deal.id 
                    WHERE deal_load.id = {$id} AND deal_load.user_id = {$user_id} ";
            $res = Yii::app()->$db->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 1063, $XF_error_code_info[1063]);
            }
            $res['platform_id'] = $platform_id;
            $loantype[1] = '按季等额还款';
            $loantype[2] = '按月等额还款';
            $loantype[3] = '一次性还本付息';
            $loantype[4] = '按月付息一次还本';
            $loantype[5] = '按天一次性还款';
            $loantype[6] = '按季付息到期还本';
            if ($res['loantype'] == 5) {
                $res['repay_time'] .= '天';
            } else {
                $res['repay_time'] .= '个月';
            }
            $res['loantype'] = $loantype[$res['loantype']];
            $sql = "SELECT company_name , registration_address FROM firstp2p_enterprise WHERE user_id = {$res['user_id']} ";
            $company = Yii::app()->db->createCommand($sql)->queryRow();
            if ($company) {
                $res['company_name']         = $company['company_name'];
                $res['registration_address'] = $company['registration_address'];
            } else {
                $sql = "SELECT name , address FROM firstp2p_user_company WHERE user_id = {$res['user_id']} ";
                $name = Yii::app()->db->createCommand($sql)->queryRow();
                if ($name) {
                    $res['company_name']         = $name['name'];
                    $res['registration_address'] = $name['address'];
                } else {
                    $sql = "SELECT real_name FROM firstp2p_user WHERE id = {$res['user_id']} ";
                    $real_name = Yii::app()->db->createCommand($sql)->queryScalar();
                    if ($real_name) {
                        $res['company_name']         = $real_name;
                        $res['registration_address'] = '';
                    } else {
                        $res['company_name']         = '';
                        $res['registration_address'] = '';
                    }
                }
            }
            $this->echoJson($res, 0, $XF_error_code_info[0]);
        } elseif (in_array($platform_id, Yii::app()->c->xf_config['offline_products'])) {

            // 金融工场 & 智多新 & 交易所
            $sql = "SELECT deal_load.id , deal_load.create_time , deal_load.money , deal_load.wait_interest , deal.name AS deal_name , deal.rate , deal.jys_record_number , deal_load.wait_capital , deal_load.status , deal.loantype , deal.repay_time , deal.user_id , project.max_rate , deal.repayment_date , project.limit_type 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_deal AS deal ON deal_load.deal_id = deal.id 
                    LEFT JOIN offline_deal_project AS project ON deal.project_id = project.id 
                    WHERE deal_load.id = {$id} AND deal_load.user_id = {$user_id} AND deal_load.platform_id = {$platform_id} AND deal.platform_id = {$platform_id} ";
            $res = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 1063, $XF_error_code_info[1063]);
            }
            $res['platform_id'] = $platform_id;
            $loantype[1] = '按季等额还款';
            $loantype[2] = '按月等额还款';
            $loantype[3] = '一次性还本付息';
            $loantype[4] = '按月付息一次还本';
            $loantype[5] = '按天一次性还款';
            $loantype[6] = '按季付息到期还本';
            $loantype[9] = '半年付息到期还本';
            if ($res['loantype'] == 5 || ($res['loantype'] == 6 && $res['limit_type'] == 1) || ($res['loantype'] == 9 && $res['limit_type'] == 1)) {
                $res['repay_time'] .= '天';
            } else {
                $res['repay_time'] .= '个月';
            }
            $res['loantype'] = $loantype[$res['loantype']];
            $sql = "SELECT company_name , registration_address FROM firstp2p_enterprise WHERE user_id = {$res['user_id']} ";
            $company = Yii::app()->db->createCommand($sql)->queryRow();
            if ($company) {
                $res['company_name']         = $company['company_name'];
                $res['registration_address'] = $company['registration_address'];
            } else {
                $sql = "SELECT real_name FROM offline_user_platform WHERE user_id = {$res['user_id']} ";
                $real_name = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                if ($real_name) {
                    $res['company_name']         = $real_name;
                    $res['registration_address'] = '';
                } else {
                    $res['company_name']         = '';
                    $res['registration_address'] = '';
                }
            }
            $this->echoJson($res, 0, $XF_error_code_info[0]);
        }
    }

    /**
     * 提交手机号修改申请 - 校验用户信息
     */
    public function actionCheckUserInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 校验姓名
        $real_name = trim($_POST['name']);
        if (empty($real_name)) {
            $this->echoJson(array(), 1067, $XF_error_code_info[1067]);
        }
        // 校验身份证号
        $id_number = trim($_POST['id_number']);
        if (empty($id_number)) {
            $this->echoJson(array(), 1068, $XF_error_code_info[1068]);
        }
        $isMatched = preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $id_number);
        if ($isMatched === 0) {
            $this->echoJson(array(), 1076, $XF_error_code_info[1076]);
        }
        $idno = GibberishAESUtil::enc($id_number, Yii::app()->c->idno_key);
        // 校验手机号
        $old_mobile = trim($_POST['old_mobile']);
        if (!empty($old_mobile)) {
            if (!is_numeric($old_mobile)) {
                $this->echoJson(array(), 1077, $XF_error_code_info[1077]);
            }
            $mobile = GibberishAESUtil::enc($old_mobile, Yii::app()->c->idno_key);
        } else {
            $mobile = '';
        }
        $sql = "SELECT * FROM firstp2p_user WHERE real_name = '{$real_name}' AND idno = '{$idno}' AND mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 AND is_online = 1";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1081, $XF_error_code_info[1081]);
        }
        $sql = "SELECT * FROM xf_user_mobile_edit_log WHERE user_id = {$user_info['id']} AND status = 1 AND type IN (1, 2, 3) ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array(), 1098, $XF_error_code_info[1098]);
        }
        $this->echoJson(array(), 0, $XF_error_code_info[0]);
    }

    /**
     * 获取手机号修改申请短信验证码
     */
    public function actionGetSMSFromMobile()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 测试用手机号
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        // 校验IP发送限制
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $time      = time();
        foreach ($this->mobile_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "mobile_send_SMS_limit_by_IP_{$key}_{$ip}";
            $check_IP  = $redis->get($redis_key);
            if ($check_IP >= $value['limit']) {
                $this->echoJson(array(), 1000, $XF_error_code_info[1000]);
            }
        }
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array(), 1001, $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/', $number);
        if ($check_number === 0) {
            $this->echoJson(array(), 1002, $XF_error_code_info[1002]);
        }
        // 校验手机号发送限制
        if (!in_array($number, $xf_test_number) && !in_array($user_info['id'], $itouzi['debt_buyer_white_list'])) {
            foreach ($this->mobile_send_SMS_limit_by_number as $key => $value) {
                $redis_key    = "mobile_send_SMS_limit_by_number_{$key}_{$number}";
                $check_number = $redis->get($redis_key);
                if ($check_number >= $value['limit']) {
                    $this->echoJson(array(), 1003, $XF_error_code_info[1003]);
                }
            }
        }
        // 校验冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1004, $XF_error_code_info[1004]);
        }
        // 校验新手机号是否被使用
        $mobile    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if ($user_info) {
            $this->echoJson(array(), 1082, $XF_error_code_info[1082]);
        }
        // 校验新手机号是否在审核中
        $sql   = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$mobile}' AND status = 1 ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array(), 1086, $XF_error_code_info[1086]);
        }
        // 保存验证码
        $redis_key   = "XF_user_mobile_change_SMS_{$number}";
        if (in_array($number, $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis   = $redis->set($redis_key, $redis_value, $redis_time);
        if (!$set_redis) {
            $this->echoJson(array(), 1005, $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number, $xf_test_number)) {
            $remind['phone']         = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code']          = "change_phone_vcode";
            $send     = new XfSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 0) {
                $this->echoJson(array(), 1006, $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        foreach ($this->mobile_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "mobile_send_SMS_limit_by_IP_{$key}_{$ip}";
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
        foreach ($this->mobile_send_SMS_limit_by_number as $key => $value) {
            $redis_key    = "mobile_send_SMS_limit_by_number_{$key}_{$number}";
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
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $set_CD    = $redis->set($redis_key, $redis_value, $this->send_SMS_limit_by_CD);
        if (!$set_CD) {
            $this->echoJson(array(), 1009, $XF_error_code_info[1009]);
        }
        $this->echoJson(array('ttl' => 60), 0, $XF_error_code_info[0]);
    }

    /**
     * 交易所用户信息审核 - 获取手机短信验证码
     */
    public function actionAddJYSUserInfoGetSMS()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 测试用手机号
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];
        // 白名单
        $itouzi = Yii::app()->c->itouzi;
        // 校验IP发送限制
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $redis     = Yii::app()->rcache;
        $time      = time();
        foreach ($this->mobile_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "mobile_send_SMS_limit_by_IP_{$key}_{$ip}";
            $check_IP  = $redis->get($redis_key);
            if ($check_IP >= $value['limit']) {
                $this->echoJson(array(), 1000, $XF_error_code_info[1000]);
            }
        }
        // 校验手机号
        if (empty($_POST['number']) || !is_numeric($_POST['number'])) {
            $this->echoJson(array(), 1001, $XF_error_code_info[1001]);
        }
        $number       = trim($_POST['number']);
        $check_number = preg_match('/^1[3-9]\d{9}$/', $number);
        if ($check_number === 0) {
            $this->echoJson(array(), 1002, $XF_error_code_info[1002]);
        }
        // 校验手机号发送限制
        if (!in_array($number, $xf_test_number) && !in_array($user_info['id'], $itouzi['debt_buyer_white_list'])) {
            foreach ($this->mobile_send_SMS_limit_by_number as $key => $value) {
                $redis_key    = "mobile_send_SMS_limit_by_number_{$key}_{$number}";
                $check_number = $redis->get($redis_key);
                if ($check_number >= $value['limit']) {
                    $this->echoJson(array(), 1003, $XF_error_code_info[1003]);
                }
            }
        }
        // 校验冷却时间
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $check_CD  = $redis->ttl($redis_key);
        if ($check_CD > 0) {
            $this->echoJson(array('ttl' => $check_CD), 1004, $XF_error_code_info[1004]);
        }
        // 校验新手机号是否被使用
        $mobile    = GibberishAESUtil::enc($number, Yii::app()->c->idno_key);
        $sql       = "SELECT * FROM firstp2p_user WHERE mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if ($user_info) {
            $this->echoJson(array(), 1082, $XF_error_code_info[1082]);
        }
        // 校验新手机号是否在审核中
        $sql   = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$mobile}' AND status = 1 ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array(), 1086, $XF_error_code_info[1086]);
        }
        // 保存验证码
        $redis_key   = "add_jys_user_info_get_SMS_{$number}";
        if (in_array($number, $xf_test_number)) {
            $redis_value = 999999;
        } else {
            $redis_value = FunctionUtil::VerifyCode();
        }
        $redis_time  = 300;
        $set_redis   = $redis->set($redis_key, $redis_value, $redis_time);
        if (!$set_redis) {
            $this->echoJson(array(), 1005, $XF_error_code_info[1005]);
        }
        // 发送短信
        if (!in_array($number, $xf_test_number)) {
            $remind['phone']         = $number;
            $remind['data']['vcode'] = $redis_value;
            $remind['code']          = "change_phone_vcode";
            $send     = new XfSmsClass();
            $send_SMS = $send->sendToUserByPhone($remind);
            if ($send_SMS['code'] != 0) {
                $this->echoJson(array(), 1006, $XF_error_code_info[1006]);
            }
        }
        // 增加IP计数
        foreach ($this->mobile_send_SMS_limit_by_IP as $key => $value) {
            $redis_key = "mobile_send_SMS_limit_by_IP_{$key}_{$ip}";
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
        foreach ($this->mobile_send_SMS_limit_by_number as $key => $value) {
            $redis_key    = "mobile_send_SMS_limit_by_number_{$key}_{$number}";
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
        $redis_key = "send_SMS_limit_by_CD_{$number}";
        $set_CD    = $redis->set($redis_key, $redis_value, $this->send_SMS_limit_by_CD);
        if (!$set_CD) {
            $this->echoJson(array(), 1009, $XF_error_code_info[1009]);
        }
        $this->echoJson(array('ttl' => 60), 0, $XF_error_code_info[0]);
    }

    /**
     * 提交手机号修改申请（旧手机号不可用）
     */
    public function actionMobileChangeApply()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 校验IP限制
        $redis     = Yii::app()->rcache;
        $ip        = ip2long(Yii::app()->request->userHostAddress);
        $time      = time();
        $redis_key = "check_user_info_limit_by_IP_{$ip}";
        $check_IP  = $redis->get($redis_key);
        if ($check_IP >= $this->check_user_info_limit_by_IP) {
            $this->echoJson(array(), 1096, $XF_error_code_info[1096]);
        }
        // 校验姓名
        $real_name = trim($_POST['name']);
        if (empty($real_name)) {
            $this->echoJson(array(), 1067, $XF_error_code_info[1067]);
        }
        // 校验身份证号
        $id_number = trim($_POST['id_number']);
        if (empty($id_number)) {
            $this->echoJson(array(), 1068, $XF_error_code_info[1068]);
        }
        $isMatched = preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $id_number);
        if ($isMatched === 0) {
            $this->echoJson(array(), 1076, $XF_error_code_info[1076]);
        }
        $idno = GibberishAESUtil::enc($id_number, Yii::app()->c->idno_key);
        // 校验旧手机号
        $old_mobile = trim($_POST['old_mobile']);
        if (!empty($old_mobile)) {
            if (!is_numeric($old_mobile)) {
                $this->echoJson(array(), 1077, $XF_error_code_info[1077]);
            }
            $mobile = GibberishAESUtil::enc($old_mobile, Yii::app()->c->idno_key);
        } else {
            $mobile = '';
        }
        // 校验新手机号
        $new_mobile = trim($_POST['new_mobile']);
        if (empty($new_mobile)) {
            $this->echoJson(array(), 1070, $XF_error_code_info[1070]);
        }
        $check_number = preg_match('/^1[3-9]\d{9}$/', $new_mobile);
        if ($check_number === 0) {
            $this->echoJson(array(), 1078, $XF_error_code_info[1078]);
        }
        if ($new_mobile == $old_mobile) {
            $this->echoJson(array(), 1080, $XF_error_code_info[1080]);
        }
        $new_mobile_str = GibberishAESUtil::enc($new_mobile, Yii::app()->c->idno_key);
        // 校验新手机号是否被使用
        $sql        = "SELECT * FROM firstp2p_user WHERE mobile = '{$new_mobile_str}' AND is_effect = 1 AND is_delete = 0 ";
        $check_user = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check_user) {
            $this->echoJson(array(), 1082, $XF_error_code_info[1082]);
        }
        // 校验新手机号是否在审核中
        $sql   = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$new_mobile_str}' AND status = 1 AND type IN (1, 2, 3) ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array(), 1086, $XF_error_code_info[1086]);
        }
        // 校验验证码
        $code = trim($_POST['new_mobile_code']);
        if (empty($code)) {
            $this->echoJson(array(), 1071, $XF_error_code_info[1071]);
        }
        $check_code = preg_match('/^\d{6}$/', $code);
        if ($check_code === 0) {
            $this->echoJson(array(), 1079, $XF_error_code_info[1079]);
        }
        $redis_key = "XF_user_mobile_change_SMS_{$new_mobile}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array(), 1012, $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array(), 1083, $XF_error_code_info[1083]);
        }
        // 校验4张照片
        $id_pic_front   = trim($_POST['id_pic_front']);
        $id_pic_back    = trim($_POST['id_pic_back']);
        $user_pic_front = trim($_POST['user_pic_front']);
        $user_pic_back  = trim($_POST['user_pic_back']);
        if (empty($id_pic_front)) {
            $this->echoJson(array(), 1072, $XF_error_code_info[1072]);
        }
        if (empty($id_pic_back)) {
            $this->echoJson(array(), 1073, $XF_error_code_info[1073]);
        }
        if (empty($user_pic_front)) {
            $this->echoJson(array(), 1074, $XF_error_code_info[1074]);
        }
        if (empty($user_pic_back)) {
            $this->echoJson(array(), 1075, $XF_error_code_info[1075]);
        }
        $id_pic_front_res   = $this->upload_base64($id_pic_front);
        $id_pic_back_res    = $this->upload_base64($id_pic_back);
        $user_pic_front_res = $this->upload_base64($user_pic_front);
        $user_pic_back_res  = $this->upload_base64($user_pic_back);
        if (!$id_pic_front_res) {
            $this->echoJson(array(), 1087, $XF_error_code_info[1087]);
        }
        if (!$id_pic_back_res) {
            $this->echoJson(array(), 1088, $XF_error_code_info[1088]);
        }
        if (!$user_pic_front_res) {
            $this->echoJson(array(), 1089, $XF_error_code_info[1089]);
        }
        if (!$user_pic_back_res) {
            $this->echoJson(array(), 1090, $XF_error_code_info[1090]);
        }
        $id_pic_front_oss   = $this->upload_oss('./'.$id_pic_front_res['pic_address'], 'user_mobile_edit/'.$id_pic_front_res['pic_name']);
        $id_pic_back_oss    = $this->upload_oss('./'.$id_pic_back_res['pic_address'], 'user_mobile_edit/'.$id_pic_back_res['pic_name']);
        $user_pic_front_oss = $this->upload_oss('./'.$user_pic_front_res['pic_address'], 'user_mobile_edit/'.$user_pic_front_res['pic_name']);
        $user_pic_back_oss  = $this->upload_oss('./'.$user_pic_back_res['pic_address'], 'user_mobile_edit/'.$user_pic_back_res['pic_name']);
        if ($id_pic_front_oss === false) {
            $this->echoJson(array(), 1091, $XF_error_code_info[1091]);
        }
        if ($id_pic_back_oss === false) {
            $this->echoJson(array(), 1092, $XF_error_code_info[1092]);
        }
        if ($user_pic_front_oss === false) {
            $this->echoJson(array(), 1093, $XF_error_code_info[1093]);
        }
        if ($user_pic_back_oss === false) {
            $this->echoJson(array(), 1094, $XF_error_code_info[1094]);
        }
        $sql = "SELECT * FROM firstp2p_user WHERE real_name = '{$real_name}' AND idno = '{$idno}' AND mobile = '{$mobile}' AND is_effect = 1 AND is_delete = 0 AND is_online = 1";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            // 增加IP计数
            $redis_key = "check_user_info_limit_by_IP_{$ip}";
            $check_IP  = $redis->exists($redis_key);
            if ($check_IP) {
                $set_IP = $redis->incr($redis_key);
            } else {
                $set_IP = $redis->incr($redis_key);
                $set_IP = $redis->expireAt($redis_key, ($time + $this->check_user_info_limit_by_IP_reset));
            }
            if (!$set_IP) {
                $this->echoJson(array(), 1095, $XF_error_code_info[1095]);
            }
            $this->echoJson(array(), 1081, $XF_error_code_info[1081]);
        }
        $sql = "INSERT INTO xf_user_mobile_edit_log (user_id , real_name , idno , old_mobile , new_mobile , status , type , id_pic_front , id_pic_back , user_pic_front , user_pic_back , add_time) VALUES ({$user_info['id']} , '{$user_info['real_name']}' , '{$user_info['idno']}' , '{$user_info['mobile']}' , '{$new_mobile_str}' , 1 , 2 , '/user_mobile_edit/{$id_pic_front_res['pic_name']}' , '/user_mobile_edit/{$id_pic_back_res['pic_name']}' , '/user_mobile_edit/{$user_pic_front_res['pic_name']}' , '/user_mobile_edit/{$user_pic_back_res['pic_name']}' , {$time}) ";
        $result = Yii::app()->db->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson(array(), 1084, $XF_error_code_info[1084]);
        }
        $this->echoJson(array(), 0, $XF_error_code_info[0]);
    }

    /**
     * 直接修改手机号（旧手机号可用）
     */
    public function actionMobileChange()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        // 校验修改次数
        $redis     = Yii::app()->rcache;
        $redis_key = "change_mobile_limit_by_ID_{$user_id}";
        $check_ID  = $redis->get($redis_key);
        if ($check_ID >= $this->change_mobile_limit_by_ID) {
            $this->echoJson(array(), 1097, $XF_error_code_info[1097]);
        }
        $sql       = "SELECT * FROM firstp2p_user WHERE id = {$user_id} ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        // 校验新手机号
        $new_mobile = trim($_POST['new_mobile']);
        if (empty($new_mobile)) {
            $this->echoJson(array(), 1070, $XF_error_code_info[1070]);
        }
        $check_number = preg_match('/^1[3-9]\d{9}$/', $new_mobile);
        if ($check_number === 0) {
            $this->echoJson(array(), 1078, $XF_error_code_info[1078]);
        }
        $new_mobile_str = GibberishAESUtil::enc($new_mobile, Yii::app()->c->idno_key);
        if ($new_mobile_str == $user_info['mobile']) {
            $this->echoJson(array(), 1080, $XF_error_code_info[1080]);
        }
        // 校验新手机号是否被使用
        $sql        = "SELECT * FROM firstp2p_user WHERE mobile = '{$new_mobile_str}' AND is_effect = 1 AND is_delete = 0 ";
        $check_user = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check_user) {
            $this->echoJson(array(), 1082, $XF_error_code_info[1082]);
        }
        // 校验新手机号是否在审核中
        $sql   = "SELECT * FROM xf_user_mobile_edit_log WHERE new_mobile = '{$new_mobile_str}' AND status = 1 AND type IN (1, 2, 3) ";
        $check = Yii::app()->db->createCommand($sql)->queryRow();
        if ($check) {
            $this->echoJson(array(), 1086, $XF_error_code_info[1086]);
        }
        // 校验验证码
        $code = trim($_POST['new_mobile_code']);
        if (empty($code)) {
            $this->echoJson(array(), 1071, $XF_error_code_info[1071]);
        }
        $check_code = preg_match('/^\d{6}$/', $code);
        if ($check_code === 0) {
            $this->echoJson(array(), 1079, $XF_error_code_info[1079]);
        }
        $redis_key = "XF_user_mobile_change_SMS_{$new_mobile}";
        $data      = $redis->get($redis_key);
        if (!$data) {
            $this->echoJson(array(), 1012, $XF_error_code_info[1012]);
        }
        if ($code != $data) {
            $this->echoJson(array(), 1083, $XF_error_code_info[1083]);
        }
        $time  = time();
        $model_a = Yii::app()->db;
        $model_b = Yii::app()->offlinedb;
        $model_c = Yii::app()->phdb;
        $model_a->beginTransaction();
        $model_b->beginTransaction();
        $model_c->beginTransaction();

        $sql = "UPDATE firstp2p_user SET mobile = '{$new_mobile_str}' , update_time = {$time} WHERE id = {$user_id} ";
        $update_user = $model_a->createCommand($sql)->execute();

        $sql = "INSERT INTO xf_user_mobile_edit_log (user_id , real_name , idno , old_mobile , new_mobile , status , type , add_time) VALUES ({$user_info['id']} , '{$user_info['real_name']}' , '{$user_info['idno']}' , '{$user_info['mobile']}' , '{$new_mobile_str}' , 2 , 3 , {$time}) ";
        $add_log = $model_a->createCommand($sql)->execute();

        $update_user_ph = true;
        $sql = "SELECT * FROM firstp2p_user WHERE id = {$user_info['id']} ";
        $user_ph = $model_c->createCommand($sql)->queryRow();
        if ($user_ph && $user_ph['mobile'] != $new_mobile_str) {
            $sql = "UPDATE firstp2p_user SET mobile = '{$new_mobile_str}' , update_time = {$time} WHERE id = {$user_info['id']} ";
            $update_user_ph = $model_c->createCommand($sql)->execute();
        }

        $update_recharge_withdraw = true;
        $sql = "SELECT * FROM xf_user_recharge_withdraw WHERE user_id = {$user_info['id']} ";
        $recharge_withdraw = $model_a->createCommand($sql)->queryRow();
        if ($recharge_withdraw && $recharge_withdraw['mobile'] != $new_mobile_str) {
            $sql = "UPDATE xf_user_recharge_withdraw SET mobile = '{$new_mobile_str}' WHERE user_id = {$user_info['id']} ";
            $update_recharge_withdraw = $model_a->createCommand($sql)->execute();
        }

        $update_user_platform = true;
        $sql = "SELECT * FROM offline_user_platform WHERE user_id = {$user_info['id']} ";
        $user_platform = $model_b->createCommand($sql)->queryRow();
        if ($user_platform && $user_platform['phone'] != $new_mobile_str) {
            $sql = "UPDATE offline_user_platform SET phone = '{$new_mobile_str}' WHERE user_id = {$user_info['id']} ";
            $update_user_platform = $model_b->createCommand($sql)->execute();
        }

        // 增加修改次数
        $redis_key = "change_mobile_limit_by_ID_{$user_id}";
        $check_ID  = $redis->exists($redis_key);
        if ($check_ID) {
            $set_ID = $redis->incr($redis_key);
        } else {
            $set_ID = $redis->incr($redis_key);
            $set_ID = $redis->expireAt($redis_key, ($time + $this->change_mobile_limit_by_ID_reset));
        }

        if (!$update_user || !$add_log || !$set_ID || !$update_user_ph || !$update_recharge_withdraw || !$update_user_platform) {
            $model_a->rollback();
            $model_b->rollback();
            $model_c->rollback();
            $this->echoJson(array(), 1085, $XF_error_code_info[1085]);
        }
        $model_a->commit();
        $model_b->commit();
        $model_c->commit();
        $this->echoJson(array(), 0, $XF_error_code_info[0]);
    }

    /**
     * 投资记录审核 - 详情
     */
    public function actionDealLoadAuditInfo()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson(array(), 1061, $XF_error_code_info[1061]);
        }
        $id = intval($_POST['id']);
        if (empty($_POST['platform_id']) || !is_numeric($_POST['platform_id']) || !in_array($_POST['platform_id'], [5])) {
            $this->echoJson(array(), 1062, $XF_error_code_info[1062]);
        }
        $platform_id = intval($_POST['platform_id']);
        if ($platform_id === 5) {
            $sql = "SELECT * FROM offline_deal_load WHERE platform_id = {$platform_id} AND id = {$id} AND user_id = {$user_id} ";
            $info = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$info) {
                $this->echoJson(array(), 1063, $XF_error_code_info[1063]);
            }
            $sql = "SELECT * FROM offline_deal_load_audit WHERE platform_id = {$platform_id} AND deal_load_id = {$info['id']} AND user_id = {$user_id} AND status != 0 ";
            $audit = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            $result['id']          = $info['id'];
            $result['platform_id'] = $platform_id;
            if ($audit) {
                $result['status']  = $audit['status'];
                $result['reason']  = $audit['reason'];
                $result['number']  = $audit['contract_number'];
                $pic_address       = json_decode($audit['pic_address_json'], true);
                $result['picture'] = array();
                foreach ($pic_address as $key => $value) {
                    $result['picture'][] = Yii::app()->c->oss_preview_address.$value;
                }
            } else {
                $result['status']  = 0;
                $result['reason']  = '';
                $result['number']  = '';
                $result['picture'] = array();
            }
            $this->echoJson($result, 0, $XF_error_code_info[0]);
        }
    }

    /**
     * 投资记录审核 - 上传
     */
    public function actionDealLoadAuditUpload()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->echoJson(array(), 1061, $XF_error_code_info[1061]);
        }
        $id = intval($_POST['id']);
        if (empty($_POST['platform_id']) || !is_numeric($_POST['platform_id']) || !in_array($_POST['platform_id'], [5])) {
            $this->echoJson(array(), 1062, $XF_error_code_info[1062]);
        }
        $platform_id = intval($_POST['platform_id']);
        if (empty($_POST['number'])) {
            $this->echoJson(array(), 1100, $XF_error_code_info[1100]);
        }
        $contract_number = trim($_POST['number']);
        if (empty($_POST['picture']) || !is_array($_POST['picture'])) {
            $this->echoJson(array(), 1101, $XF_error_code_info[1101]);
        }
        if (count($_POST['picture']) > 9) {
            $this->echoJson(array(), 1108, $XF_error_code_info[1108]);
        }
        $time = time();
        if ($platform_id === 5) {
            $sql = "SELECT * FROM offline_deal_load WHERE platform_id = {$platform_id} AND id = {$id} AND user_id = {$user_id} ";
            $info = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if (!$info) {
                $this->echoJson(array(), 1063, $XF_error_code_info[1063]);
            }
            if ($info['status'] != 3) {
                $this->echoJson(array(), 1099, $XF_error_code_info[1099]);
            }
            $sql = "SELECT * FROM offline_deal_load_audit WHERE platform_id = {$platform_id} AND deal_load_id = {$info['id']} AND user_id = {$user_id} AND status != 0 ";
            $audit = Yii::app()->offlinedb->createCommand($sql)->queryRow();
            if ($audit) {
                // 修改
                if ($audit['status'] == 2) {
                    $this->echoJson(array(), 1103, $XF_error_code_info[1103]);
                }
                $pic_address = array();
                foreach ($_POST['picture'] as $key => $value) {
                    $temp = $this->upload_base64(trim($value));
                    if ($temp) {
                        $pic_address[] = $temp;
                    } else {
                        $this->echoJson(array(), 1104, $XF_error_code_info[1104]);
                    }
                }
                $pic_oss_address = array();
                foreach ($pic_address as $key => $value) {
                    $temp = $this->upload_oss('./'.$value['pic_address'], 'deal_load_audit/'.$value['pic_name']);
                    if ($temp === false) {
                        $this->echoJson(array(), 1105, $XF_error_code_info[1105]);
                    } else {
                        $pic_oss_address[] = '/deal_load_audit/'.$value['pic_name'];
                    }
                }
                $pic_address_json = json_encode($pic_oss_address);
                $sql = "UPDATE offline_deal_load_audit SET status = 1 , update_time = {$time} , contract_number = '{$contract_number}' , pic_address_json = '{$pic_address_json}' WHERE id = {$audit['id']} AND status = {$audit['status']} AND update_time = {$audit['update_time']} ";
                $result = Yii::app()->offlinedb->createCommand($sql)->execute();
            } else {
                // 新增
                $pic_address = array();
                foreach ($_POST['picture'] as $key => $value) {
                    $temp = $this->upload_base64(trim($value));
                    if ($temp) {
                        $pic_address[] = $temp;
                    } else {
                        $this->echoJson(array(), 1104, $XF_error_code_info[1104]);
                    }
                }
                $pic_oss_address = array();
                foreach ($pic_address as $key => $value) {
                    $temp = $this->upload_oss('./'.$value['pic_address'], 'deal_load_audit/'.$value['pic_name']);
                    if ($temp === false) {
                        $this->echoJson(array(), 1105, $XF_error_code_info[1105]);
                    } else {
                        $pic_oss_address[] = '/deal_load_audit/'.$value['pic_name'];
                    }
                }
                $pic_address_json = json_encode($pic_oss_address);
                $sql = "INSERT INTO offline_deal_load_audit (platform_id , deal_load_id , user_id , status , add_time , update_time , contract_number , pic_address_json) VALUES ({$platform_id} , {$info['id']} , {$user_id} , 1 , {$time} , {$time} , '{$contract_number}' , '{$pic_address_json}') ";
                $result = Yii::app()->offlinedb->createCommand($sql)->execute();
            }
            if ($result) {
                $this->echoJson(array(), 0, $XF_error_code_info[0]);
            } else {
                $this->echoJson(array(), 1102, $XF_error_code_info[1102]);
            }
        }
    }


    /**
     * 先锋给下车商城提供授权登录code
     */
    public function actionGetUserAuthCode()
    {
        if (!$this->user_id) {
            $this->echoJson(array(), 100, '登录超时，请重新登录');
        }

        if (!XfDebtExchangeUserAllowList::checkUserAllowByOpenid($this->user_id, $this->special_area_app_id)) {
            $this->echoJson([], 100, '陆续开放中，敬请期待');
        }

        $debtInfo = (new AboutUserDebtV2($this->user_id, $this->special_area_app_id))->getUserXcAmount($this->user_id);

        $sql = 'select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status in (1,2) AND platform_no=:platform_no';
        $exchangeOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->bindValues([':platform_no' => $this->special_area_app_id])->queryRow();
        if ($debtInfo['code'] && !$exchangeOrders) {
            $this->echoJson([], 100, '陆续开放中，敬请期待');
        }

        $codeInfo = AuthCodeUtil::makeCode($this->user_id, 'AL');
        if ($codeInfo) {
            $data['auth_code'] = $codeInfo;
            $this->echoJson($data, 0, 'success');
        }
        $this->echoJson(array(), 100, '网络错误，请稍后重试');
    }

    /**
     * 接收用户上行推送的短信内容
     */
    public function actionGetUpUserSmg()
    {
        //请求方式校验
        if (!isset($_GET)) {
            Yii::log("GetUpUserSmg 10000", 'error');
            $this->echoJson(array(), 10000, '请求方式有误');
        }

        //约定扩展码校验
        $xf_sms_accountId = ConfUtil::get("xf_sms_accountId");
        if ($_GET['Extend'] != 80002 || $_GET['AccountId'] != $xf_sms_accountId) {
            Yii::log("GetUpUserSmg Extend != 80002", 'error');
            $this->echoJson(array(), 10001, '扩展码或账号有误');
        }

        //传送数据有误
        $msgid = $_GET['MsgId'];
        $up_num = $_GET['Up_YourNum'];
        $up_servnum = $_GET['Up_UserTel'];
        $up_msg = $_GET['Up_UserMsg'];
        if (empty($msgid) || empty($up_num) || empty($up_servnum) || empty($up_msg)) {
            Yii::log("GetUpUserSmg params error", 'error');
            $this->echoJson(array(), 10002, '传送数据有误');
        }

        //获取用户ID
        $enc_up_servnum = GibberishAESUtil::enc($up_servnum, Yii::app()->c->idno_key);
        $sql = "SELECT * FROM firstp2p_user WHERE mobile = '{$enc_up_servnum}' ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if ($user_info) {
            $sms_log['user_id'] = $user_info['id'];
        }

        //短信数据记录user_sms_log
        $sms_log['msg_id'] = $msgid;
        $sms_log['type'] = 2;
        $sms_log['send_mobile'] = $up_servnum;
        $sms_log['receive_mobile'] = $up_num;
        $sms_log['content'] = $up_msg;
        $sms_log['add_time'] = time();
        $ret = BaseCrudService::getInstance()->add('UserSmsLog', $sms_log);
        if (false == $ret) {//添加失败
            Yii::log("GetUpUserSmg add error, data: ".print_r($sms_log, true), 'error');
            $this->echoJson(array(), 10003, '数据记录失败');
        }
        $this->echoJson(array(), 0, '数据接收成功');
    }


    /**
     * 异步回调统一地址
     */
    public function actionYopApi(){
        /*
        $_POST = array(
            'response' => 'OFGAmGoNp15T89ctu9uQa5JFet0tGNv9vltIY2ZqnvfE9AxgbVH8j9VWyfFv39spIjm6CtKRfZQqvxqLMXurvpEjC_eEc9zDoZncTFza_CMJ8MWhQR_VgLhG_suGM_Xtakip3qRxtiDxVxGOVTTwK_5i3hyN4gE25aZHsYkKOy0LbVUWI0foddc3TB807vNliOpMma_iXb6sBJ8piEhSeraRy54v6Vm6vs6N0b1duGh3NoINO_4v0UU5W5hLyd9T0vgYg2F_KEAYIgURKsxo4zTsB6y7mqM8TxVHqncJTD2Lk4aU6bgmaDItawHA1aKK0XyAmC5Xf2iKIVq3_OPSAA$hzqkPrK6MY4CBiX-Aq3R6geZFy_G5WLHTXtHFGW-IEtuUHW31rwCUW-qBkBqe75p-OpitCwS5IRdj1xJhZlWMspcNQhKo8SiO4GZA1CODIX8Rg_EBCNv_Jl0xGZjdz0srxuCmYR_GFjTa1VaTaz-NR84GqgwJSLr6MiXq0YPoa77-F2nqC7QwKTxnhlLQe_RMNg0SnDQbtxQw408CXXtrNJoV79hTsqujTltgb2EJ0eLUB8HJqbxbuXgl_iYb0V-nsFZS-j9DfIrMthRprXnAcFXCQBY0KLac9X7sbJNwFofQ3iaeNnhj3TWcvzpi5oc87Qg_ILWfV9V7uaXXy9w1VX4-Ec4QAMVzMvNF7A3iRx57YUN3mM7wQQ0cddCxfdyqJN-ujOxwIJRRawUYxsIERPFpmusLhZYvwUogVEQmHtUiiF9yT_lDVREY5RN4Fu3AeR1TEx3k5IMnQtgg6gItynVLZ7fNgZwHiLSDghDRzrlXKWTTfmKCEf4Cbpym1YfOlxD8WEddTVyr3bYF6pK0gUyg5ddhC3aMuE9SWm6_9NvXPy8ll5GZr0yIaGjPBE53u5w_gTeYCZ8qcIT6a3brvEDJZXoIYDWbQyIY6x2rvvhRR9P27GIPrA3kI4KsPxYg9z_P-ysQ9YNUCh8NOvxHKqfmuyxd7THxUTwqgA8-635kXhwXWJGMEIjB5uNYKgTZdBtuxYeSBFFHY_6MErEMU_yx7xSNzrDyEyc4FOJil18CJjIR8P42nNAj2-dK0N51DrVEdtO4Etlv76tnPBCvXy4nDE-Z1XLFkSEnxE2-iK60aoNZf443LcWtccDGUNRf0pZpH8X_e8r3zHTjUN0NA$AES$SHA256',
            'customerIdentification' => 'app_10013183371'
        );*/

        //校验回调数据
        Yii::log(" actionYopApi post_data : " . print_r($_POST, true), 'info');
        $app_key = YopConfig::APP_KEY;
        if(!$app_key || !isset($_POST) || $_POST['customerIdentification'] != $app_key){
            Yii::log(" actionYopApi post data error or customerIdentification error" , 'error');
            exit();
        }
        try {
            //解密
            $response = $_POST["response"];
            $decrypt_data = YopSignUtils::decrypt($response, YopConfig::PRIVATE_KEY, YopConfig::PUBLIC_KEY);
            Yii::log(" POST   decrypt_data : " . $decrypt_data, 'info');
            $decrypt_data = json_decode($decrypt_data);
            $result = $this->object_array($decrypt_data);

            //查询数据
            $repay_sql = "SELECT * FROM firstp2p_deal_repay WHERE last_yop_requestno='{$result['requestno']}' and last_yop_repay_status=1  ";
            $loan_repay_info = Yii::app()->rcms->createCommand($repay_sql)->queryRow();
            if (empty($loan_repay_info)) {
                Yii::log(" last_yop_requestno[{$result['requestno']}] loan_repay_info is empty ", 'error');
                exit;
            }

            //扣款成功  变更处理中数据
            $edit_data = [];
            $now_time = time();
            $edit_data['id'] = $loan_repay_info['id'];
            $edit_data['last_yop_return_remark'] = json_encode($result);
            $edit_data['errormsg'] = $result['errormsg'] ?: '';
            $edit_data['last_yop_repay_status'] = self::$repay_status[$result['status']] ?: 10;
            if($edit_data['last_yop_repay_status'] == 2){
                $edit_data['paid_principal_time'] = $now_time;
                $edit_data['paid_principal'] = $loan_repay_info['new_principal'];

                //待还利息为0的数据执行更新还款完成
                if($loan_repay_info['new_interest'] == 0){
                    $edit_data['true_repay_time'] = $now_time;
                    $edit_data['status'] = 1;
                }
            }

            //更新repay 记录易宝返回数据
            $changeLogRet = BaseCrudService::getInstance()->update("Firstp2pDealRepay", $edit_data, "id");
            if (!$changeLogRet) {
                Yii::log(" last_yop_requestno[{$result['requestno']}] repayLoanRepay: firstp2p_deal_repay[{$loan_repay_info['id']}] update error, edit_data:" . print_r($edit_data, true), 'error');
                exit;
            }

            //查询还款用户数
            if($loan_repay_info['distribution_id'] > 0){
                $user_sql = "SELECT sum(paid_principal) as s_paid_principal , count(distinct  user_id) as c_user_id FROM firstp2p_deal_repay WHERE distribution_id={$loan_repay_info['distribution_id']}  and type=1 and last_yop_repay_status=2  and paid_principal>0";
                $loan_user_info = Yii::app()->rcms->createCommand($user_sql)->queryRow();
                if(!$loan_user_info){
                    Yii::log(" last_yop_requestno[{$result['requestno']}] repayLoanRepay: distribution_id[{$loan_repay_info['distribution_id']}] error ", 'error');
                }else{
                    $distribution_info = Firstp2pBorrowerDistribution::model()->findByPk($loan_repay_info['distribution_id']);
                    if($distribution_info){
                        $distribution_info->total_repay_amount = $loan_user_info['s_paid_principal'];
                        $distribution_info->total_repay_num = $loan_user_info['c_user_id'];
                        if(!$distribution_info->save()){
                            Yii::log(" last_yop_requestno[{$result['requestno']}] repayLoanRepay: Firstp2pBorrowerDistribution[{$loan_repay_info['distribution_id']}] edit error ", 'error');
                        }
                    }
                }
            }


            //扣款成功时触发短信
			/*
            if($edit_data['last_yop_repay_status'] == 2){
                //发送短信通知
                $remind = array();
                $remind['sms_code'] = "XXXXX";
                $remind['mobile'] = $this->getPhone($loan_repay_info['user_id']);
                $remind['data']['deal_name'] = $this->getDealname($loan_repay_info['deal_id']);
                $remind['data']['repay_amount'] = $edit_data['paid_principal'];
                $remind['data']['repay_time'] = date($loan_repay_info['repay_time']);
                $smaClass = new XfSmsClass();
                $send_ret = $smaClass->sendToUserByPhone($remind);
                if($send_ret['code'] != 0){
                    Yii::log("actionYopApi user_id:{$loan_repay_info['user_id']}, sendToUser error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
                }
            }*/

            Yii::log(" actionYopApi end ", 'info');
        } catch (Exception $ee) {
            Yii::log("actionYopApi Exception,error_msg:".print_r($ee->getMessage(),true), 'error');
        }
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

    public static $repay_status = [
        'PAY_FAIL'=>3,
        'PROCESSING'=>1,
        'TIME_OUT'=>4,
        'FAIL'=>5,
        'PAY_SUCCESS'=>2,
        'TO_VALIDATE'=>6,
    ];

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
     * 法大大回调统一地址
     */
    public function actionFddApi(){
        Yii::log(' action  actionFddApi params : '.print_r($_REQUEST, true), 'info', __FUNCTION__);
        $return_data = [
            'fdd_real_status' => 0,
            'sign_contract_status' => 0,
        ];


        //实名认证回调数据
         if(!empty($_GET) && !empty($_GET['transactionNo']) && !empty($_GET['personName']) && !empty($_GET['status']) && !empty($_GET['authenticationType']) && !empty($_GET['sign'])){
             Yii::log(" actionFddApi get " . print_r($_GET, true), 'info');
             //验签
             $check_result = XfFddService::getInstance()->checkRealSign($_GET['transactionNo'], $_GET['personName'], $_GET['status'], $_GET['authenticationType']);
             if($check_result != $_GET['sign']){
                 Yii::log(" actionFddApi 非法请求 ", 'error');
                 $this->echoJson($return_data, 3018, Yii::app()->c->XF_error_code_info[3018]);
             };
             //查询实名认证流水号
             $user_sql = "SELECT id,idno,real_name,mobile,yj_fdd_customer_id,fdd_real_status,fdd_real_src,is_displace,intensive_sign_status FROM firstp2p_user WHERE fdd_real_transaction_no = '{$_GET['transactionNo']}'  ";
             $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
             if(!$user_info  ){
                 Yii::log(" actionFddApi 实名认证流水号不存在 ", 'error');
                 $this->echoJson($return_data, 3019, Yii::app()->c->XF_error_code_info[3019]);
             }
             //跳转到来源页面
             $now_time = time();
             if(!empty($user_info['fdd_real_src'])){
                 if($user_info['fdd_real_status'] == 2 && $_GET['status'] == 2){
                     $update_sql = "update firstp2p_user set  fdd_real_time={$now_time},fdd_real_status=1   where id = {$user_info['id']}";
                     $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
                     if (!$edit_fdd) {
                         Yii::log(" actionFddApi 更新实名认证状态失败 ", 'error');
                     }
                 }
                 $real_src = urldecode($user_info['fdd_real_src']);

                 $smrz_fdd_url = Yii::app()->c->contract['smrz_fdd_url'];
                 //积分兑换的实名认证流程不变
                 if($real_src != $smrz_fdd_url[1]){
                     header("location:{$real_src}");
                     exit;
                 }

                 //普惠在途本金
                 $sql = "SELECT sum(wait_capital) as wait_capital FROM firstp2p_deal_load WHERE user_id = {$user_info['id']} and status=1 and wait_capital>0 ";
                 $ph_wait_capital = Yii::app()->phdb->createCommand($sql)->queryScalar() ?: 0;
                 //智多新
                 $zdx_sql = "SELECT sum(wait_capital) as wait_capital FROM offline_deal_load WHERE user_id ={$user_info['id']} and wait_capital>0 AND platform_id = 4 ";
                 $zdx_wait_capital = Yii::app()->offlinedb->createCommand($zdx_sql)->queryScalar() ?: 0;
                 $ph_zdx_capital = bcadd($zdx_wait_capital, $ph_wait_capital, 2);
                 //未持有在途本金直接返回首页
                 if(FunctionUtil::float_bigger_equal(0.00, $ph_zdx_capital, 2)){
                     header("location:{$real_src}");
                     exit;
                 }
                 //首页直接实名认证回来的直接跳转去签约
                 $displace_white_list = Yii::app()->c->xf_config['white_list'];
                 //集约诉讼签约地址
                 $_url = '';
                 if(!in_array($user_info['id'], $displace_white_list) && in_array($user_info['intensive_sign_status'], [0,2]) && $user_info['is_displace'] != 1){
                     $intensive_sign_url = DisplaceService::getInstance()->getIntensiveContractUrl($user_info);
                     $_url = $intensive_sign_url ?: $real_src;
                 }
                 if(in_array($user_info['id'], $displace_white_list) && in_array($user_info['is_displace'], [0,2]) && $user_info['intensive_sign_status'] != 1){
                     $displace_url = DisplaceService::getInstance()->getDisplaceContractUrl($user_info);
                     $_url = $displace_url ?: $real_src;
                 }
                 $_url = $_url ?: $real_src;
                 header("location:{$_url}");
                 exit;
             }else{
                 if($user_info['fdd_real_status'] == 1){
                     Yii::log(" actionFddApi 已实名认证成功，无需再次认证 ", 'error');
                     $return_data['fdd_real_status'] = 1;
                     $this->echoJson($return_data, 0, Yii::app()->c->XF_error_code_info[0]);
                 }elseif($user_info['fdd_real_status'] == 2 && $_GET['status'] == 2){
                     $update_sql = "update firstp2p_user set  fdd_real_time={$now_time},fdd_real_status=1   where id = {$user_info['id']}";
                     $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
                     if (!$edit_fdd) {
                         Yii::log(" actionFddApi 更新实名认证状态失败 ", 'error');
                         $this->echoJson($return_data, 3021, Yii::app()->c->XF_error_code_info[3021]);
                     }
                 }else{
                     Yii::log(" actionFddApi 请走正常流程实名认证 ", 'error');
                     $this->echoJson($return_data, 3022, Yii::app()->c->XF_error_code_info[3022]);
                 }
                 $return_data['fdd_real_status'] = 1;
                 $this->echoJson($return_data, 0, '实名认证成功');
             }
         }

         //合同签署回调数据
        if(!empty($_GET) && !empty($_GET['transaction_id']) && !empty($_GET['timestamp']) && $_GET['result_code'] == 3000 && !empty($_GET['msg_digest']) && !empty($_GET['viewpdf_url'])  ){
            Yii::log(" actionFddApi contract get " . print_r($_GET, true), 'info');
            //验签
            $check_result = XfFddService::getInstance()->checkContractSign($_GET['timestamp'], $_GET['transaction_id']);
            if($check_result != $_GET['msg_digest']){
                Yii::log(" actionFddApi 非法请求 ", 'error');
                $this->echoJson($return_data, 3023, Yii::app()->c->XF_error_code_info[3023]);
            };

            //区分流水号业务
            $tracsaction_str = substr($_GET['transaction_id'],0,4);
            //万峻置换回调处理
            /*
            if(in_array($tracsaction_str, ['WJZH','ZHFJ'])){
                $sign_ret = DisplaceService::getInstance()->signContract($_GET['transaction_id'], $tracsaction_str);
                $this->echoJson($sign_ret['data'], $sign_ret['code'], Yii::app()->c->XF_error_code_info[$sign_ret['code']]);
            }*/

            //集约诉讼签约回调
            if($tracsaction_str == 'JYSS'){
                $sign_ret = DisplaceService::getInstance()->signJyssContract($_GET['transaction_id']);
                $smrz_fdd_url = Yii::app()->c->contract['smrz_fdd_url'];
                header("location:{$smrz_fdd_url[1]}supplement");
                exit;
                //$this->echoJson($sign_ret['data'], $sign_ret['code'], Yii::app()->c->XF_error_code_info[$sign_ret['code']]);
            }

            //定向收购自动签署合同回调处理
            elseif($tracsaction_str == 'ZSSG'){
                $contract_sql = "SELECT * FROM xf_exclusive_purchase WHERE contract_transaction_id = '{$_GET['transaction_id']}'  ";
                $contract_info = Yii::app()->phdb->createCommand($contract_sql)->queryRow();
                //定向收购自动签署合同
                if($contract_info  ){
                    if($contract_info['status'] == 0){
                        $viewpdf_url = urldecode($_GET['viewpdf_url']);
                        $now_time = time();
                        $update_sql = "update xf_exclusive_purchase set  user_sign_time={$now_time},status=1,contract_url='{$viewpdf_url}'  where id = {$contract_info['id']}";
                        $edit_fdd = Yii::app()->phdb->createCommand($update_sql)->execute();
                        if (!$edit_fdd) {
                            Yii::log(" actionFddApi 更新合同状态失败 ", 'error');
                            $this->echoJson($return_data, 3025, Yii::app()->c->XF_error_code_info[3025]);
                        }
                    }else{
                        Yii::log(" actionFddApi 非待签署状态 ", 'error');
                        $this->echoJson($return_data, 3026, Yii::app()->c->XF_error_code_info[3026]);
                    }
                    $return_data['sign_contract_status'] = 1;
                    $this->echoJson($return_data, 0, '合同签署成功');
                }
            }else{
                //积分兑换合同签署回调
                $contract_sql = "SELECT * FROM xf_debt_contract WHERE contract_transaction_id = '{$_GET['transaction_id']}'  ";
                $contract_info = Yii::app()->db->createCommand($contract_sql)->queryRow();
                //积分兑换自动签署合同
                if($contract_info){
                    if($contract_info['status'] == 0){
                        $viewpdf_url = urldecode($_GET['viewpdf_url']);
                        $now_time = time();
                        $update_sql = "update xf_debt_contract set  status=1,contract_url='{$viewpdf_url}'  where id = {$contract_info['id']}";
                        $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
                        if (!$edit_fdd) {
                            Yii::log(" actionFddApi [{$contract_info['id']}] 更新合同状态失败 ", 'error');
                            $this->echoJson($return_data, 3025, Yii::app()->c->XF_error_code_info[3025]);
                        }
                        //尊享
                        $edit_fdd01 = $edit_fdd02 = true;
                        if($contract_info['platform_id'] == 1){
                            $update_sql = "update firstp2p_debt_exchange_log set  status=1   where contract_transaction_id = '{$_GET['transaction_id']}' and status=9 ";
                            $edit_fdd01 = Yii::app()->db->createCommand($update_sql)->execute();
                        }elseif ($contract_info['platform_id'] == 2){
                            $update_sql = "update firstp2p_debt_exchange_log set  status=1   where contract_transaction_id = '{$_GET['transaction_id']}' and status=9";
                            $edit_fdd01 = Yii::app()->phdb->createCommand($update_sql)->execute();
                        }elseif ($contract_info['platform_id'] == 4){
                            $update_sql = "update offline_debt_exchange_log set  status=1   where contract_transaction_id = '{$_GET['transaction_id']}' and status=9";
                            $edit_fdd01 = Yii::app()->offlinedb->createCommand($update_sql)->execute();
                        }elseif ($contract_info['platform_id'] == 99){
                            //待补充
                            $ret01 = PHDebtExchangeLog::model()->find("contract_transaction_id = '{$_GET['transaction_id']}'  and status=9 ");
                            if($ret01){
                                $update_sql = "update firstp2p_debt_exchange_log set  status=1   where contract_transaction_id = '{$_GET['transaction_id']}' and status=9";
                                $edit_fdd01 = Yii::app()->phdb->createCommand($update_sql)->execute();
                            }
                            $ret02 = OfflineDebtExchangeLog::model()->find("contract_transaction_id = '{$_GET['transaction_id']}' and status=9");
                            if($ret02){
                                $update_sql = "update offline_debt_exchange_log set  status=1   where contract_transaction_id = '{$_GET['transaction_id']}' and status=9";
                                $edit_fdd02 = Yii::app()->offlinedb->createCommand($update_sql)->execute();
                            }
                        }else{
                            Yii::log(" actionFddApi [{$contract_info['id']}] platform_id[{$contract_info['platform_id']}] error ", 'error');
                            $this->echoJson($return_data, 3003, Yii::app()->c->XF_error_code_info[3003]);
                        }
                        if(!$edit_fdd01 && !$edit_fdd02){
                            Yii::log(" actionFddApi [{$contract_info['id']}] 更新兑换记录状态失败 ", 'error');
                            $this->echoJson($return_data, 3025, Yii::app()->c->XF_error_code_info[3025]);
                        }

                        //notice数据进表
                        $notice_ret = $this->saveNotice($contract_info['notice_data']);
                        if(!$notice_ret){
                            Yii::log(" actionFddApi [{$contract_info['id']}] 异步通知数据入库失败 ", 'error');
                            $this->echoJson($return_data, 3025, Yii::app()->c->XF_error_code_info[3025]);
                        }
                    }else{
                        Yii::log(" actionFddApi 非待签署状态 ", 'error');
                        $this->echoJson($return_data, 3026, Yii::app()->c->XF_error_code_info[3026]);
                    }

                    //合同签署成功，跳转回来源商城
                    $return_url = urldecode($contract_info['return_url']);
                    header("location:{$return_url}");
                    exit;
                }
            }

            Yii::log(" actionFddApi 合同签署流水号不存在 ", 'error');
            $this->echoJson($return_data, 3024, Yii::app()->c->XF_error_code_info[3024]);
        }

        //置换合同异步通知
        if(!empty($_POST) && !empty($_POST['transaction_id']) && !empty($_POST['timestamp']) && $_POST['result_code'] == 3000 && !empty($_POST['msg_digest']) && !empty($_POST['viewpdf_url'])  ){
            Yii::log(" actionFddApi contract post " . print_r($_POST, true), 'info');
            //验签
            $check_result = XfFddService::getInstance()->checkContractSign($_POST['timestamp'], $_POST['transaction_id']);
            if($check_result != $_POST['msg_digest']){
                Yii::log(" actionFddApi 非法请求 ", 'error');
                $this->echoJson($return_data, 3023, Yii::app()->c->XF_error_code_info[3023]);
            };

            //区分流水号业务
            $tracsaction_str = substr($_POST['transaction_id'],0,4);
            //万峻置换回调处理
            if(in_array($tracsaction_str, ['WJZH','ZHFJ'])){
                $sign_ret = DisplaceService::getInstance()->signContract($_POST['transaction_id'], $tracsaction_str);
                $this->echoJson($sign_ret['data'], $sign_ret['code'], Yii::app()->c->XF_error_code_info[$sign_ret['code']]);
            }

            Yii::log(" actionFddApi 非业务范围内数据 ", 'error');
            $this->echoJson($return_data, 3024, Yii::app()->c->XF_error_code_info[3024]);
        }


    }

    private function saveNotice($notice_data)
    {
        $notice_data = json_decode($notice_data, true);
        $notice = new XfDebtExchangeNotice();
        $notice->amount = $notice_data['amount'];
        $notice->appid = $notice_data['appid'];
        $notice->user_id = $notice_data['user_id'];
        $notice->order_id = $notice_data['order_id'];
        $notice->notify_url = $notice_data['notify_url'];
        $notice->order_info = $notice_data['order_info'];
        $notice->order_sn = $notice_data['order_sn'];
        $notice->created_at = time();
        $notice->notice_time_1 = time();
        $notice->notice_time_2 = time()+30;
        $notice->notice_time_3 = time()+300;
        return  $notice->save();
    }


    /**
     * 置换操作
     */
    public function actionDisplace()
    {
        Yii::log(" actionDisplace params ".print_r($_POST, true));
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        //测试环境
        $this->user_id = 12130848;
        //登录校验
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }

        //设备与浏览器二选一必填
        if (empty($_POST['add_device']) && empty($_POST['add_browser'])) {
            $this->echoJson(array(), 3028, $XF_error_code_info[3028]);
        }
        //置换类型
        if (empty($_POST['displace_type']) || !in_array($_POST['displace_type'], [2,3])) {
            $this->echoJson(array(), 3029, $XF_error_code_info[3029]);
        }

        //置换
        $displace_ret = DisplaceService::getInstance()->displace($user_id);
        if(!$displace_ret){
            $this->echoJson(array(), 3030, $XF_error_code_info[3030]);
        }
        $this->echoJson(array(), 0, '置换成功');
    }

    /**
     * 身份证上传资料补充接口
     */
    public function actionUploadIdPhoto()
    {
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        // 校验IP限制
        $redis = Yii::app()->rcache;
        $ip = ip2long(Yii::app()->request->userHostAddress);
        $time = time();
        $redis_key = "check_upload_id_photo_limit_by_IP_{$ip}";
        $check_IP  = $redis->get($redis_key);
        if ($check_IP >= $this->check_user_info_limit_by_IP) {
            $this->echoJson(array(), 1096, $XF_error_code_info[1096]);
        }

        //提测试注释
        //$this->user_id = 12130848;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson(array(), 1016, $XF_error_code_info[1016]);
        }
        $sql = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$user_info) {
            $this->echoJson(array(), 1018, $XF_error_code_info[1018]);
        }
        if($user_info['fdd_real_status'] != 1 ){
            $this->echoJson(array(), 3040, $XF_error_code_info[3040]);
        }
        if($user_info['intensive_sign_status'] != 1 ){
            $this->echoJson(array(), 3039, $XF_error_code_info[3039]);
        }
        if($user_info['intensive_idcard_time'] >0 ){
            $this->echoJson(array(), 3041, $XF_error_code_info[3041]);
        }
        $idno = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->contract['idno_key']);

        // 校验2张照片
        $idcard_face = trim($_POST['idcard_face']);
        $idcard_back = trim($_POST['idcard_back']);
        if (empty($idcard_face)) {
            $this->echoJson(array(), 1072, $XF_error_code_info[1072]);
        }
        if (empty($idcard_back)) {
            $this->echoJson(array(), 1073, $XF_error_code_info[1073]);
        }
        $idcard_face_res = $this->intensive_upload_base64($idcard_face, $user_id.'-正');
        Yii::log(  'idcard_face_res:'.$idcard_face_res );
        if (!$idcard_face_res) {
            $this->echoJson(array() , 1087 , $XF_error_code_info[1087]);
        }
        $idcard_back_res = $this->intensive_upload_base64($idcard_back, $user_id.'-反');
        Yii::log(  'idcard_back_res:'.$idcard_back_res );
        if (!$idcard_back_res) {
            $this->echoJson(array() , 1088 , $XF_error_code_info[1088]);
        }
        //提测删除
        //$idcard_back_res['pic_address'] = "uploads/intensive_idcard/3.png";
        //$idcard_back_res['pic_name'] = '3.png';

        $idcard_face_address = Yii::app()->c->contract['api_url']."/".$idcard_face_res['pic_address'];
        $idcard_back_address = Yii::app()->c->contract['api_url']."/".$idcard_back_res['pic_address'];
        //正面校验阿里云接口校验
        $face_info = XfCurlService::getInstance()->cardPost($idcard_face_address);
        if(!$face_info){
            $this->echoJson(array(), 3043, $XF_error_code_info[3043]);
        }
        Yii::log(  $user_id.' idno:'.$idno.'; code:'.$face_info['result']['code'] );
        if($face_info['result']['code'] != $idno){
            $this->echoJson(array(), 3044, $XF_error_code_info[3044]);
        }

        //反面校验
        $back_info = XfCurlService::getInstance()->cardPost($idcard_back_address,'back');
        if(!$back_info){
            $this->echoJson(array(), 3043, $XF_error_code_info[3043]);
        }
        $f_time = date('Ymd');
        if($back_info['result']['expiryDate'] <= $f_time){
            $this->echoJson(array(), 3046, $XF_error_code_info[3046]);
        }


        $dir = date('Ymd');
        $oss_face_url = 'intensive_sign_idcard/'.$dir.'/'.$idcard_face_res['pic_name'];
        $idcard_face_oss = $this->upload_oss('./'.$idcard_face_res['pic_address'], $oss_face_url); 

        $oss_back_url = 'intensive_sign_idcard/'.$dir.'/'.$idcard_back_res['pic_name'];
        $idcard_back_oss = $this->upload_oss('./'.$idcard_back_res['pic_address'], $oss_back_url);
        /*
        if($idcard_face_oss === false) {
            $this->echoJson(array(), 1091, $XF_error_code_info[1091]);
        }
        if ($idcard_back_oss === false) {
            $this->echoJson(array(), 1092, $XF_error_code_info[1092]);
        }*/


        //更新用户信息
        $sql = "update firstp2p_user set intensive_idcard_time=$time,intensive_idcard_face='$oss_face_url',intensive_idcard_back='$oss_back_url' where id={$user_id}";
        //$sql = "update firstp2p_user set intensive_idcard_time=$time,intensive_idcard_face='{$idcard_face_res['pic_address']}',intensive_idcard_back='{$idcard_back_res['pic_address']}' where id={$user_id}";
        $result = Yii::app()->db->createCommand($sql)->execute();
        if (!$result) {
            $this->echoJson(array(), 2006, $XF_error_code_info[2006]);
        }
        $this->echoJson(array(), 0, $XF_error_code_info[0]);
    }

}
