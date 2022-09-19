<?php

include_once( dirname(__FILE__).'/RongpayBase.php');

class RongpayNotifyClass extends RongpayBase {
	
	var $gateway;           //网关地址
	var $_key;  			//安全校验码
	var $merchant_ID;       //合作伙伴ID
	var $sign_type;         //签名方式 系统默认
	var $mysign;            //签名结果
	var $charset;    		//字符编码格式
	var $transport;         //访问模式
	
	/**
	 * 构造函数
	 * 从配置文件中初始化变量
	 * @param: $merchant_ID 合作身份者ID
	 * @param: $key 安全校验码
	 * @param: $sign_type 签名类型
	 * @param: $charset 字符编码格式
	 * @param: $transport 访问模式
	 */
	function __construct($merchant_ID, $key, $sign_type, $charset = "UTF-8", $transport= "http") {
		$this->transport = $transport;
		if($this->transport == "https") {
			$this->gateway = "";
		} else {
			$this->gateway = "http://interface.reapal.com/verify/notify?";
		}
		$this->merchant_ID      = $merchant_ID;
		$this->_key    			= $key;
		$this->mysign           = "";
		$this->sign_type	    = $sign_type;
		$this->charset   = $charset;
	}

	/**
	 * 对notify_url的认证
	 * 返回的验证结果：true/false
	 */
	function notifyVerify() {
		//获取远程服务器ATN结果，验证是否是融宝支付服务器发来的请求
		if($this->transport == "https") {
			$veryfy_url = $this->gateway. "service=notify_verify" ."&merchant_ID=" .$this->merchant_ID. "&notify_id=".$_POST["notify_id"];
		} else {
			$veryfy_url = $this->gateway. "merchant_ID=".$this->merchant_ID."&notify_id=".$_POST["notify_id"];
		}
		$veryfy_result = file_get_contents($veryfy_url);

		if(empty($_POST)) {							//判断POST来的数组是否为空
			return false;
		} else {
			$post = self::paraFilter($_POST);	//对所有POST返回的参数去空
			$sort_post = self::argSort($post);	    //对所有POST反馈回来的数据排序
			$this->mysign = self::buildMySign($sort_post, $this->_key, $this->sign_type);   //生成签名结果

			//判断veryfy_result是否为ture，生成的签名结果mysign与获得的签名结果sign是否一致
			//$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$veryfy_result) && $this->mysign == $_POST["sign"]) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * 对return_url的认证
	 * @return 验证结果：true/false
	 */
	function returnVerify() {
		//获取远程服务器ATN结果，验证是否是融宝支付服务器发来的请求
		if($this->transport == "https") {
			$veryfy_url = $this->gateway. "service=notify_verify" ."&merchant_ID=" .$this->merchant_ID. '&notify_id='.$_POST["notify_id"];
		} else {
			$veryfy_url = $this->gateway. "merchant_ID=".$this->merchant_ID.'&notify_id='.$_REQUEST["notify_id"];
		}

		$veryfy_result =file_get_contents($veryfy_url);
		
		//生成签名结果
		if(empty($_REQUEST)) {							//判断GET来的数组是否为空
			return false;
		} else {
			$get = self::paraFilter($_POST);	    //对所有GET反馈回来的数据去空
			$sortGet = self::argSort($get);		    //对所有GET反馈回来的数据排序
			$this->mysign = self::buildMySign($sortGet, $this->_key, $this->sign_type);    //生成签名结果
			
			//判断veryfy_result是否为ture，生成的签名结果mysign与获得的签名结果sign是否一致
			//$veryfy_result的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//mysign与sign不等，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$veryfy_result) && $this->mysign == $_REQUEST["sign"]) {   
				return true;
			} else {
				return false;
			}
		}
	}

    /**
     * 获取远程服务器ATN结果
	 * @param: $url 指定URL路径地址
	 * @return: 服务器ATN结果集
     */
    function getVerify($url, $time_out = "60") {
        $urlarr     = parse_url($url);
        $errno      = "";
        $errstr     = "";
        $transports = "";
        if($urlarr["scheme"] == "https") {
            $transports = "ssl://";
            $urlarr["port"] = "443";
        } else {   
            $transports = "tcp://";
            $urlarr["port"] = "18183";
        }
        $fp=@fsockopen($transports . $urlarr['host'],$urlarr['port'],$errno,$errstr,$time_out);
        if(!$fp) {
            die("ERROR: $errno - $errstr<br />\n");
        } else {
            fputs($fp, "POST ".$urlarr["path"]." HTTP/1.1\r\n");
            fputs($fp, "Host: ".$urlarr["host"]."\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ".strlen($urlarr["query"])."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $urlarr["query"] . "\r\n\r\n");
            while(!feof($fp)) {
                $info[]=@fgets($fp, 1024);
            }
            fclose($fp);
            $info = implode(",",$info);
            return $info;
        }
	}

}