<?php
namespace core\service;

use core\service\BaseService;

class MsgConfigService extends BaseService {
    /**
     * 函数列表
     */
    private static $funcMap = array(
        'getAllMsgConfig' => array(), // 获取用户订阅消息配置
        'getUserConfig' => array('userId', 'field'), // 获取用户短信或者邮件订阅配置
        'setSwitches' => array('userId', 'field', 'switches'), // 设置开关 支持 插入和更新
        'checkMsgConfig' => array('msgConfig', 'type'), // 检查配置项
        'checkIsSendSms' => array('userId', 'smsTemplateId'), // 用户订阅配置是否短信通知
        'checkP2pcnIsSendSms' => array('siteId', 'tplName', 'checkOption'), // 网信普惠是否发短信
        'checkIsSendEmail' => array('userId', 'tplKey'), // 用户订阅配置是否邮件通知
    );

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed
     */
    public static function __callStatic($name, $params) {
        if (!array_key_exists($name, self::$funcMap)) {
            self::setError('invalid method', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }
        return self::rpc('ncfwx', 'Usermsg/'.$name, $args);
    }
}