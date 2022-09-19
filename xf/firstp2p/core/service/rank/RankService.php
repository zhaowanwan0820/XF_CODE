<?php
/**
 * 排行榜服务
 *
 * @author sunxuefeng@ucfgroup.com
 * @date 2018.10.22
 */

namespace core\service\rank;

use NCFGroup\Common\Library\ApiService;

class RankService extends ApiService{

    private static $funcMap = array(
        'getRank' => array('rankId', 'userId'),         // 获取排行榜
        'updateRankScoreByTrigger' => array('userId', 'bidAmount', 'annualizedAmount', 'dealLoadId', 'dealType', 'extra'),  //投资触发排行榜积分更新
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
            self::setError($name.' method not exist', 1);
            return false;
        }

        $args = array();
        $argNames = self::$funcMap[$name];
        foreach ($params as $key=>$arg) {
            if (!empty($argNames[$key])) {
                $args[$argNames[$key]] = $arg;
            }
        }

        return self::rpc('o2o', 'rank/'.$name, $args);
    }
}
