<?php

class SmsIdentityUtils
{
    private static $sendUrl = 'https://www.zichanhuayuan.com/yzm/sms/send/code';
    private static $checkUrl = 'https://www.zichanhuayuan.com/yzm/sms/Verification/code';
    private static $alertUrl = 'https://www.zichanhuayuan.com/yzm/sms/send/alert/msg';
    private static $sendType;

    /*
    *检查某手机号请求接口的速度
    *保证其在一分钟内只能请求一次
    *@param $phone手机号
    */
    private static function speedCheck($phone)
    {
        $flag_cache_id = $phone.':sms_send_flag:'.self::$sendType;
        if ($vcode = Yii::app()->rcache->get($flag_cache_id)) {
            return false;
        } else {
            Yii::app()->rcache->set($flag_cache_id, '1', 60);

            return true;
        }
    }

    /*
    *发送手机验证码
    *@param string $phone 手机号
    *@param string $type 验证码业务类型 xf_auth_login:授权登录
    *@return array code 为0正常发送否则请参考错误代码规范
    */
    public static function sendCode($phone, $type)
    {
        self::$sendType = $type;
        if (self::speedCheck($phone)) {
            if (self::sendSms($phone)) {
                $run_result['code'] = 0;
            } else {
                Yii::log("sms send error register {$phone}", 'error', __CLASS__);
                $run_result['code'] = 1006;
            }
        } else {
            Yii::log("fast send of msg { $phone }", 'error', __CLASS__);
            $run_result['code'] = 1007;
        }

        return $run_result;
    }

    private static function sendSms($phone)
    {
        // 测试用手机号
        $xf_test_number = Yii::app()->c->xf_config['xf_test_number'];

        $cache_key = self::getCacheKey($phone);
        self::destroy($cache_key);
        $vcode = FunctionUtil::VerifyCode();
        $remind['phone'] = $phone;
        $remind['data']['vcode'] = $vcode;
        $remind['code'] = self::$sendType;

        if (in_array($phone, $xf_test_number)) {
            $send_SMS['code']=0;
            $vcode=999999;
        } else {
            $send_SMS = (new XfSmsClass())->sendToUserByPhone($remind);
        }
        Yii::log('send sms phone: '.$phone.' info:'.print_r($remind, true) .' result:'.print_r($send_SMS, true), 'info', __CLASS__);

        if (0 != $send_SMS['code']) {//1成功，0失败
            Yii::log('send sms error:'.print_r($send_SMS, true).' '.print_r($phone, true), 'error');

            return false;
        } else {
            $data['nums'] = 1;
            $data['vcode'] = $vcode;
            Yii::app()->rcache->set($cache_key, $data, 300);

            return true;
        }
    }

    private static function getCacheKey($phone)
    {
        $bushash = self::getBusHash();

        return  $phone.':'.$bushash.':'.self::$sendType;
    }

    public static function ValidateCode($phone, $vcode_input, $type, $destroy = true)
    {
        self::$sendType = $type;
        $info = ['code' => 0];
        $cache_key = self::getCacheKey($phone);
        $vcodeData = Yii::app()->rcache->get($cache_key);
        if ($vcodeData) {
            if ($vcodeData['nums'] > 3) {
                $info['code'] = 1005; //重新获取
            } else {
                if ($vcodeData['vcode'] == $vcode_input) {
                    if ($destroy) {
                        self::destroy($cache_key);
                    }
                    $info['code'] = 0;
                } else {
                    $info['code'] = 1004;
                    ++$vcodeData['nums'];
                    Yii::app()->rcache->set($cache_key, $vcodeData, 300);
                }
            }
        } else {
            $info['code'] = 1003;
        }

        return $info;
    }

    private static function destroy($cache_key)
    {
        Yii::app()->rcache->delete($cache_key);
    }

    private static function getBusHash()
    {
        $ref = $_SERVER['SERVER_ADDR'];

        return substr(md5($ref), 1, 5);
    }

    /**
     * 资金报警.
     *
     * @param $phone
     * @param $error_info
     * @param $mark
     *
     * @return mixed
     */
    public static function fundAlarm($error_info, $mark = 'zj', $phone = '15810571697')
    {
        //返回
        $run_result['code'] = 0;
        //非空校验
        if (empty($error_info)) {
            Yii::log('fundAlarm params error', 'error', __CLASS__);
            $run_result['code'] = 1001;

            return $run_result;
        }
        //发送频次限制
        if (false == self::zjSpeedCheck($phone, $mark)) {
            Yii::log("fast send of msg { $phone }", 'error', __CLASS__);
            $run_result['code'] = 1007;

            return $run_result;
        }
        //报警发送
        $result = self::curlRequest(self::$alertUrl, 'GET', ['mobile' => $phone, 'error' => $error_info]);
        if (!isset($result['status']) || 200 != $result['status']) {
            Yii::log('send sms error:'.print_r($result, true).' '.print_r($phone, true), 'error');
            $run_result['code'] = 1008;

            return $run_result;
        }

        return $run_result;
    }

    private static function zjSpeedCheck($phone, $mark)
    {
        $flag_cache_id = $phone.'sms_send_flag'.$mark;
        if (Yii::app()->rcache->get($flag_cache_id)) {
            return false;
        }
        Yii::app()->rcache->set($flag_cache_id, '1', 3600);

        return true;
    }

    private static function curlRequest($api, $method = 'GET', $params = [], $headers = [], $json_decode = true)
    {
        $curl = curl_init();
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $api .= (strpos($api, '?') ? '&' : '?').http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $api);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        $response = curl_exec($curl);
        if (false === $response) {
            curl_close($curl);

            return false;
        } else {
            // 解决windows 服务器 BOM 问题
            $response = trim($response, chr(239).chr(187).chr(191));
            if ($json_decode) {
                $response = json_decode($response, true);
            }
        }
        curl_close($curl);

        return $response;
    }
}
