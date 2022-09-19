<?php

namespace NCFGroup\Common\Library\Sms;

use NCFGroup\Common\Library\CommonLogger as Logger;
use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Common\Library\HttpLib;
use NCFGroup\Common\Library\Curl;

/**
 * 短信网关发送客户端
 */
class Sms
{

    const REQUEST_TIMEOUT = 1;

    const API_SEND = '/sms/send';

    /**
     * 短信发送方法
     */
    public static function send($app, $secret, $mobile, $tpl, array $vars = array())
    {
        foreach ($vars as $key => $value) {
            $vars[$key] = (string) $value;
        }

        if (is_array($mobile)) {
            $mobile = implode($mobile, ',');
        }

        $params = array(
            'app' => $app,
            'tpl' => $tpl,
            'mobile' => $mobile,
            'vars' => json_encode($vars),
            'ip' => HttpLib::getClientIp()
        );
        $params['sign'] = SignatureLib::generate($params, $secret);

        $config = getDi()->getConfig()->sms->toArray();
        $url = $config['url'].self::API_SEND;

        $curl = Curl::instance();
        $result = $curl->setTimeout(self::REQUEST_TIMEOUT)->post($url, $params);
        Logger::info("common sms send. app:{$app}, cost:{$curl->resultInfo['cost']}, params:".json_encode($params).", result:{$result}");

        return json_decode($result, true);
    }

}
