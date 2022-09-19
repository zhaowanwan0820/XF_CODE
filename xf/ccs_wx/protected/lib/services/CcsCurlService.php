<?php
/**
 * curl class for ipassport
 * Created by PhpStorm.
 * User: cxt
 * Date: 2016/12/5
 * Time: 17:34
 */
class CcsCurlService extends ItzInstanceService{
    private $headers = array(
        "x-itz-apptoken: r&ht0E@*aGeJkNg3d6X3gOM&WWEbGCgO",
        "Content-Type: application/json; charset=utf-8",
    );
    
    /**
     * 循环curl api
     * zlei
     */
    public function setCurlLoop($url,$params=array(),$method='POST'){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    			'code'=>0,'info'=>'','data'=>array()
    	);
    	for ($i=0;$i<2;$i++){
    		$code = 0;
    		try {
    			$return =  $this->setCurl($url,$params,$method);
    			$return = json_decode($return['body'],true);
    			//返回结果
    			$returnResult['code'] = isset($return['code']) ? $return['code'] : 112211; // 112211 接口不通
    			$returnResult['info'] = $return['msg'];
    			$returnResult['data'] = isset($return['data'])?$return['data']:array();
    			break;
    		} catch (Exception $e) {
    			$code++;
    			Yii::log('curl api error, Msg:'.print_r($e->getMessage(),true).' ! Time:'.date('Y-m-d H:i:s'),'error');
    		}
    	}
    	if($code>0){
    		$returnResult['code'] = 1;
    		$returnResult['info'] = '请求第三方失败';
    	}
    	return $returnResult;
    }
    
    /**
     * 设置调取外呼接口
     * zlei
     */
    public function setCurl($url,$params=array(),$method='POST'){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	if( empty($url) || !is_array($params) ){
    		$body = array(
    			'code'=>1003,
    			'info'=>'必要参数错误'
    		);
    		$return['body'] = json_encode($body);
    		return $return;
    	}
    	
    	$method = strtoupper($method);
    	
    	$header = $this->headers;
    	return $this->request($url, $params, $method, $header);
    }
    
    /**
     * 调取接口
     * @param unknown $url
     * @param unknown $params
     * @param unknown $method
     * @param unknown $my_header
     * @return boolean|multitype:unknown multitype:
     */
    public function request($url, $params, $method, $my_header){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	/* 开始一个新会话 */
    	$curl_session = curl_init();
    
    	/* 基本设置 */
    	curl_setopt($curl_session, CURLOPT_FORBID_REUSE, true); // 处理完后，关闭连接，释放资源
    	curl_setopt($curl_session, CURLOPT_HEADER, true);//结果中包含头部信息
    	curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);//把结果返回，而非直接输出
    	curl_setopt($curl_session, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);//采用1.0版的HTTP协议
    	$url_parts = $this->parse_raw_url($url); //处理URL
    	$header = array();
    	/* 设置主机 */
    	$header[] = 'Host: ' . $url_parts['host'];
    	
    	/* 格式化自定义头部信息 */
    	if ($my_header && is_array($my_header)){
    		foreach ($my_header as $key => $value){
    			$header[] = $value;
    		}
    	}
    	if ($method === 'GET'){
    		$params = http_build_query($params);
    		curl_setopt($curl_session, CURLOPT_HTTPGET, true);
    		$url .= $params ? '?' . $params : '';
    	}else{
    		$params = json_encode($params);
    		curl_setopt($curl_session, CURLOPT_POST, true);
    		//$header[] = 'Content-Type: application/x-www-form-urlencoded';
    		$header[] = 'Content-Length: ' . strlen($params);
    		curl_setopt($curl_session, CURLOPT_POSTFIELDS, $params);
    	}

    	/* 设置请求地址 */
    	curl_setopt($curl_session, CURLOPT_URL, $url);
    	/* 设置头部信息 */
    	curl_setopt($curl_session, CURLOPT_HTTPHEADER, $header);
    	/* 发送请求 */
    	$http_response = curl_exec($curl_session);

    	if (curl_errno($curl_session) != 0){
    		return false;
    	}
    	$separator = '/\r\n\r\n|\n\n|\r\r/';
    	list($http_header, $http_body) = preg_split($separator, $http_response, 2);
    	 
    	$http_response = array(
    			'header' => $http_header,//肯定有值
    			'body'   => $http_body //可能为空
    	);
    	curl_close($curl_session);
    	Yii::log ( __FUNCTION__.'-OPMP CURL Result：-'.print_r($http_response,true),'info');
    	return $http_response;
    }
    private function parse_raw_url($raw_url){
    	$retval   = array();
    	$raw_url  = (string) $raw_url;
    	if (strpos($raw_url, '://') === false){
    		$raw_url = 'http://' . $raw_url;
    	}
    	$retval = parse_url($raw_url);
    	if (!isset($retval['path'])){
    		$retval['path'] = '/';
    	}
    	if (!isset($retval['port'])){
    		$retval['port'] = '8080';
    	}
    	return $retval;
    }
    
    
    function post($url,$body){
        $ch = curl_init();//初始化curl
        curl_setopt($ch,CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        // Set headers
        //设置凭证 测试环境需要
        //curl_setopt($ch, CURLOPT_USERPWD, 'zhanglei:FpBe6vRYsltHg');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $data = curl_exec($ch);//运行curl
        
        curl_close($ch);
        Yii::log('post:'.$url.' body:'.$body.' return:'.json_encode($data,JSON_UNESCAPED_UNICODE));
        return $data;
    }

    function get($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        Yii::log('get:'.$url.' return:'.json_encode($res,JSON_UNESCAPED_UNICODE));
        return $res;
    }

    function delete($url,$body=''){
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt( $curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt( $curl, CURLOPT_POST, 1);//post提交方式
        // Set headers
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 120 );
        curl_setopt( $curl, CURLOPT_NOSIGNAL, true );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
        $res = curl_exec($curl);
        Yii::log('delete:'.$url.' body:'.$body.' return:'.json_encode($res,JSON_UNESCAPED_UNICODE));
        return $res;
    }

    function patch($url,$body=''){
        $curl = curl_init ();
        curl_setopt( $curl, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt( $curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt( $curl, CURLOPT_POST, 1);//post提交方式
        // Set headers
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 120 );
        curl_setopt( $curl, CURLOPT_NOSIGNAL, true );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PATCH' );
        $res = curl_exec ( $curl);
        Yii::log('patch:'.$url.' body:'.$body.' return:'.json_encode($res,JSON_UNESCAPED_UNICODE));
        return $res;
    }
}