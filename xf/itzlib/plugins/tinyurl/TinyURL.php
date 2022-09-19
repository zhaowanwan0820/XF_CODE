<?php
/**
 * PHP N位短链接生成代码 
 * @author QPWOEIRU96
 * @date 2012-03-27
 * @site: http://sou.la/blog
 */
Class TinyURL {
	static private $key = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"; //可以多位 保证每位的字符在URL里面正常显示即可
	static private $delimiter = '-';
    private function  __construct() {}
	private function  __clone(){}
    
    static public function encode($value) {
        if(is_array($value)){
            $results = array();
            foreach($value as $v){
                $result = '';
                if(is_numeric($v)){
                    $result = self::vencode($v);
                }
                $results[] = $result;
            }
            return implode(self::$delimiter,$results);
        }else{
            return self::vencode($value);
        }
	}

	static public function decode($value) {
		$tinyStrs = explode(self::$delimiter, $value);
        if(count($tinyStrs) === 1){
            return self::vdecode($tinyStrs[0]);
        }
        $results = array();
        foreach($tinyStrs as $tinyStr){
            $results[] = self::vdecode($tinyStr);
        }
        return $results;
	}
    
	static public function vencode($value) {
		$base = strlen( self::$key );
		$arr = array();
		while( $value != 0 ) {
			$arr[] = $value % $base;
			$value = floor( $value / $base );
		}
		$result = "";
		while( isset($arr[0]) ) $result .= substr(self::$key, array_pop($arr), 1 );
		return $result;
	}

	static public function vdecode($value) {
		$base = strlen( self::$key );
		$num = 0;
		$key = array_flip( str_split(self::$key) );
		$arr = str_split($value);
		for($len = count($arr) - 1, $i = 0; $i <= $len; $i++) {
			$num += pow($base, $i) * $key[$arr[$len-$i]];
		}
		return $num;
	}
}
?>
