<?php

namespace core\service\email;

use core\service\BaseService;

class SendEmailService extends BaseService {

    private static $funcMap = array(
        'sendEmail' => array('userEmail', 'userId', 'contentData', 'tplName', 'title', 'site','data'),
        //返回结果为0（接口调用成功但可发送邮件数量为0），true(发送成功),false（发送失败）,
        'batchSendEmail' => array('batchData'),
    );


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
        return self::rpc('ncfwx', 'Sendemail/'.$name, $args,false,10);
    }

}
