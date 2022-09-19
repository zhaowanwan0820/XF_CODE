<?php
/*
*生成验证码或者调用缓存中的验证码的类
*/
class CaptchaController extends CommonController
{

    /**
     * [actionLogin 登录图形验证码]
     * @return [type] [description]
     */
    public function actionLogin()
    {
        return $this->returnImage();
    }
  
    /*
    *获取验证码
    */
    private function returnImage($cap_type="common")
    {
        $config = $this->getConfig();
        $code = $this->getCaptchaImageAndCode($cap_type);
        $this->setHeader();
        $hash = (new CaptchaCheck())->getBusHash($code['code']);
        $this->setCaptchaCache($code['code'], $hash, $config['timeout']);
        echo $code['image'];
    }
 

    private function setCaptchaCache($code, $hash, $timeout)
    {
        if (Yii::app()->rcache->set($hash, array("code" => $code, "count" => 1), $timeout)) {
            Yii::log("set captcha check cache success", "info");
        } else {
            Yii::log("set captcha check cache failed!", "error");
        }
    }
    
  
    private function getCaptchaImageAndCode($type)
    {
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
            Yii::log("get image from cache", "info");
            return $captchaImg;
        }
    }

    /*
    *设置图片相应头
    */
    private function setHeader()
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header("Content-Type: image/jpeg");
    }

    /*
    *获取图片验证码、
    *@param string $type 分为common和special获取验证码的类型
    *return Object图片验证码
    */
    private function generateImage($type)
    {
        $config = $this->getConfig();
        $timeout = intval($config['timeout']);
        $common = intval($config['common']);
        $special = intval($config['special']);
        $img = null;
        if ($type == "common") {
            $rand = mt_rand(1, 1000);
            $key = "captcha:common:" . $rand;
            $img =  Yii::app()->rcache->get($key);
        } elseif ($type == "special") {
            $rand = mt_rand(1, 1000);
            $key = "captcha:special:" . $rand;
            $img =  Yii::app()->rcache->get($key);
        }
        return $img;
    }
    
    /*
    *获取配置文件
    *返回
    */
    private function getConfig()
    {
        $config = require_once(WWW_DIR . "/itzlib/captcha/config.php");
        return $config;
    }

    /*验证验证码的正确性*/
    public function actionCheck($valicode='', $type='')
    {
        $cc_by_ip = BlockCC::getInstance()->getNew('IpAction')->Check(['error']);
        $cc_by_ip_set = BlockCC::getInstance()->getNew('IpAction')->Set(['error']);
        if (!$cc_by_ip) {
            $audit_logs['parameters']['info'] = '您操作过于频繁，请稍后重试';
            return $this->echoJson(array(), 2044, "您操作过于频繁，请稍后重试");
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
            } elseif ($result['code'] == 2106) {
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
