<?php

namespace core\service\deal;

use core\service\BaseService;

/**
 * 产品管理相关接口.
 */
class DealTypeGradeService extends BaseService
{
    private static $funcMap = array(
        /**
         * 按照分类ID获取一套模板
         * @param
         * @return array
         */
        'getAllLevelByName' => array('level2','level3'),
        'getGradeList' => array('layer'),
        'getThirdLayerGradeList' => array(),
        'getAllSecondLayersByName' => array('name','sortCond'),
        'getAllThirdLayersByName' => array('name'),
        'getSubThirdGradeByNameArray' => array('nameArray'),
        'getbyId' => array('id'),
        'getbyIds' => array('ids'),

    );

    private static $defaultFunc = array(
        'getThirdLayerGradeList',
    );

    private static $defaultParamValue = array(
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
        // 对于方法中，后面几个未传，则会指定默认值
        if (in_array($name,self::$defaultFunc)) {
            foreach(self::$defaultParamValue as $key => $default){
                if(in_array($key,$argNames)){
                    $args[$key] = empty($args[$key]) ? $default : $args[$key];
                }
            }
        }

        // 处理特殊的ncfph的api接口
        $ncfphApiArr = array(
            'getbyId' => 'getDealGradeById',
            'getAllSecondLayersByName' => 'getAllSecondLayersByName',
        );


        // 公用配置表 为了性能 移动到wxuser_center
        if (array_key_exists($name, $ncfphApiArr)) {
            return self::rpc('user', 'ncfph/'.$ncfphApiArr[$name], $args);
        }

        return self::rpc('ncfwx', 'DealTypeGrade/'.$name, $args);
    }
}
