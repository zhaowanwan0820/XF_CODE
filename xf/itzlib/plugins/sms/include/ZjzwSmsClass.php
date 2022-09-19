<?php

class ZjzwSmsClass {
	
	/**
	 * 网关地址
	 */
	static protected $url;
    static protected $userid = 156;
    
	static protected $srv_ip = 'http://hy6.nbark.com';  //你的目标服务地址或频道
	static protected $srv_port = 7602;

	static public function postRequest($url, $post_params){

		$srv_ip = self::$srv_ip;
		$srv_port = self::$srv_port;
		$fp = '';
		$resp_str = '';
		$errno = 0;
		$errstr = '';
		$timeout = 10;
		$post_str = http_build_query($post_params); //进行UTF-8转码
		unset($post_strarr);
		unset($post_params);
		$err='';
		if ($srv_ip == '' || $url == ''){
			$err='ip or dest url empty'; 
			return $err;
		}
		$fp = fsockopen($srv_ip,$srv_port,$errno,$errstr,$timeout);  
		if(!$fp){ 
			$err.='fp fail';
			return $err;
		}

		$content_length = strlen($post_str); 
		$post_header = "POST $url HTTP/1.1\r\n"; 
		$post_header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$post_header .= "User-Agent: MSIE\r\n";
		$post_header .= "Host: ".$srv_ip."\r\n";  
		$post_header .= "Content-Length: ".$content_length."\r\n";
		$post_header .= "Connection: close\r\n\r\n"; 
		$post_header .= $post_str."\r\n\r\n";  
		fwrite($fp,$post_header); 
		$inheader = 1;
		while(!feof($fp)){
			$line=fgets($fp,512);
			if ($inheader && ($line == "\n" || $line == "\r\n")) { 
				$inheader = 0; 
			}
			if ($inheader == 0) { 
				$resp_str .= $line;//返回值放入$resp_str 
			}
		}
		$bodytag = trim($resp_str);	
		fclose($fp); 

		$dom = new DOMDocument('1.0');
		$dom ->loadXML($bodytag);
		$xml = simplexml_import_dom($dom);
		$res= $xml;
		
		unset ($resp_str);
		return $res;
	}

	static public function getRequest($url) {
	    $srv_ip = self::$srv_ip;
		$srv_port = self::$srv_port;
		$url = $srv_ip . ':' . $srv_port . $url;
		$fcontent = file_get_contents($url);
		
		$bodytag = trim($fcontent);
		$dom = new DOMDocument('1.0');
		$dom ->loadXML($bodytag);
		$xml = simplexml_import_dom($dom);
		$res= $xml;
		return $res;
		
		
	}
    /**
	  * 发送短信
	  *
	  *
	  */
	static public function sendMessage($username, $password, $phones, $contents, $scode = '', $setTime = '') {
		$userid = self::$userid;        //企业ID
	    $url = "/sms.aspx?action=send&userid={$userid}&account={$username}&password={$password}&mobile={$phones}&content={$contents}&sendTime=&extno="; //接收你post的URL具体地址
	
        $result = self::getRequest($url);
        return $result;
        if($result['successCounts'] > 0) {
            return $result['successCounts'];
        } else {
            return false;
        }
	}
	
	/**
	 * 余额查询
	 * @return double 余额
	 */
	static public function getBalance($username, $password){
		$userid = self::$userid;         //企业ID
		$url = "/sms.aspx?action=overage&userid={$userid}&account={$username}&password={$password}"; //接收你post的URL具体地址
		$params = array();
		$params['username'] = $username;
		$params['password'] = $password;

        $result = self::getRequest($url);
        $blance = (int)$result->overage;
 
		if($blance >= 0) {
			return $blance;
		} else {
			return false;
		}
	}

}