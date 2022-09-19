<?php
/**
 * BwlistService.php
 *
 * @date 2018-05-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\BwlistModel;
use libs\utils\Logger;
use core\dao\BwlistTypeModel;

class BwlistService {

    /**
     * 查询value是否存在黑白名单里
     *
     * @param $type_key 类型key
     * @param $value value 默认取当前登录用户ID
     * @return boolean
     */
    public static function inList($type_key, $value = false, $value2 = false, $value3 = false)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $type_key, $value, $value2, $value3);
        if ($value === false && isset($GLOBALS['user_info'])) {
            $value = $GLOBALS['user_info']['id'];
        }
        if (empty($type_key) || empty($value)) {
            Logger::info(implode(" | ", array_merge($log_info, array('empty'))));
            return false;
        }
        $rs = BwlistModel::instance()->inList($type_key, $value, $value2, $value3);
        Logger::info(implode(" | ", array_merge($log_info, array($rs))));
        return $rs;
    }

    /**
     * 根据 key 获取list
     * @param $type_key
     * @return bool
     */
    public static function getValueList($type_key){
        $log_info = array(__CLASS__, __FUNCTION__, __FILE__.$type_key);
        if (empty($type_key)) {
            Logger::info(implode(" | ", array_merge($log_info, array('empty'))));
            return false;
        }

        return BwlistModel::instance()->getValueList($type_key);

    }
    public static function  isWhiteListExist($type_key){
        if(!$type_key) return null;
        $condition ='type_key = ":type_key" ';
        $params = [':type_key' => $type_key];
        $res = BwlistTypeModel::instance()->findByViaSlave($condition, 'type_key',$params);
        return $res;
    }

    /**
     * 查询value是否存在黑白名单里
     *
     * @param $type_key 类型key
     * @param $value value 默认取当前登录用户ID
     * @return boolean
     */
    public static function addToList($type_key, $value, $value2 = false, $value3 = false) {
        $typeId = self::getTypeIdByTypeKey($type_key);
        Logger::info('addToList key:'.$type_key.', typeId: '.$typeId.', value:'.$value.', value2:'.$value2.', value3: '.$value3);

        if ($typeId > 0) {
            $time = time();
            $data = array(
                'type_id'=>$typeId,
                'value'=>$value,
                'create_time'=>$time,
                'update_time'=>$time
            );

            if (!empty($value2)) {
                $data['value2'] = $value2;
            }

            if (!empty($value3)) {
                $data['value3'] = $value3;
            }

            $res = BwlistModel::instance()->addRecord($data);
            if ($res > 0) {
                Logger::info('addToList success, id:'.$res.', key: '.$type_key);
                return true;
            }
        }

        return false;
    }

    /**
     * 通过type_key获取type id
     */
    public static function getTypeIdByTypeKey($type_key) {
        if (!$type_key) {
            return 0;
        }

        $condition ='type_key = ":type_key" ';
        $params = [':type_key' => $type_key];
        $res = BwlistTypeModel::instance()->findByViaSlave($condition, '*', $params);
        return $res ? $res['id'] : 0;
    }
}
