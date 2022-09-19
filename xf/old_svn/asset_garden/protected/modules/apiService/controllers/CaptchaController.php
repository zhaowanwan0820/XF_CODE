<?php
/*
*生成验证码或者调用缓存中的验证码的类
*/
class CaptchaController extends ItzController {

    /**
     * [actionLogin 登录图形验证码]
     * @return [type] [description]
     */
    public function actionLoginWap() {
        $key = implode('_', [FunctionUtil::ip_address(), '/wap/user/loginApi', 'error']);
        return $this->loginValicode($key);
    }

    /**
     * [actionLogin 微信绑定图形验证码]
     * @return [type] [description]
     */
    public function actionWechatBind() {
        $key = implode('_', [FunctionUtil::ip_address(), '/wap/user/bindwechatlogin', 'error']);
        return $this->loginValicode($key);
    }
    /**
     * [actionLogin 登录图形验证码]
     * @return [type] [description]
     */
    public function actionLogin() {
        $key = implode('_', [FunctionUtil::ip_address(), '/newuser/rAjax/login', 'error']);
        return $this->loginValicode($key);
    }
    /**
     * [actionLogin 提现]
     * @return [type] [description]
     */
    public function actionWithdraw() {
        $key = implode('_', [FunctionUtil::ip_address(), '/user/Withdraw', 'error']);
        return $this->loginValicode($key);
    }
    /**
     * [actionLogin 找回登录密码]
     * @return [type] [description]
     */
    public function actionFindPwd() {
        $key = implode('_', [FunctionUtil::ip_address(), '/user/FindPwd', 'error']);
        return $this->loginValicode($key);
    }
    /**
     * [actionLogin 债权]
     * @return [type] [description]
     */
    public function actionDebt() {
        $key = implode('_', [FunctionUtil::ip_address(), '/Debt/submit', 'error']);
        return $this->loginValicode($key);
    }
    /**
     * [actionLogin 直投]
     * @return [type] [description]
     */
    public function actionInvest($id) {
        if ( !$this->user_id ) {
            $this->echoJson(array(),1,'您还没有登录');
            exit;
        }

        //解析项目
        $borrowid = current(UrlUtil::_url2key($id));
        $valicodekey = 'valicode@'.$borrowid;
        if ( !$borrowid ) {
            $this->echoJson(array(),1,'项目不存在');
            exit();
        }

        //防爆破
        $limit = BlockCC::getInstance()->getNew('UserKey')->SetExpireTime( 60 )->SetAndCheck(['tender_imgcode_counter', $borrowid, $this->user_id]);
        if ( !$limit ) {
            $_SESSION[$valicodekey] = FunctionUtil::get_random_nstr(4);
            header("location:".Yii::app()->c->baseUrl."/static/img/common/operationDenied.jpg");
            exit;
        }

        //项目信息
        $borrow = BorrowService::getInstance()->getBorrowInfoFromCache($borrowid);
        if ($borrow['appointment_money'] && (int)$borrow['appointment_money'] > 0) {
            $reserve_borrow_obj = QueryBorrowAppointment::api()->run( $this->user_id, $borrowid )->result;
            if ($reserve_borrow_obj['code'] === 0) {
                $is_in_queue = $reserve_borrow_obj['data'];
            }
        }
        if ( empty($borrow) 
            || empty($borrow['borrow']) 
            || !( time() > (int)$borrow['borrow']['formal_time']-600 )
        ) {
            if (!$is_in_queue) {
                $this->echoJson(array(),1,'项目未在投标中');
                exit();
            }
        }

        //防爆破处理验证码难度
        $key = implode('_', [FunctionUtil::ip_address(), '/Invest/submit', 'error']);
        $cap_type = "common";
        $ipActionError = RedisService::getInstance()->hGetAll($key);
        // IP错误次数限制， ≥100次 为 弱  ≥100 且 ≤200 为强
        if ($ipActionError['day'] >= 200 || Yii::app()->session->get('loginLockCounter') >= 5) {
            $cap_type = "special";
        }

        $config = $this->getConfig();
        $code = $this->getCaptchaImageAndCode($cap_type);
        $this->setHeader();
        $_SESSION[$valicodekey] = strtolower($code['code']);//注册session
        $_SESSION[$valicodekey.'_expiretime']=time()+15*60;//验证码有效期15分钟
        echo $code['image'];
    }

    private function loginValicode($key) {
        $cap_type = "common";
        $ipActionError = RedisService::getInstance()->hGetAll($key);
        // IP错误次数限制， ≥100次 为 弱  ≥100 且 ≤200 为强
        if ($ipActionError['day'] >= 200 || Yii::app()->session->get('loginLockCounter') >= 5) {
            $cap_type = "special";
        }
        return $this->returnImage($cap_type);
    }

    /**
     * [actionGetReg 注册图形验证码]
     * @return [type] [description]
     */
    public function actionReg() {
        $cap_type = "common";
        /*$session_name = "count_".$this->getBusHash();
        if(!isset($_SESSION[$session_name])){
            $_SESSION[$session_name] = 0;
        } else {
            $_SESSION[$session_name] = $_SESSION[$session_name] + 1;
            if($_SESSION[$session_name] >= 3){ // 同一 session 下请求次数超过 3 次
                $cap_type = "special";
            }
        }
        $cc_by_ip = BlockCC::getInstance()->getNew('IpAction')->Check(['total']);
        $cc_by_ip_set = BlockCC::getInstance()->getNew('IpAction')->Set(['total']);
        if (!$cc_by_ip) { // 同一 ip 下请求次数超过 12 次
            $cap_type = "special";
        }*/
        return $this->returnImage($cap_type);
    }

    /*
    *获取验证码
    */
    private function returnImage($cap_type) {
        $config = $this->getConfig();
        $code = $this->getCaptchaImageAndCode($cap_type);
        $this->setHeader();
        $hash = $this->getHash($code['image']);
        $this->setCaptchaCache($code['code'], $hash, $config['timeout']);
        $this->setCaptchaCookie($hash, $config['timeout']);
        echo $code['image'];
    }

    private function getBusHash() {
        $ref = $_SERVER['HTTP_REFERER'];
        return substr(md5($ref), 1, 5);
    }

    private function getHash($img) {
         return md5($img . mt_rand(1,10000000));
    }

    private function setCaptchaCache($code, $hash, $timeout) {
        if (Yii::app()->dcache->set($hash, array("code" => $code, "count" => 1), $timeout)) {
            Yii::log("set captcha check cache success", "info");
        } else {
            Yii::log("set captcha check cache failed!", "error");
        }
    }
    
    private function setCaptchaCookie($hash, $timeout) {
        $cookie_name = "caphash" . "_" . $this->getBusHash();
        setcookie($cookie_name, $hash, time() + $timeout, "/", ".itouzi.com", true);
    }

    private function getCaptchaImageAndCode($type) {
        $captchaImg = $this->generateImage($type);
        if (!$captchaImg) {
            Yii::log("build image  just in time", "info");
            require_once(WWW_DIR . "/itzlib/captcha/captcha.php");
            $captcha = new CAPTCHA($type);
            $code = $captcha->keystring;
            $image = $captcha->captchaImg;
            ob_start();
            if (function_exists("imagejpeg")) {
                imagejpeg($image); 
            } else {
                imagepng($image); 
            }
            $imageCode = ob_get_contents();
            ob_end_clean();
            return array("image" => $imageCode, "code" => $code);
        } else {
            Yii::log("get image from cache","info");
            return $captchaImg;
        }
    }

    /*
    *设置图片相应头
    */
    private function setHeader() {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
        header('Cache-Control: no-store, no-cache, must-revalidate'); 
        header('Cache-Control: post-check=0, pre-check=0', FALSE); 
        header('Pragma: no-cache');
        header("Content-Type: image/jpeg");
    }

    /*
    *获取图片验证码、
    *@param string $type 分为common和special获取验证码的类型
    *return Object图片验证码
    */
    private function generateImage($type) {
        $config = $this->getConfig();
        $timeout = intval($config['timeout']);    
        $common = intval($config['common']);
        $special = intval($config['special']);
        $img = null;
        if ($type == "common") {
            $rand = mt_rand(1, 1000);
            $key = "captcha:common:" . $rand;
            $img =  Yii::app()->dcache->get($key);
        } else if ($type == "special") {
            $rand = mt_rand(1, 1000);
            $key = "captcha:special:" . $rand;
            $img =  Yii::app()->dcache->get($key);
        }
        return $img;  
    }
    
    /*
    *获取配置文件
    *返回
    */
    private function getConfig() {
        $config = require_once(WWW_DIR . "/itzlib/captcha/config.php");
        return $config;
    }

    /*验证验证码的正确性*/
    public function actionCheck($valicode='', $type='') {
        $cc_by_ip = BlockCC::getInstance()->getNew('IpAction')->Check(['error']);
        $cc_by_ip_set = BlockCC::getInstance()->getNew('IpAction')->Set(['error']);
        if (!$cc_by_ip) {
            $audit_logs['parameters']['info'] = '您操作过于频繁，请稍后重试';
            AuditLog::getInstance()->method('add', $audit_logs);
            return $this->echoJson( array() ,2044 ,"您操作过于频繁，请稍后重试" );
        }
        if (!$valicode || $valicode == '') {
            if ($type == 'nojson') {
                echo "false";
                Yii::app()->end();
            }
            return $this->echoJson([], 2008, '请先输入图形验证码');
        }
        $captchaCheck  = new CaptchaCheck();
        $result = $captchaCheck->ValidCaptcha($valicode, false);//验证并销毁验证码
        if ($result['code'] !== 0) {
            if ($type == 'nojson') {
                echo "false";
                Yii::app()->end();
            }
            if ($result['code'] == 2105) {
                return $this->echoJson([], 2043, '图形验证码超时，请重新输入');
            } else if ($result['code'] == 2106) {
                return $this->echoJson([], 2043, '图形验证码不正确，请重新输入');
            }
        }
        if ($type == 'nojson') {
            echo "true";
            Yii::app()->end();
        }
        return $this->echoJson([], 0, '验证码正确');
    }
}
