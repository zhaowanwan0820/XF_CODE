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

}

