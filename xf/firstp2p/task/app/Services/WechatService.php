<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/3/3
 * Time: 16:03
 */

namespace NCFGroup\Task\Services;


class WechatService {

    const CORPID = 'wx0afefe1e0748ee97';
    const SECRET = 'tfeMfeUKZonQiJLqWCd20ZNuwaGS-6UJjoacrt5PvOwHFZknGSbKgkex6m-JQLyv';

    const AGENTID = 1;

    const GET_TOKEN_URL_FORMAT = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=%s&corpsecret=%s";
    const SEND_MESSAGE_URL_FORMAT = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=%s";

    const TOKEN_EXPIRE_TIME = 7200;

    const TOKEN_REDIS_KEY = "/task/token/key";

    public static function getToken() {
        $token = getDI()->get('taskRedis')->get(self::TOKEN_REDIS_KEY);
        if($token) {
            return $token;
        }
        $tokenUrl = sprintf(self::GET_TOKEN_URL_FORMAT, self::CORPID, self::SECRET);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_REFERER,'');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (substr($tokenUrl, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if($result['access_token']) {
            getDI()->get('taskRedis')->setEx(self::TOKEN_REDIS_KEY, self::TOKEN_EXPIRE_TIME, $result['access_token']);
            return $result['access_token'];
        }
        return "";
    }

    public static function sendMessage($title, $message) {
        $token = self::getToken();
        if(empty($token)) {
            return false;
        }
        $sendMessageUrl = sprintf(self::SEND_MESSAGE_URL_FORMAT, $token);
        $content = array(
            'touser' => '@all',
            'msgtype' => 'text',
            'agentid' => self::AGENTID,
            'text' => array(
                'content' => "======{$title}======\n\n{$message}"
            ),
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sendMessageUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (substr($sendMessageUrl, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}