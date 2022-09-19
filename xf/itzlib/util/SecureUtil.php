<?php
class SecureUtil{
    protected static $randomkey=array("h","g","a","j","b");
    protected static $keyrandom=array("h"=>0,"g"=>1,"a"=>2,"j"=>3,"b"=>4);
    protected static $akeys=array('A','K','c','B','G','D','i','F','L','E');
    protected static  $auths=array(array(9,7,8,0,2,3,5,4,1,6),array(2,3,9,7,8,0,4,5,1,6),array(2,3,8,0,4,5,9,7,1,6),array(2,3,8,0,1,6,4,5,9,7),array(1,2,3,7,8,0,4,5,6,9)); 
    final private function __construct() {}
    final private function __clone() {}

    private static function dkey($s){
		if($s=="") return 0;
		$e="";
		if (strlen($s)>10)  return 0;
		for($i=0;$i<strlen($s);$i++){
			$e=$e.(ord($s[$i])-ord(self::$akeys[$i]));
		}
		return $e;
    }
    //解密10位时间戳
    public static  function  dekey($s){
        if(strlen($s)!=11) return 0;
        $auth=self::$auths[self::$keyrandom[$s[0]]];
        $s=substr($s,1);
        $dkey=array();
        for($i=0;$i<10;$i++){
         $dkey[$auth[$i]]=ord($s[$i])-ord(self::$akeys[$i]);
        }
        $k="";
        return implode("",$dkey);
 }
 //加密10位时间戳
 public static function enkey($s){
     $st=strval($s);
     if(strlen($st)!=10) return "";
     $r=rand(0,4);
     $result=array();
     $result[0]=self::$randomkey[$r];
     for($i=0;$i<10;$i++){
        $result[self::$auths[$r][$i]+1]=chr(intval($st[$i])+ord(self::$akeys[$i]));
     }
     return implode("",$result);
 }



}
?>