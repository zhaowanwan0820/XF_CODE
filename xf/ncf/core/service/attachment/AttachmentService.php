<?php
/**
 * 附件相关操作
 *
 * @author sunxuefeng@ucfgroup.com
 * @date   2018-08-06
 */

namespace core\service\attachment;

use core\service\BaseService;
use libs\utils\Logger;

/**
 * 附件相关接口
 */
class AttachmentService extends BaseService {

    private static $funcMap = array(
        'getAttachmentById' => array('imageId'),
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
            self::setError('invalid method: '.$name, 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }

        return self::rpc('ncfwx', 'attachment/'.$name, $args, true);
    }
}
