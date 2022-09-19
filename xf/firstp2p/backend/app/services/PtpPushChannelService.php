<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;

require_once APP_ROOT_DIR.'/Common/Vendor/BaiduPushSDK/sdk.php';

/**
 * PtpPushChannelService
 * 推送底层实现
 */
class PtpPushChannelService extends ServiceBase
{

    const OSTYPE_IOS = 1;

    const OSTYPE_ANDROID = 2;

    public $error = array();

    /**
     * 向单台设备推送
     */
    public function toSingle($appId, $osType, $channelId, $content, $badge, array $params)
    {
        $config = $this->_getConfig($appId, $osType);

        $message = $this->_getMessage($osType, $content, $badge, $params);
        $options = $config['options'];

        $sdk = new \PushSDK($config['apiKey'], $config['secretKey']);
        $sdk->setDeviceType($osType == self::OSTYPE_IOS ? 4 : 3);
        $result = $sdk->pushMsgToSingleDevice($channelId, $message, $options);
        if ($result === false) {
            $this->error = array(
                'errno' => $sdk->getLastErrorCode(),
                'error' => $sdk->getLastErrorMsg(),
                'baiduAppId' => $config['appId'],
                'baiduApiKey' => $config['apiKey'],
            );
        }

        return $result;
    }

    /**
     * 向全体推送
     */
    public function toAll($appId, $osType, $content, array $params)
    {
        $config = $this->_getConfig($appId, $osType);

        $message = $this->_getMessage($osType, $content, 0, $params);
        $options = $config['options'];

        $sdk = new \PushSDK($config['apiKey'], $config['secretKey']);
        $sdk->setDeviceType($osType == self::OSTYPE_IOS ? 4 : 3);
        $result = $sdk->pushMsgToAll($message, $options);
        if ($result === false) {
            $this->error[$osType] = array(
                'errno' => $sdk->getLastErrorCode(),
                'error' => $sdk->getLastErrorMsg(),
            );
        }

        return $result;
    }


    /**
     * 获取App相关的配置
     */
    private function _getConfig($appId, $osType)
    {
        $key = $osType == self::OSTYPE_IOS ? 'ios' : 'android';

        $config = getDI()->getConfig()->push;
        if (empty($config[$appId][$key])) {
            throw new \Exception('App config not found');
        }

        return $config[$appId][$key]->toArray();
    }

    /**
     * 根据osType获取格式化的消息
     */
    private function _getMessage($osType, $content, $badge, array $params)
    {
        if ($osType == self::OSTYPE_IOS) {
            return $this->_getIOSMessage($content, $badge, $params);
        }

        return $this->_getAndroidMessage($content, $params);
    }

    /**
     * 获取Android格式消息
     */
    private function _getAndroidMessage($content, array $params)
    {
        $message = $params;
        $message['content'] = $content;

        return $message;
    }

    /**
     * 获取IOS格式消息
     */
    private function _getIOSMessage($content, $badge, array $params)
    {
        $message = array(
            'aps' => array(
                'alert' => $content,
                'sound' => '1',
                'badge' => $badge,
            ),
        );

        return array_merge($message, $params);
    }

}
