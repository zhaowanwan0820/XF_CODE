<?php

/**
 * ArrayUtil 
 * 
 */
class ArrayUtil
{
	
	static private $sortKey=null;
	static private $sortAsc=true;

	/**
	 * getArray 
	 * 得到一个数组，如果不是数组，返回数组
	 *
	 * @param mixed $array 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function getArray($array)
	{
		if(!is_array($array)){
			return array($array);
		}
		return $array;
	}
	
	/**
	 * getKeyArray 
	 * 根据key，生成新的array
	 * 
	 * @param mixed $array 
	 * @param mixed $key 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function getKeyArray($array,$key)
	{
		$retArr = array();
		$array=self::getArray($array);
		foreach($array as $k=>$v){
			if(isset($v[$key])){
				$retArr[$v[$key]]=$v;
			}
		}
		return $retArr;
	}
	/**
	 * getNoKeyArray 
	 * 将map数组中的key值去掉 
	 * @param mixed $array 
	 * @static
	 * @note public
	 * @return void
	 */
	static public function getNoKeyArray($array)
	{
		$retArr = array();
		$array=self::getArray($array);
		foreach ($array as $k=>$v) {
			$retArr[] = $v;
		}
		return $retArr;
	}
	
	/**
	 * getKeyValue 
	 * 根据key，生成新的子array
	 * 
	 * @param mixed $array 
	 * @param mixed $key 
	 * @param mixed $allowEmpty 是否允许key为空 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function getKeyValue($array,$key,$allowEmpty=true)
	{
		$retArr = array();
		$array=self::getArray($array);
		foreach($array as $k=>$v){
			if(isset($v[$key]) && ($allowEmpty || !empty($v[$key]))){
				$retArr[]=$v[$key];
			}
		}
		return $retArr;
	}
	
	/**
	 * unsetKeyArray 
	 * 
	 * @param mixed $array 
	 * @param mixed $key 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function unsetKeyArray($array,$key)
	{
		$retArr=self::getArray($array);
		foreach($retArr as $k=>$v){
			if(isset($v[$key])){
				unset($retArr[$k][$key]);
			}
		}
		return $retArr;
	}

	/**
	 * sortKeyFunc 
	 * 
	 * @param mixed $a 
	 * @param mixed $b 
	 * @static
	 * @access private
	 * @return void
	 */
	static private function sortKeyFunc($a,$b)
	{
		if(isset($a[self::$sortKey])&&isset($b[self::$sortKey])){
			if(self::$sortAsc){
				return $a[self::$sortKey]>$b[self::$sortKey]?1:-1;
			}else{
				return $a[self::$sortKey]<$b[self::$sortKey]?1:-1;
			}
		}
		throw new exception("item should have key[$key]");
	}
	
	/**
	 * sortKeyArray 
	 * 
	 * @param mixed $array 
	 * @param mixed $key 
	 * @param mixed $asc 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function sortKeyArray($array,$key,$asc=true)
	{
		$array=self::getArray($array);
		self::$sortAsc = $asc;
		self::$sortKey = $key;
		usort($array,array('ArrayUtil',"sortKeyFunc"));
		return $array;
	}

	/**
	 * utf8Array
	 * 将一个array encode成utf8编码
	 * 
	 * @param mixed $array 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function utf8Array($array){
	    if(is_array($array)){
		    foreach($array as $key=>$value){
			    $array[$key]=self::utf8Array($value);
			}
		}else if(is_string($array)){
		    if(!EncodeUtil::isUtf8($array)){
		        $array = "";
			}
		}
		return $array;
	}
	
	/**
	 * isUtf8Array 
	 * 检查一个数组是否utf8编码
	 * 
	 * @param mixed $array 
	 * @static
	 * @access public
	 * @return void
	 */
	static public function isUtf8Array($array){
	    if(is_array($array)){
		    foreach($array as $key=>$value){
			    if(!self::isUtf8Array($value)){
				    return false;
				}
			}
		}else if(is_string($array)){
		    if(!EncodeUtil::isUtf8($array)){
			    return false;
			}
		}
		return true;
	}

	/**
	 * array_column
	 * php >= 5.5 有原生支持
	 * php < 5.5 用我们重写的方式
	 */
	static public function array_column($array, $column_name){
	    if( !function_exists("array_column") )
		{
		    return array_map(function($element) use($column_name){return $element[$column_name];}, $array);
		}
		else
		{
			return array_column($array, $column_name);
		}
	}
}
