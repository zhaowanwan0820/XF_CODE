<?php

/**
 * Description of class
 *
 * @author yutao <yutao@ucfgroup.com>
 */
require_once '../../libs/common/functions.php';

/**
 * 将对象转换为标准数组
 * @param type $obj
 * @return type
 */
function object_to_array($obj) {

    if (!is_array($obj) && !is_object($obj)) {
        return $obj;
    }
    $ret = array();
    /**
     * 数组包含对象的情况
     */
    if (is_object($obj)) {
        $obj = $obj->getRow();
    }
    foreach ($obj as $key => $value) {
        $ret[$key] = object_to_array($value);
    }
    return $ret;
}
