<?php
/**
 * Msg SDK 客户端
 * User: kjson
 * Date: 16/8/24
 * Time: 上午10:12
 */
namespace itzlib\sdk\service;

use itzlib\sdk\SdkClient;

class Message extends SdkClient
{
    /**
     * @var string APP Key
     */
    public $key = '2Ams9ey';
    /**
     * @var string APP Secret
     */
    public $secret = 'sJLo2w9W5RwV';
    /**
     * @var string  message 服务位于 zookeeper 树的名称
     */
    public $service = 'com.itouzi.msg';

    /**
     * @var string
     */
    const VERSION = 'v1';

    /**
     * @param $params : $code,$group,$level,$times,需要json格式:$title,$content
     * @return mixed
     */
    public function sendNotifyEmail($params)
    {
        $verb = 'POST';
        $path = 'api/' . self::VERSION . '/notify/sendEmail';
        return $this->curl($verb, $path, $params);
    }

    /**
     * @param $params : $code,$group,$level,$times,需要json格式:$title,$content
     * @return mixed
     */
    public function sendNotifySms($params)
    {
        $verb = 'POST';
        $path = 'api/' . self::VERSION . '/businessNotify/sendSms';
        return $this->curl($verb, $path, $params);
    }

    /**
     * @param $params [receive_user => '271', 'phone' => '18810040986', data => ['a' => 'b], 'mtype' => 'test']
     * @return bool|mixed
     */
    public function sendBusinessSms($params) {
        if(is_array($params['data'])) {
            $params['data'] = http_build_query($params['data']);
        }
        $verb = 'POST';
        $path = 'api/' . self::VERSION . '/businessNotify/sendSms';
        return $this->curl($verb, $path, $params);
    }

    /**
     * @param $params [receive_user => '271', 'phone' => 'lvfujun@xxx.com', data => ['a' => 'b], 'mtype' => 'test']
     * @return bool|mixed
     */
    public function sendBusinessEmail($params) {
        if(is_array($params['data'])) {
            $params['data'] = http_build_query($params['data']);
        }
        $verb = 'POST';
        $path = 'api/' . self::VERSION . '/businessNotify/sendEmail';
        return $this->curl($verb, $path, $params);
    }

    /**
     * @param $params [receive_user => '271', 'phone' => 'lvfujun@xxx.com', data => ['a' => 'b], 'mtype' => 'test']
     * @return bool|mixed
     */
    public function sendBusinessMessage($params) {
        if(is_array($params['data'])) {
            $params['data'] = http_build_query($params['data']);
        }
        $verb = 'POST';
        $path = 'api/' . self::VERSION . '/businessNotify/sendMessage';
        return $this->curl($verb, $path, $params);
    }

}
