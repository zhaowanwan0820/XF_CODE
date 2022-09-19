<?php

/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/6
 * Time: 15:07
 */
class CurlService extends ItzInstanceService
{
    private $headers = [];
    protected $serviceErrorCode = ['100007','100009','100013'];
     
    public function __construct(){
    	parent::__construct();
    	$x_itz_apptoken = ConfUtil::get('xw-java-interface.apptoken');
    	if(empty($x_itz_apptoken)){
    		$x_itz_apptoken = 'r&ht0E@*aGeJkNg3d6X3gOM&WWEbGCgO';
    	}
    	$this->headers = [
    		"x-itz-apptoken: ".$x_itz_apptoken,
    		"Content-Type: application/json; charset=utf-8",
    	];
    }
    
    /* ----------存管---------- */
    /**
     * 存管报警
     */
    public function alertXwTeam($result,$data){
    	$now_time = time();
    	if( $now_time > strtotime('2018-09-01 20:00:00') && $now_time < strtotime('2018-09-01 23:59:59')){
    		return '';
    	}
    	
    	if (empty($result)) return false;
    	if (empty($data)) return false;
    	//报警给相关人
    	$msg_phones = array(
    		1 => array('phone' => '13716970622', 'email' => 'liuchunhua@xxx.com'),
    		2 => array('phone' => '15810571697', 'email' => 'zhaowanwan@xxx.com'),
    		3 => array('phone' => '18211130584', 'email' => 'wangyanan@xxx.com'),
    	);
    	$remind = array();
    	$remind['sent_user'] = 0;
    	$remind['receive_user'] = -1;
    	$remind['data']['cg_btty'] = '存管相关报警';
    	$remind['data']['cg_nrty'] = 'Send：'.print_r($data,true).'<br />'.' Result:'.print_r($result,true);
    	$remind['mtype'] = 'cg_yhcgxtbj'; // 触发点CODE
    	foreach ($msg_phones as $key => $value) {
    		$remind['email'] = $value['email'];
    		$result = NewRemindService::getInstance()->SendToUser($remind, false, true, false);
    		Yii::log("RequestToJava NewRemindService return result=" . $result, "info", __METHOD__);
    	}
    }
    
    //获取客户端系统
    public function getDeviceOs($userDevice=''){
    	if($userDevice != 'PC' ){
	    	if(strripos($_SERVER['HTTP_USER_AGENT'],'Volley')){
	    		return 'android';
	    	}elseif(strripos($_SERVER['HTTP_USER_AGENT'],'CFNetwork')){
	    		return 'ios';
	    	}elseif(!empty($_SESSION['wapapi'])){
	    		return 'wap';
	    	}else{
	    		return '';
	    	}
	    	
    	}else {
    		return 'pc';
    	}
    }
    
    /**
     * 网关接口  
     */
    public function gateway($data = array(),$method='post'){
    	$url = Yii::app()->c->xw_request_url.'/gateway';
    	#审计日志
    	$Audit_data = array(
    		"system"    => 'toXw/'.$this->getDeviceOs($data['userDevice']),
    		"action"    => 'gateway',
    		"resource"  => $data['serviceName'],
    		"status"    => '',
    		"parameters"=> array(
    			'method' => $method,
    			'request_url' => $url,
    			'send' => $data,
    			'result' => array()
    		)
    	);
    	//请求java
    	$result = $this->request($method, $url, json_encode($data,JSON_UNESCAPED_SLASHES));
    	$Audit_data['parameters']['result'] = $results = json_decode($result,true);
    	$Audit_data['status'] = ($results['code']===0) ? 'success' : 'fail';#success|fail
    	AuditLog::getInstance()->method('add', $Audit_data);
    	if($results){
    		$returnResult = $results;
    	}else { //调取java接口失败时
    		$returnResult = array(
    			'code'=>11211,
    			'message'=>'接口调取失败',
    			'data'=>array()
    		);
    	}
    	if($returnResult['code']!=0){
    		$this->alertXwTeam($returnResult,$data);
    	}
    	return $returnResult;
    }
    /**
     * 直连接口
     */
    public function service($data = array(),$method='post'){
    	$url = Yii::app()->c->xw_request_url.'/service';
    	#审计日志
    	$Audit_data = array(
	    	"system"    => 'toXw/'.$this->getDeviceOs($data['userDevice']),
	    	"action"    => 'service',
	    	"resource"  => $data['serviceName'],
	    	"status"    => '',
	    	"parameters"=> array(
		    	'method' => $method,
		    	'request_url' => $url,
		    	'send' => $data,
		    	'result' => array()
	    	)
    	);
    	//请求java
    	$result = $this->request($method, $url, json_encode($data,JSON_UNESCAPED_SLASHES));
    	$Audit_data['parameters']['result'] = $results = json_decode($result,true);
    	$Audit_data['status'] = ($results['code']===0) ? 'success' : 'fail';#success|fail
    	
    	AuditLog::getInstance()->method('add', $Audit_data);
    	if($results){
    		$returnResult = $results;
    	}else { //调取java接口失败时
    		$returnResult = array(
    			'code'=>11211,
    			'message'=>'接口调取失败',
    			'data'=>array()
    		);
    	}
    	if($returnResult['code']!=0){
    		if( !in_array($returnResult['data']['errorCode'],$this->serviceErrorCode) && ($data['reqData']['tradeType'] != 'PLATFORM_SERVICE_DEDUCT' && mb_strpos($returnResult['data']['errorMessage'],"超过小额支付单日限额") === false) ){
                $this->alertXwTeam($returnResult,$data);
            }
        }
    	return $returnResult;
    }

	public function downloadService($data = array(),$method='post'){
		$url = Yii::app()->c->xw_request_url.'/download/file';
		//$url ="http://20.100.201.179:8080/trustee/v1/download/file";
		#审计日志
		$Audit_data = array(
			"system"    => 'toXw/'.$this->getDeviceOs($data['userDevice']),
			"action"    => 'service',
			"resource"  => $data['serviceName'],
			"status"    => '',
			"parameters"=> array(
				'method' => $method,
				'request_url' => $url,
				'send' => $data,
				'result' => array()
			)
		);
		//请求java
		$result = $this->request($method, $url, json_encode($data,JSON_UNESCAPED_SLASHES));
		$Audit_data['parameters']['result'] = $results = json_decode($result,true);
		$Audit_data['status'] = ($results['code']===0) ? 'success' : 'fail';#success|fail
		AuditLog::getInstance()->method('add', $Audit_data);
		if($results){
			$returnResult = $results;
		}else { //调取java接口失败时
			$returnResult = array(
				'code'=>11211,
				'message'=>'接口调取失败',
				'data'=>array()
			);
		}
		if($returnResult['code']!=0){
			if( !in_array($returnResult['data']['errorCode'],$this->serviceErrorCode) && ($data['reqData']['tradeType'] != 'PLATFORM_SERVICE_DEDUCT' && mb_strpos($returnResult['data']['errorMessage'],"超过小额支付单日限额") === false) ){
				//$this->alertXwTeam($returnResult,$data);
			}
		}
		return $returnResult;
	}

    /**
     * 延签接口
     */
    public function verifySignatur($data = array(),$method='post'){
    	
    	// 必要参数验证
    	if( empty($data) || empty($data['serviceName']) ){
    		Yii::log('verifySignatur params data:'.json_encode($data),'error');
    		return array( 'code'=>100, 'message'=>'参数错误','data'=>array() );
    	}
    	
    	$url = Yii::app()->c->xw_request_url.'/asyn-handler';
    	#审计日志
    	$Audit_data = array(
	    	"system"    => 'toXw/pc',
	    	"action"    => 'verifySignatur',
	    	"resource"  => $data['serviceName'],
	    	"status"    => '',
	    	"parameters"=> array(
		    	'method' => $method,
		    	'request_url' => $url,
		    	'send' => $data,
		    	'result' => array()
    		)
    	);
    	//请求java
    	$result = $this->request($method, $url, json_encode($data,JSON_UNESCAPED_SLASHES));
    	$Audit_data['parameters']['result'] = $results = json_decode($result,true);
    	$Audit_data['status'] = ($results['code']===0) ? 'success' : 'fail';#success|fail
    	
    	AuditLog::getInstance()->method('add', $Audit_data);
    	if($results){
    		$returnResult = $results;
    	}else { //调取java接口失败时
    		$returnResult = array(
    			'code'=>11211,
    			'message'=>'接口调取失败',
    			'data'=>array()
    		);
    	}
    	if($returnResult['code']!=0){
    		$this->alertXwTeam($returnResult,$data);
    	}
    	return $returnResult;
    }
    
	/* -----------存管END--------- */

    /**
     * 智选-直连接口
     */
    public function zxService($data = array(),$method='post'){
        $url = Yii::app()->c->zx_request_url;
        #审计日志
        $Audit_data = array(
            "action"    => 'service',
            "resource"  => $data['serviceName'],
            "status"    => '',
            "parameters"=> array(
                'method' => $method,
                'request_url' => $url,
                'send' => $data,
                'result' => array()
            )
        );
        //请求java
        $result = $this->request($method, $url, json_encode($data,JSON_UNESCAPED_SLASHES));
        $Audit_data['parameters']['result'] = $results = json_decode($result,true);
        $Audit_data['status'] = ($results['code']===0) ? 'success' : 'fail';#success|fail
        
        AuditLog::getInstance()->method('add', $Audit_data);
        if($results){
            $returnResult = $results;
        }else { //调取java接口失败时
            $returnResult = array(
                'code'=>11211,
                'message'=>'接口调取失败',
                'data'=>array()
            );
        }
        return $returnResult;
    }

    /* -----------END--------- */

    function post($url, $body)
    {
        return $this->request('post', $url, $body);
    }

    function get($url)
    {
        return $this->request('get', $url);
    }

    function delete($url, $body = '')
    {
        return $this->request('delete', $url, $body);
    }

    function patch($url, $body = '')
    {
        return $this->request('patch', $url, $body);
    }

    function put($url, $body = '')
    {
        return $this->request('put', $url, $body);
    }

	public function yjRequest($url = '', $data = '', $method = 'get')
	{
		Yii::log(" CurlService yjRequest  func_get_args :".json_encode(func_get_args()));
		return $this->request($method, $url, $data, true);
	}


	/**
	 * ITZ+YJ
	 * @param string $methord
	 * @param string $url
	 * @param string $body
	 * @param bool $is_yj
	 * @return mixed
	 */
    function request($methord = 'get', $url = '', $body = '', $is_yj=false)
    {
        // 1.初始化
        $curl = curl_init();
        // 2.设置属性
        curl_setopt($curl, CURLOPT_URL, $url);          // 需要获取的 URL 地址
        curl_setopt($curl, CURLOPT_HEADER, 0);          // 设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // 要求结果为字符串且输出到屏幕上
		// Set headers
		if($is_yj){
			$this->headers = [
				"Content-Type: application/json; charset=utf-8",
			];
		}

        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers); // 设置 HTTP 头字段的数组
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        switch ($methord) {
            case 'get':
                break;
            case 'post':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'delete':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'patch':
                curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            case 'put':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                break;
            default:

        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_NOSIGNAL, true);
        // 3.执行并获取结果
        $res = curl_exec($curl);
        // 4.释放句柄
        curl_close($curl);
        // Yii::log($methord . ':' . $url . ' body:' . $body . ' return:' . json_encode($res, JSON_UNESCAPED_UNICODE));
        return $res;
    }


	/**
	 * 资产花园
	 * @param $api
	 * @param string $method
	 * @param array $params
	 * @param array $headers
	 * @param bool $json_decode
	 * @return bool|mixed|string
	 */
	public static function AgRequest($api, $method = 'GET', $params = array(), $headers = [], $json_decode = true)
	{
		$curl = curl_init();
		switch (strtoupper($method)) {
			case 'GET':
				if (!empty($params)) {
					$api .= (strpos($api, '?') ? '&' : '?') . http_build_query($params);
				}
				curl_setopt($curl, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($curl, CURLOPT_POST, true);
				if(is_array($params)) {
					$params = http_build_query($params);
				}
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
			case 'PUT':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
				break;
		}

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $api);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		if(isset($_SERVER['HTTP_USER_AGENT'])){
			curl_setopt($curl,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		}

		$response = curl_exec($curl);
		if ($response === false) {
			curl_close($curl);
			return false;
		} else {
			// 解决windows 服务器 BOM 问题
			$response = trim($response, chr(239).chr(187).chr(191));
			if ($json_decode) {
				$response = json_decode($response, true);
			}
		}
		curl_close($curl);
		return $response;
	}

}