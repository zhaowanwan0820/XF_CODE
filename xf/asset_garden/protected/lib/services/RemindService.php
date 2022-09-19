<?php

/**
 * 提醒类
 */
class RemindService
{

    private static $url              = '';
    private static $smsUrl           = '/api/v1/BusinessNotify/sendSms';
    private static $header           = ["x-itz-apptoken:r&ht0E@*aGeJkNg3d6X3gOM&WWEbGCgO"];

    /**
     * 公共调用方法
     * RemindService::sendSms($data)     发短信
     * @param $name
     * @param $data
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public static function __callStatic($name, $data)
    {
        $res = [];
        $pos = strrpos($name, "send");
        if ($pos === false) {
            throw new \Exception("undefined method:$name");
        }
        $data = current($data);
        self::makeUrl($name);
        self::check($name, $data);
        if (isset($data['phone'])) {
            self::$url = self::getRequestIp() . self::$smsUrl;
            if (is_array($data['phone'])) {
                foreach ($data['phone'] as $phone) {
                    $data['phone'] = $phone;
                    $res           = self::curlRequest(self::$url, 'POST', $data, self::$header);
                }
            } else {
                $res = self::curlRequest(self::$url, 'POST', $data, self::$header);
            }
        }
        return $res;
    }

    /**
     * 获取请求地址
     * @param $func
     * @throws \Exception
     */
    private static function makeUrl($func)
    {
        switch ($func) {
            case 'sendSms':
                self::$url = self::getRequestIp() . self::$smsUrl;
                break;
            default:
                throw new \Exception('当前仅支持邮件、短信');
        }
    }

    /**
     * 校验参数
     * @param $name
     * @param $data
     * @throws \Exception
     */
    private static function check($name, $data)
    {
        if (!isset($data['receive_user']) || empty($data['receive_user'])) {
            throw new \Exception('Parameter "receive_user" cannot be empty');
        }
        if ($name == 'sendSms' && (!isset($data['phone']) || empty($data['phone']))) {
            throw new \Exception('Parameter "phone" cannot be empty');
        }
        if ($name == 'sendEmail' && (!isset($data['email']) || empty($data['email']))) {
            throw new \Exception('Parameter "email" cannot be empty');
        }
        if (!isset($data['mtype']) || empty($data['mtype'])) {
            throw new \Exception('Parameter "mtype" cannot be empty');
        }
    }

    /**
     * 获取i_message 请求ip
     * @return mixed
     */
    private static function getRequestIp()
    {
        return ConfUtil::get('IMESSAGEIP', '10.0.0.115');
    }


    private static function curlRequest($api, $method = 'GET', $params = array(), $headers = [], $json_decode = true)
    {
        $curl = curl_init();
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $api .= (strpos($api, '?') ? '&' : '?') . http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if(is_array($params)) {
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
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            curl_setopt($curl,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        $response = curl_exec($curl);
        if ($response === false) {
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