<?php
namespace core\service\user;

use core\service\BaseService;

class PassportService extends BaseService {
    private static $funcMap = array(
        'isLocalPassport' => array('userId'), // 是否网信通行证用户
        'updatePassportInfo' => array('ppId', 'oldMobile', 'newMobile', 'requestId'), // 更新通行证信息
        'isThirdPassport' => array('mobile', 'bizInfo'),// 判断是否是第三方用户
        'userBind' => array('ppId'), // 绑定通行证
        'needLocalVerify' => array('mobile'), // 验证是否需要二次验证
        'sessionDestroyByUserId' => array('userId'), // 本地修改密码同步通行证逻辑
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
        return self::rpc('ncfwx', 'user/'.$name, $args);
    }
}
