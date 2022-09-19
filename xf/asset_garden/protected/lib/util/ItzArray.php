<?php

/* 
 * 专门用处理数组类的自定义函数
 */

class ItzArray{
   static function remove_duplicate($array){
    $result=array();
    for($i = 0; $i < count($array); $i++){
          $source = $array[$i];
         if(array_search( $source , $array ) == $i && $source <> "" ){
                $result[] = $source;
         }
    }
    return $result;
    }


    public static function array_column($input, $columnKey, $indexKey = null)
    {
        if (!function_exists('array_column')) {
            $result = [];
            foreach ($input as $arr) {
                if (!is_array($arr)) {
                    continue;
                }

                if (is_null($columnKey)) {
                    $value = $arr;
                } else {
                    $value = $arr[$columnKey];
                }

                if (!is_null($indexKey)) {
                    $key = $arr[$indexKey];
                    $result[$key] = $value;
                } else {
                    $result[] = $value;
                }
            }

            return $result;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
    }

}

